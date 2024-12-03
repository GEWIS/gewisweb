<?php

declare(strict_types=1);

namespace User\Service\Factory;

use Decision\Mapper\Member as MemberMapper;
use Laminas\Mvc\I18n\Translator as MvcTranslator;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use User\Form\CompanyUserLogin as CompanyUserLoginForm;
use User\Form\CompanyUserReset as CompanyUserResetForm;
use User\Form\Register as RegisterForm;
use User\Form\UserLogin as UserLoginForm;
use User\Form\UserReset as UserResetForm;
use User\Mapper\CompanyUser as CompanyUserMapper;
use User\Mapper\NewCompanyUser as NewCompanyUserMapper;
use User\Mapper\NewUser as NewUserMapper;
use User\Mapper\User as UserMapper;
use User\Service\AclService;
use User\Service\Email as EmailService;
use User\Service\PwnedPasswords as PwnedPasswordsService;
use User\Service\User as UserService;

class UserFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): UserService {
        return new UserService(
            $container->get(AclService::class),
            $container->get(MvcTranslator::class),
            $container->get('user_bcrypt'),
            $container->get('user_auth_user_service'),
            $container->get('user_auth_companyUser_service'),
            $container->get(EmailService::class),
            $container->get(PwnedPasswordsService::class),
            $container->get(CompanyUserMapper::class),
            $container->get(UserMapper::class),
            $container->get(NewUserMapper::class),
            $container->get(NewCompanyUserMapper::class),
            $container->get(MemberMapper::class),
            $container->get(RegisterForm::class),
            $container->get('user_form_activate_companyUser'),
            $container->get('user_form_activate_user'),
            $container->get(UserLoginForm::class),
            $container->get(CompanyUserLoginForm::class),
            $container->get(CompanyUserResetForm::class),
            $container->get('user_form_password_companyUser'),
            $container->get('user_form_password_user'),
            $container->get(UserResetForm::class),
        );
    }
}
