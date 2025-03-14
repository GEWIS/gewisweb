<?php

declare(strict_types=1);

namespace Photo\Service\Factory;

use Application\Service\FileStorage as FileStorageService;
use Decision\Service\Member as MemberService;
use Decision\Service\Organ as OrganService;
use Laminas\Mvc\I18n\Translator as MvcTranslator;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Photo\Mapper\Photo as PhotoMapper;
use Photo\Mapper\ProfilePhoto as ProfilePhotoMapper;
use Photo\Mapper\Tag as TagMapper;
use Photo\Mapper\Vote as VoteMapper;
use Photo\Mapper\WeeklyPhoto as WeeklyPhotoMapper;
use Photo\Service\AclService;
use Photo\Service\Photo as PhotoService;
use Psr\Container\ContainerInterface;

class PhotoFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): PhotoService {
        return new PhotoService(
            $container->get(AclService::class),
            $container->get(MvcTranslator::class),
            $container->get(MemberService::class),
            $container->get(OrganService::class),
            $container->get(FileStorageService::class),
            $container->get(PhotoMapper::class),
            $container->get(TagMapper::class),
            $container->get(VoteMapper::class),
            $container->get(WeeklyPhotoMapper::class),
            $container->get(ProfilePhotoMapper::class),
            $container->get('config')['photo'],
        );
    }
}
