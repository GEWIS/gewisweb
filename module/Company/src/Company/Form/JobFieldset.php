<?php

namespace Company\Form;

use Zend\Form\Form;
use Zend\Form\Fieldset;
use Zend\InputFilter\InputFilter;
use Zend\Mvc\I18n\Translator as Translator;

class JobFieldset extends Fieldset
{
    public function __construct($mapper, Translator $translate, $hydrator)
    {
        // we want to ignore the name passed
        parent::__construct();

        $this->setAttribute('method', 'post');
        $this->mapper = $mapper;
        $this->translate = $translate;
        $this->setHydrator($hydrator);

        $this->add([
            'name' => 'id',
            'attributes' => [
                'type' => 'hidden',
            ],
        ]);

        $this->add([
            'name' => 'name',
            'attributes' => [
                'type' => 'text',
            ],
            'options' => [
                'label' => $translate->translate('Name'),
            ],
        ]);
        $this->add([
            'name' => 'slugName',
            'attributes' => [
                'type' => 'text',
            ],
            'options' => [
                'label' => $translate->translate('Permalink'),
            ],
        ]);
        $this->add([
            'name' => 'active',
            'type' => 'Zend\Form\Element\Checkbox',
            'options' => [
                'label' => $translate->translate('Active'),
                'use_hidden_element' => true,
                'checked_value' => '1',
                'unchecked_value' => '0',
            ],
        ]);
        $this->add([
            'name' => 'website',
            'attributes' => [
                'type' => 'text',
            ],
            'options' => [
                'label' => $translate->translate('Website'),
            ],
        ]);
        $this->add([
            'name' => 'attachment_file',
            'type' => '\Zend\Form\Element\File',
            'attributes' => [
                'type' => 'file',
            ],
            'options' => [
                'label' => $translate->translate('Attachment'),
            ],
        ]);
        $this->add([
            'name' => 'email',
            'type' => 'Zend\Form\Element\Email',
            'attributes' => [
                'type' => 'text',
            ],
            'options' => [
                'label' => $translate->translate('Email'),
            ],
        ]);
        $this->add([
            'name' => 'contactName',
            'attributes' => [
                'type' => 'text',
            ],
            'options' => [
                'label' => $translate->translate('Contact name'),
            ],
        ]);
        $this->add([
            'name' => 'phone',
            'attributes' => [
                'type' => 'text',
            ],
            'options' => [
                'label' => $translate->translate('Phone'),
            ],
        ]);
        $this->add([
            'name' => 'description',
            'type' => 'Zend\Form\Element\Textarea',
            'options' => [
                'label' => $translate->translate('Description'),
            ],
        ]);
        $this->add([
            'name' => 'active',
            'type' => 'Zend\Form\Element\Checkbox',
            'options' => [
                'label' => $translate->translate('Active'),
            ],
        ]);
         //$this->add([
            //'name' => 'category',
            //'type' => 'DoctrineModule\Form\Element\ObjectSelect',
            //'options' => [
                //'label' => $translate->translate('Category'),
                //'object_manager' => $objectManager,
                //'target_class' => 'Company\Model\JobCategory',
                //'property' => 'category'
            //],
            ////'attributes' => [
                ////'class' => 'form-control input-sm'
            ////]
        //]);
    }
    protected $mapper;
    protected $translate;
    public function setLanguage($lang)
    {
        $jc = new \Company\Model\Job();
        $jc->setLanguage($lang);
        $this->add(
            $this->mapper->createObjectSelectConfig(
                'Company\Model\JobCategory',
                'name',
                $this->translate->translate('Category'),
                'category',
                $lang
            )
        );
        $this->setObject($jc);
    }
}
