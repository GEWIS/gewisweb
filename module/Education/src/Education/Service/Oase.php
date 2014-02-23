<?php

namespace Education\Service;

use Zend\ServiceManager\ServiceManager;
use Zend\ServiceManager\ServiceManagerAwareInterface;
use Zend\Soap\Client as SoapClient;

/**
 * Exam service.
 */
class Oase implements ServiceManagerAwareInterface
{

    /**
     * Service manager.
     *
     * @var ServiceManager
     */
    protected $sm;

    /**
     * Update course info from OASE.
     */
    public function update()
    {
        $client = $this->sm->get('education_oase_client');

        var_dump($client->GeefDoelgroepen('2013', 'NL'));
    }

    /**
     * Set the service manager.
     *
     * @param ServiceManager $sm
     */
    public function setServiceManager(ServiceManager $sm)
    {
        $this->sm = $sm;
    }

    /**
     * Get the service manager.
     *
     * @return ServiceManager
     */
    public function getServiceManager()
    {
        return $this->sm;
    }
}

