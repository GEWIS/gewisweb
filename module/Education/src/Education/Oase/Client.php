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
     * Extract group ID's
     *
     * @param array $studies
     *
     * @return array Group ID's
     */
    public function extractGroupIds($studies)
    {
        return array_unique(array_map(function ($study) {
            return $study->getGroupId();
        }, $studies));
    }

    /**
     * ZoekActiviteitenOpDoelgroep API call.
     *
     * @param array $studies
     * @param string $lang
     *
     * @return array
     */
    public function ZoekActiviteitenOpDoelgroep($studies, $lang)
    {
        $vraag = new Vraag(__FUNCTION__);

        $vraag->addProperty(new Property("AlleZoekwoorden", "boolean", "false"));
        $vraag->addProperty(new Property("ExamensRetourneren", "boolean", "false"));

        $groepen = $this->extractGroupIds($studies);

        foreach ($groepen as $groep) {
            $vraag->addProperty(new Property("GroepscategorieId", "short", $groep));
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
