<?php

declare(strict_types=1);

namespace App\Twig\Components\Activity\Admin;

use App\Entity\Decision\Organ;
use App\Entity\User\Enums\UserRoles;
use App\Repository\Decision\OrganRepository;
use App\Service\Activity\CalendarMonthBuilder;
use App\ViewModel\Activity\Calendar\CalendarMonth;
use DateTimeImmutable;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

use function preg_match;

/**
 * The month grid: what is already in the agenda and what bodies are asking for, side by side.
 *
 * Server-rendered rather than handed to a calendar library. The grid is a table of days that changes when the month
 * changes, which is what a live component is for, and it keeps working without JavaScript, inside the site's strict
 * content-security policy, and without carrying a dependency for one screen.
 *
 * Moving through the months is in the address, so a link to a particular month is a link somebody can send.
 */
#[AsLiveComponent(
    name: 'Activity:Admin:OptionCalendar',
    template: 'components/Activity/Admin/OptionCalendar.html.twig',
)]
#[IsGranted(new Expression(
    'is_granted("' . UserRoles::ActiveMember->value . '") or is_granted("' . UserRoles::Board->value . '")',
))]
final class OptionCalendar
{
    use DefaultActionTrait;

    /**
     * The month on screen as `YYYY-MM`. In the address, so a month can be linked to.
     */
    #[LiveProp(
        writable: true,
        url: true,
    )]
    public string $month = '';

    /**
     * Narrow the grid to one body, which is how a body checks its own claims against everybody else's.
     */
    #[LiveProp(
        writable: true,
        url: true,
    )]
    public ?int $organ = null;

    public function __construct(
        private readonly CalendarMonthBuilder $calendarMonthBuilder,
        private readonly OrganRepository $organRepository,
        private readonly Security $security,
    ) {
    }

    /**
     * Deliberately not `getMonth()`: a live component reads a getter of that name as the type of the `$month` prop,
     * which is the string in the address, not the grid it stands for.
     */
    public function getGrid(): CalendarMonth
    {
        return $this->calendarMonthBuilder->build(
            $this->currentMonth(),
            $this->selectedOrgan(),
        );
    }

    public function getSelectedOrgan(): ?Organ
    {
        return $this->selectedOrgan();
    }

    /**
     * @return Organ[]
     */
    public function getOrgans(): array
    {
        return $this->organRepository->findActive();
    }

    #[LiveAction]
    public function previousMonth(): void
    {
        $this->assertAccess();
        $this->month = $this->currentMonth()->modify('-1 month')->format('Y-m');
    }

    #[LiveAction]
    public function nextMonth(): void
    {
        $this->assertAccess();
        $this->month = $this->currentMonth()->modify('+1 month')->format('Y-m');
    }

    #[LiveAction]
    public function today(): void
    {
        $this->assertAccess();
        $this->month = new DateTimeImmutable('today')->format('Y-m');
    }

    /**
     * A live action arrives over its own request, which the class-level attribute does not cover, so every one of them
     * asks again.
     */
    private function assertAccess(): void
    {
        if (
            $this->security->isGranted(UserRoles::ActiveMember->value)
            || $this->security->isGranted(UserRoles::Board->value)
        ) {
            return;
        }

        throw new AccessDeniedException('You are not allowed to look at the option calendar.');
    }

    /**
     * The month on screen, falling back to this one. The prop is writable and in the address, so anything at all can
     * arrive in it.
     */
    private function currentMonth(): DateTimeImmutable
    {
        if (
            1 !== preg_match(
                '/^\d{4}-\d{2}$/',
                $this->month,
            )
        ) {
            return new DateTimeImmutable('first day of this month');
        }

        $parsed = DateTimeImmutable::createFromFormat(
            'Y-m-d',
            $this->month . '-01',
        );

        return false === $parsed
            ? new DateTimeImmutable('first day of this month')
            : $parsed;
    }

    private function selectedOrgan(): ?Organ
    {
        if (null === $this->organ) {
            return null;
        }

        return $this->organRepository->find($this->organ);
    }
}
