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
        $ret = [];
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
        $this->map = [];
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
        $year = (empty($year) || $year == 0) ? date('Y') : $year;
        $code = $course->ActCode->__toString();

        $course = $this->client->GeefVakGegevens($code, $year, 'NL');

        $studies = [];

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
        $data = [
            'code' => $course->VakCode->__toString(),
            'name' => $course->VakOmschr->__toString(),
            'url' => $course->UrlStudiewijzer->__toString(),
            'quartile' => 'q1', //TODO: determine this value
            'year' => $year, // TODO: correctly determine this value
            // from $course->Studiejaar
            'studies' => $studies
        ];

        // get the children course codes
        $children = [];
        foreach ($course->VakOnderdelen->VakOnderdeel as $child) {
            $children[] = $child->OnderdeelVakcode->__toString();
        }


        return [
            'course' => $this->hydrator->hydrate($data, new CourseModel()),
            'children' => $children
        ];
    }

    /**
     * Get courses
     *
     * @param array $studies
     * @param string $year
     *
     * @return array
     */
    public function getCourses($studies, $year)
    {
        $this->createStudiesMap($studies);

        $groups = $this->extractGroupIds($studies);

        // get all courses
        $courses = [];

        foreach ($groups as $group) {
            $activiteiten1 = $this->client->ZoekActiviteitenOpDoelgroep([$group], 'NL', $year);
            $activiteiten2 = $this->client->ZoekActiviteitenOpDoelgroep([$group], 'EN', $year);

            // turn into nice arrays
            $courses1 = $this->toArray($activiteiten1->ZoekActiviteitenOpDoelgroepResult->Vakken->Activiteit);
            $courses2 = $this->toArray($activiteiten2->ZoekActiviteitenOpDoelgroepResult->Vakken->Activiteit);

            // merge
            $courses = array_merge($courses, $courses1);
            $courses = array_merge($courses, $courses2);
        }

        // WARNING: looks like a simple map, but the mapped function actually
        // gets a LOT of info from OASE per course, hence, this call takes quite long
        $info = array_map([$this, 'getCourseInfo'], $courses);

        // filter null values
        $info = array_filter($info, function ($data) {
            return null !== $data;
        });

        // match children
        $ret = [];

        foreach ($info as $code => $data) {
            $course = $data['course'];
            foreach ($data['children'] as $child) {
                if (isset($info[$child])) {
                    $info[$child]['course']->setParent($course);
                }
            }
            $ret[] = $course;
        }

        return $ret;
    }

    /**
     * Get a single course.
     *
     * @param string $code
     * @param string $year
     *
     * @return SimpleXMLElement
     */
    public function getCourse($code, $year)
    {
        return $this->client->GeefVakGegevens($code, $year, 'NL');
    }
}
