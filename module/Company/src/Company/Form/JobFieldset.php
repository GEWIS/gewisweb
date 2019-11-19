<?php

namespace Company\Form;

use Zend\Form\Form;
use Zend\Form\Fieldset;
use Zend\InputFilter\InputFilter;
use Zend\Mvc\I18n\Translator as Translator;

class JobFieldset extends Fieldset
{
    public function __construct($mapper, Translator $translator, $hydrator)
    {
        parent::__construct();

        $this->setAttribute('method', 'post');
        $this->mapper = $mapper;
        $this->translator = $translator;
        $this->setHydrator($hydrator);
        $this->addFields($translator);
    }

    public function addFields($translator)
    {
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
                'label' => $translator->translate('Name'),
            ],
        ]);
        $this->add([
            'name' => 'slugName',
            'attributes' => [
                'type' => 'text',
            ],
            'options' => [
                'label' => $translator->translate('Permalink'),
            ],
        ]);
        $this->add([
            'name' => 'active',
            'type' => 'Zend\Form\Element\Checkbox',
            'options' => [
                'label' => $translator->translate('Active'),
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
                'label' => $translator->translate('Website'),
                'required' => false,
            ],
        ]);
        $this->add([
            'name' => 'attachment_file',
            'type' => '\Zend\Form\Element\File',
            'attributes' => [
                'type' => 'file',
            ],
            'options' => [
                'label' => $translator->translate('Attachment'),
                'required' => false,
            ],
        ]);
        $this->add([
            'name' => 'email',
            'type' => 'Zend\Form\Element\Email',
            'attributes' => [
                'type' => 'text',
            ],
            'options' => [
                'label' => $translator->translate('Email'),
                'required' => false,
            ],
        ]);
        $this->add([
            'name' => 'contactName',
            'attributes' => [
                'type' => 'text',
            ],
            'options' => [
                'label' => $translator->translate('Contact name'),
                'required' => false,
            ],
        ]);
        $this->add([
            'name' => 'phone',
            'attributes' => [
                'type' => 'text',
            ],
            'options' => [
                'label' => $translator->translate('Phone'),
                'required' => false,
            ],
        ]);
        $this->add([
            'name' => 'description',
            'type' => 'Zend\Form\Element\Textarea',
            'options' => [
                'label' => $translator->translate('Description'),
                'required' => false,
            ],
        ]);
    }

    protected $mapper;
    protected $translator;

    public function setLanguage($lang)
    {
        $jc = new \Company\Model\Job();
        $jc->setLanguage($lang);
        $this->add(
            $this->mapper->createObjectSelectConfig(
                'Company\Model\JobCategory',
                'name',
                $this->translator->translate('Category'),
                'category',
                $lang
            )
        );
        $this->setObject($jc);
    }
}
