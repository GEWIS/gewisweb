<?php

declare(strict_types=1);

namespace Frontpage\Service\Factory;

use Application\Service\Email as EmailService;
use Frontpage\Form\Poll as PollForm;
use Frontpage\Form\PollApproval as PollApprovalForm;
use Frontpage\Mapper\Poll as PollMapper;
use Frontpage\Mapper\PollComment as PollCommentMapper;
use Frontpage\Mapper\PollOption as PollOptionMapper;
use Frontpage\Service\AclService;
use Frontpage\Service\Poll as PollService;
use Laminas\Mvc\I18n\Translator as MvcTranslator;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class PollFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): PollService {
        return new PollService(
            $container->get(AclService::class),
            $container->get(MvcTranslator::class),
            $container->get(EmailService::class),
            $container->get(PollMapper::class),
            $container->get(PollCommentMapper::class),
            $container->get(PollOptionMapper::class),
            $container->get(PollForm::class),
            $container->get(PollApprovalForm::class),
        );
    }
}
