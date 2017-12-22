<?php
namespace Company\Form;

use Zend\Form\Form;
use Zend\Form\Fieldset;
use Zend\InputFilter\InputFilter;
use Zend\Mvc\I18n\Translator;

/**
 *
 */
class CategoryFieldset extends Fieldset
{
    public function __construct($translate, $hydrator)
    {
        parent::__construct();
        $this->setHydrator($hydrator);
        $this->add([
            'name' => 'id',
            'attributes' => [
                'type' => 'hidden',
            ],
        ]);
        $this->add([
            'name' => 'slug',
            'attributes' => [
                'type' => 'text',
                'required' => true,
            ],
            'options' => [
                'label' => $translate->translate('Slug name'),
            ],
        ]);
        $this->add([
            'name' => 'name',
            'attributes' => [
                'type' => 'text',
                'required' => 'required',
            ],
            'options' => [
                'label' => $translate->translate('Display name'),
                'required' => 'required',
            ],
        ]);
        $this->add([
            'name' => 'pluralName',
            'attributes' => [
                'type' => 'text',
                'required' => 'required',
            ],
            'options' => [
                'label' => $translate->translate('Plural display name'),
                'required' => 'required',
            ],
        ]);
        $this->add([
            'name' => 'hidden',
            'type' => 'Zend\Form\Element\Checkbox',
            'options' => [
                'label' => $translate->translate('Hidden'),
                'checked_value' => '1',
                'unchecked_value' => '0',
                'use_hidden_element' => true,
            ],
        ]);
        // Hidden language element, because it will only be set at initialization.
        $this->add([
            'name' => 'language',
            'attributes' => [
                'type' => 'hidden',
            ],
        ]);
    }
    public function setLanguage($lang)
    {
        $jc = new \Company\Model\JobCategory();
        $jc->setLanguage($lang);
        $this->setObject($jc);
    }
}
