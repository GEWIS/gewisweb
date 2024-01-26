<?php

/**
 * This makes our life easier when dealing with paths. Everything is relative
 * to the application root now.
 */

chdir(dirname(__DIR__));

require 'vendor/autoload.php';

use League\Flysystem\FilesystemException as FilesystemV2Exception;
use League\Glide\Server;
use League\Glide\ServerFactory;
use League\Glide\Filesystem\FilesystemException;
use League\Glide\Signatures\Signature;
use League\Glide\Signatures\SignatureException;
use League\Glide\Signatures\SignatureFactory;
use Override;

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
    #[Override]
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
 * Override the actual `Server`, such that output of images is cached regardless of the `expires` value.
 */
class GlideServer extends Server
{
    /**
     * @inheritDoc
     */
    #[Override]
    public function outputImage($path, array $params): void
    {
        // MODIFIED: unset the 'expires' parameter to ensure that we do not generate daily cache files.
        unset($params['expires']);

        $path = $this->makeImage($path, $params);

        try {
            header('Content-Type:' . $this->cache->mimeType($path));
            header('Content-Length:' . $this->cache->fileSize($path));
            header('Expires:' . date_create('tomorrow')->format('D, d M Y H:i:s') . ' GMT');

            $stream = $this->cache->readStream($path);

            if (0 !== ftell($stream)) {
                rewind($stream);
            }
            fpassthru($stream);
            fclose($stream);
        } catch (FilesystemV2Exception) {
            throw new FilesystemException('Could not read the image `' . $path . '`.');
        }
    }
}

class GlideSignatureFactory extends SignatureFactory
{
    /**
     * @inheritDoc
     */
    #[Override]
    public static function create($signKey)
    {
        return new GlideSignature($signKey);
    }
}

class GlideSignature extends Signature
{
    /**
     * MODIFIED: check expiration from the parameters.
     *
     * @inheritDoc
     */
    #[Override]
    public function validateRequest($path, array $params)
    {
        if (!isset($params['s'])) {
            throw new SignatureException('Signature is missing.');
        }

        if ($params['s'] !== $this->generateSignature($path, $params)) {
            throw new SignatureException('Signature is not valid.');
        }

        // MODIFIED: check that the link has not expired.
        if ((new DateTime('now')) >= (DateTime::createFromFormat(DateTimeInterface::ATOM, $params['expires']))) {
            throw new SignatureException('Signature has expired.');
        }
    }

    /**
     * MODIFIED: use SHA3-256 instead of MD5.
     *
     * @inheritDoc
     */
    #[Override]
    public function generateSignature($path, array $params): string
    {
        unset($params['s']);
        ksort($params);

        // MODIFIED: use SHA3-256 instead of md5 as we want better guarantees that the signature is not crafted.
        return hash('sha3-256', $this->signKey.':'.ltrim($path, '/').'?'.http_build_query($params));
    }
}

// Setup Glide server
$server = (new GlideServerFactory([
    'source' => '/code/public/data',
    'cache' => '/glide/cache',
    'driver' => 'imagick',
]))->getServer();

// set complicated sign key
$signkey = getenv('GLIDE_KEY');

$base = '';
$path = explode('?', $_SERVER['REQUEST_URI'], 2)[0];

// Ensure that all parameters are a string, this is how the Glide server will handle them.
$params = array_map('strval', $_GET);

try {
    // Validate HTTP signature
    GlideSignatureFactory::create($signkey)->validateRequest($base . $path, $params);
} catch (SignatureException $e) {
    http_response_code(403);
    die('Forbidden');
}

$server->outputImage($path, $params);
