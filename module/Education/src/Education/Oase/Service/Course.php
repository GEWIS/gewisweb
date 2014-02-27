<?php

namespace Education\Oase\Service;

use Education\Oase\Client;
use Education\Model\Course as CourseModel;

use Zend\Stdlib\Hydrator\HydratorInterface;

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
     * Hydrator for courses.
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
     * Set the hydrator.
     *
     * @param HydratorInterface $hydrator
     */
    public function setHydrator(HydratorInterface $hydrator)
    {
        $this->hydrator = $hydrator;
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
            $ret[$el->ActCode->__toString()] = $el;
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

        // create the course
        $data = array(
            'code' => $course->VakCode->__toString(),
            'name' => $course->VakOmschr->__toString(),
            'url'  => $course->UrlStudiewijzer->__toString(),
            'quartile' => 'q1', //TODO: determine this value
            'year' => $year, // TODO: correctly determine this value
                             // from $course->Studiejaar
            'studies' => $studies
        );
        return $this->hydrator->hydrate($data, new CourseModel());
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

        // get all courses
        $courses = array();

        foreach ($groups as $group) {
            $activiteiten1 = $this->client->ZoekActiviteitenOpDoelgroep(array($group), 'NL');
            $activiteiten2 = $this->client->ZoekActiviteitenOpDoelgroep(array($group), 'EN');

            // turn into nice arrays
            $courses1 = $this->toArray($activiteiten1->ZoekActiviteitenOpDoelgroepResult->Vakken->Activiteit);
            $courses2 = $this->toArray($activiteiten2->ZoekActiviteitenOpDoelgroepResult->Vakken->Activiteit);

            // merge
            $courses = array_merge($courses, $courses1);
            $courses = array_merge($courses, $courses2);
        }
        // now simply take just the values
        $courses = array_values($courses);

        // WARNING: looks like a simple map, but the mapped function actually
        // gets a LOT of info from OASE per course, hence, this call takes quite long
        $info = array_map(array($this, 'getCourseInfo'), $courses);

        // filter
        return array_values(array_filter($info, function($data) { return null !== $data; }));
    }

    /**
     * Get a single course.
     *
     * @param string $code
     *
     * @return SimpleXMLElement
     */
    public function getCourse($code)
    {
        return $this->client->GeefVakGegevens($code, '2013', 'NL');
    }
}
