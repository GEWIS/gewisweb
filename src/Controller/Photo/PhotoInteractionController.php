<?php

declare(strict_types=1);

namespace App\Controller\Photo;

use App\Entity\Decision\Member;
use App\Entity\Decision\Organ;
use App\Entity\Photo\Photo;
use App\Entity\User\Enums\UserRoles;
use App\Entity\User\User;
use App\Repository\Decision\OrganRepository;
use App\Repository\Photo\MemberTagRepository;
use App\Repository\Photo\OrganTagRepository;
use App\Repository\Photo\PhotoRepository;
use App\Repository\Photo\TagRepository;
use App\Repository\Photo\VoteRepository;
use App\Security\Photo\PhotoVoter;
use App\Security\Photo\TagVoter;
use App\Service\Photo\ProfilePhotoService;
use App\Service\Photo\TagService;
use App\Service\Photo\VoteService;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

use function array_map;
use function assert;
use function is_numeric;

/**
 * The tag, vote and profile-photo actions the gallery viewer calls. Reading a photo's tags and votes only needs view
 * access; adding a tag or voting needs ROLE_MEMBER (the graduate exclusion), and removing a tag is decided per tag.
 */
#[IsGranted(
    attribute: UserRoles::User->value,
    message: 'You are not allowed to view photos.',
)]
#[Route(
    path: '/photos',
    name: 'photo/',
)]
class PhotoInteractionController extends AbstractController
{
    public function __construct(
        private readonly PhotoRepository $photoRepository,
        private readonly TagRepository $tagRepository,
        private readonly MemberTagRepository $memberTagRepository,
        private readonly OrganTagRepository $organTagRepository,
        private readonly VoteRepository $voteRepository,
        private readonly OrganRepository $organRepository,
        private readonly TagService $tagService,
        private readonly VoteService $voteService,
        private readonly ProfilePhotoService $profilePhotoService,
    ) {
    }

    /**
     * The tags and vote state of a photo, for the viewer overlay. Available to anyone who may view the photo; the
     * per-user flags say what that viewer may then do.
     */
    #[Route(
        path: '/photo/{photo}/details',
        name: 'details',
        requirements: ['photo' => '\d+'],
        methods: ['GET'],
    )]
    public function details(int $photo): JsonResponse
    {
        $photoEntity = $this->viewablePhoto($photo);
        $member = $this->member();

        // The member and organ tags are loaded with their subject fetch-joined (one query each), so reading a name
        // below never triggers a per-tag lazy load; the viewer re-fetches this on every photo it opens.
        $memberTags = [];
        $taggedSelf = false;
        foreach ($this->memberTagRepository->findByPhotoWithMember($photo) as $tag) {
            $taggedSelf = $taggedSelf || $tag->getMember()->getLidnr() === $member->getLidnr();
            $memberTags[] = [
                'id' => $tag->getId(),
                'lidnr' => $tag->getMember()->getLidnr(),
                'fullName' => $tag->getMember()->getFullName(),
                'x' => $tag->getPositionX(),
                'y' => $tag->getPositionY(),
                'canRemove' => $this->isGranted(
                    TagVoter::REMOVE,
                    $tag,
                ),
            ];
        }

        $organTags = [];
        foreach ($this->organTagRepository->findByPhotoWithOrgan($photo) as $tag) {
            $organTags[] = [
                'id' => $tag->getId(),
                'organId' => $tag->getOrgan()->getId(),
                'name' => $tag->getOrgan()->getName(),
                'abbr' => $tag->getOrgan()->getAbbr(),
                'x' => $tag->getPositionX(),
                'y' => $tag->getPositionY(),
                'canRemove' => $this->isGranted(
                    TagVoter::REMOVE,
                    $tag,
                ),
            ];
        }

        return new JsonResponse([
            'memberTags' => $memberTags,
            'organTags' => $organTags,
            'canTag' => $this->isGranted(
                PhotoVoter::TAG,
                $photoEntity,
            ),
            'canVote' => $this->isGranted(
                PhotoVoter::VOTE,
                $photoEntity,
            ),
            'voted' => null !== $this->voteRepository->findVote(
                $photo,
                $member->getLidnr(),
            ),
            // The pulsing-dot nudge shows only when the member has not voted recently (#2066).
            'recentVote' => $this->voteRepository->hasRecentVote($member->getLidnr()),
            'taggedSelf' => $taggedSelf,
        ]);
    }

    #[Route(
        path: '/photo/{photo}/tag',
        name: 'tag',
        requirements: ['photo' => '\d+'],
        methods: ['POST'],
    )]
    public function tag(
        int $photo,
        Request $request,
    ): JsonResponse {
        $photoEntity = $this->viewablePhoto($photo);
        if (
            !$this->isGranted(
                PhotoVoter::TAG,
                $photoEntity,
            )
        ) {
            throw $this->createAccessDeniedException();
        }

        $payload = $request->getPayload();
        $id = $payload->getInt('id');
        [
            $x, $y
        ] = $this->coordinates(
            $payload->get('x'),
            $payload->get('y'),
        );

        try {
            $tag = 'organ' === $payload->getString('type')
                ? $this->tagService->addOrganTag(
                    $photoEntity,
                    $id,
                    $x,
                    $y,
                )
                : $this->tagService->addMemberTag(
                    $photoEntity,
                    $id,
                    $x,
                    $y,
                );
        } catch (InvalidArgumentException) {
            return new JsonResponse(
                ['success' => false],
                Response::HTTP_BAD_REQUEST,
            );
        }

        if (null === $tag) {
            return new JsonResponse(
                ['success' => false],
                Response::HTTP_CONFLICT,
            );
        }

        return new JsonResponse([
            'success' => true,
            'id' => $tag->getId(),
        ]);
    }

    #[Route(
        path: '/tag/{tag}/remove',
        name: 'tag_remove',
        requirements: ['tag' => '\d+'],
        methods: ['POST'],
    )]
    public function removeTag(int $tag): JsonResponse
    {
        $tagEntity = $this->tagRepository->find($tag);
        if (null === $tagEntity) {
            throw new NotFoundHttpException();
        }

        if (
            !$this->isGranted(
                TagVoter::REMOVE,
                $tagEntity,
            )
        ) {
            throw $this->createAccessDeniedException();
        }

        $this->tagService->removeTag($tagEntity);

        return new JsonResponse(['success' => true]);
    }

    #[Route(
        path: '/photo/{photo}/vote',
        name: 'vote',
        requirements: ['photo' => '\d+'],
        methods: ['POST'],
    )]
    public function vote(int $photo): JsonResponse
    {
        $photoEntity = $this->viewablePhoto($photo);
        if (
            !$this->isGranted(
                PhotoVoter::VOTE,
                $photoEntity,
            )
        ) {
            throw $this->createAccessDeniedException();
        }

        $this->voteService->castVote(
            $photoEntity,
            $this->member(),
        );

        return new JsonResponse(['success' => true]);
    }

    #[Route(
        path: '/photo/{photo}/profile-photo',
        name: 'profile_photo_set',
        requirements: ['photo' => '\d+'],
        methods: ['POST'],
    )]
    public function setProfilePhoto(int $photo): JsonResponse
    {
        $photoEntity = $this->viewablePhoto($photo);

        // The service also enforces this, but check up front so an untagged member gets a clean 403 rather than a
        // silent no-op.
        if (
            !$this->profilePhotoService->setProfilePhoto(
                $photoEntity,
                $this->member(),
            )
        ) {
            throw $this->createAccessDeniedException();
        }

        return new JsonResponse(['success' => true]);
    }

    #[Route(
        path: '/profile-photo/remove',
        name: 'profile_photo_remove',
        methods: ['POST'],
    )]
    public function removeProfilePhoto(): JsonResponse
    {
        $this->profilePhotoService->removeProfilePhoto($this->member());

        return new JsonResponse(['success' => true]);
    }

    /**
     * The active organs, for the organ tag select.
     */
    #[Route(
        path: '/organs',
        name: 'organs',
        methods: ['GET'],
    )]
    public function organs(): JsonResponse
    {
        return new JsonResponse(array_map(
            static fn (Organ $organ): array => [
                'id' => $organ->getId(),
                'abbr' => $organ->getAbbr(),
                'name' => $organ->getName(),
            ],
            $this->organRepository->findActive(),
        ));
    }

    private function viewablePhoto(int $photo): Photo
    {
        $photoEntity = $this->photoRepository->find($photo);
        if (
            null === $photoEntity
            || !$this->isGranted(
                PhotoVoter::VIEW,
                $photoEntity,
            )
        ) {
            throw new NotFoundHttpException();
        }

        return $photoEntity;
    }

    /**
     * @return array{0: ?float, 1: ?float}
     */
    private function coordinates(
        mixed $x,
        mixed $y,
    ): array {
        return [
            is_numeric($x) ? (float) $x : null,
            is_numeric($y) ? (float) $y : null,
        ];
    }

    private function member(): Member
    {
        $user = $this->getUser();
        assert($user instanceof User);

        return $user->getMember();
    }
}
