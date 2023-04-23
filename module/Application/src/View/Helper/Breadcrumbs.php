<?php

declare(strict_types=1);

namespace Application\View\Helper;

use Laminas\View\Helper\Placeholder\Container\{
    AbstractContainer,
    AbstractStandalone,
};

/**
 * Helper for setting and retrieving breadcrumbs.
 */
class Breadcrumbs extends AbstractStandalone
{
    /**
     * Add a breadcrumb to the container. By default, the breadcrumb is placed after the last breadcrumb (or first if no
     * other breadcrumbs have been added). This behaviour can be changed by setting `$setType`.
     *
     * @param string $breadcrumb
     * @param bool $active
     * @param string $url
     * @param string|null $setType
     *
     * @return $this
     */
    public function addBreadcrumb(
        string $breadcrumb = '',
        bool $active = true,
        string $url = '',
        ?string $setType = null,
    ): self {
        if (null === $setType) {
            $setType = AbstractContainer::APPEND;
        }

        if ('' !== $breadcrumb) {
            $item = [
                'name' => $breadcrumb,
                'active' => $active,
                'url' => $url,
            ];

            if (AbstractContainer::SET === $setType) {
                $this->set($item);
            } elseif (AbstractContainer::PREPEND === $setType) {
                $this->prepend($item);
            } else {
                $this->append($item);
            }
        }

        return $this;
    }

    /**
     * Append
     *
     * @param array $value
     * @return AbstractContainer
     */
    public function append(array $value): AbstractContainer
    {
        return $this->getContainer()->append($value);
    }

    /**
     * Prepend
     *
     * @param array $value
     * @return AbstractContainer
     */
    public function prepend(array $value): AbstractContainer
    {
        return $this->getContainer()->prepend($value);
    }

    /**
     * Set
     *
     * @param array $value
     * @return AbstractContainer
     */
    public function set(array $value): AbstractContainer
    {
        return $this->append($value);
    }

    /**
     * @return string
     */
    public function toString(): string
    {
        $output = '';

        foreach ($this as $item) {
            $output .= '<li class="' . (($item['active']) ? 'active' : '') . '">';

            if ('' !== $item['url']) {
                $output .= sprintf(
                    '<a href="%s">%s</a>',
                    ($this->getAutoEscape()) ? $this->escapeAttribute($item['url']) : $item['url'],
                    ($this->getAutoEscape()) ? $this->escape($item['name']) : $item['name'],
                );
            } else {
                $output .= ($this->getAutoEscape()) ? $this->escape($item['name']) : $item['name'];
            }

            $output .= '</li>';
        }

        return $output;
    }
}
