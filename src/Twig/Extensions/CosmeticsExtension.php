<?php

declare(strict_types=1);

namespace App\Twig\Extensions;

use App\Entity\User\User;
use Override;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Exposes `cosmetics_disabled()` to templates: true only when the current user is a member who has turned the festive
 * cosmetics off. Anonymous visitors and company users have no such setting, so they always keep cosmetics on.
 * Centralising the check keeps `base.html.twig` from having to guard the user type inline.
 */
class CosmeticsExtension extends AbstractExtension
{
    public function __construct(private readonly TokenStorageInterface $tokenStorage)
    {
    }

    /**
     * @return TwigFunction[]
     */
    #[Override]
    public function getFunctions(): array
    {
        return [
            new TwigFunction(
                'cosmetics_disabled',
                $this->cosmeticsDisabled(...),
            ),
        ];
    }

    public function cosmeticsDisabled(): bool
    {
        $user = $this->tokenStorage->getToken()?->getUser();

        return $user instanceof User
            && $user->hasDisabledCosmetics();
    }
}
