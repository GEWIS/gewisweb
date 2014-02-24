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
     * Studies map.
     *
     * @var array
     */
    protected $map;


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
    protected function toArray(\SimpleXMLElement $element)
    {
        $ret = array();
        foreach ($element as $el) {
            $ret[] = $el;
        }
        return $ret;
    }

    /**
     * Create a studies map.
     *
     * @param array $studies
     */
    protected function createStudiesMap($studies)
    {
        $this->map = array();
        foreach ($studies as $study) {
            $this->map[$study->getName()] = $study;
        }
    }

    /**
     * Extract group ID's
     *
     * @param array $studies
     *
     * @return array Group ID's
     */
    protected function extractGroupIds($studies)
    {
        return array_unique(array_map(function ($study) {
            return $study->getGroupId();
        }, $studies));
    }

    /**
     * Get more information from a course.
     *
     * @param SimpleXMLElement $course
     *
     * @return SimpleXMLElement Course info
     */
    protected function getCourseInfo($course)
    {
        $year = $course->LaatsteStudiejaarGegeven->__toString();
        $year = empty($year) ? '2013' : $year;
        $code = $course->ActCode->__toString();

        $course = $this->client->GeefVakGegevens($code, $year, 'NL');

        $studies = array();

        // first check if it actually is a study we want
        foreach ($course->DoelgroepBlokken->DoelgroepBlok as $blok) {
            foreach ($blok->Doelgroepen->Doelgroep as $doelgroep) {
                $name = $doelgroep->DoelgroepOmschr->__toString();
                if (isset($this->map[$name])) {
                    $studies[] = $this->map[$name];
                }
            }
        }

        if (empty($studies)) {
            return null;
        }
        return array(
            'studies' => $studies
        );
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
        $this->createStudiesMap($studies);

        $groups = $this->extractGroupIds($studies);
        $activiteiten1 = $this->client->ZoekActiviteitenOpDoelgroep($groups, 'NL');
        $activiteiten2 = $this->client->ZoekActiviteitenOpDoelgroep($groups, 'EN');

        // turn them into arrays
        $courses1 = $this->toArray($activiteiten1->ZoekActiviteitenOpDoelgroepResult->Vakken->Activiteit);
        $courses2 = $this->toArray($activiteiten2->ZoekActiviteitenOpDoelgroepResult->Vakken->Activiteit);

         // merge
        $courses = array_merge($courses1, $courses2);

        // NOTE: looks like a simple map, but the mapped function actually
        // gets a LOT of info from OASE per course
        $info = array_map(array($this, 'getCourseInfo'), $courses);

        // filter
        return array_values(array_filter($info, function($data) { return null !== $data; }));
    }
}
