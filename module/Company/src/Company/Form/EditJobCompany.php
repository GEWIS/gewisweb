<?php

namespace Company\Form;

use Zend\Form\Form;
use Zend\InputFilter\InputFilter;
use Zend\Mvc\I18n\Translator as Translator;

class EditJobCompany extends CollectionBaseFieldsetAwareForm
{
    private $translator;
    private $companySlug;
    private $currentSlug;
    private $languages;

    protected $extraInputFilter;

    public function __construct($mapper, Translator $translator, $languages, $hydrator, $labels)
    {
        // we want to ignore the name passed
        parent::__construct();
        $this->translator = $translator;
        $this->mapper = $mapper;


        $this->setHydrator($hydrator);
        $this->setAttribute('method', 'post');

        $labelOptions = [];
        foreach ($labels as $label) {
            $labelOptions[] = array('value' => $label->getId(),
                'label' => $label->getName(),
                'label_attributes' => array('class' => 'checkbox')
            );
        }

        $this->setLanguages($languages);
        $this->add([
            'type' => '\Company\Form\FixedKeyDictionaryCollection',
            'name' => 'jobs',
            'hydrator' => $this->getHydrator(),
            'options' => [
                'use_as_base_fieldset' => true,
                'count' => count($languages),
                'target_element' => new JobFieldset($mapper, $translator, $this->getHydrator()),
                'items' => $languages,
            ]
        ]);

        $this->add(
            $this->mapper->createObjectSelectConfig(
                'Company\Model\JobCategory',
                'name',
                $this->translator->translate('Category'),
                'category',
                'nl'
            )
        );



        $this->add([
            'name' => 'labels',
            'type' => 'Zend\Form\Element\MultiCheckbox',
            'options' => [
                'label' => $translator->translate('What labels apply to this job?'),
                'value_options' => $labelOptions
            ],
        ]);

        $this->add(
            $this->mapper->createObjectSelectConfig(
                'Company\Model\JobSector',
                'name',
                $this->translator->translate('Sectors'),
                'sectors',
                'nl'
            )
        );

        $this->add([
            'name' => 'email',
            'hydrator' => $this->getHydrator(),
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
            'name' => 'location',
            'attributes' => [
                'type' => 'text',
            ],
            'options' => [
                'label' => $translator->translate('Location'),
                'required' => false,
            ],
        ]);

        $this->add([
            'name' => 'startingDate',
            'type' => 'Zend\Form\Element\Date',
            'attributes' => [
                'step' => '1',
            ],
            'options' => [
                'label' => $translator->translate('Starting Date'),
            ],
        ]);

        $this->add(array(
            'type' => 'Zend\Form\Element\Select',
            'name' => 'hours',
            'options' => array(
                'label' => $translator->translate('Hours'),
                'value_options' => array(
                    '0' => 'Part time',
                    '1' => 'Full time',
                ),
            )
        ));

        $this->add([
            'name' => 'phone',
            'hydrator' => $this->getHydrator(),
            'attributes' => [
                'type' => 'text',
            ],
            'options' => [
                'label' => $translator->translate('Phone'),
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
            'name' => 'submit',
            'attributes' => [
                'type' => 'submit',
                'value' => $translator->translate('Submit changes'),
                'id' => 'submitbutton',
            ],
        ]);

        $this->initFilters();
    }

    protected function initFilters()
    {
        $parentFilter = new InputFilter();
        $rootFilter = new InputFilter();

        // TODO: Set filters correctly
        foreach ($this->languages as $lang) {
            $filter = new JobInputFilter();

            $filter->add([
                'name' => 'id',
                'required' => false,
            ]);

            $filter->add([
                'name' => 'active',
                'required' => false,
            ]);

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
                        'callback' => [$this, 'slugNameUnique'],
                        'message' => $this->translator->translate('This slug is already taken'),
                    ]),
                    new \Zend\Validator\Regex([
                        'message' => $this->translator->translate('This slug contains invalid characters'),
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
                'name' => 'teaser',
                'required' => false,
                'validators' => [
                    [
                        'name' => 'string_length',
                        'options' => [
                            'min' => 2,
                            'max' => 1000,
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
                'name' => 'active',
                'required' => false,
                'validators' => [

                ]
            ]);

            $filter->add([
                'name' => 'email',
                'required' => false,
                'validators' => [
                    ['name' => 'email_address'],
                ],
            ]);

            $filter->add([
                'name' => 'phone',
                'required' => false,
            ]);

            $filter->add([
                'name' => 'hours',
                'required' => true,
            ]);

//            $filter->add([
//                'name' => 'active',
//                'required' => false,
//            ]);

            $filter->add([
                'name' => 'attachment_file',
                'required' => false,
                'validators' => [
                    [
                        'name' => 'Callback',
                        'options' => [
                            'callback' => function ($value) {
                                // If no file is uploaded, we don't care, because it is optional
                                if ($value['error'] == 4) {
                                    return true;
                                }
                                $extensionValidator = new \Zend\Validator\File\Extension('pdf');
                                if (!$extensionValidator->isValid($value)) {
                                    return false;
                                }
                                $mimeValidator = new \Zend\Validator\File\MimeType('application/pdf');
                                return $mimeValidator->isValid($value);
                            }
                        ],
                    ],
                ],
            ]);

            $filter->add([
                'name' => 'category',
                'required' => false,
            ]);

            $filter->add([
                'name' => 'sectors',
                'required' => false,
            ]);

            $rootFilter->add($filter, $lang);
        }

        $parentFilter->add($rootFilter, $this->baseFieldset->getName());
        $this->extraInputFilter = $parentFilter;
        $this->setInputFilter($parentFilter);
    }

    public function getInputFilter()
    {
        return $this->extraInputFilter;
    }

    public function setLanguages($languages)
    {
        $this->languages = $languages;
    }

    public function setLabels($labels)
    {
        $labelsElement = $this->get('labels');
        $options = [];

        foreach ($labels as $label) {
            $options[] = $label->getId();
        }

        $labelsElement->setValue(array_values($options));
    }

    public function getLanguages()
    {
        return $this->languages;
    }

    public function setCompanySlug($companySlug)
    {
        $this->companySlug = $companySlug;
    }

    public function setCurrentSlug($currentSlug)
    {
        $this->currentSlug = $currentSlug;
    }

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
