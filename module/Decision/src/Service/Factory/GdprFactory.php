<?php

declare(strict_types=1);

namespace Decision\Service\Factory;

use Activity\Mapper\Activity as ActivityMapper;
use Activity\Mapper\Signup as SignupMapper;
use Company\Mapper\Company as CompanyMapper;
use Company\Mapper\Job as JobMapper;
use Decision\Mapper\Authorization as AuthorizationMapper;
use Decision\Mapper\Member as MemberMapper;
use Decision\Mapper\SubDecision as SubDecisionMapper;
use Decision\Service\AclService;
use Decision\Service\Gdpr as GdprService;
use Education\Mapper\CourseDocument as CourseDocumentMapper;
use Frontpage\Mapper\Poll as PollMapper;
use Frontpage\Mapper\PollComment as PollCommentMapper;
use Laminas\Mvc\I18n\Translator as MvcTranslator;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Photo\Mapper\Photo as PhotoMapper;
use Photo\Mapper\ProfilePhoto as ProfilePhotoMapper;
use Photo\Mapper\Tag as TagMapper;
use Photo\Mapper\Vote as VoteMapper;
use Psr\Container\ContainerInterface;
use User\Mapper\ApiAppAuthentication as ApiAppAuthenticationMapper;
use User\Mapper\LoginAttempt as LoginAttemptMapper;
use User\Mapper\User as UserMapper;

class GdprFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): GdprService {
        return new GdprService(
            $container->get(AclService::class),
            $container->get(MvcTranslator::class),
            $container->get(ActivityMapper::class),
            $container->get(ApiAppAuthenticationMapper::class),
            $container->get(AuthorizationMapper::class),
            $container->get(CompanyMapper::class),
            $container->get(CourseDocumentMapper::class),
            $container->get(JobMapper::class),
            $container->get(LoginAttemptMapper::class),
            $container->get(MemberMapper::class),
            $container->get(PollMapper::class),
            $container->get(PollCommentMapper::class),
            $container->get(PhotoMapper::class),
            $container->get(ProfilePhotoMapper::class),
            $container->get(SignupMapper::class),
            $container->get(SubDecisionMapper::class),
            $container->get(TagMapper::class),
            $container->get(UserMapper::class),
            $container->get(VoteMapper::class),
        );
    }
}
