<?php
namespace Company\Form;

use Zend\Form\Form;
use Zend\InputFilter\InputFilter;
//use Zend\I18n\Translator\TranslatorInterface as Translator;
use Zend\Mvc\I18n\Translator as Translator;

class EditJob extends Form
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
            'name' => 'name',
            'attributes' => array(
                'type'  => 'text',
            ),
            'options' => array(
                'label' => $translate->translate('Name'),
            ),
        ));
        $this->add(array(
            'name' => 'asciiName',
            'attributes' => array(
                'type'  => 'text',
            ),
            'options' => array(
                'label' => $translate->translate('asciiName'),
            ),
        ));
        $this->add(array(
            'name' => 'active',
            'attributes' => array(
 //               'type'  => 'boolean',
                'type' => 'Checkbox',
            ),
            'options' => array(
                'label' => $translate->translate('Active'),
            ),
        ));
        $this->add(array(
            'name' => 'website',
            'attributes' => array(
                'type'  => 'text',
            ),
            'options' => array(
                'label' => $translate->translate('Website'),
            ),
        ));
        $this->add(array(
            'name' => 'email',
            'attributes' => array(
                'type'  => 'text',
            ),
            'options' => array(
                'label' => $translate->translate('Email'),
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
            'name' => 'description',
            'attributes' => array(
                'type'  => 'textarea',
            ),
            'options' => array(
                'label' => $translate->translate('Description'),
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
                        'max' => 255
                    )
                ),
                array('name' => 'alnum')
            )
        ));

//        $filter->add(array(
//            'name' => 'date',
//            'required' => true,
//            'validators' => array(
//                array('name' => 'date')
//            )
//        ));

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
        
        /**
         * TODO: Add more filters
         * 
         * Email filter: http://stackoverflow.com/questions/20946210/zend2-limiting-e-mail-validation-to-only-one-error-message
         */

        $this->setInputFilter($filter);
    }
}
