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
use User\Authentication\{
    ApiAuthenticationService,
    AuthenticationService as UserAuthenticationService,
};
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
        /** @var MvcTranslator $translator */
        $translator = $container->get(MvcTranslator::class);
        /** @var UserAuthenticationService $authService */
        $authService = $container->get('user_auth_service');
        /** @var ApiAuthenticationService $apiAuthService */
        $apiAuthService = $container->get('user_apiauth_service');
        /** @var array<array-key, string> $tueRanges */
        $tueRanges = $container->get('config')['tue_ranges'];
        /** @var string $remoteAddress */
        $remoteAddress = $container->get('user_remoteaddress');

        return match ($requestedName) {
            'activity_service_acl' => new ActivityAclService(
                $translator,
                $authService,
                $apiAuthService,
                $tueRanges,
                $remoteAddress,
            ),
            'company_service_acl' => new CompanyAclService(
                $translator,
                $authService,
                $apiAuthService,
                $tueRanges,
                $remoteAddress,
            ),
            'decision_service_acl' => new DecisionAclService(
                $translator,
                $authService,
                $apiAuthService,
                $tueRanges,
                $remoteAddress,
            ),
            'education_service_acl' => new EducationAclService(
                $translator,
                $authService,
                $apiAuthService,
                $tueRanges,
                $remoteAddress,
            ),
            'frontpage_service_acl' => new FrontpageAclService(
                $translator,
                $authService,
                $apiAuthService,
                $tueRanges,
                $remoteAddress,
            ),
            'photo_service_acl' => new PhotoAclService(
                $translator,
                $authService,
                $apiAuthService,
                $tueRanges,
                $remoteAddress,
            ),
            'user_service_acl' => new UserAclService(
                $translator,
                $authService,
                $apiAuthService,
                $tueRanges,
                $remoteAddress,
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
