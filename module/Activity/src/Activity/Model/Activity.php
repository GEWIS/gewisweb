<?php
namespace Activity\Model;

use Doctrine\ORM\Mapping as ORM;

//input filter
use Zend\InputFilter\Factory as InputFactory;
use Zend\InputFilter\InputFilter;
use Zend\InputFilter\InputFilterAwareInterface;
use Zend\InputFilter\InputFilterInterface;
/**
 * Activity model
 *
 * @ORM\Entity
 */
class Activity implements InputFilterAwareInterface
{
    /**
     * ID for the activity
     *
     * @ORM\Id
     * @ORM\Column(type="integer", nullable=false)
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
	protected $id;

    /**
     * Name for the activity
     *
     * @Orm\Column(type="string")
     */
    protected $name;

    /**
     * The date and time the activity starts
     *
     * @ORM\Column(type="datetime")
     */
	protected $beginTime;

    /**
     * The date and time the activity ends
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
	protected $endTime;


    /**
     * The location the activity is held at
     *
     * @ORM\Column(type="string")
     */
    protected $location;


    /**
     * How much does it cost. 0 = free!
     *
     * @ORM\Column(type="integer")
     */
    protected $costs;

    /**
     * Are people able to sign up for this activity?
     *
     * @ORM\Column(type="boolean")
     */
    protected $canSignUp;

    /**
     * Are people outside of GEWIS allowed to sign up
     * N.b. if $canSignUp is false, this column does not matter
     *
     * @ORM\Column(type="boolean")
     */
    protected $onlyGEWIS;

    // TODO -> FK's
    /**
     * Who did approve this activity
     *
     * @ORM\ManyToOne(targetEntity="User\Model\User", inversedBy="roles")
     * @ORM\JoinColumn(referencedColumnName="lidnr")
     */
    protected $approver;

    /**
     * Who created this activity
     *
     * @ORM\Column(nullable=false)
     * @ORM\ManyToOne(targetEntity="User\Model\User", inversedBy="roles")
     * @ORM\JoinColumn(referencedColumnName="lidnr")
     */
    protected $creator;

    /**
     * Is this activity approved
     *
     * @ORM\Column(type="boolean")
     */
    protected $approved;


    // TODO -> where can i find member organ?
    protected $organ;

    /**
     * Input filter to validate create/edit event form data
     */
    protected $inputFilter;

    public function get($variable) {
        return $this->$variable;
    }

    /**
     * Create a new activity
     *
     * @param array $params Parameters for the new activity
     * @throws \Exception If a activity is loaded
     * @return \Activity\Model\Activity the created activity
     */
    public function create(array $params) {
        if ($this->id != null) {
            throw new \Exception("There is already a loaded activity");
        }
        foreach(['name', 'beginTime', 'endTime', 'costs', 'location'] as $param) {
            if (!isset($params[$param])) {
                throw new \Exception("create: parameter $param not set");
            }
            $this->$param =  $params[$param];
        }

        // TODO: These values need to be set correctly
        $this->beginTime = new \DateTime('0000-00-00 00:00');
        $this->endTime = new \DateTime('0000-00-00 00:00');
        $this->canSignUp = true;
        $this->onlyGEWIS = true;
        $this->creator = 1;
        $this->approved = 0;
        return $this;
    }

    /*************** INPUT FILTEr*****************/
    /** The code below this deals with the input filter
     * of the create and edit activity form data
     */

    /**
     * Get the input filter
     *
     * @return InputFilterInterface
     */
    public function getInputFilter() {
        // Check if the input filter is set. If so, serve
        if ($this->inputFilter) {
            return $this->inputFilter;
        }

        $inputFilter = new InputFilter();
        $factory = new InputFactory();
        $inputFilter->add($factory->createInput([
            'name' => 'name',
            'required' => true,
            'filters' => [
                ['name' => 'StripTags'],
                ['name' => 'StringTrim']
            ],
            'validators' => [
                [
                    'name'    => 'StringLength',
                    'options' => [
                        'encoding' => 'UTF-8',
                        'min'      => 1,
                        'max'      => 100,
                    ],
                ],
            ],
        ]));

        $inputFilter->add($factory->createInput([
            'name' => 'location',
            'required' => true,
            'filters' => [
                ['name' => 'StripTags'],
                ['name' => 'StringTrim']
            ],
            'validators' => [
                [
                    'name'    => 'StringLength',
                    'options' => [
                        'encoding' => 'UTF-8',
                        'min'      => 1,
                        'max'      => 100,
                    ],
                ],
            ],
        ]));

        $inputFilter->add($factory->createInput([
            'name' => 'costs',
            'required' => true,
            'filters' => [
                ['name' => 'Int'],
            ],
            'validators' => [
                [
                    'name'    => 'Between',
                    'options' => [
                        'min'      => 0,
                        'max'      => 10000,
                    ],
                ],
            ],
        ]));

        $this->inputFilter = $inputFilter;
        return $this->inputFilter;
    }

    public function setInputFilter(InputFilterInterface $inputFilter) {
        throw new \Exception("Not used");
    }

}