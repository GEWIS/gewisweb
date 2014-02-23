<?php

namespace Education\Service;

use Zend\ServiceManager\ServiceManager;
use Zend\ServiceManager\ServiceManagerAwareInterface;
use Zend\Soap\Client as SoapClient;

use Education\Oase\Vraag;
use Education\Oase\Property;
use Education\Oase\Antwoord;

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
        $client = $this->sm->get('education_oase_soapclient');

        $vraag = new Vraag("GeefDoelgroepen");

        $vraag->addProperty(new Property('Taal', 'string', 'NL'));
        $vraag->addProperty(new Property('StudiejaarId', 'string', '2013'));
        $vraag->addProperty(new Property('JaargangId', 'string', 'Alle'));

        var_dump($client->VraagEnAntwoord($vraag));
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

