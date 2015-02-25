<?php
namespace Company\Form;

use Zend\Form\Element;
use Zend\Form\Form;
use Zend\InputFilter\InputFilter;
use Zend\Mvc\I18n\Translator as Translator;

class EditJob extends Form
{
    public function __construct(Translator $translate)
    {
        // we want to ignore the name passed
        parent::__construct();
        
        $this->setAttribute('method', 'post');
        $this->add(array(
            'name' => 'job-id',
            'attributes' => array(
                'type'  => 'hidden',
            ),
        ));
        $this->add(array(
            'name' => 'company-id',
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
<<<<<<< HEAD
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
=======
            'name' => 'address',
            'attributes' => array(
                'type'  => 'text',
>>>>>>> Worked on Form for JobEditor.
            ),
            'options' => array(
                'label' => $translate->translate('Location'),
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
            'type' => 'Zend\Form\Element\Email',
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
<<<<<<< HEAD
            'attributes' => array(
                'type'  => 'textarea',
            ),
=======
            'type' => 'Zend\Form\Element\Textarea',
>>>>>>> Worked on Form for JobEditor.
            'options' => array(
                'label' => $translate->translate('Description'),
            ),
        ));
        $this->add(array(
            'name' => 'active',
            'type' => 'Zend\Form\Element\Checkbox',
            'options' => array(
                'label' => $translate->translate('Active')
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
            )
        ));
        
        $filter->add(array(
            'name' => 'website',
            'required' => true,
            'validators' => array(
                array(
                    'name' => 'regex',
                    'options' => array(
                        'pattern' => '_^(?:(?:https?|ftp)://)(?:\S+(?::\S*)?@)?(?:(?!10(?:\.\d{1,3}){3})(?!127(?:\.\d{1,3}){3})(?!169\.254(?:\.\d{1,3}){2})(?!192\.168(?:\.\d{1,3}){2})(?!172\.(?:1[6-9]|2\d|3[0-1])(?:\.\d{1,3}){2})(?:[1-9]\d?|1\d\d|2[01]\d|22[0-3])(?:\.(?:1?\d{1,2}|2[0-4]\d|25[0-5])){2}(?:\.(?:[1-9]\d?|1\d\d|2[0-4]\d|25[0-4]))|(?:(?:[a-z\x{00a1}-\x{ffff}0-9]+-?)*[a-z\x{00a1}-\x{ffff}0-9]+)(?:\.(?:[a-z\x{00a1}-\x{ffff}0-9]+-?)*[a-z\x{00a1}-\x{ffff}0-9]+)*(?:\.(?:[a-z\x{00a1}-\x{ffff}]{2,})))(?::\d{2,5})?(?:/[^\s]*)?$_iu'
                     )     
                )
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
                array('name' => 'email_address'),
            )
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
        
        /**
         * TODO: Add more filters
         * 
         * Email filter: http://stackoverflow.com/questions/20946210/zend2-limiting-e-mail-validation-to-only-one-error-message
         */

        $this->setInputFilter($filter);
    }
}
