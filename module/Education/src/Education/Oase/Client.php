<?php

namespace Education\Oase;

use Zend\Soap\Client as SoapClient;

class Client
{

    /**
     * SOAP client
     *
     * @var Soapclient
     */
    protected $client;

    /**
     * Constructor
     *
     * @param SoapClient $client
     */
    public function __construct(SoapClient $client)
    {
        $this->client = $client;
    }

    /**
     * GeefDoelgroepen API call.
     *
     * @param string $studiejaar
     * @param string $taal
     */
    public function GeefDoelgroepen($studiejaar, $taal)
    {
        $vraag = new Vraag(__FUNCTION__);

        $vraag->addProperty(new Property('Taal', 'string', $taal));
        $vraag->addProperty(new Property('StudiejaarId', 'string', $studiejaar));
        $vraag->addProperty(new Property('JaargangId', 'string', 'Alle'));

        return $this->call($vraag);
    }

    /**
     * Make an API call.
     *
     * @param Vraag $vraag
     *
     * @return array
     */
    protected function call(Vraag $vraag)
    {
        $antwoord = $this->client->VraagEnAntwoord($vraag);

        return simplexml_load_string($antwoord->any);
    }
}
