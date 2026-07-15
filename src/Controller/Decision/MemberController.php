<?php

declare(strict_types=1);

namespace App\Controller\Decision;

use App\Entity\Decision\Member;
use App\Entity\User\Enums\UserRoles;
use App\Entity\User\User;
use App\Repository\Decision\MemberRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

use function array_map;
use function assert;
use function mb_strlen;
use function trim;

#[IsGranted(
    attribute: UserRoles::User->value,
    message: 'You are not allowed to view members.',
)]
#[Route(
    path: '/members',
    name: 'members/',
)]
class MemberController extends AbstractController
{
    public function __construct(private readonly MemberRepository $memberRepository)
    {
    }

    #[Route(
        path: '',
        name: 'index',
    )]
    public function index(): Response
    {
        return $this->render('decision/index.html.twig');
    }

    /**
     * Name autocomplete for the photo tag UI: matches current members by name and returns their lidnr and full name.
     */
    #[Route(
        path: '/search',
        name: 'search',
        methods: ['GET'],
    )]
    public function search(#[MapQueryParameter]
    string $q = '',): JsonResponse
    {
        $query = trim($q);
        if (mb_strlen($query) < 2) {
            return new JsonResponse([]);
        }

        return new JsonResponse(array_map(
            static fn (array $row): array => [
                'lidnr' => $row['lidnr'],
                'fullName' => $row['fullName'],
            ],
            $this->memberRepository->searchByName($query),
        ));
    }

    #[Route(
        path: '/me',
        name: 'me',
    )]
    #[Route(
        path: '/{member}',
        name: 'view',
        requirements: ['member' => '[1-9][0-9]{,4}'],
        defaults: ['member' => null],
    )]
    public function member(
        Request $request,
        ?Member $member = null,
    ): Response {
        if (
            null !== $request->attributes->get('member')
            && (
                null === $member
                || true === $member->getDeleted()
            )
        ) {
            throw $this->createNotFoundException();
        }

        if (null === $member) {
            $user = $this->getUser();
            assert($user instanceof User);

            $member = $user->getMember();
        } else {
            if (
                $member->isExpired()
                && $this->isGranted(UserRoles::Admin->value)
            ) {
                throw $this->createNotFoundException();
            }
        }

        return $this->render(
            'decision/member.html.twig',
            ['member' => $member],
        );
    }
}
