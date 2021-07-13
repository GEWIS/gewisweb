<?php

namespace Activity\Model;

use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use Laminas\Session\Container as SessionContainer;

/**
 * Class LocalisedText: stores Dutch and English versions of text fields.
 *
 * @ORM\Entity
 */
class LocalisedText
{
    /**
     * ID for the LocalisedText.
     *
     * @ORM\Id
     * @ORM\Column(type="integer", nullable=false)
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * English text.
     *
     * @Orm\Column(type="text", nullable=true)
     */
    protected $valueEN;

    /**
     * Dutch text.
     *
     * @Orm\Column(type="text", nullable=true)
     */
    protected $valueNL;

    public function __construct($valueEN, $valueNL)
    {
        $this->valueEN = $valueEN;
        $this->valueNL = $valueNL;
    }

    public function getValueEN()
    {
        return $this->valueEN;
    }

    public function setValueEN($valueEN)
    {
        return new LocalisedText($valueEN, $this->valueNL);
    }

    public function getValueNL()
    {
        return $this->valueNL;
    }

    public function updateValues($valueEN, $valueNL)
    {
        $this->updateValueEN($valueEN);
        $this->updateValueNL($valueNL);
    }

    public function updateValueEN($valueEN)
    {
        $this->valueEN = $valueEN;
    }

    public function updateValueNL($valueNL)
    {
        $this->valueNL = $valueNL;
    }

    /**
     * @param string|null $locale
     *
     * @return string the localised text
     */
    public function getText($locale = null)
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
                throw new InvalidArgumentException('Locale not supported: '.$locale);
        }
    }

    /**
     * @return string the preferred language: either 'nl'  or 'en'
     */
    private function getPreferredLocale()
    {
        $langSession = new SessionContainer('lang');

        return $langSession->lang;
    }

    /**
     * @param string|null $locale
     *
     * @return string the localised text
     */
    public function getExactText($locale = null)
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
                throw new InvalidArgumentException('Locale not supported: '.$locale);
        }
    }

    /**
     * @return LocalisedText
     */
    public function copy()
    {
        return new LocalisedText($this->valueEN, $this->valueNL);
    }
}
