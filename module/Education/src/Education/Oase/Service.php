<?php

namespace Education\Oase;

class Service
{

    /**
     * Client.
     *
     * @var Client
     */
    protected $client;

    /**
     * Constructor.
     *
     * @param Client $client
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * Get ID's of all W&I's studies.
     *
     * @return array
     */
    public function getStudies()
    {
        $data = $this->client->GeefDoelgroepen('2013', 'NL');

        $doelgroepen = array();
        foreach ($data->Doelgroep as $doelgroep) {
            $doelgroepen[] = $doelgroep;
        }

        // we are only interested in studies with the following group id's
        $ids = array(
            110, // schakelprogramma's
            155, // HBO-minor
            200, // bachelor (pre-bachelor-college)
            210, // regulier onderwijs
        );

        // we are interested in studies with the following keywords
        $studies = array("software science", "web science", "wiskunde",
            "informatica", "mathematics", "statistics, probability, and operations research",
            "computer", "security", "business information systems", "embedded systems");


        $isWin = function ($doelgroep) use ($studies, $ids) {
            if (!in_array($doelgroep->Opleidingstype, array('master', 'bachelor'))) {
                return false;
            }
            if (!in_array($doelgroep->GroepscategorieId, $ids)) {
                return false;
            }
            foreach ($studies as $study) {
                if (preg_match('/' . $study . '/i', $doelgroep->Omschrijving)) {
                    return true;
                }
            }
            return false;
        };

        // we are not interested in studies with the following keywords
        $notStudies = array('leraar', 'natuurkunde');

        $isNot= function ($doelgroep) use ($notStudies) {
            foreach ($notStudies as $study) {
                if (preg_match('/' . $study . '/i', $doelgroep->Omschrijving)) {
                    return false;
                }
            }
            return true;
        };

        $filter = function($doelgroep) use ($isWin, $isNot) {
            return $isWin($doelgroep) && $isNot($doelgroep);
        };

        return array_filter($doelgroepen, $filter);
    }
}
