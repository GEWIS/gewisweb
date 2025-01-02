<?php

declare(strict_types=1);

namespace Frontpage\Service;

use Application\Model\Enums\Languages;
use Application\Service\FileStorage;
use Doctrine\ORM\Exception\ORMException;
use Frontpage\Form\Page as PageForm;
use Frontpage\Mapper\Page as PageMapper;
use Frontpage\Model\FrontpageLocalisedText;
use Frontpage\Model\Page as PageModel;
use InvalidArgumentException;
use Laminas\Mvc\I18n\Translator;
use Laminas\Stdlib\Parameters;
use Laminas\Validator\File\Extension;
use Laminas\Validator\File\IsImage;
use User\Model\Enums\UserRoles;
use User\Permissions\NotAllowedException;

/**
 * Page service, used for content management.
 */
class Page
{
    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
     */
    public function __construct(
        private readonly AclService $aclService,
        private readonly Translator $translator,
        private readonly FileStorage $storageService,
        private readonly PageMapper $pageMapper,
        private readonly PageForm $pageForm,
        private readonly array $storageConfig,
    ) {
    }

    /**
     * Get the translator.
     */
    public function getTranslator(): Translator
    {
        return $this->translator;
    }

    /**
     * Returns a single page.
     */
    public function getPage(
        Languages $language,
        string $category,
        ?string $subCategory = null,
        ?string $name = null,
    ): ?PageModel {
        $page = $this->pageMapper->findPage($language, $category, $subCategory, $name);

        if (null !== $page && !$this->aclService->isAllowed('view', $page)) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to view this page'));
        }

        return $page;
    }

    /**
     * Returns the parent pages of a page if those exist.
     *
     * @return array<array-key, PageModel|null>
     */
    public function getPageParents(
        PageModel $page,
        Languages $language,
    ): array {
        $parents = [];

        if (null !== $page->getSubCategory()->getExactText($language)) {
            $parents[] = $this->pageMapper->findPage(
                $language,
                $page->getCategory()->getExactText($language),
            );

            if (null !== $page->getName()->getExactText($language)) {
                $parents[] = $this->pageMapper->findPage(
                    $language,
                    $page->getCategory()->getExactText($language),
                    $page->getSubCategory()->getExactText($language),
                );
            }
        }

        return $parents;
    }

    /**
     * Returns a single page by its id.
     */
    public function getPageById(int $pageId): ?PageModel
    {
        return $this->pageMapper->find($pageId);
    }

    /**
     * Returns an associative array of all pages in a tree-like structure.
     *
     * @return array<string, array{
     *     page?: PageModel,
     *     children?: array<string, array{
     *         page?: PageModel,
     *         children?: array<string, array{
     *             page: PageModel,
     *         }>,
     *     }>,
     * }>
     */
    public function getPages(): array
    {
        if (!$this->aclService->isAllowed('list', 'page')) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to view the list of pages.'),
            );
        }

        $pages = $this->pageMapper->findAll();

        $pageArray = [];
        foreach ($pages as $page) {
            $category = $page->getCategory()->getText();
            $subCategory = $page->getSubCategory()->getText();
            $name = $page->getName()->getText();

            if (null === $name) {
                if (null === $subCategory) {
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
     * @param Parameters $data form post data
     *
     * @throws ORMException
     */
    public function createPage(Parameters $data): bool
    {
        // TODO: Move form checks to the controller.
        $form = $this->getPageForm();
        $form->setData($data);

        if (!$form->isValid()) {
            return false;
        }

        $page = new PageModel();
        $page = $this->savePageData($page, $form->getData());

        $this->pageMapper->persist($page);
        $this->pageMapper->flush();

        return true;
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
     */
    public function savePageData(
        PageModel $page,
        array $data,
    ): PageModel {
        $page->setCategory(new FrontpageLocalisedText($data['categoryEn'], $data['category']));
        $page->setSubCategory(new FrontpageLocalisedText($data['subCategoryEn'], $data['subCategory']));
        $page->setName(new FrontpageLocalisedText($data['nameEn'], $data['name']));

        $page->setTitle(new FrontpageLocalisedText($data['titleEn'], $data['title']));
        $page->setContent(new FrontpageLocalisedText($data['contentEn'], $data['content']));

        $page->setRequiredRole(UserRoles::from($data['requiredRole']));

        return $page;
    }

    /**
     * @throws ORMException
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
     */
    public function updatePage(
        PageModel $page,
        array $data,
    ): bool {
        $page = $this->savePageData($page, $data);

        $this->pageMapper->persist($page);
        $this->pageMapper->flush();

        return true;
    }

    /**
     * Removes a page.
     *
     * @param int $pageId the id of the page to remove
     *
     * @throws ORMException
     */
    public function deletePage(int $pageId): void
    {
        $this->pageMapper->remove($this->getPageById($pageId));
    }

    /**
     * Upload an image to be displayed on a page.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
     */
    public function uploadImage(array $files): string
    {
        $imageValidator = new IsImage(
            ['magicFile' => false],
        );

        $extensionValidator = new Extension(
            ['JPEG', 'JPG', 'JFIF', 'TIFF', 'RIF', 'GIF', 'BMP', 'PNG'],
        );

        if ($imageValidator->isValid($files['upload']['tmp_name'])) {
            if ($extensionValidator->isValid($files['upload'])) {
                $config = $this->storageConfig;
                $fileName = $this->storageService->storeUploadedFile($files['upload']);

                return $config['public_dir'] . '/' . $fileName;
            }

            throw new InvalidArgumentException(
                $this->translator->translate('The uploaded file does not have a valid extension'),
            );
        }

        throw new InvalidArgumentException(
            $this->translator->translate('The uploaded file is not a valid image'),
        );
    }

    /**
     * Get the Page form.
     */
    public function getPageForm(): PageForm
    {
        if (!$this->aclService->isAllowed('create', 'page')) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to create new pages.'),
            );
        }

        return $this->pageForm;
    }
}
