<?php

namespace Company\Form;

use Zend\Form\Form;
use Zend\InputFilter\InputFilter;
use Zend\Mvc\I18n\Translator as Translator;

class EditJob extends CollectionBaseFieldsetAwareForm
{
    public function __construct($mapper, Translator $translate, $languages, $hydrator)
    {
        // we want to ignore the name passed
        parent::__construct();

        $this->mapper = $mapper;

        $this->setHydrator($hydrator);

        $this->setAttribute('method', 'post');

        $this->add([
            'type' => '\Company\Form\FixedKeyDictionaryCollection',
            'name' => 'jobs',
            'hydrator' => $this->getHydrator(),
            'options' => [
                'use_as_base_fieldset' => true,
                'count' => count($languages),
                'target_element' => new JobFieldset($mapper, $translate, $this->getHydrator()),
                'items' => $languages,
            ]
        ]);

        $this->add([
            'name' => 'submit',
            'attributes' => [
                'type' => 'submit',
                'value' => $translate->translate('Submit changes'),
                'id' => 'submitbutton',
            ],
        ]);

        $this->initFilters($translate);
    }
    protected function initFilters($translate)
    {
        $filter = new InputFilter();

        $filter->add([
            'name' => 'name',
            'required' => true,
            'validators' => [
                [
                    'name' => 'string_length',
                    'options' => [
                        'min' => 2,
                        'max' => 127,
                    ],
                ],
            ],
        ]);

        $filter->add([
            'name' => 'slugName',
            'required' => true,
            'validators' => [
                new \Zend\Validator\Callback([
                    'callback' => [$this,'slugNameUnique'],
                    'message' => $translate->translate('This slug is already taken'),
                ]),
                new \Zend\Validator\Regex([
                    'message' => $translate->translate('This slug contains invalid characters') ,
                    'pattern' => '/^[0-9a-zA-Z_\-\.]*$/',
                ]),
            ],
            'filters' => [
            ],
        ]);

        $filter->add([
            'name' => 'website',
            'required' => false,
            'validators' => [
                [
                    'name' => 'uri',
                ],
            ],
        ]);

        $filter->add([
            'name' => 'description',
            'required' => false,
            'validators' => [
                [
                    'name' => 'string_length',
                    'options' => [
                        'min' => 2,
                        'max' => 10000,
                    ],
                ],
            ],
        ]);
        $filter->add([
            'name' => 'contactName',
            'required' => false,
            'validators' => [
                [
                    'name' => 'string_length',
                    'options' => [
                        'max' => 200,
                    ],
                ],
            ],
        ]);

        $filter->add([
            'name' => 'email',
            'required' => false,
            'validators' => [
                ['name' => 'email_address'],
            ],
        ]);

        //$filter->add([
            //'name' => 'attachment_file',
            //'required' => false,
            //'validators' => [
                //[
                    //'name' => 'File\Extension',
                    //'options' => [
                        //'extension' => 'pdf',
                    //],
                //],
                //[
                    //'name' => 'File\MimeType',
                    //'options' => [
                        //'mimeType' => 'application/pdf',
                    //],
                //],
            //],
        //]);
        $this->inputFilter = $filter;
    }

    public function isValid()
    {
        if (!parent::isValid()) {
            return false;
        }
        $arr = [];
        foreach ($this->get('jobs')->getFieldSets() as $fieldset) {
            foreach ($fieldset->getElements() as $el) {
                $val = $el->getValue();
                $arr[$el->getName()] = $val;
            }
        }
        $this->inputFilter->setData($arr);
        $valid = $this->inputFilter->isValid();
        if (!$valid) {
            var_dump($this->inputFilter->getMessages());
        }
        return $valid;
    }

    private $companySlug;

    private $currentSlug;

    public function setCompanySlug($companySlug)
    {
        $this->companySlug = $companySlug;
    }

    public function setCurrentSlug($currentSlug)
    {
        $this->currentSlug = $currentSlug;
    }

    protected $inputFilter;

    /**
     *
     * Checks if a given slugName is unique. (Callback for validation).
     *
     */
    public function slugNameUnique($slugName, $context)
    {
        $jid = $context['id'];
        $cat = $context['category'];
        if ($this->currentSlug === $slugName) {
            return true;
        }

        return $this->mapper->isSlugNameUnique($this->companySlug, $slugName, $jid, $cat);

    }
}
