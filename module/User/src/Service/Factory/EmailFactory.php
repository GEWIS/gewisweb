<?php

declare(strict_types=1);

namespace User\Service\Factory;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Override;
use Psr\Container\ContainerInterface;
use User\Service\Email as EmailService;

class EmailFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    #[Override]
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): EmailService {
        return new EmailService(
            $container->get('ViewRenderer'),
            $container->get('user_mail_transport'),
            $container->get('config')['email'],
        );
    }
}
