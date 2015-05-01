<?php
namespace Company\Form;

use Zend\Form\Element;
use Zend\Form\Form;
use Zend\InputFilter\InputFilter;
use Zend\Mvc\I18n\Translator;

class EditCompany extends Form
{
    public function __construct(Translator $translate)
    {
        // we want to ignore the name passed
        parent::__construct();
        
        $this->setAttribute('method', 'post');
        $this->add(array(
            'name' => 'id',
            'attributes' => array(
                'type'  => 'hidden',
            ),
        ));
        $this->add(array(
            'name' => 'languageNeutralId',
            'attributes' => array(
                'type'  => 'hidden',
            ),
        ));
        $this->add(array(
            'name' => 'slugName',
            'attributes' => array(
                'type'  => 'text',
                'required' => 'required'
            ),
            'options' => array(
                'label' => $translate->translate('Permalink'),
                'required' => 'required'
            ),
        ));
        $this->add(array(
            'name' => 'name',
            'attributes' => array(
                'type'  => 'text',
                'required' => 'required'
            ),
            'options' => array(
                'label' => $translate->translate('Name'),
                'required' => 'required'
            ),
        ));
        $this->add(array(
            'name' => 'address',
            'type' => 'Zend\Form\Element\Textarea',
            'attributes' => array(
                'required' => 'required',
                'type' => 'textarea'
            ),
            'options' => array(
                'label' => $translate->translate('Location'),
                'required' => 'required'
            ),
        ));
        $this->add(array(
            'name' => 'website',
            'type' => 'Zend\Form\Element\Url',
            'attributes' => array(
                'required' => 'required'
            ),
            'options' => array(
                'label' => $translate->translate('Website'),
                'required' => 'required'
            ),
        ));
        $this->add(array(
            'name' => 'slogan',
            'attributes' => array(
                'type'  => 'text',
            ),
            'options' => array(
                'label' => $translate->translate('Slogan'),
            ),
        ));
        $this->add(array(
            'name' => 'email',
            'type' => 'Zend\Form\Element\Email',
            'attributes' => array(
                'required' => 'required'
            ),
            'options' => array(
                'label' => $translate->translate('Email'),
                'required' => 'required'
            ),
        ));
        $this->add(array(
            'name' => 'phone',
            'attributes' => array(
                'type'  => 'text',
            ),
            'options' => array(
                'label' => $translate->translate('Phone'),
            ),
        ));
        $this->add(array(
            'name' => 'logo',
            'attributes' => array(
                'type'  => 'file',
            ),
            'options' => array(
                'label' => $translate->translate('Logo'),
            ),
        ));
        $this->add(array(
            'name' => 'description',
            'type' => 'Zend\Form\Element\Textarea',
            'options' => array(
                'label' => $translate->translate('Description'),
                'required' => 'required'
            ),
            'attributes' => array(
                'type' => 'textarea'  
            ),
        ));
        $this->add(array(
            'name' => 'submit',
            'attributes' => array(
                'type'  => 'submit',
                'value' => $translate->translate('Submit changes'),
                'id' => 'submitbutton',
            ),
        ));
        
        $this->initFilters();
    }
    
    protected function initFilters()
    {
        $filter = new InputFilter();


        $filter->add(array(
            'name' => 'name',
            'required' => true,
            'validators' => array(
                array(
                    'name' => 'string_length',
                    'options' => array(
                        'min' => 2,
                        'max' => 127
                    )
                )
            ),
            'filters' => array(
                array('name' => 'StripTags'),
                array('name' => 'StringTrim')
            ),
        ));
        
        $filter->add(array(
            'name' => 'website',
            'required' => true,
            'filters' => array(
                array('name' => 'StripTags'),
                array('name' => 'StringTrim')
            ),
            'validators' => array(
            )
        ));
        
        $filter->add(array(
            'name' => 'description',
            'required' => true,
            'validators' => array(
                array(
                    'name' => 'string_length',
                    'options' => array(
                        'min' => 2,
                        'max' => 10000
                    )
                )
            )
        ));
        
        $filter->add(array(
            'name' => 'email',
            'required' => true,
            'validators' => array(
                array(
                    'name' => 'EmailAddress',
                    'options' => array(
                        'messages' => array(
                            'emailAddressInvalidFormat' => 'Email address format is not valid'
                        )
                    )
                ),
            ),
            'filters' => array(
                array('name' => 'StripTags'),
                array('name' => 'StringTrim')
            ),
        ));

        $filter->add(array(
            'name' => 'logo',
            'required' => false,
            'validators' => array(
                array(
                    'name' => 'File\Extension',
                    'options' => array(
                        'extension' => 'png'
                    )
                ),
                array(
                    'name' => 'File\MimeType',
                    'options' => array(
                        'mimeType' => 'image/png'
                    )
                )
            )
        ));
        
        $this->setInputFilter($filter);
    }
}
