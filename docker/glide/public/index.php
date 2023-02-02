<?php

/**
 * This makes our life easier when dealing with paths. Everything is relative
 * to the application root now.
 */

chdir(dirname(__DIR__));

require 'vendor/autoload.php';

use League\Flysystem\FilesystemException as FilesystemV2Exception;
use League\Glide\{
    Server,
    ServerFactory,
};
use League\Glide\Filesystem\{
    FileNotFoundException,
    FilesystemException,
};
use League\Glide\Signatures\{
    SignatureFactory,
    SignatureException,
};


/**
 * Override the actual `ServerFactory`, such that our custom `Server` is returned.
 */
class GlideServerFactory extends ServerFactory
{
    /**
     * Get configured custom server.
     *
     * @return GlideServer Configured Glide server.
     */
    public function getServer(): GlideServer
    {
        $server = new GlideServer(
            $this->getSource(),
            $this->getCache(),
            $this->getApi()
        );

        $server->setSourcePathPrefix($this->getSourcePathPrefix() ?: '');
        $server->setCachePathPrefix($this->getCachePathPrefix() ?: '');
        $server->setGroupCacheInFolders($this->getGroupCacheInFolders());
        $server->setCacheWithFileExtensions($this->getCacheWithFileExtensions());
        $server->setDefaults($this->getDefaults());
        $server->setPresets($this->getPresets());
        $server->setBaseUrl($this->getBaseUrl() ?: '');
        $server->setResponseFactory($this->getResponseFactory());

        if ($this->getTempDir()) {
            $server->setTempDir($this->getTempDir());
        }

        return $server;
    }
}

/**
 * Override the actual `Server`, such that creation of images does not use the memory hogging
 * `file_{get,put}_contents()` functions.
 */
class GlideServer extends Server
{
    /**
     * Generate manipulated image.
     *
     * @param string $path   Image path.
     * @param array  $params Image manipulation params.
     *
     * @return string Cache path.
     *
     * @throws FileNotFoundException
     * @throws FilesystemException
     */
    public function makeImage($path, array $params): string
    {
        $sourcePath = $this->getSourcePath($path);
        $cachedPath = $this->getCachePath($path, $params);

        if (true === $this->cacheFileExists($path, $params)) {
            return $cachedPath;
        }

        if (false === $this->sourceFileExists($path)) {
            throw new FileNotFoundException('Could not find the image `'.$sourcePath.'`.');
        }

        try {
            // MODIFIED: Do not read the whole file into memory (`file_get_contents()`), simply check its existence.
            $source = $this->source->fileExists(
                $sourcePath
            );
        } catch (FilesystemV2Exception $exception) {
            throw new FilesystemException('Could not read the image `'.$sourcePath.'`.');
        }

        // We need to write the image to the local disk before
        // doing any manipulations. This is because EXIF data
        // can only be read from an actual file.
        $tmp = tempnam($this->tempDir, 'Glide');

        // MODIFIED: Copy the file with a native copy instead of using `file_put_contents()`;
        try {
            $this->source->copy(
                $sourcePath,
                $tmp,
            );
        } catch (FilesystemV2Exception $exception) {
            throw new FilesystemException('Could not write temp file for `'.$sourcePath.'`.');
        }

        try {
            $this->cache->write(
                $cachedPath,
                $this->api->run($tmp, $this->getAllParams($params))
            );
        } catch (FilesystemV2Exception $exception) {
            throw new FilesystemException('Could not write the image `'.$cachedPath.'`.');
        } finally {
            unlink($tmp);
        }

        return $cachedPath;
    }
}

// Setup Glide server
$server = GlideServerFactory::create([
    'source' => '/code/public/data',
    'cache' => '/glide/cache',
    'driver' => 'imagick',
]);

// set complicated sign key
$signkey = getenv('GLIDE_KEY');

$base = '';
$path = explode('?', $_SERVER['REQUEST_URI'], 2)[0];

try {
    // Validate HTTP signature
    SignatureFactory::create($signkey)->validateRequest($base . $path, $_GET);
} catch (SignatureException $e) {
    http_response_code(403);
    die('Forbidden');
}

$server->outputImage($path, $_GET);
