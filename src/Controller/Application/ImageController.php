<?php

declare(strict_types=1);

namespace App\Controller\Application;

use App\Entity\Application\Enums\ImageVariant;
use App\Entity\Application\Enums\StorageNamespace;
use App\Security\Application\ServingAccessCheckerInterface;
use App\Service\Application\FileStorage;
use App\Service\Application\ImagePathResolver;
use App\Service\Application\ImageSigner;
use App\Service\Application\VariantGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use function fpassthru;
use function is_file;

/**
 * Serves a pre-generated (or, on a cache miss, freshly generated) image variant.
 *
 * Private namespaces (album photos) require a valid day-signature and pass the pluggable per-namespace access check
 * (default: an authenticated session; the photos checker additionally runs the album voter for graduates). Public
 * namespaces (covers, career, organ, page images) are served unsigned and immutably cacheable. Generate-on-miss
 * produces the variant synchronously; only a missing original is a 404. In production the response is offloaded to
 * Caddy via X-Sendfile; where the file is not on local disk (tests, a future S3 adapter) the bytes are streamed.
 *
 * The route is defined non-localised in `config/routes.yaml`, so image URLs carry no `/en`|`/nl` prefix.
 */
final class ImageController extends AbstractController
{
    public function __construct(
        private readonly ImageSigner $imageSigner,
        private readonly ImagePathResolver $pathResolver,
        private readonly VariantGenerator $variantGenerator,
        private readonly FileStorage $fileStorage,
        /** @var iterable<ServingAccessCheckerInterface> */
        #[AutowireIterator('app.serving_access_checker')]
        private readonly iterable $accessCheckers,
        #[Autowire('%kernel.project_dir%/data')]
        private readonly string $storageRootDir,
    ) {
    }

    public function serve(
        Request $request,
        string $variant,
        string $path,
    ): Response {
        $imageVariant = ImageVariant::tryFrom($variant);
        $namespace = $this->pathResolver->namespaceForPath($path);
        if (
            null === $imageVariant
            || null === $namespace
        ) {
            throw new NotFoundHttpException();
        }

        if ($namespace->isPrivate()) {
            $valid = $this->imageSigner->isValid(
                $variant,
                $path,
                $request->query->getInt('expires'),
                $request->query->getString('signature'),
            );
            if (!$valid) {
                throw new AccessDeniedHttpException('Invalid or expired image signature.');
            }
        }

        if (
            !$this->accessGranted(
                $path,
                $namespace,
            )
        ) {
            throw new AccessDeniedHttpException();
        }

        $cachePath = $this->variantGenerator->cachePath(
            $path,
            $imageVariant,
        );
        if (!$this->fileStorage->exists($cachePath)) {
            $quality = $this->pathResolver->profileForPath(
                $path,
                $imageVariant,
            )?->webpQuality() ?? 85;
            // On a miss, cap at the original width rather than skipping, so a valid original never 404s.
            if (
                !$this->variantGenerator->generateVariant(
                    $path,
                    $imageVariant,
                    $quality,
                    skipUpscale: false,
                )
            ) {
                throw new NotFoundHttpException();
            }
        }

        return $this->serveVariant(
            $cachePath,
            $namespace,
        );
    }

    private function accessGranted(
        string $path,
        StorageNamespace $namespace,
    ): bool {
        // Checkers are iterated in descending priority; the first that governs this namespace decides.
        foreach ($this->accessCheckers as $checker) {
            if ($checker->supports($namespace)) {
                return $checker->isGranted(
                    $path,
                    $namespace,
                );
            }
        }

        return false;
    }

    private function serveVariant(
        string $cachePath,
        StorageNamespace $namespace,
    ): Response {
        $absolutePath = $this->storageRootDir . '/' . $cachePath;

        if (is_file($absolutePath)) {
            // Local disk: hand the file to Caddy via X-Sendfile (framework.trust_x_sendfile_type_header).
            $response = new BinaryFileResponse($absolutePath);
        } else {
            // Non-local adapter (tests / future S3): stream the bytes ourselves.
            $stream = $this->fileStorage->readStream($cachePath);
            $response = new StreamedResponse(static function () use ($stream): void {
                fpassthru($stream);
            });
        }

        $response->headers->set(
            'Content-Type',
            'image/webp',
        );

        if ($namespace->isPrivate()) {
            $response->setPrivate();
            $response->headers->set(
                'Cache-Control',
                'private, max-age=86400',
            );
        } else {
            $response->headers->set(
                'Cache-Control',
                'public, max-age=31536000, immutable',
            );
        }

        return $response;
    }
}
