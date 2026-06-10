<?php

declare(strict_types=1);

namespace App\Twig\Components\Activity\Admin;

use App\Entity\Activity\Activity;
use App\Entity\Activity\Enums\AllocationMethod;
use App\Entity\Activity\Enums\RecipientScope;
use App\Entity\Activity\ExternalSignup;
use App\Entity\Activity\Signup;
use App\Entity\Activity\SignupList;
use App\Entity\Activity\UserSignup;
use App\Entity\Application\Enums\AlertTypes;
use App\Entity\Application\Enums\Languages;
use App\Entity\Decision\Member;
use App\Entity\User\Enums\UserRoles;
use App\Entity\User\User;
use App\Message\Activity\OrganiserAnnouncementEmail;
use App\Repository\Activity\ExternalSignupVerificationRepository;
use App\Security\Application\RevisionVoter;
use App\Service\Activity\SignupAdminWindow;
use App\ViewModel\Activity\Admin\SignupAdminListView;
use DateTime;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Random\Randomizer;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

use function array_map;
use function assert;
use function count;
use function in_array;
use function trim;

/**
 * The sign-ups table(s) for one activity: a panel per live sign-up list showing every subscriber with their
 * answers, membership type and attendance, plus a per-row present toggle, row selection, a quick filter and a bulk
 * email composer.
 *
 * Unlike the other (read-only) live components this one writes to the database (attendance marking) and dispatches
 * messages (bulk email), so it re-asserts access on every action: a live request is independent of the gated page that
 * embedded the component, and the activity prop is rehydrated from a client-supplied id.
 *
 * Feedback ($feedback/$feedbackType) is component-local rather than a session flash: a live action re-renders only this
 * component, not the layout that shows flashes, so the message is rendered inside the component and is transient (reset
 * on the next interaction).
 */
#[AsLiveComponent(
    name: 'Activity:Admin:SignupOverview',
    template: 'components/Activity/Admin/SignupOverview.html.twig',
)]
#[IsGranted(new Expression("is_granted('ROLE_ACTIVE_MEMBER') or is_granted('ROLE_BOARD')"))]
final class SignupOverview
{
    use DefaultActionTrait;

    #[LiveProp]
    public Activity $activity;

    /**
     * The ticked signup ids, held as one flat set. Signup ids are globally unique, so this maps back to lists
     * unambiguously and every operation on it -- the per-list "Selected" count, the email recipients, selectAll() and
     * clearSelection() -- is scoped to a single sign-up list; there is no cross-list selection. Checkbox hydration
     * delivers the ids as strings while selectAll() pushes ints, so the type is mixed; read them normalised via
     * {@see self::selectedIds()}.
     *
     * @var list<int|string>
     */
    #[LiveProp(writable: true)]
    public array $selected = [];

    /**
     * Ids of the sign-up fields whose column the organiser has hidden, as one flat set. Field ids are globally
     * unique, so -- like {@see self::$selected} -- this is effectively per-list (a hidden id only matches the one
     * list it belongs to). Toggled via {@see self::toggleFieldColumn()}; read normalised via
     * {@see self::hiddenFieldIds()}.
     *
     * @var list<int|string>
     */
    #[LiveProp(writable: true)]
    public array $hiddenFields = [];

    #[LiveProp(writable: true)]
    public string $filter = '';

    // Which sign-up list's email composer is open (null = none). Keeps the composer scoped to a single list so the
    // recipients and the selection stay coherent on multi-list activities.
    #[LiveProp(writable: true)]
    public ?int $composingListId = null;

    // Held as the backing string and converted to a RecipientScope; avoids relying on enum-prop hydration.
    #[LiveProp(writable: true)]
    public string $scope = RecipientScope::All->value;

    #[LiveProp(writable: true)]
    public string $emailSubject = '';

    #[LiveProp(writable: true)]
    public string $emailBody = '';

    // Which sign-up list is in attendance mode (null = none). Attendance mode replaces the table with a focused,
    // mobile-first search list of large tap targets, because presence is marked on a phone at the door.
    #[LiveProp(writable: true)]
    public ?int $attendanceListId = null;

    // Transient, set by an action and rendered once in this component's own markup.
    public ?string $feedback = null;
    public string $feedbackType = '';

    /** @var SignupAdminListView[]|null memoised for the duration of one render */
    private ?array $listViews = null;

    /** @var array<int, int[]> pending external-signup ids keyed by list id, memoised for one request */
    private array $pendingExternalIds = [];

    public function __construct(
        private readonly Security $security,
        private readonly TranslatorInterface $translator,
        private readonly EntityManagerInterface $entityManager,
        private readonly MessageBusInterface $messageBus,
        private readonly string $internalAffairsEmail,
        private readonly ExternalSignupVerificationRepository $verificationRepository,
    ) {
    }

    /**
     * @return SignupAdminListView[]
     */
    public function getListViews(): array
    {
        $this->assertAccess();

        if (null !== $this->listViews) {
            return $this->listViews;
        }

        $this->primeSignups();

        $views = [];
        $selected = $this->selectedIds();
        $hiddenFields = $this->hiddenFieldIds();
        foreach ($this->activity->getLiveSignupLists() as $signupList) {
            $views[] = SignupAdminListView::fromSignupList(
                $signupList,
                $this->translator,
                $this->filter,
                $selected,
                $hiddenFields,
                $this->pendingExternalIds($signupList),
            );
        }

        return $this->listViews = $views;
    }

    /**
     * Eagerly load the sign-ups and the associations each row reads -- the member behind a user sign-up (its type and
     * generation are columns that ride along) and every answer's field and option -- for this activity's live lists in
     * a fixed number of queries. Without this, building the table lazy-loads the member and the field values per row:
     * an N+1 that grows with the number of sign-ups.
     */
    private function primeSignups(): void
    {
        $lists = $this->activity->getLiveSignupLists()->toArray();
        if ([] === $lists) {
            return;
        }

        // Each list's sign-ups in one query, instead of one lazy load per list.
        $this->entityManager
            ->createQuery(
                'SELECT sl, s FROM ' . SignupList::class . ' sl LEFT JOIN sl.signUps s WHERE sl IN (:lists)',
            )
            ->setParameter(
                'lists',
                $lists,
            )
            ->getResult();

        // The member behind every user sign-up in one query.
        $this->entityManager
            ->createQuery(
                'SELECT us, u FROM ' . UserSignup::class . ' us JOIN us.user u WHERE us.signupList IN (:lists)',
            )
            ->setParameter(
                'lists',
                $lists,
            )
            ->getResult();

        // Every answer with its field and option in one query, so displayValueForField() never lazy-loads per row.
        $this->entityManager
            ->createQuery(
                'SELECT s, fv, f, o FROM ' . Signup::class . ' s'
                . ' LEFT JOIN s.fieldValues fv LEFT JOIN fv.field f LEFT JOIN fv.option o'
                . ' WHERE s.signupList IN (:lists)',
            )
            ->setParameter(
                'lists',
                $lists,
            )
            ->getResult();
    }

    /**
     * Whether attendance may be marked right now (30 minutes before the activity until a day after it ends).
     */
    public function canMarkPresence(): bool
    {
        return SignupAdminWindow::canMarkPresence(
            $this->activity->getBeginTime(),
            $this->activity->getEndTime(),
        );
    }

    /**
     * Whether the current user is on the board (the only role allowed to perform a draw).
     */
    public function isBoard(): bool
    {
        return $this->security->isGranted(UserRoles::Board->value);
    }

    /**
     * Whether admission (the draw and manual admit/un-admit) may still be changed. Open until a day after the activity
     * ends -- the same upper bound as attendance -- so a draw forgotten before the activity can still be run at the
     * door (otherwise a never-drawn limited list would strand: no admission and, since presence needs admission, no
     * attendance either).
     */
    public function admissionOpen(): bool
    {
        return SignupAdminWindow::canChangeAdmission($this->activity->getEndTime());
    }

    /**
     * The recipient scopes offered in the composer. {@see RecipientScope::Admitted}/{@see RecipientScope::Waitlisted}
     * only make sense for a limited-capacity list, where a draw distinguishes admittees from the waiting list.
     *
     * @return RecipientScope[]
     */
    public function getRecipientScopes(bool $limited = false): array
    {
        $scopes = [RecipientScope::All];

        if ($limited) {
            $scopes[] = RecipientScope::Admitted;
            $scopes[] = RecipientScope::Waitlisted;
        }

        $scopes[] = RecipientScope::Selected;
        $scopes[] = RecipientScope::Present;

        return $scopes;
    }

    #[LiveAction]
    public function togglePresent(#[LiveArg]
    int $signupId,): void
    {
        $this->assertAccess();

        $signup = $this->findOwnedSignup($signupId);
        if (
            null === $signup
            || !$this->canMarkPresence()
        ) {
            return;
        }

        // On a limited-capacity list only admittees (drawn) can attend, so presence cannot be set for someone still on
        // the waiting list.
        $list = $signup->getSignupList();
        if (
            $list->getLimitedCapacity()
            && !$signup->isDrawn()
        ) {
            return;
        }

        $signup->setPresent(!$signup->isPresent());

        // The list records that presence has been taken the first time anyone is marked; this drives the public
        // "presence taken" indicator and the review diff, and is never automatically unset.
        if (
            $signup->isPresent()
            && !$list->isPresenceTaken()
        ) {
            $list->setPresenceTaken(true);
        }

        $this->entityManager->flush();
    }

    /**
     * Manually admit/un-admit one sign-up (board or organiser) to backfill after the draw: promote someone from the
     * waiting list or drop a confirmed no-show. Only after the draw has been performed (and locked) and only until the
     * activity starts. Un-admitting also clears attendance -- you cannot have attended without being admitted.
     * Overbooking past capacity is allowed (the template warns).
     */
    #[LiveAction]
    public function toggleAdmission(#[LiveArg]
    int $signupId,): void
    {
        $this->assertAccess();

        $signup = $this->findOwnedSignup($signupId);
        if (null === $signup) {
            return;
        }

        $list = $signup->getSignupList();
        // Manual admission needs an open window and either a locked draw (FCFS/conditional methods) or a manual method
        // (external-party/custom, which never run a draw).
        if (
            !$list->getLimitedCapacity()
            || !$this->admissionOpen()
            || (
                !$list->isDrawLocked()
                && !$list->getAllocationMethod()->isManual()
            )
        ) {
            return;
        }

        $admitted = !$signup->isDrawn();
        $signup->setDrawn($admitted);
        if (!$admitted) {
            $signup->setPresent(false);
        }

        $this->entityManager->flush();
    }

    /**
     * First-come-first-served draw (board only): admit the earliest sign-ups (ordered by id, i.e. creation) up to
     * capacity, waitlist the rest, and lock the draw.
     */
    #[LiveAction]
    public function drawFirstCome(#[LiveArg]
    int $listId,): void
    {
        $this->runDraw(
            $listId,
            AllocationMethod::FirstComeFirstServed,
            false,
        );
    }

    /**
     * Random lottery draw (board only): shuffle the sign-ups, then admit up to capacity and waitlist the rest.
     */
    #[LiveAction]
    public function drawLottery(#[LiveArg]
    int $listId,): void
    {
        $this->runDraw(
            $listId,
            AllocationMethod::ConditionalDraw,
            true,
        );
    }

    /**
     * Shared draw runner (board only): look up the owned list, check the draw may run for the given method, optionally
     * shuffle to randomise the ordering, then admit up to capacity and lock it. Confirmed client-side by a Bootstrap
     * modal (see the `confirm-modal` Stimulus controller); re-checked here because a live action is independent of the
     * page that rendered it.
     */
    private function runDraw(
        int $listId,
        AllocationMethod $method,
        bool $shuffle,
    ): void {
        $this->assertAccess();
        $this->assertBoard();

        $list = $this->findOwnedList($listId);
        if (null === $list) {
            return;
        }

        // Serialise concurrent draws of the same list (a double-click, or two tabs): a pessimistic write lock makes the
        // second draw block until the first commits; refresh() then re-reads the now-locked row so the canDraw()
        // recheck sees the freshly set drawnAt and bails -- a lottery is never re-run and its result never changes.
        $this->entityManager->wrapInTransaction(function () use ($list, $method, $shuffle): void {
            $this->entityManager->lock(
                $list,
                LockMode::PESSIMISTIC_WRITE,
            );
            $this->entityManager->refresh($list);

            if (
                !$this->canDraw(
                    $list,
                    $method,
                )
            ) {
                return;
            }

            // Only confirmed sign-ups take part in the draw: an external guest who has not verified their email is not
            // yet a real participant and must neither be admitted nor take up a capacity slot.
            $signups = $this->confirmedSignups($list);
            if ($shuffle) {
                $signups = new Randomizer()->shuffleArray($signups);
            }

            $this->applyDraw(
                $list,
                $signups,
            );
        });
    }

    /**
     * Whether the given draw may be run on a list now: it is limited with a real capacity, uses that draw method, has
     * not been drawn yet, the sign-up list has closed, and we are within the admission window. The capacity guard is
     * essential: without it a capacity-less limited list would admit zero and lock irreversibly.
     */
    private function canDraw(
        SignupList $list,
        AllocationMethod $method,
    ): bool {
        return $list->getLimitedCapacity()
            && null !== $list->getCapacity()
            && $list->getCapacity() >= 1
            && $list->getAllocationMethod() === $method
            && !$list->isDrawLocked()
            && $list->isClosed()
            && $this->admissionOpen();
    }

    /**
     * The ticked signup ids as ints. The $selected LiveProp is fed checkbox values, which arrive as strings, while
     * selectAll() pushes ints and signup ids are ints; normalising avoids strict-comparison mismatches.
     *
     * @return int[]
     */
    private function selectedIds(): array
    {
        return $this->intList($this->selected);
    }

    /**
     * The hidden field-column ids as ints (the $hiddenFields LiveProp is mixed, like $selected).
     *
     * @return int[]
     */
    private function hiddenFieldIds(): array
    {
        return $this->intList($this->hiddenFields);
    }

    /**
     * Normalise a writable id-list LiveProp to ints: checkbox/JS hydration delivers ids as strings while the live
     * actions push ints, so the stored type is mixed; callers compare against int entity ids.
     *
     * @param list<int|string> $ids
     *
     * @return int[]
     */
    private function intList(array $ids): array
    {
        return array_map(
            static fn (int|string $id): int => (int) $id,
            $ids,
        );
    }

    /**
     * Admit the first capacity of the (pre-ordered) sign-ups, waitlist the rest (clearing their attendance), then
     * lock the draw with an audit stamp. The draw is a one-shot board event and cannot be re-run; later adjustments
     * are manual ({@see self::toggleAdmission()}).
     *
     * @param Signup[] $orderedSignups
     */
    private function applyDraw(
        SignupList $list,
        array $orderedSignups,
    ): void {
        $capacity = $list->getCapacity() ?? 0;
        $position = 0;
        foreach ($orderedSignups as $signup) {
            $admitted = $position < $capacity;
            $signup->setDrawn($admitted);
            if (!$admitted) {
                $signup->setPresent(false);
            }

            ++$position;
        }

        $list->setDrawnAt(new DateTime());
        $list->setDrawnBy($this->currentMember());

        $this->entityManager->flush();
    }

    #[LiveAction]
    public function selectAll(#[LiveArg]
    int $listId,): void
    {
        $this->assertAccess();

        // Select every subscriber on the list, regardless of the quick filter (the button says "Select all"); read the
        // already-selected ids as ints so a mix of checkbox-supplied strings and these ids dedupes correctly.
        $list = $this->findOwnedList($listId);
        if (null === $list) {
            return;
        }

        $selected = $this->selectedIds();
        foreach ($this->confirmedSignups($list) as $signup) {
            $id = $signup->getId();
            if (
                null === $id
                || in_array(
                    $id,
                    $selected,
                    true,
                )
            ) {
                continue;
            }

            $this->selected[] = $id;
            $selected[] = $id;
        }
    }

    #[LiveAction]
    public function clearSelection(#[LiveArg]
    int $listId,): void
    {
        $this->assertAccess();

        // Selection is scoped per list: drop only this list's signup ids and leave any other list's selection intact.
        // Signup ids are globally unique, so the flat $selected set maps back to a single list unambiguously.
        $list = $this->findOwnedList($listId);
        if (null === $list) {
            return;
        }

        $listIds = [];
        foreach ($list->getSignUps() as $signup) {
            $id = $signup->getId();
            if (null === $id) {
                continue;
            }

            $listIds[] = $id;
        }

        $kept = [];
        foreach ($this->selectedIds() as $id) {
            if (
                in_array(
                    $id,
                    $listIds,
                    true,
                )
            ) {
                continue;
            }

            $kept[] = $id;
        }

        $this->selected = $kept;
    }

    /**
     * Show/hide a sign-up field's column. Pure display state -- a field id only matches the one list it belongs to,
     * so the hidden set is effectively per-list. A field absent from the set is shown (the default).
     */
    #[LiveAction]
    public function toggleFieldColumn(#[LiveArg]
    int $fieldId,): void
    {
        $this->assertAccess();

        $next = [];
        $found = false;
        foreach ($this->hiddenFieldIds() as $id) {
            if ($id === $fieldId) {
                $found = true;

                continue;
            }

            $next[] = $id;
        }

        if (!$found) {
            $next[] = $fieldId;
        }

        $this->hiddenFields = $next;
    }

    #[LiveAction]
    public function openComposer(
        #[LiveArg]
        int $listId,
        #[LiveArg]
        ?string $scope = null,
    ): void {
        $this->assertAccess();

        $this->composingListId = $listId;
        $this->scope = (RecipientScope::tryFrom($scope ?? '') ?? RecipientScope::All)->value;
        $this->emailSubject = '';
        $this->emailBody = '';
    }

    #[LiveAction]
    public function closeComposer(): void
    {
        $this->composingListId = null;
    }

    #[LiveAction]
    public function enterAttendance(#[LiveArg]
    int $listId,): void
    {
        $this->assertAccess();

        if (!$this->canMarkPresence()) {
            return;
        }

        $this->attendanceListId = $listId;
        $this->filter = '';
    }

    #[LiveAction]
    public function exitAttendance(): void
    {
        $this->assertAccess();

        $this->attendanceListId = null;
        $this->filter = '';
    }

    #[LiveAction]
    public function sendEmail(#[LiveArg]
    int $listId,): void
    {
        $this->assertAccess();

        $subject = trim($this->emailSubject);
        $body = trim($this->emailBody);
        if (
            '' === $subject
            || '' === $body
        ) {
            $this->setFeedback(
                AlertTypes::Warning,
                $this->translator->trans('Please provide both a subject and a message.'),
            );

            return;
        }

        $signupList = $this->findOwnedList($listId);
        if (null === $signupList) {
            return;
        }

        $scope = RecipientScope::tryFrom($this->scope) ?? RecipientScope::All;

        // Admitted/Waitlisted only distinguish recipients once admission is settled -- a locked draw, or a manual
        // allocation method where admission is set by hand. On any other list every sign-up is not-yet-drawn, so the
        // two scopes would silently resolve to "everyone"/"no one". The composer only offers them in the same
        // circumstances, but $scope is a writable prop, so re-check it here.
        if (
            in_array(
                $scope,
                [
                    RecipientScope::Admitted,
                    RecipientScope::Waitlisted,
                ],
                true,
            )
            && !(
                $signupList->getLimitedCapacity()
                && (
                    $signupList->isDrawLocked()
                    || $signupList->getAllocationMethod()->isManual()
                )
            )
        ) {
            $this->setFeedback(
                AlertTypes::Warning,
                $this->translator->trans('That recipient group is not available for this sign-up list.'),
            );

            return;
        }

        $recipients = $this->recipientsFor(
            $signupList,
            $scope,
        );
        if ([] === $recipients) {
            $this->setFeedback(
                AlertTypes::Warning,
                $this->translator->trans('There are no recipients in the selected group.'),
            );

            return;
        }

        // Fall back to CIB (Internal Affairs) rather than an organiser's personal address when the organ has no public
        // email (or an empty one).
        $organEmail = $this->activity->getOrgan()?->getApprovedOrganInformation()?->getEmail();
        $replyTo = null !== $organEmail && '' !== $organEmail
            ? $organEmail
            : $this->internalAffairsEmail;

        // Always render the activity name in English: the email's boilerplate is English regardless of the composing
        // organiser's locale (see OrganiserAnnouncementEmail), falling back to Dutch only when there is no English
        // name.
        $activityName = $this->activity->getName()->getText(Languages::English) ?? '';

        // One message carrying every recipient: a single, atomic enqueue (never a half-enqueued per-recipient fan-out).
        // The handler sends one email per recipient and tolerates an individual failure, so there is no duplicate
        // re-send on retry either.
        $this->messageBus->dispatch(
            new OrganiserAnnouncementEmail(
                $subject,
                $body,
                $activityName,
                $replyTo,
                $recipients,
            ),
        );

        $this->setFeedback(
            AlertTypes::Success,
            $this->translator->trans(
                'Your message is being sent to %count% recipient(s).',
                ['%count%' => count($recipients)],
            ),
        );

        $this->composingListId = null;
    }

    /**
     * Resolve the concrete recipients of a bulk email for a list, by scope. External and member sign-ups alike carry
     * an email; any without one is skipped.
     *
     * @return list<array{email: string, name: string}>
     */
    private function recipientsFor(
        SignupList $signupList,
        RecipientScope $scope,
    ): array {
        // Normalise once: the $selected LiveProp holds checkbox-supplied strings, while getId() is an int.
        $selected = $this->selectedIds();
        $recipients = [];
        foreach ($this->confirmedSignups($signupList) as $signup) {
            $include = match ($scope) {
                RecipientScope::All => true,
                RecipientScope::Selected => in_array(
                    $signup->getId(),
                    $selected,
                    true,
                ),
                RecipientScope::Present => $signup->isPresent(),
                RecipientScope::Admitted => $signup->isDrawn(),
                RecipientScope::Waitlisted => !$signup->isDrawn(),
            };

            if (!$include) {
                continue;
            }

            $email = $signup->getEmail();
            if (null === $email) {
                continue;
            }

            $recipients[] = [
                'email' => $email,
                'name' => $signup->getFullName(),
            ];
        }

        return $recipients;
    }

    /**
     * Find a sign-up by id, but only within this activity's live sign-up lists, so a crafted id cannot reach another
     * activity's sign-ups.
     */
    private function findOwnedSignup(int $signupId): ?Signup
    {
        foreach ($this->activity->getLiveSignupLists() as $signupList) {
            foreach ($this->confirmedSignups($signupList) as $signup) {
                if ($signup->getId() === $signupId) {
                    return $signup;
                }
            }
        }

        return null;
    }

    private function findOwnedList(int $listId): ?SignupList
    {
        foreach ($this->activity->getLiveSignupLists() as $signupList) {
            if ($signupList->getId() === $listId) {
                return $signupList;
            }
        }

        return null;
    }

    /**
     * The ids of this list's externals still awaiting e-mail verification, memoised per list id so the underlying query
     * runs at most once per list for the lifetime of this (per-request) component instance — the toggles and draws that
     * call {@see confirmedSignups()} in a loop otherwise re-run it for every list.
     *
     * @return int[]
     */
    private function pendingExternalIds(SignupList $signupList): array
    {
        $listId = $signupList->getId();
        if (null === $listId) {
            return $this->verificationRepository->findPendingExternalSignupIdsForList($signupList);
        }

        return $this->pendingExternalIds[$listId]
            ??= $this->verificationRepository->findPendingExternalSignupIdsForList($signupList);
    }

    /**
     * A list's sign-ups excluding externals still awaiting e-mail verification: an unconfirmed external is not a real
     * participant, so it must never be drawn, e-mailed, toggled or counted.
     *
     * @return Signup[]
     */
    private function confirmedSignups(SignupList $signupList): array
    {
        $pending = $this->pendingExternalIds($signupList);

        $signups = [];
        foreach ($signupList->getSignUps() as $signup) {
            if (
                $signup instanceof ExternalSignup
                && in_array(
                    $signup->getId(),
                    $pending,
                    true,
                )
            ) {
                continue;
            }

            $signups[] = $signup;
        }

        return $signups;
    }

    private function assertBoard(): void
    {
        if (!$this->isBoard()) {
            throw new AccessDeniedException();
        }
    }

    private function currentMember(): Member
    {
        $user = $this->security->getUser();
        assert($user instanceof User);

        return $user->getMember();
    }

    private function setFeedback(
        AlertTypes $type,
        string $message,
    ): void {
        $this->feedbackType = $type->value;
        $this->feedback = $message;
    }

    /**
     * The security boundary for every render and action: the viewer must be allowed to see the activity and be within
     * the viewing window (the board is never time-limited).
     */
    private function assertAccess(): void
    {
        if (
            !$this->security->isGranted(
                RevisionVoter::VIEW,
                $this->activity,
            )
            || !SignupAdminWindow::canView(
                $this->activity->getEndTime(),
                $this->security->isGranted(UserRoles::Board->value),
            )
        ) {
            throw new AccessDeniedException();
        }
    }
}
