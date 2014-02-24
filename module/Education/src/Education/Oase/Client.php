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
     * GeefVakGegevens API call.
     *
     * @param string $code
     * @param string $year
     * @param string $lang
     *
     * @return array
     */
    public function GeefVakGegevens($code, $year, $lang)
    {
        $vraag = new Vraag(__FUNCTION__);

        $vraag->addProperty(new Property('Studiejaar', 'int', $year));
        $vraag->addProperty(new Property('Vakcode', 'string', $code));
        $vraag->addProperty(new Property('Taal', 'string', $lang));

        return $this->call($vraag);
    }

    /**
     * ZoekActiviteitenOpDoelgroep API call.
     *
     * @param array $studies
     * @param string $lang
     *
     * @return array
     */
    public function ZoekActiviteitenOpDoelgroep($groups, $lang)
    {
        $vraag = new Vraag(__FUNCTION__);

        $vraag->addProperty(new Property("AlleZoekwoorden", "boolean", "false"));
        $vraag->addProperty(new Property("ExamensRetourneren", "boolean", "false"));

        foreach ($groups as $group) {
            $vraag->addProperty(new Property("GroepscategorieId", "short", $group));
        }

        $vraag->addProperty(new Property("Jaargang", "string", "Alle"));
        $vraag->addProperty(new Property("MaxAantalVakken", "int", "5000"));
        $vraag->addProperty(new Property("PersVakSelectie", "boolean", "false"));
        $vraag->addProperty(new Property("StudiejaarId", "short", '2013'));
        $vraag->addProperty(new Property("Taal", "string", 'NL'));
        $vraag->addProperty(new Property("TentamenMogelijk", "boolean", "false"));
        $vraag->addProperty(new Property("TijdslotId", "", "-1"));
        // K, V, X (Keuze, Verplicht, Beide)
        $vraag->addProperty(new Property("VerplichtKeuze", "string", "X"));
        $vraag->addProperty(new Property("Voertaal", "string", $lang));
        $vraag->addProperty(new Property("ZoekInFullText", "boolean", "true"));
        $vraag->addProperty(new Property("Zoekstring", "string", ""));
 
        return $this->call($vraag);
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
