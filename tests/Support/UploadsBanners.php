<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\Entity\Career\Enums\CompanyBannerFormats;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;

use function imagecolorallocate;
use function imagecreatetruecolor;
use function imagefilledrectangle;
use function imagejpeg;
use function sys_get_temp_dir;
use function tempnam;

/**
 * Posting a banner image the way a browser does, for the two screens that accept one: the company proposing a banner
 * and the committee setting one.
 */
trait UploadsBanners
{
    /**
     * A POST carrying an image of the given format, named the way the form expects to find it. The stateless CSRF
     * token is satisfied by naming the cookie and saying the request came from this site, which is what a browser does
     * for a form on one of our own pages.
     *
     * The request is pushed onto the stack as well, because that is the one the CSRF check reads rather than the one
     * the form is handed.
     */
    private function bannerUploadRequest(CompanyBannerFormats $format): Request
    {
        $request = new Request(
            request: ['banner_image' => ['_csrf_token' => 'csrf-token']],
            files: [
                'banner_image' => [
                    'image' => $this->bannerImageFile(
                        $format->width(),
                        $format->height(),
                    ),
                ],
            ],
            server: ['HTTP_SEC_FETCH_SITE' => 'same-origin'],
        );
        $request->setMethod(Request::METHOD_POST);
        $request->setSession(self::getContainer()->get('session.factory')->createSession());
        self::getContainer()->get('request_stack')->push($request);

        return $request;
    }

    private function bannerImageFile(
        int $width,
        int $height,
    ): UploadedFile {
        $path = tempnam(
            sys_get_temp_dir(),
            'gewisweb-banner-test',
        );
        self::assertIsString($path);

        $image = imagecreatetruecolor(
            $width,
            $height,
        );
        self::assertNotFalse($image);
        $colour = imagecolorallocate(
            $image,
            0x30,
            0x60,
            0x90,
        );
        self::assertNotFalse($colour);
        imagefilledrectangle(
            $image,
            0,
            0,
            $width,
            $height,
            $colour,
        );
        imagejpeg(
            $image,
            $path,
        );

        return new UploadedFile(
            $path,
            'banner.jpg',
            'image/jpeg',
            null,
            true,
        );
    }
}
