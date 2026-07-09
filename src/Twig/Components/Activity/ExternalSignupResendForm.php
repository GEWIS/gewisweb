<?php

declare(strict_types=1);

namespace App\Twig\Components\Activity;

use App\Entity\Activity\SignupList as SignupListEntity;
use App\Form\Activity\ResendVerificationType;
use App\Message\Activity\ExternalSignupResendVerificationEmail;
use App\Repository\Activity\SignupListRepository;
use App\Service\Application\AltchaSolutionGuard;
use App\Twig\Components\Concerns\FlashesTrait;
use Override;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;

use function strval;

/**
 * Recovery path for a lost/undelivered external-sign-up confirmation email: a small live form (email + Altcha) that
 * re-sends the verification link. A live component for the same reasons as {@see ExternalSignupForm}: inline
 * validation (incl. the captcha) without a full-page reload, and the Altcha widget can live here behind
 * `data-live-ignore`.
 *
 * Like the password-reset request, it dispatches {@see ExternalSignupResendVerificationEmail} unconditionally and the
 * existence check happens in the handler, off the request thread, so the response never reveals whether the address is
 * signed up. Not gated on the list being open (confirming a sign-up made while open is honoured after close).
 */
#[AsLiveComponent(
    name: 'Activity:ExternalSignupResendForm',
    template: 'components/Activity/ExternalSignupResendForm.html.twig',
)]
final class ExternalSignupResendForm
{
    use ComponentWithFormTrait;
    use DefaultActionTrait;
    use FlashesTrait;

    #[LiveProp]
    public SignupListEntity $signupList;

    /** Component-local, transient: a rate-limit warning shown on the render right after a throttled submit. */
    public ?string $feedback = null;

    public function __construct(
        private readonly FormFactoryInterface $formFactory,
        private readonly AltchaSolutionGuard $altchaSolutionGuard,
        private readonly SignupListRepository $signupListRepository,
        private readonly MessageBusInterface $messageBus,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly RequestStack $requestStack,
        private readonly TranslatorInterface $translator,
        #[Autowire(service: 'limiter.signup_verify_resend_ip')]
        private readonly RateLimiterFactory $resendIpLimiter,
        #[Autowire(service: 'limiter.signup_verify_resend_email')]
        private readonly RateLimiterFactory $resendEmailLimiter,
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
        return $this->formFactory->create(
            ResendVerificationType::class,
            null,
            [
                // The live request is itself CSRF-protected; the form is submitted programmatically, not by a POST.
                'csrf_protection' => false,
            ],
        );
    }

    #[LiveAction]
    public function submit(): ?Response
    {
        $this->assertList();

        // Throws on invalid input (incl. an unsolved/invalid captcha), re-rendering the form inline with the errors.
        $this->submitForm();

        // Single-use: reject a replayed proof-of-work even though its signature is still within the validity window.
        if (!$this->altchaSolutionGuard->consume(strval($this->getForm()->get('security')->getData()))) {
            $this->feedback = $this->translator->trans('Please complete the verification again and resubmit.');

            return null;
        }

        /** @var array<string, mixed> $data */
        $data = $this->getForm()->getData();
        $email = strval($data['email'] ?? '');
        $request = $this->requestStack->getCurrentRequest();

        if (
            !$this->resendIpLimiter->create($request?->getClientIp() ?? '')->consume()->isAccepted()
            || !$this->resendEmailLimiter->create($email)->consume()->isAccepted()
        ) {
            $this->feedback = $this->translator->trans('Too many requests. Please try again later.');

            return null;
        }

        // Dispatch unconditionally; the handler does the sign-up existence lookup off the request thread, so response
        // timing never reveals whether this email is signed up (mirrors the password-reset request).
        $this->messageBus->dispatch(
            new ExternalSignupResendVerificationEmail(
                (int) $this->signupList->getId(),
                $email,
            ),
        );

        $this->flash(
            'info',
            $this->translator->trans(
                'If a pending sign-up exists for this email address, we have sent a new confirmation link.',
            ),
        );

        return new RedirectResponse(
            $this->urlGenerator->generate(
                'activity/view',
                ['activity' => $this->signupList->getActivity()->getId()],
            ),
        );
    }

    /**
     * Re-assert this is the activity's publicly live list that accepts externals (not gated on being open; see above).
     */
    private function assertList(): void
    {
        if (
            $this->signupList->getOnlyGEWIS()
            || $this->signupList->getActivity()->isFrozen()
            || $this->signupList->getActivity()->getLiveRevision() !== $this->signupList->getRevision()
        ) {
            throw new AccessDeniedException();
        }
    }
}
