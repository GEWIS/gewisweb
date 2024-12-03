<?php

declare(strict_types=1);

namespace Decision\Service\Factory;

use Application\Service\Email as EmailService;
use Application\Service\FileStorage as FileStorageService;
use Decision\Form\OrganInformation as OrganInformationForm;
use Decision\Mapper\Member as MemberMapper;
use Decision\Mapper\Organ as OrganMapper;
use Decision\Service\AclService;
use Decision\Service\Organ as OrganService;
use Laminas\Mvc\I18n\Translator as MvcTranslator;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class OrganFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): OrganService {
        return new OrganService(
            $container->get(AclService::class),
            $container->get(MvcTranslator::class),
            $container->get('doctrine.entitymanager.orm_default'),
            $container->get(FileStorageService::class),
            $container->get(EmailService::class),
            $container->get(MemberMapper::class),
            $container->get(OrganMapper::class),
            $container->get(OrganInformationForm::class),
            $container->get('config')['organ_information'],
        );
    }
}
