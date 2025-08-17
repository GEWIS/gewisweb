<?php

declare(strict_types=1);

namespace User\Form\Factory;

use Laminas\Mvc\I18n\Translator as MvcTranslator;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Override;
use Psr\Container\ContainerInterface;
use User\Form\UserLogin as UserLoginForm;

class UserLoginFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    #[Override]
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): UserLoginForm {
        return new UserLoginForm(
            $container->get(MvcTranslator::class),
            $container->get('config')['passwords']['min_length_user'],
        );
    }
}
