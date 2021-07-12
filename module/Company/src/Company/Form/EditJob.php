<?php

namespace Company\Form;

use Laminas\InputFilter\InputFilter;
use Laminas\Mvc\I18n\Translator;
use Laminas\Validator\Callback;
use Laminas\Validator\EmailAddress;
use Laminas\Validator\File\Extension;
use Laminas\Validator\File\MimeType;
use Laminas\Validator\Regex;
use Laminas\Validator\StringLength;

class EditJob extends CollectionBaseFieldsetAwareForm
{
    private $translator;
    private $companySlug;
    private $currentSlug;
    private $languages;

    protected $extraInputFilter;
    private $mapper;

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
        $this->add(
            [
            'type' => '\Company\Form\FixedKeyDictionaryCollection',
            'name' => 'jobs',
            'hydrator' => $this->getHydrator(),
            'options' => [
                'use_as_base_fieldset' => true,
                'count' => count($languages),
                'target_element' => new JobFieldset($mapper, $translator, $this->getHydrator()),
                'items' => $languages,
            ]
            ]
        );

        $this->add(
            [
            'name' => 'labels',
            'type' => 'Laminas\Form\Element\MultiCheckbox',
            'options' => [
                'label' => $translator->translate('What labels apply to this job?'),
                'value_options' => $labelOptions
            ],
            ]
        );

        $this->add(
            [
            'name' => 'submit',
            'attributes' => [
                'type' => 'submit',
                'value' => $translator->translate('Submit changes'),
                'id' => 'submitbutton',
            ],
            ]
        );

        $this->initFilters();
    }

    protected function initFilters()
    {
        $parentFilter = new InputFilter();
        $rootFilter = new InputFilter();

        foreach ($this->languages as $lang) {
            $filter = new JobInputFilter();

            $filter->add(
                [
                'name' => 'id',
                'required' => false,
                ]
            );

            $filter->add(
                [
                'name' => 'name',
                'required' => true,
                'validators' => [
                    [
                        'name' => StringLength::class,
                        'options' => [
                            'min' => 2,
                            'max' => 127,
                        ],
                    ],
                ],
                ]
            );

            $filter->add(
                [
                'name' => 'slugName',
                'required' => true,
                'validators' => [
                    new Callback(
                        [
                        'callback' => [$this, 'slugNameUnique'],
                        'message' => $this->translator->translate('This slug is already taken'),
                        ]
                    ),
                    new Regex(
                        [
                        'message' => $this->translator->translate('This slug contains invalid characters'),
                        'pattern' => '/^[0-9a-zA-Z_\-\.]*$/',
                        ]
                    ),
                ],
                'filters' => [
                ],
                ]
            );

            $filter->add(
                [
                'name' => 'website',
                'required' => false,
                'validators' => [
                    [
                        'name' => 'uri',
                    ],
                ],
                ]
            );

            $filter->add(
                [
                'name' => 'description',
                'required' => false,
                'validators' => [
                    [
                        'name' => StringLength::class,
                        'options' => [
                            'min' => 2,
                            'max' => 10000,
                        ],
                    ],
                ],
                ]
            );

            $filter->add(
                [
                'name' => 'contactName',
                'required' => false,
                'validators' => [
                    [
                        'name' => StringLength::class,
                        'options' => [
                            'max' => 200,
                        ],
                    ],
                ],
                ]
            );

            $filter->add(
                [
                'name' => 'email',
                'required' => false,
                'validators' => [
                    ['name' => EmailAddress::class],
                ],
                ]
            );

            $filter->add(
                [
                'name' => 'phone',
                'required' => false,
                ]
            );

            $filter->add(
                [
                'name' => 'active',
                'required' => false,
                ]
            );

            $filter->add(
                [
                'name' => 'attachment_file',
                'required' => false,
                'validators' => [
                    [
                        'name' => Callback::class,
                        'options' => [
                            'callback' => function ($value) {
                                // If no file is uploaded, we don't care, because it is optional
                                if ($value['error'] == 4) {
                                    return true;
                                }
                                $extensionValidator = new Extension('pdf');
                                if (!$extensionValidator->isValid($value)) {
                                    return false;
                                }
                                $mimeValidator = new MimeType('application/pdf');
                                return $mimeValidator->isValid($value);
                            }
                        ],
                    ],
                ],
                ]
            );

            $filter->add(
                [
                'name' => 'category',
                'required' => false,
                ]
            );

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
