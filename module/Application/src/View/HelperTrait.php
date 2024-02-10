<?php

declare(strict_types=1);

namespace Application\View;

use Application\Model\LocalisedText as LocalisedTextModel;
use Application\View\Helper\Acl;
use Application\View\Helper\Breadcrumbs;
use Application\View\Helper\Diff;
use Application\View\Helper\GlideUrl;
use Application\View\Helper\HrefLang;
use Application\View\Helper\ScriptUrl;
use Company\Model\CompanyFeaturedPackage as CompanyFeaturedPackageModel;
use Company\Model\JobCategory as JobCategoryModel;
use Laminas\Form\ElementInterface;
use Laminas\Form\View\HelperTrait as FormHelperTrait;
use Laminas\I18n\View\HelperTrait as I18nHelperTrait;
use Laminas\Mvc\Plugin\FlashMessenger\View\HelperTrait as FlashMessengerHelperTrait;
use User\Model\CompanyUser as CompanyUserModel;

/**
 * Helper trait for auto-completion of code in modern IDEs.
 *
 * The trait provides convenience methods for view helpers, defined in the application module. It is designed to be used
 * for type-hinting $this variable inside laminas-view templates via doc blocks.
 *
 * Other traits from laminas are already chained into this trait. This includes support for the FlashMessenger, Form,
 * and i18n view helpers.
 *
 * @method Acl acl(string $factory)
 * @method string bootstrapElementError(ElementInterface $element)
 * @method Breadcrumbs breadcrumbs()
 * @method Breadcrumbs addBreadcrumb(string $breadcrumb = '', bool $active = true, string $url = '', string|null $setType = null)
 * @method CompanyUserModel|null companyIdentity()
 * @method string diff(string|null $old, string|null $new, string $renderer = Diff::DIFF_RENDER_COMBINED, array $rendererOverwrites = [], array $differOverwrites = [])
 * @method CompanyFeaturedPackageModel|null featuredCompanyPackage()
 * @method string fileUrl(string $path)
 * @method GlideUrl glideUrl()
 * @method JobCategoryModel[] jobCategories()
 * @method string localisedTextElement(ElementInterface $element)
 * @method string localiseText(LocalisedTextModel $localisedText)
 * @method string markdown(string $text, bool $company = false)
 * @method bool isModuleActive(array $conditions)
 * @method HrefLang hrefLang()
 * @method ScriptUrl scriptUrl()
 * @method string truncate(string $text, int $length = 100, array $options = [])
 */
trait HelperTrait
{
    use FlashMessengerHelperTrait;
    use FormHelperTrait;
    use I18nHelperTrait;
}
