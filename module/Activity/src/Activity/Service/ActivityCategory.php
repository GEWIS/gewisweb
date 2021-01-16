<?php

namespace Activity\Service;

use Activity\Form\ActivityCategory as CategoryForm;
use Activity\Model\ActivityCategory as CategoryModel;
use Activity\Model\LocalisedText;

use Application\Service\AbstractAclService;
use Zend\ServiceManager\ServiceManagerAwareInterface;

class ActivityCategory extends AbstractAclService implements ServiceManagerAwareInterface
{
    /**
     * Get the ACL.
     *
     * @return \Zend\Permissions\Acl\Acl
     */
    public function getAcl()
    {
        return $this->getServiceManager()->get('activity_acl');
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

    /**
     * Get all categories.
     *
     * @param $id
     * @return \Activity\Model\ActivityCategory
     */
    public function getCategoryById($id)
    {
        if (!$this->isAllowed('listCategories', 'activity')) {
            $translator = $this->getTranslator();
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You are not allowed to view activity categories')
            );
        }
    
        $categoryMapper = $this->getCategoryMapper();
        return $categoryMapper->getCategoryById($id);
    }

    /**
     * Get all categories.
     *
     * @return array
     */
    public function getAllCategories()
    {
        if (!$this->isAllowed('listCategories', 'activity')) {
            $translator = $this->getTranslator();
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You are not allowed to view activity categories')
            );
        }
    
        $categoryMapper = $this->getCategoryMapper();
        return $categoryMapper->getAllCategories();
    }

    /**
     * Return Category creation form.
     *
     * @return CategoryForm
     */
    public function getCategoryForm()
    {
        if (!$this->isAllowed('addCategory', 'activity')) {
            $translator = $this->getTranslator();
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You are not allowed to create an activity category')
            );
        }

        return $this->getServiceManager()->get('activity_form_category');
    }

    public function createCategory($data)
    {
        if (!$this->isAllowed('addCategory', 'activity')) {
            $translator = $this->getTranslator();
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You are not allowed to create an activity category')
            );
        }

        $form = $this->getCategoryForm();
        $form->setData($data);

        if (!$form->isValid()) {
            return false;
        }

        $category = new CategoryModel();
        $category->setName(new LocalisedText($data['nameEn'], $data['name']));

        $em = $this->getServiceManager()->get('Doctrine\ORM\EntityManager');
        $em->persist($category);
        $em->flush();

        return true;
    }

    public function updateCategory($category, $data)
    {
        if (!$this->isAllowed('editCategory', 'activity')) {
            $translator = $this->getTranslator();
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You are not allowed to edit an activity category')
            );
        }

        $form = $this->getCategoryForm();
        $form->setData($data);

        if (!$form->isValid()) {
            return false;
        }

        $name = $category->getName();
        $name->updatevalues($data['nameEn'], $data['name']);

        $em = $this->getServiceManager()->get('Doctrine\ORM\EntityManager');
        $em->persist($name);
        $em->persist($category);
        $em->flush();

        return true;
    }

    public function deleteCategory($category)
    {
        if (!$this->isAllowed('deleteCategory', 'activity')) {
            $translator = $this->getTranslator();
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You are not allowed to delete an activity category')
            );
        }

        $em = $this->getServiceManager()->get('Doctrine\ORM\EntityManager');
        $em->remove($category);
        $em->flush();
    }

    /**
     * Get the activity mapper.
     *
     * @return \Activity\Mapper\ActivityCategory
     */
    public function getCategoryMapper()
    {
        return $this->getServiceManager()->get('activity_mapper_category');
    }
}
