<?php

namespace Education\Oase\Service;

use Education\Oase\Client;
use Education\Model\Study as StudyModel;

class Course
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
     * Convert a course element to array.
     *
     * @param SimpleXMLElement $element
     */
    public function toArray(\SimpleXMLElement $element)
    {
        $ret = array();
        foreach ($element as $el) {
            $ret[] = $el;
        }
        return $ret;
    }

    /**
     * Get courses
     *
     * @param array $studies
     *
     * @return array
     */
    public function getCourses($studies)
    {
        $activiteiten1 = $this->client->ZoekActiviteitenOpDoelgroep($studies, 'NL');
        $activiteiten2 = $this->client->ZoekActiviteitenOpDoelgroep($studies, 'EN');

        // turn them into arrays
        $courses1 = $this->toArray($activiteiten1->ZoekActiviteitenOpDoelgroepResult->Vakken->Activiteit);
        $courses2 = $this->toArray($activiteiten2->ZoekActiviteitenOpDoelgroepResult->Vakken->Activiteit);

         // merge
        $courses = array_merge($courses1, $courses2);

        // turn into course codes
        $codes = array_map(function ($course) {
            return $course->ActCode->__toString();
        }, $courses);

        return $codes;
    }
}
