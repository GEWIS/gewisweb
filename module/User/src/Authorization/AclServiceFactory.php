<?php

namespace User\Authorization;

use Activity\Service\AclService as ActivityAclService;
use Company\Service\AclService as CompanyAclService;
use Decision\Service\AclService as DecisionAclService;
use Education\Service\AclService as EducationAclService;
use Frontpage\Service\AclService as FrontpageAclService;
use Psr\Container\ContainerInterface;
use Laminas\Mvc\I18n\Translator as MvcTranslator;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Laminas\ServiceManager\Exception\InvalidArgumentException;
use Photo\Service\AclService as PhotoAclService;
use User\Service\AclService as UserAclService;

class AclServiceFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param array|null $options
     *
     * @return GenericAclService
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): GenericAclService {
        $translator = $container->get(MvcTranslator::class);
        $authService = $container->get('user_auth_service');
        $apiAuthService = $container->get('user_apiauth_service');
        $remoteAddress = $container->get('user_remoteaddress');
        $tueRange = $container->get('config')['tue_range'];

        return match ($requestedName) {
            'activity_service_acl' => new ActivityAclService(
                $translator,
                $authService,
                $apiAuthService,
                $remoteAddress,
                $tueRange,
            ),
            'company_service_acl' => new CompanyAclService(
                $translator,
                $authService,
                $apiAuthService,
                $remoteAddress,
                $tueRange,
            ),
            'decision_service_acl' => new DecisionAclService(
                $translator,
                $authService,
                $apiAuthService,
                $remoteAddress,
                $tueRange,
            ),
            'education_service_acl' => new EducationAclService(
                $translator,
                $authService,
                $apiAuthService,
                $remoteAddress,
                $tueRange,
            ),
            'frontpage_service_acl' => new FrontpageAclService(
                $translator,
                $authService,
                $apiAuthService,
                $remoteAddress,
                $tueRange,
            ),
            'photo_service_acl' => new PhotoAclService(
                $translator,
                $authService,
                $apiAuthService,
                $remoteAddress,
                $tueRange,
            ),
            'user_service_acl' => new UserAclService(
                $translator,
                $authService,
                $apiAuthService,
                $remoteAddress,
                $tueRange,
            ),
            default => throw new InvalidArgumentException(
                sprintf(
                    "The service with name %s could not be found and was therefore not created.",
                    $requestedName,
                )
            ),
        };
    }
}
