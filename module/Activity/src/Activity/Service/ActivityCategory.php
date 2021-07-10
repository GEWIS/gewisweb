<?php

namespace Activity\Service;

use Activity\Form\ActivityCategory as CategoryForm;
use Activity\Model\ActivityCategory as CategoryModel;
use Activity\Model\LocalisedText;
use Application\Service\AbstractAclService;
use User\Permissions\NotAllowedException;
use Zend\Mvc\I18n\Translator;
use Zend\Permissions\Acl\Acl;
use Zend\ServiceManager\ServiceManager;
use Zend\ServiceManager\ServiceManagerAwareInterface;

class ActivityCategory extends AbstractAclService implements ServiceManagerAwareInterface
{

    /**
     * Service manager.
     *
     * @var ServiceManager
     */
    protected $sm;

    /**
     * Set the service manager.
     *
     * @param ServiceManager $sm
     */
    public function setServiceManager(ServiceManager $sm)
    {
        $this->sm = $sm;
    }

    public function getRole()
    {
        return $this->sm->get('user_role');
    }
    /**
     * @var Translator
     */
    private $translator;

    public function __construct(Translator $translator)
    {
        $this->translator = $translator;
    }

    /**
     * Get the ACL.
     *
     * @return Acl
     */
    public function getAcl()
    {
        return $this->sm->get('activity_acl');
    }

    /**
     * Get all categories.
     *
     * @param $id
     * @return CategoryModel
     */
    public function getCategoryById($id)
    {
        if (!$this->isAllowed('listCategories', 'activity')) {

            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to view activity categories')
            );
        }

        $categoryMapper = $this->getCategoryMapper();
        return $categoryMapper->getCategoryById($id);
    }

    /**
     * Get the activity mapper.
     *
     * @return \Activity\Mapper\ActivityCategory
     */
    public function getCategoryMapper()
    {
        return $this->sm->get('activity_mapper_category');
    }

    /**
     * Get all categories.
     *
     * @return array
     */
    public function getAllCategories()
    {
        if (!$this->isAllowed('listCategories', 'activity')) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to view activity categories')
            );
        }

        $categoryMapper = $this->getCategoryMapper();
        return $categoryMapper->getAllCategories();
    }

    public function createCategory($data)
    {
        if (!$this->isAllowed('addCategory', 'activity')) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to create an activity category')
            );
        }

        $form = $this->getCategoryForm();
        $form->setData($data);

        if (!$form->isValid()) {
            return false;
        }

        $category = new CategoryModel();
        $category->setName(new LocalisedText($data['nameEn'], $data['name']));

        $em = $this->sm->get('Doctrine\ORM\EntityManager');
        $em->persist($category);
        $em->flush();

        return true;
    }

    /**
     * Return Category creation form.
     *
     * @return CategoryForm
     */
    public function getCategoryForm()
    {
        if (!$this->isAllowed('addCategory', 'activity')) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to create an activity category')
            );
        }

        return $this->sm->get('activity_form_category');
    }

    public function updateCategory($category, $data)
    {
        if (!$this->isAllowed('editCategory', 'activity')) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to edit an activity category')
            );
        }

        $form = $this->getCategoryForm();
        $form->setData($data);

        if (!$form->isValid()) {
            return false;
        }

        $name = $category->getName();
        $name->updatevalues($data['nameEn'], $data['name']);

        $em = $this->sm->get('Doctrine\ORM\EntityManager');
        $em->persist($name);
        $em->persist($category);
        $em->flush();

        return true;
    }

    public function deleteCategory($category)
    {
        if (!$this->isAllowed('deleteCategory', 'activity')) {

            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to delete an activity category')
            );
        }

        $em = $this->sm->get('Doctrine\ORM\EntityManager');
        $em->remove($category);
        $em->flush();
    }

    /**
     * Get the default resource ID.
     *
     * This is used by {@link isAllowed()} when no resource is specified.
     *
     * @return string
     */
    protected function getDefaultResourceId()
    {
        return 'activity';
    }
}
