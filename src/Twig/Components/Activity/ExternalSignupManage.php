<?php

declare(strict_types=1);

namespace App\Twig\Components\Activity;

use App\Entity\Activity\Enums\ExternalSignupVerificationPurpose;
use App\Entity\Activity\ExternalSignup;
use App\Form\Activity\SignupType;
use App\Service\Activity\ExternalSignupTokenResolver;
use App\Service\Activity\SignupManager;
use App\Twig\Components\Concerns\FlashesTrait;
use Override;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;

use function strval;

/**
 * Self-service edit/unsubscribe for an external (non-member) sign-up, reached through the emailed Manage link. There
 * is no login, so the token rides as a read-only, signed {@see LiveProp} and is RE-VALIDATED on every render and action
 * (via {@see self::signup()}); a once-validated token is never trusted. Editing the name and answers is an inline live
 * action ({@see self::save()}); the email is locked ({@see SignupType::MODE_MANAGE}) because it is the verified
 * identity. Unsubscribing ({@see self::unsubscribe()}) withdraws and redirects to the activity, confirmed via the
 * shared confirm-modal.
 */
#[AsLiveComponent(
    name: 'Activity:ExternalSignupManage',
    template: 'components/Activity/ExternalSignupManage.html.twig',
)]
final class ExternalSignupManage
{
    use ComponentWithFormTrait;
    use DefaultActionTrait;
    use FlashesTrait;

    #[LiveProp]
    public string $token = '';

    /** Component-local, transient: a success message shown on the render right after {@see self::save()}. */
    public ?string $feedback = null;

    private ?ExternalSignup $resolvedSignup = null;

    public function __construct(
        private readonly ExternalSignupTokenResolver $tokenResolver,
        private readonly SignupManager $signupManager,
        private readonly FormFactoryInterface $formFactory,
        private readonly TranslatorInterface $translator,
        private readonly UrlGeneratorInterface $urlGenerator,
        // Consumed by FlashesTrait::flash() (the unsubscribe success flash).
        private readonly RequestStack $requestStack,
    ) {
    }

    public function mount(string $token): void
    {
        $this->token = $token;
        // Validate eagerly so a bad/expired token 404s on the page, not only when an action is attempted.
        $this->signup();
    }

    /**
     * Re-resolve and re-validate the Manage token on every request, 404-ing on any failure. Memoised within the request
     * only: a fresh component instance is created per request, so this never serves a stale sign-up across requests.
     */
    public function signup(): ExternalSignup
    {
        if (null !== $this->resolvedSignup) {
            return $this->resolvedSignup;
        }

        $verification = $this->tokenResolver->resolve(
            $this->token,
            ExternalSignupVerificationPurpose::Manage,
        );
        if (null === $verification) {
            throw new NotFoundHttpException();
        }

        return $this->resolvedSignup = $verification->getExternalSignup();
    }

    /**
     * Whether the subscriber may still edit/unsubscribe: the list is the activity's live list, is open, and the
     * activity has not been frozen (cancelled or unpublished) by the board.
     */
    public function isEditable(): bool
    {
        $signupList = $this->signup()->getSignupList();

        return $signupList->isOpen()
            && !$signupList->getActivity()->isFrozen()
            && $signupList->getActivity()->getLiveRevision() === $signupList->getRevision();
    }

    /**
     * @return FormInterface<array<string, mixed>>
     */
    #[Override]
    protected function instantiateForm(): FormInterface
    {
        $signup = $this->signup();
        $signupList = $signup->getSignupList();

        return $this->formFactory->create(
            SignupType::class,
            SignupType::fieldPrefill(
                $signupList,
                $signup,
            ) + [
                'fullName' => $signup->getFullName(),
                'email' => $signup->getEmail(),
            ],
            [
                'signupList' => $signupList,
                'mode' => SignupType::MODE_MANAGE,
                // The live request is itself CSRF-protected; the form is submitted programmatically, not by a POST.
                'csrf_protection' => false,
            ],
        );
    }

    #[LiveAction]
    public function save(): void
    {
        $signup = $this->signup();
        if (!$this->isEditable()) {
            return;
        }

        // Throws on invalid input, re-rendering the form with field errors instead of proceeding.
        $this->submitForm();

        /** @var array<string, mixed> $data */
        $data = $this->getForm()->getData();
        $this->signupManager->editExternalSignup(
            $signup,
            strval($data['fullName'] ?? ''),
            SignupType::extractFieldData(
                $signup->getSignupList(),
                $data,
            ),
        );

        $this->feedback = $this->translator->trans('Your sign-up has been updated.');
    }

    /**
     * Withdraw and redirect to the activity. A live action that redirects (rather than re-rendering an inline
     * "unsubscribed" state) because the withdrawal also deletes the Manage token, so the page can no longer be
     * re-resolved, and the natural next view is the activity itself.
     */
    #[LiveAction]
    public function unsubscribe(): RedirectResponse
    {
        $signup = $this->signup();
        $activityId = $signup->getSignupList()->getActivity()->getId();

        if ($this->isEditable()) {
            $this->signupManager->withdraw($signup);
            $this->flash(
                'success',
                $this->translator->trans('You have been unsubscribed.'),
            );
        }

        return new RedirectResponse(
            $this->urlGenerator->generate(
                'activity/view',
                ['activity' => $activityId],
            ),
        );
    }
}
