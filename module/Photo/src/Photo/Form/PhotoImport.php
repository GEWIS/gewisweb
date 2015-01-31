<?php

namespace Photo\Form;

use Zend\Form\Form;
use Zend\InputFilter\InputFilter;
use Zend\Mvc\I18n\Translator as Translator;

class PhotoImport extends Form
{

    public function __construct(Translator $translate)
    {
        parent::__construct();

        $this->add(array(
            'name' => 'album_id',
            'type' => 'text',
            'value' => '1' //TODO: get album id
        ));

        $this->add(array(
            'name' => 'folder_path',
            'type' => 'text',
            'options' => array(
                'label' => $translate->translate('Folder path')
            )
        ));

        $this->add(array(
            'name' => 'submit',
            'type' => 'submit',
            'options' => array(
                'label' => $translate->translate('Import folder')
            )
        ));

        $this->initFilters();
    }

    protected function initFilters()
    {
        $filter = new InputFilter();
        //TODO: Validators!!
        /*$filter->add(array(
            'name' => 'folder_path',
            'required' => true,
            'validators' => array(array(
                    'name' => 'exists',
                ),
            )
        ));*/

        $filter->add(array(
            'name' => 'album_id',
            'required' => true,
            'validators' => array(
                array('name' => 'not_empty'),
               
                //TODO: check if album exists
            )
        ));
        $this->setInputFilter($filter);
    }

}
