<?php

declare(strict_types=1);

namespace Decision\Service\Factory;

use Application\Service\Email as EmailService;
use Application\Service\FileStorage as FileStorageService;
use Decision\Form\Authorization as AuthorizationForm;
use Decision\Form\AuthorizationRevocation as AuthorizationRevocationForm;
use Decision\Form\Document as DocumentForm;
use Decision\Form\Minutes as MinutesForm;
use Decision\Form\SearchDecision as SearchDecisionForm;
use Decision\Mapper\Authorization as AuthorizationMapper;
use Decision\Mapper\Decision as DecisionMapper;
use Decision\Mapper\Meeting as MeetingMapper;
use Decision\Mapper\MeetingDocument as MeetingDocumentMapper;
use Decision\Mapper\MeetingMinutes as MeetingMinutesMapper;
use Decision\Mapper\Member as MemberMapper;
use Decision\Service\AclService;
use Decision\Service\Decision as DecisionService;
use Laminas\Mvc\I18n\Translator as MvcTranslator;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Override;
use Psr\Container\ContainerInterface;

class DecisionFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    #[Override]
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): DecisionService {
        return new DecisionService(
            $container->get(AclService::class),
            $container->get(MvcTranslator::class),
            $container->get(FileStorageService::class),
            $container->get(EmailService::class),
            $container->get(MemberMapper::class),
            $container->get(MeetingMapper::class),
            $container->get(MeetingDocumentMapper::class),
            $container->get(MeetingMinutesMapper::class),
            $container->get(DecisionMapper::class),
            $container->get(AuthorizationMapper::class),
            $container->get(MinutesForm::class),
            $container->get(DocumentForm::class),
            $container->get(SearchDecisionForm::class),
            $container->get(AuthorizationForm::class),
            $container->get(AuthorizationRevocationForm::class),
        );
    }
}
