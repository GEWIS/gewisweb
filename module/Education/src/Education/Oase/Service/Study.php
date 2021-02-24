<?php

namespace Education\Oase\Service;

use Education\Oase\Client;
use Education\Model\Study as StudyModel;

use Zend\Stdlib\Hydrator\HydratorInterface;

class Study
{
    /**
     * Client.
     *
     * @var Client
     */
    protected $client;

    /**
     * Keywords of W&I studies.
     *
     * @var array
     */
    protected $keywords;

    /**
     * Negative keywords for W&I studies.
     *
     * @var array
     */
    protected $negativeKeywords;

    /**
     * Group ID's for W&I studies.
     *
     * @var array
     */
    protected $groupIds;

    /**
     * Education types for W&I studies.
     *
     * @var array
     */
    protected $educationTypes;

    /**
     * Hydrator for studies.
     *
     * @var HydratorInterface
     */
    protected $hydrator;


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
     * Set the keywords
     *
     * @param array $keywords
     */
    public function setKeywords(array $keywords)
    {
        $this->keywords = $keywords;
    }

    /**
     * Set the negative keywords.
     *
     * @param array $keywords
     */
    public function setNegativeKeywords(array $keywords)
    {
        $this->negativeKeywords = $keywords;
    }

    /**
     * Set the group ID's
     *
     * @param array $groupIds
     */
    public function setGroupIds(array $groupIds)
    {
        $this->groupIds = $groupIds;
    }

    /**
     * Set the education types
     *
     * @param array $educationTypes
     */
    public function setEducationTypes(array $educationTypes)
    {
        $this->educationTypes = $educationTypes;
    }

    /**
     * Set the hydrator.
     *
     * @param HydratorInterface $hydrator
     */
    public function setHydrator(HydratorInterface $hydrator)
    {
        $this->hydrator = $hydrator;
    }

    /**
     * Check if a given keyword occurs in a string.
     *
     * @param string $keyword
     * @param string $haystack
     *
     * @return boolean If the keyword occurs.
     */
    protected function isSubString($keyword, $haystack)
    {
        return stristr($haystack, $keyword) !== false;
    }

    /**
     * Filter if a given 'doelgroep' is a W&I study.
     *
     * @param \SimpleXMLElement $doelgroep
     *
     * @return boolen If it is a W&I study
     */
    protected function filterDoelgroep(\SimpleXMLElement $doelgroep)
    {
        // first do simple checks
        if (!in_array($doelgroep->Opleidingstype, $this->educationTypes)
            || !in_array($doelgroep->GroepscategorieId, $this->groupIds)) {
            return false;
        }
        // do negative checks
        foreach ($this->negativeKeywords as $keyword) {
            if ($this->isSubString($keyword, $doelgroep->Omschrijving)) {
                return false;
            }
        }
        // do positive checks
        foreach ($this->keywords as $keyword) {
            if ($this->isSubString($keyword, $doelgroep->Omschrijving)) {
                return true;
            }
        }
        // if not matched, return false
        return false;
    }

    /**
     * Create a study from a doelgroep.
     *
     * @param \SimpleXMLElement $doelgroep
     *
     * @return StudyModel
     */
    public function createStudy(\SimpleXMLElement $doelgroep)
    {
        $data = [
            'id' => (int)$doelgroep->Id->__toString(),
            'name' => $doelgroep->Omschrijving->__toString(),
            'phase' => $doelgroep->Opleidingstype->__toString(),
            'groupId' => (int)$doelgroep->GroepscategorieId->__toString()
        ];
        return $this->hydrator->hydrate($data, new StudyModel());
    }

    /**
     * Get ID's of all W&I's studies.
     *
     * @param string $year
     *
     * @return array
     */
    public function getStudies($year)
    {
        $data = $this->client->GeefDoelgroepen($year, 'NL');

        // convert doelgroepen to array
        $doelgroepen = [];
        foreach ($data->Doelgroep as $doelgroep) {
            $doelgroepen[] = $doelgroep;
        }

        $doelgroepen = array_filter($doelgroepen, [$this, 'filterDoelgroep']);

        // since all this filtering, re-index the array
        $doelgroepen = array_values($doelgroepen);

        // convert doelgroepen to studies
        return array_map([$this, 'createStudy'], $doelgroepen);
    }

    /**
     * Get all studies of the TU/e
     *
     * @param string $year
     *
     * @return array
     */
    public function getAllStudies($year)
    {
        return $this->client->GeefDoelgroepen($year, 'NL');
    }
}
