<?php

declare(strict_types=1);

namespace App\Twig\Components\Activity;

use App\Entity\Activity\SignupList as SignupListEntity;
use App\Entity\User\Enums\UserRoles;
use App\Form\Activity\SignupType;
use App\Message\Activity\ExternalSignupResendVerificationEmail;
use App\Repository\Activity\ExternalSignupRepository;
use App\Repository\Activity\SignupListRepository;
use App\Service\Activity\SignupManager;
use App\Service\Application\AltchaSolutionGuard;
use App\Twig\Components\Concerns\FlashesTrait;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Override;
use Symfony\Bundle\SecurityBundle\Security;
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
 * The guest (non-member) external sign-up form for one list. A live component so an invalid submit re-renders the form
 * inline (no full-page reload, no re-opening the modal), like the member flow. The Altcha widget can live here because
 * it carries `data-live-ignore`: the loading-directive scanner skips it (so it no longer throws on the widget's own
 * `data-loading`) and the solved proof-of-work survives re-renders untouched.
 *
 * Window/GEWIS-only/members rules are re-asserted on submit (a live request bypasses any page-level gate). A successful
 * submit redirects to the activity (the verification email is the real next step), so the modal closes on reload;
 * rate-limited or invalid submits re-render inline.
 */
#[AsLiveComponent(
    name: 'Activity:ExternalSignupForm',
    template: 'components/Activity/ExternalSignupForm.html.twig',
)]
final class ExternalSignupForm
{
    use ComponentWithFormTrait;
    use DefaultActionTrait;
    use FlashesTrait;

    #[LiveProp]
    public SignupListEntity $signupList;

    /** Component-local, transient: a rate-limit warning shown on the render right after a throttled submit. */
    public ?string $feedback = null;

    public function __construct(
        private readonly Security $security,
        private readonly FormFactoryInterface $formFactory,
        private readonly SignupManager $signupManager,
        private readonly AltchaSolutionGuard $altchaSolutionGuard,
        private readonly SignupListRepository $signupListRepository,
        private readonly ExternalSignupRepository $externalSignupRepository,
        private readonly MessageBusInterface $messageBus,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly RequestStack $requestStack,
        private readonly TranslatorInterface $translator,
        #[Autowire(service: 'limiter.external_signup_ip')]
        private readonly RateLimiterFactory $externalSignupIpLimiter,
        #[Autowire(service: 'limiter.external_signup_email')]
        private readonly RateLimiterFactory $externalSignupEmailLimiter,
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
            SignupType::class,
            null,
            [
                'signupList' => $this->signupList,
                'mode' => SignupType::MODE_EXTERNAL,
                // The live request is itself CSRF-protected; the form is submitted programmatically, not by a POST.
                'csrf_protection' => false,
            ],
        );
    }

    public function hasSensitiveField(): bool
    {
        return $this->signupList->hasSensitiveField();
    }

    #[LiveAction]
    public function submit(): ?Response
    {
        $this->assertCanSignUpExternally();

        // Throws on invalid input (incl. an unsolved/invalid captcha), re-rendering the form inline with the errors.
        $this->submitForm();

        // Single-use: the local Altcha validator accepts the same solved proof-of-work repeatedly within its signature
        // window, so reject a replay here even though the captcha itself just validated.
        if (!$this->altchaSolutionGuard->consume(strval($this->getForm()->get('security')->getData()))) {
            $this->feedback = $this->translator->trans('Please complete the verification again and resubmit.');

            return null;
        }

        /** @var array<string, mixed> $data */
        $data = $this->getForm()->getData();
        $email = strval($data['email'] ?? '');
        $request = $this->requestStack->getCurrentRequest();

        if (
            !$this->externalSignupIpLimiter->create($request?->getClientIp() ?? '')->consume()->isAccepted()
            || !$this->externalSignupEmailLimiter->create($email)->consume()->isAccepted()
        ) {
            // Stay in the modal with the entered data and a warning rather than navigating away.
            $this->feedback = $this->translator->trans('Too many sign-up attempts. Please try again later.');

            return null;
        }

        if (
            null !== $this->externalSignupRepository->findOneByListAndEmail(
                $this->signupList,
                $email,
            )
        ) {
            // Already signed up: re-send the confirmation asynchronously rather than dead-ending. The handler decides
            // whether anything is still pending; the flash below is identical to a fresh sign-up, so resubmitting never
            // reveals that the address was already used.
            $this->messageBus->dispatch(
                new ExternalSignupResendVerificationEmail(
                    (int) $this->signupList->getId(),
                    $email,
                ),
            );
        } else {
            try {
                $this->signupManager->createExternalSignup(
                    $this->signupList,
                    strval($data['fullName'] ?? ''),
                    $email,
                    SignupType::extractFieldData(
                        $this->signupList,
                        $data,
                    ),
                );
            } catch (UniqueConstraintViolationException) {
                // The pre-check above missed a concurrent sign-up for the same address: the unique index caught it.
                // Show the same neutral flash as the "already signed up" branch, so the outcome never reveals the race.
                $this->flash(
                    'success',
                    $this->translator->trans('Almost there! Check your email to confirm your sign-up.'),
                );

                return new RedirectResponse(
                    $this->urlGenerator->generate(
                        'activity/view',
                        ['activity' => $this->signupList->getActivity()->getId()],
                    ),
                );
            }
        }

        $this->flash(
            'success',
            $this->translator->trans('Almost there! Check your email to confirm your sign-up.'),
        );

        return new RedirectResponse(
            $this->urlGenerator->generate(
                'activity/view',
                ['activity' => $this->signupList->getActivity()->getId()],
            ),
        );
    }

    /**
     * Re-assert that this is a guest signing up to the activity's live, open, non-members-only list.
     */
    private function assertCanSignUpExternally(): void
    {
        if (
            $this->security->isGranted(UserRoles::User->value)
            || $this->signupList->getOnlyGEWIS()
            || !$this->signupList->isOpen()
            || $this->signupList->getActivity()->isFrozen()
            || $this->signupList->getActivity()->getLiveRevision() !== $this->signupList->getRevision()
        ) {
            throw new AccessDeniedException();
        }
    }
}
