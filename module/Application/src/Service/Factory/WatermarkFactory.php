<?php

declare(strict_types=1);

namespace Application\Service\Factory;

use Application\Service\Watermark;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Override;
use Psr\Container\ContainerInterface;
use User\Authentication\Adapter\UserAdapter;
use User\Authentication\AuthenticationService;
use User\Authentication\Storage\UserSession;

class WatermarkFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    #[Override]
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): Watermark {
        /** @var AuthenticationService<UserSession, UserAdapter> $authService */
        $authService = $container->get('user_auth_user_service');

        return new Watermark(
            $authService,
            $container->get('user_remoteaddress'),
            $container->get('config')['watermark'],
        );
    }
}
