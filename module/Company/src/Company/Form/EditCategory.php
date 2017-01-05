<?php

namespace Company\Form;

use Zend\Form\Form;
use Zend\Form\Element\Collection;
use Zend\InputFilter\InputFilter;
use Zend\Mvc\I18n\Translator;
use Zend\Form\FormInterface;

class EditCategory extends CollectionBaseFieldsetAwareForm
{
    public function __construct($mapper, Translator $translate, $languages, $hydrator)
    {
        // we want to ignore the name passed
        parent::__construct();

        $this->mapper = $mapper;

        $this->setHydrator($hydrator);

        $this->setAttribute('method', 'post');

        $this->add(
            [
                'type' => '\Company\Form\FixedKeyDictionaryCollection',
                'name' => 'categories',
                'hydrator' => $this->getHydrator(),
                'options' => [
                    'use_as_base_fieldset' => true,
                    'count' => count($languages),
                    'target_element' => new CategoryFieldset($translate, $this->getHydrator()),
                    'items' => $languages,
                ]
            ]
        );
        $this->add([
            'name' => 'submit',
            'attributes' => [
                'type' => 'submit',
                'value' => $translate->translate('Submit changes'),
                'id' => 'submitbutton',
            ],
        ]);
    }

    public function slugNameUnique($slugName, $context)
    {
        $cid = $context['id'];
        return $this->mapper->isSlugNameUnique($slugName, $cid);

    }
}
