<?php

declare(strict_types=1);

namespace Application;

use Application\Command\LoadFixtures;
use Application\Controller\Factory\IndexControllerFactory;
use Application\Controller\IndexController;
use Application\Extensions\CommonMark\CompanyImage\CompanyImageExtension;
use Application\Extensions\CommonMark\NoImage\NoImageExtension;
use Application\Extensions\CommonMark\VideoIframe\VideoIframeExtension;
use Application\View\Helper\Acl;
use Application\View\Helper\BootstrapElementError;
use Application\View\Helper\Breadcrumbs;
use Application\View\Helper\CompanyIdentity;
use Application\View\Helper\Diff;
use Application\View\Helper\FeaturedCompanyPackage;
use Application\View\Helper\FileUrl;
use Application\View\Helper\GlideUrl;
use Application\View\Helper\HashUrl;
use Application\View\Helper\HighlightSearch;
use Application\View\Helper\HrefLang;
use Application\View\Helper\JobCategories;
use Application\View\Helper\LocalisedTextElement;
use Application\View\Helper\LocaliseText;
use Application\View\Helper\Markdown;
use Application\View\Helper\ModuleIsActive;
use Application\View\Helper\ScriptUrl;
use Application\View\Helper\TimeDiff;
use Company\Service\Company as CompanyService;
use Company\Service\CompanyQuery as CompanyQueryService;
use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use DoctrineModule\Cache\LaminasStorageCache;
use Laminas\Cache\Service\StorageCacheAbstractServiceFactory;
use Laminas\Cache\Storage\Adapter\Memcached;
use Laminas\Cache\Storage\Adapter\MemcachedOptions;
use Laminas\I18n\Translator\Resources;
use Laminas\Mvc\I18n\Translator as MvcTranslator;
use Laminas\Router\Http\Literal;
use Laminas\Session\Config\ConfigInterface;
use Laminas\Session\Service\SessionConfigFactory;
use Laminas\View\Helper\ServerUrl;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\ExternalLink\ExternalLinkExtension;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\MarkdownConverter;
use Psr\Container\ContainerInterface;
use RuntimeException;

return [
    'router' => [
        'routes' => [
            'teapot' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/coffee',
                    'defaults' => [
                        'controller' => IndexController::class,
                        'action' => 'teapot',
                    ],
                ],
                'priority' => 100,
            ],
        ],
    ],
    'service_manager' => [
        'abstract_factories' => [
            StorageCacheAbstractServiceFactory::class,
        ],
        'factories' => [
            ConfigInterface::class => SessionConfigFactory::class,
            'doctrine.cache.my_memcached' => static function () {
                $memcached = new Memcached();
                $options = $memcached->getOptions();

                if (!($options instanceof MemcachedOptions)) {
                    throw new RuntimeException('Unable to retrieve and set options for Memcached');
                }

                $options->setServers([
                    [
                        'host' => 'memcached',
                        'port' => 11211,
                    ],
                ]);
                $options->setNamespace('DoctrineORMModule');

                return new LaminasStorageCache($memcached);
            },
        ],
    ],
    'translator' => [
        'locale' => 'en',
        'translation_file_patterns' => [
            [
                'type' => 'gettext',
                'base_dir' => __DIR__ . '/../language',
                'pattern' => '%s.mo',
            ],
            // Translations for Laminas\Validator.
            [
                'type' => 'phparray',
                'base_dir' => Resources::getBasePath(),
                'pattern' => Resources::getPatternForValidator(),
            ],
        ],
    ],
    'controllers' => [
        'factories' => [
            IndexController::class => IndexControllerFactory::class,
        ],
    ],
    'view_manager' => [
        'display_not_found_reason' => true,
        'display_exceptions' => true,
        'doctype' => 'HTML5',
        'not_found_template' => ('production' === APP_ENV ? 'error/404' : 'error/debug/404'),
        'exception_template' => ('production' === APP_ENV ? 'error/500' : 'error/debug/500'),
        'template_map' => [
            'layout/layout' => __DIR__ . '/../view/layout/layout.phtml',
            'application/index/teapot' => __DIR__ . '/../view/error/418.phtml',
            'error/404' => __DIR__ . '/../view/error/404.phtml',
            'error/403' => __DIR__ . '/../view/error/403.phtml',
            'error/418' => __DIR__ . '/../view/error/418.phtml',
            'error/500' => __DIR__ . '/../view/error/500.phtml',
            'error/debug/404' => __DIR__ . '/../view/error/debug/404.phtml',
            'error/debug/403' => __DIR__ . '/../view/error/debug/403.phtml',
            'error/debug/500' => __DIR__ . '/../view/error/debug/500.phtml',
            'paginator/default' => __DIR__ . '/../view/partial/paginator.phtml',
        ],
        'template_path_stack' => [
            'laminas-developer-tools' => __DIR__ . '/../view',
            0 => __DIR__ . '/../view',
        ],
    ],
    'view_helpers' => [
        'factories' => [
            'acl' => static function (ContainerInterface $container) {
                $helper = new Acl();
                $helper->setServiceLocator($container);

                return $helper;
            },
            'breadcrumbs' => static function () {
                return new Breadcrumbs();
            },
            'bootstrapElementError' => static function () {
                return new BootstrapElementError();
            },
            'companyIdentity' => static function (ContainerInterface $container) {
                return new CompanyIdentity(
                    $container->get('user_auth_companyUser_service'),
                );
            },
            'diff' => static function (ContainerInterface $container) {
                return new Diff($container->get('config')['php-diff']);
            },
            'featuredCompanyPackage' => static function (ContainerInterface $container) {
                return new FeaturedCompanyPackage($container->get(CompanyService::class));
            },
            'fileUrl' => static function (ContainerInterface $container) {
                $helper = new FileUrl();
                $helper->setServiceLocator($container);

                return $helper;
            },
            'glideUrl' => static function (ContainerInterface $container) {
                $helper = new GlideUrl();
                $helper->setUrlBuilder($container->get('glide_url_builder'));

                return $helper;
            },
            'hashUrl' => static function (ContainerInterface $container) {
                $viewHelperManager = $container->get('ViewHelperManager');
                $serverUrlHelper = $viewHelperManager->get(ServerUrl::class);

                return new HashUrl($serverUrlHelper);
            },
            'highlightSearch' => static function () {
                return new HighlightSearch();
            },
            'hrefLang' => static function () {
                return new HrefLang();
            },
            'jobCategories' => static function (ContainerInterface $container) {
                return new JobCategories($container->get(CompanyQueryService::class));
            },
            'localiseText' => static function () {
                return new LocaliseText();
            },
            'localisedTextElement' => static function () {
                return new LocalisedTextElement();
            },
            'markdown' => static function (ContainerInterface $container) {
                $environment = new Environment($container->get('config')['commonmark']);
                $environment->addExtension(new CommonMarkCoreExtension())
                    ->addExtension(new GithubFlavoredMarkdownExtension())
                    ->addExtension(new ExternalLinkExtension());

                // Create separate environment for companies.
                $companyEnvironment = clone $environment;
                $glide = new GlideUrl();
                $glide->setUrlBuilder($container->get('glide_url_builder'));
                $companyEnvironment->addExtension(new CompanyImageExtension($glide))
                    ->addExtension(new VideoIframeExtension());

                // Do not render images in the default environment (activities, news items, etc.).
                $environment->addExtension(new NoImageExtension());

                return new Markdown(
                    $container->get(MvcTranslator::class),
                    new MarkdownConverter($environment),
                    new MarkdownConverter($companyEnvironment),
                );
            },
            'moduleIsActive' => static function (ContainerInterface $container) {
                $helper = new ModuleIsActive();
                $helper->setServiceLocator($container);

                return $helper;
            },
            'scriptUrl' => static function () {
                return new ScriptUrl();
            },
            'timeDiff' => static function (ContainerInterface $container) {
                return new TimeDiff($container->get(MvcTranslator::class));
            },
        ],
    ],
    'view_helper_config' => [
        'flashmessenger' => [
            // phpcs:ignore Generic.Files.LineLength.TooLong -- template string cannot be easily split
            'message_open_format' => '<div%s><button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button><ul><li>',
            'message_close_string' => '</li></ul></div>',
            'message_separator_string' => '</li><li>',
        ],
    ],
    'laminas-cli' => [
        'commands' => [
            'application:fixtures:load' => LoadFixtures::class,
        ],
    ],
    'doctrine' => [
        'driver' => [
            __NAMESPACE__ . '_driver' => [
                'class' => AttributeDriver::class,
                'paths' => [
                    __DIR__ . '/../src/Model/',
                ],
            ],
            'orm_default' => [
                'drivers' => [
                    __NAMESPACE__ . '\Model' => __NAMESPACE__ . '_driver',
                ],
            ],
        ],
    ],
];
