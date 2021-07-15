<?php

namespace Application\View\Helper;

use Application\Service\Legacy as LegacyService;
use Laminas\View\Helper\AbstractHelper;

class Infima extends AbstractHelper
{
    /**
     * Legacy service.
     *
     * @var LegacyService
     */
    protected $legacyService;

    /**
     * Get an infima.
     *
     * @return string
     */
    public function __invoke()
    {
        return $this->getLegacyService()->getInfima();
    }

    /**
     * Get the legacy service.
     *
     * @return LegacyService
     */
    protected function getLegacyService()
    {
        return $this->legacyService;
    }

    /**
     * Set the legacy service locator.
     */
    public function setLegacyService(LegacyService $service)
    {
        $this->legacyService = $service;
    }
}
