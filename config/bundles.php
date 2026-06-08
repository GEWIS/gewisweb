<?php

declare(strict_types=1);

use Ambta\DoctrineEncryptBundle\AmbtaDoctrineEncryptBundle;
use ApiPlatform\Symfony\Bundle\ApiPlatformBundle;
use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Doctrine\Bundle\FixturesBundle\DoctrineFixturesBundle;
use Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle;
use Endroid\QrCodeBundle\EndroidQrCodeBundle;
use IgorPhp\IgorBundle\IgorPhpBundle;
use Nelmio\CorsBundle\NelmioCorsBundle;
use Nelmio\SecurityBundle\NelmioSecurityBundle;
use Scheb\TwoFactorBundle\SchebTwoFactorBundle;
use Sensiolabs\TypeScriptBundle\SensiolabsTypeScriptBundle;
use Symfony\Bundle\DebugBundle\DebugBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\MakerBundle\MakerBundle;
use Symfony\Bundle\MercureBundle\MercureBundle;
use Symfony\Bundle\MonologBundle\MonologBundle;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Bundle\WebProfilerBundle\WebProfilerBundle;
use Symfony\UX\CalendarLink\UXCalendarLinkBundle;
use Symfony\UX\LiveComponent\LiveComponentBundle;
use Symfony\UX\StimulusBundle\StimulusBundle;
use Symfony\UX\TwigComponent\TwigComponentBundle;
use Symfonycasts\SassBundle\SymfonycastsSassBundle;
use Tito10047\AltchaBundle\AltchaBundle;
use Twig\Extra\TwigExtraBundle\TwigExtraBundle;

return [
    FrameworkBundle::class => ['all' => true],
    DoctrineBundle::class => ['all' => true],
    DoctrineMigrationsBundle::class => ['all' => true],
    DebugBundle::class => ['dev' => true],
    TwigBundle::class => ['all' => true],
    WebProfilerBundle::class => [
        'dev' => true,
        'test' => true,
    ],
    TwigExtraBundle::class => ['all' => true],
    SecurityBundle::class => ['all' => true],
    MonologBundle::class => ['all' => true],
    MakerBundle::class => ['dev' => true],
    SymfonycastsSassBundle::class => ['all' => true],
    NelmioCorsBundle::class => ['all' => true],
    ApiPlatformBundle::class => ['all' => true],
    DoctrineFixturesBundle::class => [
        'dev' => true,
        'test' => true,
    ],
    MercureBundle::class => ['all' => true],
    NelmioSecurityBundle::class => ['all' => true],
    StimulusBundle::class => ['all' => true],
    TwigComponentBundle::class => ['all' => true],
    LiveComponentBundle::class => ['all' => true],
    SensiolabsTypeScriptBundle::class => ['all' => true],
    IgorPhpBundle::class => [
        'dev' => true,
        'test' => true,
    ],
    SchebTwoFactorBundle::class => ['all' => true],
    EndroidQrCodeBundle::class => ['all' => true],
    AmbtaDoctrineEncryptBundle::class => ['all' => true],
    UXCalendarLinkBundle::class => ['all' => true],
    AltchaBundle::class => ['all' => true],
];
