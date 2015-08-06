<?php

namespace Frontpage\Service;

use Application\Service\AbstractAclService;
use Frontpage\Model\Page as PageModel;

/**
 * Page service, used for content management.
 */
class Page extends AbstractAclService
{

    /**
     * Returns a single page
     *
     * @param string $category
     * @param string $subCategory
     * @param string $name
     * @return \Frontpage\Model\Page|null
     */
    public function getPage($category, $subCategory, $name)
    {
        $page = $this->getPageMapper()->findPage($category, $subCategory, $name);
        if (!$this->isPageAllowed($page)) {
            throw new \User\Permissions\NotAllowedException(
                $this->getTranslator()->translate('You are not allowed to view this page.')
            );
        }
        return $page;
    }

    /**
     * Returns a single page by its id
     *
     * @param integer $pageId
     * @return \Frontpage\Model\Page|null
     */
    public function getPageById($pageId)
    {
        return $this->getPageMapper()->findPageById($pageId);
    }

    /**
     * Checks if the current user is allowed to view the given page
     *
     * @param \Frontpage\Model\Page $page
     *
     * @return bool
     */
    public function isPageAllowed($page)
    {
        $acl = $this->getAcl();
        $requiredRole = $page->getRequiredRole();
        $resource = 'page_' . $page->getId();
        $acl->addResource($resource);
        $acl->allow($requiredRole, $resource, 'view');
        return $this->isAllowed('view', $resource);
    }

    /**
     * Returns an associative array of all pages in a tree-like structure.
     *
     * @return array
     */
    public function getPages()
    {
        $pages = $this->getPageMapper()->getAllPages();
        $pageArray = array();
        foreach($pages as $page) {
            $category = $page->getCategory();
            $subCategory = $page->getSubCategory();
            $name = $page->getName();
            if(is_null($name)) {
                if(is_null($subCategory)) {
                    // Page url is /$category
                        $pageArray[$category]['page'] = $page;
                } else {
                    $pageArray[$category]['children'][$subCategory]['page'] = $page;
                    // Page url is /$category/$subCategory
                }
            } else {
                // Page url is /$category/$subCategory/$name
                $pageArray[$category]['children'][$subCategory]['children'][$name]['page'] = $page;
            }
        }

        return $pageArray;
    }

    /**
     * Creates a new Page.
     *
     * @param array $data form post data
     * @return bool|PageModel false if creation was not successful.
     */
    public function createPage($data)
    {
        $form = $this->getPageForm();
        $page = new PageModel();
        $form->bind($page);
        $form->setData($data);

        if (!$form->isValid()) {
            return false;
        }

        $this->getPageMapper()->persist($page);
        $this->getPageMapper()->flush();

        return $page;
    }

    /**
     * @param integer $pageId
     * @param array $data form post data
     * @return bool
     */
    public function updatePage($pageId, $data)
    {
        $form = $this->getPageForm($pageId);
        $form->setData($data);

        if (!$form->isValid()) {
            return false;
        }

        $this->getPageMapper()->flush();

        return true;
    }

    /**
     * Get the Page form.
     *
     * @param integer $pageId
     *
     * @return \Frontpage\Form\Page
     */
    public function getPageForm($pageId = null)
    {
        $form = $this->sm->get('frontpage_form_page');

        if(!is_null($pageId)) {
            $page = $this->getPageById($pageId);
            $form->bind($page);
        }

        return $form;
    }

    /**
     * Get the role of the current user.
     *
     * @return \User\Model\User|string
     */
    public function getRole()
    {
        return $this->sm->get('user_role');
    }

    /**
     * Get the frontpage config, as used by this service.
     *
     * @return array
     */
    public function getConfig()
    {
        $config = $this->sm->get('config');
        return $config['frontpage'];
    }

    /**
     * Get the page mapper.
     *
     * @return \Frontpage\Mapper\Page
     */
    public function getPageMapper()
    {
        return $this->sm->get('frontpage_mapper_page');
    }

    /**
     * Get the Acl.
     *
     * @return \Zend\Permissions\Acl\Acl
     */
    public function getAcl()
    {
        return $this->getServiceManager()->get('frontpage_acl');
    }

    /**
     * Get the default resource ID.
     *
     * @return string
     */
    protected function getDefaultResourceId()
    {
        return 'page';
    }
}
