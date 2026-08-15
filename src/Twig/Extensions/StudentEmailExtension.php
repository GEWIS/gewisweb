<?php

declare(strict_types=1);

namespace App\Twig\Extensions;

use App\Entity\User\User;
use Override;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

use function str_ends_with;
use function strtolower;

/**
 * Exposes `show_student_email_notice()`: whether the member reading this page is reachable only at their university
 * address, which stops working the day they graduate.
 *
 * The domain is injected rather than written in, so the check is a plain unit test and the address can be changed
 * without touching code.
 */
class StudentEmailExtension extends AbstractExtension
{
    public function __construct(
        private readonly Security $security,
        #[Autowire(param: 'app.user.student_email_domain')]
        private readonly string $studentEmailDomain,
    ) {
    }

    /**
     * @return TwigFunction[]
     */
    #[Override]
    public function getFunctions(): array
    {
        return [
            new TwigFunction(
                'show_student_email_notice',
                $this->showStudentEmailNotice(...),
            ),
        ];
    }

    public function showStudentEmailNotice(): bool
    {
        $user = $this->security->getUser();

        // A company user has no membership to lose touch over, and a passer-by has nothing to be told.
        if (!$user instanceof User) {
            return false;
        }

        $email = $user->getMember()->getEmail();
        if (null === $email) {
            return false;
        }

        return str_ends_with(
            strtolower($email),
            strtolower($this->studentEmailDomain),
        );
    }
}
