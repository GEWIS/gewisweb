<?php

namespace Activity\Model;

use Doctrine\ORM\Mapping as ORM;

/**
 * Activity field model.
 *
 * @ORM\Entity
 */
class ActivityField
{
    /**
     * ID for the field.
     *
     * @ORM\Id
     * @ORM\Column(type="integer", nullable=false)
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;
    
    /**
     * Activity that the field belongs to.
     *
     * @ORM\ManyToOne(targetEntity="Activity\Model\Activity", inversedBy="fields", cascade={"persist"})
     * @ORM\JoinColumn(name="activity_id",referencedColumnName="id")
     */
    protected $activity;
    
    /**
     * The name of the field.
     * 
     * @ORM\Column(type="string", nullable=false) 
     */
    protected $name;

    /**
     * The type of the field.
     * 
     * @ORM\Column(type="integer", nullable=false)
     */
    protected $type;
    
    /**
     * The minimal value constraint for the ``number'' type
     * 
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $minimumValue;
    
    /**
     * The maximal value constraint for the ``number'' type.
     * 
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $maximumValue;
    
    /**
     * The allowed options for the field of the ``option'' type.
     * 
     * @ORM\OneToMany(targetEntity="ActivityOption", mappedBy="field")
     */
    protected $options;

    /**
     * Create a new field.
     *
     * @param array $params Parameters for the new field
     * @param Activity $activity The activity 
     *        the field is associated with
     * @param EntityManager $em The relevant entity manager
     * 
     * @throws \Exception If a field is loaded
     * @throws \Exception If a necessary parameter is not set
     *
     * @return \Activity\Model\ActivityField the created field
     */   
    public function create(array $params, Activity $activity, $em){
        
        if ($this->id != null) {
            throw new \Exception('There is already a loaded activity');
        }
        
        foreach (['name', 'type'] as $param) {
            if (!isset($params[$param])) {
                throw new \Exception("create: parameter $param not set");
            }
            $this->$param = $params[$param];
        }        
        //Add min,max for numerical fields
        if ($params['type'] === '2'){
            $this->minimumValue = $params['min. value'];
            $this->maximumValue = $params['max. value'];
        }
        
        
        $this->activity = $activity;
        
        if ($params['options'] !== ''){
            
            $options = explode(',', $params['options']);
            foreach ($options as $optionparam){
            
                $option = new ActivityOption();
                $option->setValue($optionparam);
                $option->setField($this);
            
                $em->persist($option);           
            }
        
            $em->flush();
        }
        return $this;
    }
    
    public function get($variable)
    {
        return $this->$variable;
    }
}
