<?php
namespace Activity\Form;

use Zend\Form\Form;
class Activity extends Form
{
    public function __construct() {
        parent::__construct('activity');
        $this->setAttribute('method', 'post');

        $this->add([
            'name' => 'name',
            'attributes' => [
                'type' => 'text'
            ],
            'options' => [
                'label' =>  'Name:'
            ]
        ]);

        $this->add([
            'name' => 'beginTime',
            'attributes' => [
                'type' => 'text'
            ],
            'options' => [
                'label' =>  'Begin date and time of the activity: (yyyy-mm-dd hh:mm)'
            ]
        ]);

        $this->add([
            'name' => 'endTime',
            'attributes' => [
                'type' => 'text'
            ],
            'options' => [
                'label' =>  'End date and time of the activity: (yyyy-mm-dd hh:mm)'
            ]
        ]);

        $this->add([
            'name' => 'location',
            'attributes' => [
                'type' => 'text'
            ],
            'options' => [
                'label' =>  'Location:'
            ]
        ]);

        $this->add([
            'name' => 'costs',
            'attributes' => [
                'type' => 'text'
            ],
            'options' => [
                'label' =>  'Costs:'
            ]
        ]);

        $this->add(array(
            'name' => 'submit',
            'attributes' => array(
                'type'  => 'submit',
                'value' => 'Create',
            ),
        ));
    }
}