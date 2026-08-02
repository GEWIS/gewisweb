<?php

declare(strict_types=1);

namespace App\Controller\Decision;

use App\Entity\Application\Enums\AlertTypes;
use App\Entity\Decision\Meeting;
use App\Entity\Decision\Member;
use App\Entity\User\Enums\UserRoles;
use App\Entity\User\User;
use App\Form\Decision\AuthorizationType;
use App\Repository\Decision\AuthorizationRepository;
use App\Service\Decision\AuthorizationService;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

use function assert;
use function count;
use function strval;

/**
 * A member's GMM proxy authorization: grant one for the upcoming GMM, see who represents you, and revoke it again.
 * Graduates cannot authorize, mirroring the old access rules.
 */
#[IsGranted(
    attribute: UserRoles::Member->value,
    message: 'You are not allowed to manage authorizations.',
)]
#[Route(
    path: '/members/authorizations',
    name: 'members/authorizations/',
)]
class AuthorizationController extends AbstractController
{
    public function __construct(
        private readonly AuthorizationService $authorizationService,
        private readonly AuthorizationRepository $authorizationRepository,
        private readonly TranslatorInterface $translator,
    ) {
    }

    #[Route(
        path: '',
        name: 'index',
        methods: [
            'GET',
            'POST',
        ],
    )]
    public function index(
        Request $request,
        #[MapQueryParameter]
        ?int $meeting = null,
    ): Response {
        $member = $this->member();
        $meetings = $this->authorizationService->getUpcomingALVs();
        $selected = $this->selectMeeting(
            $meetings,
            $meeting,
        );
        $authorization = null;
        $received = 0;

        $form = $this->createForm(AuthorizationType::class);

        if (null !== $selected) {
            $authorization = $this->authorizationService->getCurrentAuthorization(
                $member,
                $selected,
            );
            $received = count($this->authorizationRepository->findRecipientAuthorization(
                $selected->getNumber(),
                $member,
            ));

            $form->handleRequest($request);
            if (
                $form->isSubmitted()
                && $form->isValid()
                && null === $authorization
            ) {
                try {
                    $this->authorizationService->authorize(
                        $member,
                        (int) strval($form->get('recipient')->getData()),
                        $selected,
                    );
                    $this->addFlash(
                        AlertTypes::Success->value,
                        $this->translator->trans('Your authorization has been registered.'),
                    );
                } catch (RuntimeException $exception) {
                    $this->addFlash(
                        AlertTypes::Danger->value,
                        $exception->getMessage(),
                    );
                }

                return $this->redirectToRoute(
                    'members/authorizations/index',
                    ['meeting' => $selected->getNumber()],
                );
            }
        }

        return $this->render(
            'decision/authorizations/index.html.twig',
            [
                'meetings' => $meetings,
                'meeting' => $selected,
                'authorization' => $authorization,
                'received' => $received,
                'form' => $form,
            ],
        );
    }

    #[Route(
        path: '/revoke',
        name: 'revoke',
        methods: ['POST'],
    )]
    #[IsCsrfTokenValid(
        id: 'authorization_revoke',
        tokenKey: '_csrf_token',
    )]
    public function revoke(Request $request): Response
    {
        $member = $this->member();
        $meeting = $this->selectMeeting(
            $this->authorizationService->getUpcomingALVs(),
            $request->request->getInt('meeting'),
        );

        if (null !== $meeting) {
            $authorization = $this->authorizationService->getCurrentAuthorization(
                $member,
                $meeting,
            );

            if (null !== $authorization) {
                $this->authorizationService->revoke(
                    $authorization,
                    $member,
                );
                $this->addFlash(
                    AlertTypes::Success->value,
                    $this->translator->trans('Your authorization has been revoked.'),
                );
            }
        }

        return $this->redirectToRoute(
            'members/authorizations/index',
            null === $meeting ? [] : ['meeting' => $meeting->getNumber()],
        );
    }

    /**
     * @param Meeting[] $meetings
     */
    private function selectMeeting(
        array $meetings,
        ?int $number,
    ): ?Meeting {
        foreach ($meetings as $candidate) {
            if ($candidate->getNumber() === $number) {
                return $candidate;
            }
        }

        return $meetings[0] ?? null;
    }

    private function member(): Member
    {
        $user = $this->getUser();
        assert($user instanceof User);

        return $user->getMember();
    }
}
