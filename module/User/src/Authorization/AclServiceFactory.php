<?php

declare(strict_types=1);

namespace User\Authorization;

use Activity\Service\AclService as ActivityAclService;
use Company\Service\AclService as CompanyAclService;
use Decision\Service\AclService as DecisionAclService;
use Education\Service\AclService as EducationAclService;
use Frontpage\Service\AclService as FrontpageAclService;
use Laminas\Mvc\I18n\Translator as MvcTranslator;
use Laminas\ServiceManager\Exception\InvalidArgumentException;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Photo\Service\AclService as PhotoAclService;
use Psr\Container\ContainerInterface;
use User\Authentication\Adapter\CompanyUserAdapter;
use User\Authentication\Adapter\UserAdapter;
use User\Authentication\ApiAuthenticationService;
use User\Authentication\AuthenticationService as CompanyUserAuthenticationService;
use User\Authentication\AuthenticationService as UserAuthenticationService;
use User\Authentication\Storage\CompanyUserSession;
use User\Authentication\Storage\UserSession;
use User\Service\AclService as UserAclService;

use function sprintf;

class AclServiceFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): GenericAclService {
        $translator = $container->get(MvcTranslator::class);
        /** @var UserAuthenticationService<UserSession, UserAdapter> $userAuthService */
        $userAuthService = $container->get('user_auth_user_service');
        /** @var CompanyUserAuthenticationService<CompanyUserSession, CompanyUserAdapter> $companyUserAuthService */
        $companyUserAuthService = $container->get('user_auth_companyUser_service');
        $apiUserAuthService = $container->get(ApiAuthenticationService::class);
        /** @var string[] $tueRanges */
        $tueRanges = $container->get('config')['tue_ranges'];
        /** @var string $remoteAddress */
        $remoteAddress = $container->get('user_remoteaddress');

        return match ($requestedName) {
            ActivityAclService::class => new ActivityAclService(
                $translator,
                $userAuthService,
                $companyUserAuthService,
                $apiUserAuthService,
                $tueRanges,
                $remoteAddress,
            ),
            CompanyAclService::class => new CompanyAclService(
                $translator,
                $userAuthService,
                $companyUserAuthService,
                $apiUserAuthService,
                $tueRanges,
                $remoteAddress,
            ),
            DecisionAclService::class => new DecisionAclService(
                $translator,
                $userAuthService,
                $companyUserAuthService,
                $apiUserAuthService,
                $tueRanges,
                $remoteAddress,
            ),
            EducationAclService::class => new EducationAclService(
                $translator,
                $userAuthService,
                $companyUserAuthService,
                $apiUserAuthService,
                $tueRanges,
                $remoteAddress,
            ),
            FrontpageAclService::class => new FrontpageAclService(
                $translator,
                $userAuthService,
                $companyUserAuthService,
                $apiUserAuthService,
                $tueRanges,
                $remoteAddress,
            ),
            PhotoAclService::class => new PhotoAclService(
                $translator,
                $userAuthService,
                $companyUserAuthService,
                $apiUserAuthService,
                $tueRanges,
                $remoteAddress,
            ),
            UserAclService::class => new UserAclService(
                $translator,
                $userAuthService,
                $companyUserAuthService,
                $apiUserAuthService,
                $tueRanges,
                $remoteAddress,
            ),
            default => throw new InvalidArgumentException(
                sprintf(
                    'The service with name %s could not be found and was therefore not created.',
                    $requestedName,
                ),
            ),
        };
    }
}
