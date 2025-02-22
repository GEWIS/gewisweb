<?php

declare(strict_types=1);

namespace Decision;

use Application\Form\Factory\BaseFormFactory;
use Application\Mapper\Factory\BaseMapperFactory;
use Decision\Controller\FileBrowser\Factory\LocalFileReaderFactory;
use Decision\Controller\FileBrowser\LocalFileReader;
use Decision\Form\Authorization as AuthorizationForm;
use Decision\Form\AuthorizationRevocation as AuthorizationRevocationForm;
use Decision\Form\Document as DocumentForm;
use Decision\Form\Factory\OrganInformationFactory as OrganInformationFormFactory;
use Decision\Form\Minutes as MinutesForm;
use Decision\Form\OrganInformation as OrganInformationForm;
use Decision\Form\SearchDecision as SearchDecisionForm;
use Decision\Mapper\Authorization as AuthorizationMapper;
use Decision\Mapper\Decision as DecisionMapper;
use Decision\Mapper\Meeting as MeetingMapper;
use Decision\Mapper\MeetingDocument as MeetingDocumentMapper;
use Decision\Mapper\MeetingMinutes as MeetingMinutesMapper;
use Decision\Mapper\Member as MemberMapper;
use Decision\Mapper\Organ as OrganMapper;
use Decision\Mapper\SubDecision as SubDecisionMapper;
use Decision\Service\AclService;
use Decision\Service\Decision as DecisionService;
use Decision\Service\Factory\DecisionFactory as DecisionServiceFactory;
use Decision\Service\Factory\GdprFactory as GdprServiceFactory;
use Decision\Service\Factory\MemberFactory as MemberServiceFactory;
use Decision\Service\Factory\MemberInfoFactory as MemberInfoServiceFactory;
use Decision\Service\Factory\OrganFactory as OrganServiceFactory;
use Decision\Service\Gdpr as GdprService;
use Decision\Service\Member as MemberService;
use Decision\Service\MemberInfo as MemberInfoService;
use Decision\Service\Organ as OrganService;
use Doctrine\Laminas\Hydrator\DoctrineObject;
use Psr\Container\ContainerInterface;
use User\Authorization\AclServiceFactory;

class Module
{
    /**
     * Get the configuration for this module.
     *
     * @return array Module configuration
     */
    public function getConfig(): array
    {
        return include __DIR__ . '/../config/module.config.php';
    }

    /**
     * Get service configuration.
     *
     * @return array Service configuration
     */
    public function getServiceConfig(): array
    {
        return [
            'factories' => [
                LocalFileReader::class => LocalFileReaderFactory::class,
                // Services
                AclService::class => AclServiceFactory::class,
                DecisionService::class => DecisionServiceFactory::class,
                GdprService::class => GdprServiceFactory::class,
                MemberInfoService::class => MemberInfoServiceFactory::class,
                MemberService::class => MemberServiceFactory::class,
                OrganService::class => OrganServiceFactory::class,
                // Mappers
                AuthorizationMapper::class => BaseMapperFactory::class,
                DecisionMapper::class => BaseMapperFactory::class,
                MeetingDocumentMapper::class => BaseMapperFactory::class,
                MeetingMapper::class => BaseMapperFactory::class,
                MeetingMinutesMapper::class => BaseMapperFactory::class,
                MemberMapper::class => BaseMapperFactory::class,
                OrganMapper::class => BaseMapperFactory::class,
                SubDecisionMapper::class => BaseMapperFactory::class,
                // Forms
                AuthorizationForm::class => BaseFormFactory::class,
                AuthorizationRevocationForm::class => BaseFormFactory::class,
                DocumentForm::class => BaseFormFactory::class,
                MinutesForm::class => BaseFormFactory::class,
                OrganInformationForm::class => OrganInformationFormFactory::class,
                SearchDecisionForm::class => BaseFormFactory::class,
                'decision_hydrator' => static function (ContainerInterface $container) {
                    return new DoctrineObject(
                        $container->get('doctrine.entitymanager.orm_default'),
                    );
                },
                // Commands
                // N/A
            ],
        ];
    }
}
