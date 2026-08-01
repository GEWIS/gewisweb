<?php

declare(strict_types=1);

namespace App\Service\Decision;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

use function explode;
use function is_dir;
use function is_file;
use function realpath;
use function scandir;
use function str_starts_with;
use function strcasecmp;
use function usort;

/**
 * Read-only view of the SFTP-mirrored public archive (`data/public-archive/`). Paths come straight from the URL, so
 * everything is validated: no `..` segments, no hidden entries, and the resolved location (through any symlink) must
 * stay inside the archive root.
 */
final readonly class PublicArchiveBrowser
{
    public function __construct(
        #[Autowire('%kernel.project_dir%/data/public-archive')]
        private string $root,
    ) {
    }

    public function isFile(string $path): bool
    {
        $resolved = $this->resolve($path);

        return null !== $resolved && is_file($resolved);
    }

    /**
     * The entries of a directory, folders first: `{name, path, isDirectory}`. Null when the path is not a directory
     * inside the archive.
     *
     * @return ?list<array{name: string, path: string, isDirectory: bool}>
     */
    public function listDirectory(string $path): ?array
    {
        $resolved = $this->resolve($path);
        if (
            null === $resolved
            || !is_dir($resolved)
        ) {
            return null;
        }

        $names = scandir($resolved);
        if (false === $names) {
            return null;
        }

        $entries = [];
        foreach ($names as $name) {
            if (
                str_starts_with(
                    $name,
                    '.',
                )
            ) {
                continue;
            }

            $childPath = '' === $path
                ? $name
                : $path . '/' . $name;
            if (null === $this->resolve($childPath)) {
                continue;
            }

            $entries[] = [
                'name' => $name,
                'path' => $childPath,
                'isDirectory' => is_dir($resolved . '/' . $name),
            ];
        }

        usort(
            $entries,
            static function (array $a, array $b): int {
                if ($a['isDirectory'] !== $b['isDirectory']) {
                    return $a['isDirectory']
                        ? -1
                        : 1;
                }

                return strcasecmp(
                    $a['name'],
                    $b['name'],
                );
            },
        );

        return $entries;
    }

    /**
     * Resolves a URL path to an absolute filesystem path, or null when it is illegal or escapes the archive.
     */
    private function resolve(string $path): ?string
    {
        if ('' !== $path) {
            foreach (
                explode(
                    '/',
                    $path,
                ) as $segment
            ) {
                if (
                    '' === $segment
                    || str_starts_with(
                        $segment,
                        '.',
                    )
                ) {
                    return null;
                }
            }
        }

        $root = realpath($this->root);
        if (false === $root) {
            return null;
        }

        $resolved = realpath($this->root . ('' === $path ? '' : '/' . $path));
        if (false === $resolved) {
            return null;
        }

        if (
            $resolved !== $root
            && !str_starts_with(
                $resolved,
                $root . '/',
            )
        ) {
            return null;
        }

        return $resolved;
    }
}
