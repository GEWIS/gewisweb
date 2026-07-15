<?php

declare(strict_types=1);

namespace App\Service\Application;

use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Drivers\Vips\Driver as VipsDriver;
use Intervention\Image\ImageManager;
use Throwable;

use function extension_loaded;

/**
 * Provides an Intervention {@see ImageManager} backed by the best available driver. When php-vips (via FFI) is
 * installed it uses libvips, which is far cheaper in memory because it streams tiles instead of decoding whole
 * frames. Otherwise it uses GD, which is always present and keeps CI and libvips-less environments working.
 *
 * A fresh manager is created per call rather than cached on the service. Under FrankenPHP's long-lived worker the
 * vips FFI handle must not be reused across requests, and constructing a manager is cheap.
 */
final readonly class ImageManagerProvider
{
    public function create(): ImageManager
    {
        // `ffi` gates php-vips; even with it, libvips itself may be absent, so fall back if the driver cannot load.
        if (extension_loaded('ffi')) {
            try {
                return new ImageManager(new VipsDriver());
            } catch (Throwable) {
                // libvips is not loadable here, so fall through to GD.
            }
        }

        return new ImageManager(new GdDriver());
    }
}
