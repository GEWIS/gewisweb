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

    //protected $languages;

    //// The idea of overriding this function is that we do not have access to the name in the constructor,
    //// and the constructor is called by zf2 code. This is the first point in zf2 where we have access to the name.
    //// Possibly not the best way to do this, but it works
    //public function setName($name)
    //{
        //if (!in_array($name, $languages) && is_int($name) && $name >= 0 && $name <= count($languages)) {
            //// In this case, it is possible to use the language code instead of the index
            //$newName = $languages[$name];
            //parent::setName($newName);
            //$this->getHydrator()->setObject($newName);
            //return $this;
        //}
        //parent::setName($name);
        //$this->getHydrator()->setObject($name);
    //}
}
