<?php

namespace Frontpage\Service;

use Application\Service\AbstractAclService;

/**
 * Page service, used for content management.
 */
class Page extends AbstractAclService
{

    /**
     * Returns a single page
     * @param string $category
     * @param string $subCategory
     * @param string $name
     * @return \Frontpage\Model\Page|null
     */
    public function getPage($category, $subCategory, $name)
    {
        $page = $this->getPageMapper()->findPage($category, $subCategory, $name);
        return $page;
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
