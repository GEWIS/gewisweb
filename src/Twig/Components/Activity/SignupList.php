<?php

declare(strict_types=1);

namespace App\Twig\Components\Activity;

use App\Entity\Activity\SignupList as SignupListEntity;
use App\Entity\Activity\UserSignup;
use App\Entity\Decision\Member;
use App\Entity\User\Enums\UserRoles;
use App\Entity\User\User;
use App\Form\Activity\SignupType;
use App\Repository\Activity\ExternalSignupVerificationRepository;
use App\Repository\Activity\SignupListRepository;
use App\Repository\Activity\UserSignupRepository;
use App\Service\Activity\SignupManager;
use App\ViewModel\Activity\SignupListView;
use Override;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;

use function assert;

/**
 * The member-facing body of one sign-up list: the Sign up / Edit / Unsubscribe controls, the sign-up form (in a
 * Bootstrap modal) and the subscriber table — all live, so signing up, editing answers and withdrawing update the
 * panel inline with no page reload. Members only ({@see IsGranted}); guests get the server-rendered partial with the
 * plain Altcha external form, which cannot live in a live component.
 *
 * A successful {@see self::submit()} dispatches a `signup:success` browser event (scoped by list id) that the
 * `modal-close` controller uses to close the modal, then the panel re-renders to the signed-up state. {@see
 * self::withdraw()} is confirmed via the shared confirm-modal. The window/ownership rules are re-asserted in every
 * action — a live request bypasses any page-level gate.
 */
#[AsLiveComponent(
    name: 'Activity:SignupList',
    template: 'components/Activity/SignupList.html.twig',
)]
#[IsGranted(UserRoles::User->value)]
final class SignupList
{
    use ComponentToolsTrait;
    use ComponentWithFormTrait;
    use DefaultActionTrait;

    #[LiveProp]
    public SignupListEntity $signupList;

    /** Component-local, transient: a success message shown on the render right after an action. */
    public ?string $feedback = null;

    public function __construct(
        private readonly Security $security,
        private readonly FormFactoryInterface $formFactory,
        private readonly SignupManager $signupManager,
        private readonly SignupListRepository $signupListRepository,
        private readonly UserSignupRepository $userSignupRepository,
        private readonly ExternalSignupVerificationRepository $verificationRepository,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function mount(int $signupList): void
    {
        $list = $this->signupListRepository->find($signupList);
        if (null === $list) {
            throw new NotFoundHttpException();
        }

        $this->signupList = $list;
    }

    /**
     * @return FormInterface<array<string, mixed>>
     */
    #[Override]
    protected function instantiateForm(): FormInterface
    {
        $signup = $this->memberSignup();

        return $this->formFactory->create(
            SignupType::class,
            null !== $signup
                ? SignupType::fieldPrefill(
                    $this->signupList,
                    $signup,
                )
                : null,
            [
                'signupList' => $this->signupList,
                'mode' => SignupType::MODE_MEMBER,
                // The live request is itself CSRF-protected; the form is submitted programmatically, not by a POST.
                'csrf_protection' => false,
            ],
        );
    }

    /**
     * The read-model used to render the subscriber table and count (members may always see details; the viewer's own
     * row is highlighted, others' sensitive fields hidden, unverified externals excluded).
     */
    public function listView(): SignupListView
    {
        return SignupListView::fromSignupList(
            $this->signupList,
            true,
            $this->currentMember()->getLidnr(),
            $this->translator,
            $this->verificationRepository->findPendingExternalSignupIdsForList($this->signupList),
        );
    }

    public function isEditing(): bool
    {
        return null !== $this->memberSignup();
    }

    public function hasSensitiveField(): bool
    {
        foreach ($this->signupList->getFields() as $field) {
            if ($field->isSensitive()) {
                return true;
            }
        }

        return false;
    }

    #[LiveAction]
    public function submit(): void
    {
        if (!$this->isOpenForSignup()) {
            throw new AccessDeniedException();
        }

        // Throws on invalid input, re-rendering the form (inside the still-open modal) with the field errors.
        $this->submitForm();

        $fieldData = SignupType::extractFieldData(
            $this->signupList,
            $this->formData(),
        );
        $existing = $this->memberSignup();
        if (null !== $existing) {
            $this->signupManager->editSignup(
                $existing,
                $fieldData,
            );
            $this->feedback = $this->translator->trans('Your sign-up has been updated.');
        } else {
            $this->signupManager->createUserSignup(
                $this->signupList,
                $this->currentMember(),
                $fieldData,
            );
            $this->feedback = $this->translator->trans('You are signed up.');
        }

        // Close the modal client-side (scoped to this list) now that the panel will re-render to the signed-up state.
        $this->dispatchBrowserEvent(
            'signup:success',
            ['listId' => $this->signupList->getId()],
        );
    }

    #[LiveAction]
    public function withdraw(): void
    {
        $signup = $this->memberSignup();
        if (
            null === $signup
            || !$this->isOpenForSignup()
        ) {
            throw new AccessDeniedException();
        }

        $this->signupManager->withdraw($signup);
        $this->feedback = $this->translator->trans('You have been unsubscribed.');
    }

    /**
     * Whether this is the activity's live list and its sign-up window is open right now.
     */
    private function isOpenForSignup(): bool
    {
        return $this->signupList->getActivity()->getLiveRevision() === $this->signupList->getRevision()
            && $this->signupList->isOpen();
    }

    private function memberSignup(): ?UserSignup
    {
        return $this->userSignupRepository->findOneByListAndMember(
            $this->signupList,
            $this->currentMember(),
        );
    }

    private function currentMember(): Member
    {
        $user = $this->security->getUser();
        assert($user instanceof User);

        return $user->getMember();
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        /** @var array<string, mixed> $data */
        $data = $this->getForm()->getData();

        return $data;
    }
}
