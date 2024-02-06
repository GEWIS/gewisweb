<?php

declare(strict_types=1);

namespace Application\View\Helper;

use DateTime;
use DateTimeInterface;
use Laminas\View\Helper\AbstractHelper;
use League\Glide\Urls\UrlBuilder;

use function array_map;

/**
 * Url view helper for generating (signed) glide urls
 * Usage: $this->glideUrl()->getUrl('path to image', ['parameters']);.
 */
class GlideUrl extends AbstractHelper
{
    protected UrlBuilder $urlBuilder;

    /**
     * @return GlideUrl
     */
    public function __invoke(): self
    {
        return $this;
    }

    /**
     * Gets a signed glide URL.
     *
     * @param array{w: int, h: int, fm?: string, q?: int, expires?: DateTime}|array<never, never> $params
     */
    public function getUrl(
        string $imagePath,
        array $params,
    ): string {
        // If the encoding format is not specifically defined, default to webp.
        if (!isset($params['fm'])) {
            $params['fm'] = 'webp';
        }

        // If the quality is not specifically defined, default to 90.
        if (!isset($params['q'])) {
            $params['q'] = 90;
        }

        // If the expiration is not specifically defined, default to tomorrow at midnight.
        if (!isset($params['expires'])) {
            $params['expires'] = new DateTime('tomorrow'); // = midnight tomorrow
        }

        // Convert expiration to string.
        $params['expires'] = $params['expires']->format(DateTimeInterface::ATOM);

        // Ensure that all parameters are a string, this is how the Glide server will handle them.
        $params = array_map('strval', $params);

        return $this->urlBuilder->getUrl($imagePath, $params);
    }

    /**
     * Set the url builder.
     */
    public function setUrlBuilder(UrlBuilder $urlBuilder): void
    {
        $this->urlBuilder = $urlBuilder;
    }
}
