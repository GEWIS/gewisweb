<?php

namespace Frontpage\Service;

use Application\Service\AbstractAclService;
use Application\Service\FileStorage;
use Exception;
use Frontpage\Model\Page as PageModel;
use InvalidArgumentException;
use User\Model\User;
use User\Permissions\NotAllowedException;
use Laminas\Mvc\I18n\Translator;
use Laminas\Permissions\Acl\Acl;
use Laminas\Validator\File\Extension;
use Laminas\Validator\File\IsImage;

/**
 * Page service, used for content management.
 */
class Page extends AbstractAclService
{
    /**
     * @var Translator
     */
    private $translator;

    /**
     * @var User|string
     */
    private $userRole;

    /**
     * @var Acl
     */
    private $acl;

    /**
     * @var FileStorage
     */
    private $storageService;

    /**
     * @var \Frontpage\Mapper\Page
     */
    private $pageMapper;

    /**
     * @var \Frontpage\Form\Page
     */
    private $pageForm;

    /**
     * @var array
     */
    private $storageConfig;

    public function __construct(
        Translator $translator,
        $userRole,
        Acl $acl,
        FileStorage $storageService,
        \Frontpage\Mapper\Page $pageMapper,
        \Frontpage\Form\Page $pageForm,
        array $storageConfig
    ) {
        $this->translator = $translator;
        $this->userRole = $userRole;
        $this->acl = $acl;
        $this->storageService = $storageService;
        $this->pageMapper = $pageMapper;
        $this->pageForm = $pageForm;
        $this->storageConfig = $storageConfig;
    }

    public function getRole()
    {
        return $this->userRole;
    }

    /**
     * Get the translator.
     *
     * @return Translator
     */
    public function getTranslator()
    {
        return $this->translator;
    }

    /**
     * Returns a single page
     *
     * @param string $category
     * @param string $subCategory
     * @param string $name
     * @return PageModel|null
     */
    public function getPage($category, $subCategory, $name)
    {
        $page = $this->pageMapper->findPage($category, $subCategory, $name);
        if (!(is_null($page) || $this->isPageAllowed($page))) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to view this page.')
            );
        }

        return $page;
    }

    /**
     * Returns the parent pages of a page if those exist.
     *
     * @param PageModel $page
     * @return array
     */
    public function getPageParents($page)
    {
        $parents = [];
        if (!is_null($page) && !is_null($page->getSubCategory())) {
            $parents[] = $this->pageMapper->findPage($page->getCategory());
            if (!is_null($page->getName())) {
                $parents[] = $this->pageMapper->findPage($page->getCategory(), $page->getSubCategory());
            }
        }

        return $parents;
    }

    /**
     * Returns a single page by its id
     *
     * @param integer $pageId
     * @return PageModel|null
     */
    public function getPageById($pageId)
    {
        return $this->pageMapper->findPageById($pageId);
    }

    /**
     * Checks if the current user is allowed to view the given page
     *
     * @param PageModel $page
     *
     * @return bool
     */
    public function isPageAllowed($page)
    {
        $requiredRole = $page->getRequiredRole();
        $resource = 'page_' . $page->getId();
        $this->acl->addResource($resource);
        $this->acl->allow($requiredRole, $resource, 'view');

        return $this->isAllowed('view', $resource);
    }

    /**
     * Returns an associative array of all pages in a tree-like structure.
     *
     * @return array
     */
    public function getPages()
    {
        if (!$this->isAllowed('list')) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to view the list of pages.')
            );
        }
        $pages = $this->pageMapper->getAllPages();
        $pageArray = [];
        foreach ($pages as $page) {
            $category = $page->getCategory();
            $subCategory = $page->getSubCategory();
            $name = $page->getName();
            if (is_null($name)) {
                if (is_null($subCategory)) {
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

        $this->pageMapper->persist($page);
        $this->pageMapper->flush();

        return $page;
    }

    /**
     * @param integer $pageId
     * @param array $data form post data
     * @return bool
     */
    public function updatePage($pageId, $data)
    {
        if (!$this->isAllowed('edit')) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to edit pages.')
            );
        }
        $form = $this->getPageForm($pageId);
        $form->setData($data);

        if (!$form->isValid()) {
            return false;
        }

        $this->pageMapper->flush();

        return true;
    }

    /**
     * Removes a page.
     *
     * @param integer $pageId The id of the page to remove.
     */
    public function deletePage($pageId)
    {
        $page = $this->getPageById($pageId);
        $this->pageMapper->remove($page);
        $this->pageMapper->flush();
    }

    /**
     * Upload an image to be displayed on a page.
     *
     * @param array $files
     *
     * @return array
     * @throws Exception
     */
    public function uploadImage($files)
    {
        $imageValidator = new IsImage(
            ['magicFile' => false]
        );

        $extensionValidator = new Extension(
            ['JPEG', 'JPG', 'JFIF', 'TIFF', 'RIF', 'GIF', 'BMP', 'PNG']
        );

        if ($imageValidator->isValid($files['upload']['tmp_name'])) {
            if ($extensionValidator->isValid($files['upload'])) {
                $config = $this->storageConfig;
                $fileName = $this->storageService->storeUploadedFile($files['upload']);
                return $config['public_dir'] . '/' . $fileName;
            }
            throw new InvalidArgumentException(
                $this->translator->translate('The uploaded file does not have a valid extension')
            );
        }
        throw new InvalidArgumentException(
            $this->translator->translate('The uploaded file is not a valid image')
        );
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
        if (!$this->isAllowed('create')) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to create new pages.')
            );
        }
        $form = $this->pageForm;

        if (!is_null($pageId)) {
            $page = $this->getPageById($pageId);
            $form->bind($page);
        }

        return $form;
    }

    /**
     * Get the Acl.
     *
     * @return Acl
     */
    public function getAcl()
    {
        return $this->acl;
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
