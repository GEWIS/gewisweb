<?php

namespace Activity\Model;

use Doctrine\ORM\Mapping\{
    Column,
    Entity,
    GeneratedValue,
    Id,
};
use InvalidArgumentException;
use Laminas\Session\Container as SessionContainer;

/**
 * Class LocalisedText: stores Dutch and English versions of text fields.
 */
#[Entity]
class LocalisedText
{
    /**
     * ID for the LocalisedText.
     */
    #[Id]
    #[Column(type: "integer")]
    #[GeneratedValue(strategy: "IDENTITY")]
    protected int $id;

    /**
     * English text.
     */
    #[Column(
        type: "text",
        nullable: true,
    )]
    protected ?string $valueEN;

    /**
     * Dutch text.
     */
    #[Column(
        type: "text",
        nullable: true,
    )]
    protected ?string $valueNL;

    /**
     * LocalisedText constructor.
     *
     * @param string|null $valueEN
     * @param string|null $valueNL
     */
    public function __construct(?string $valueEN, ?string $valueNL)
    {
        $this->valueEN = $valueEN;
        $this->valueNL = $valueNL;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return string|null
     */
    public function getValueEN(): ?string
    {
        return $this->valueEN;
    }

    /**
     * @return string|null
     */
    public function getValueNL(): ?string
    {
        return $this->valueNL;
    }

    /**
     * @param string|null $valueEN
     * @param string|null $valueNL
     */
    public function updateValues(?string $valueEN, ?string $valueNL): void
    {
        $this->updateValueEN($valueEN);
        $this->updateValueNL($valueNL);
    }

    /**
     * @param string|null $valueEN
     */
    public function updateValueEN(?string $valueEN): void
    {
        $this->valueEN = $valueEN;
    }

    /**
     * @param string|null $valueNL
     */
    public function updateValueNL(?string $valueNL): void
    {
        $this->valueNL = $valueNL;
    }

    /**
     * @param string|null $locale
     *
     * @return string the localised text
     *
     * @throws InvalidArgumentException
     */
    public function getText(string $locale = null): string
    {
        if (null === $locale) {
            $locale = $this->getPreferredLocale();
        }

        switch ($locale) {
            case 'nl':
                return !is_null($this->valueNL) ? $this->valueNL : $this->valueEN;
            case 'en':
                return !is_null($this->valueEN) ? $this->valueEN : $this->valueNL;
            default:
                throw new InvalidArgumentException('Locale not supported: ' . $locale);
        }
    }

    /**
     * @return string the preferred language: either 'nl'  or 'en'
     */
    private function getPreferredLocale(): string
    {
        $langSession = new SessionContainer('lang');

        return $langSession->lang;
    }

    /**
     * @param string|null $locale
     *
     * @return string the localised text
     *
     * @throws InvalidArgumentException
     */
    public function getExactText(string $locale = null): string
    {
        if (null === $locale) {
            $locale = $this->getPreferredLocale();
        }

        switch ($locale) {
            case 'nl':
                return $this->valueNL;
            case 'en':
                return $this->valueEN;
            default:
                throw new InvalidArgumentException('Locale not supported: ' . $locale);
        }
    }

    /**
     * @return LocalisedText
     */
    public function copy(): LocalisedText
    {
        return new LocalisedText($this->valueEN, $this->valueNL);
    }
}
