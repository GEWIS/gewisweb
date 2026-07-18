<?php

declare(strict_types=1);

namespace App\Service\User;

use App\Entity\Decision\Member;
use App\Repository\Activity\UserSignupRepository;
use App\Repository\Decision\AuthorizationRepository;
use App\Repository\Education\SummaryRepository;
use App\Repository\Frontpage\PollCommentRepository;
use App\Repository\Frontpage\PollRepository;
use App\Repository\Frontpage\PollVoteRepository;
use App\Repository\Photo\MemberTagRepository;
use App\Repository\Photo\PhotoRepository;
use App\Repository\Photo\ProfilePhotoRepository;
use App\Repository\Photo\VoteRepository;
use App\Repository\User\ExternalAppAuthenticationRepository;
use App\Repository\User\SessionRepository;
use App\Repository\User\UserRepository;

use function array_map;
use function strval;

/**
 * Gathers everything the association holds about a member into a single structure for a self-service data export. Each
 * domain entity describes itself through its own `toGdprArray()`; this service only walks the relations and collects
 * them.
 */
class GdprService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly ProfilePhotoRepository $profilePhotoRepository,
        private readonly SessionRepository $sessionRepository,
        private readonly ExternalAppAuthenticationRepository $externalAppAuthenticationRepository,
        private readonly UserSignupRepository $signupRepository,
        private readonly AuthorizationRepository $authorizationRepository,
        private readonly SummaryRepository $summaryRepository,
        private readonly MemberTagRepository $memberTagRepository,
        private readonly VoteRepository $voteRepository,
        private readonly PhotoRepository $photoRepository,
        private readonly PollVoteRepository $pollVoteRepository,
        private readonly PollCommentRepository $pollCommentRepository,
        private readonly PollRepository $pollRepository,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function collectMemberData(Member $member): array
    {
        $lidnr = $member->getLidnr();
        $user = $this->userRepository->find($lidnr);
        $profilePhoto = $this->profilePhotoRepository->getProfilePhotoByLidnr($lidnr);

        return [
            'member' => [
                'information' => $member->toGdprArray(),
                'account' => $user?->toGdprArray(),
                'profile_photo' => $profilePhoto?->toGdprArray(),
                'addresses' => array_map(
                    static fn ($address) => $address->toGdprArray(),
                    $member->getAddresses()->toArray(),
                ),
                'mailing_lists' => array_map(
                    static fn ($membership) => $membership->toGdprArray(),
                    $member->getMailingListMemberships()->toArray(),
                ),
                'sessions' => array_map(
                    static fn ($session) => $session->toGdprArray(),
                    $this->sessionRepository->findAllByUser(strval($lidnr)),
                ),
                'external_applications' => array_map(
                    static fn ($authentication) => $authentication->toGdprArray(),
                    $this->externalAppAuthenticationRepository->getMemberAuthenticationsPerExternalApp($member),
                ),
            ],
            'activities' => [
                'signups' => array_map(
                    static fn ($signup) => $signup->toGdprArray(),
                    $this->signupRepository->findSignupsByMember($member),
                ),
            ],
            'decisions' => [
                'authorizations_given' => array_map(
                    static fn ($authorization) => $authorization->toGdprArray(),
                    $this->authorizationRepository->findByMember(
                        $member,
                        true,
                    ),
                ),
                'authorizations_received' => array_map(
                    static fn ($authorization) => $authorization->toGdprArray(),
                    $this->authorizationRepository->findByMember(
                        $member,
                        false,
                    ),
                ),
            ],
            'education' => [
                'authored_documents' => array_map(
                    static fn ($summary) => $summary->toGdprArray(),
                    $this->summaryRepository->findSummariesByAuthor($member),
                ),
            ],
            'photos' => [
                'tags' => array_map(
                    static fn ($tag) => $tag->toGdprArray(),
                    $this->memberTagRepository->getTagsByLidnr($lidnr),
                ),
                'votes' => array_map(
                    static fn ($vote) => $vote->toGdprArray(),
                    $this->voteRepository->getVotesByLidnr($lidnr),
                ),
                'taken' => array_map(
                    static fn ($photo) => $photo->toGdprArray(),
                    $this->photoRepository->findPhotosByMember($member),
                ),
            ],
            'polls' => [
                'votes' => array_map(
                    static fn ($vote) => $vote->toGdprArray(),
                    $this->pollVoteRepository->findVotesByMember($member),
                ),
                'comments' => array_map(
                    static fn ($comment) => $comment->toGdprArray(),
                    $this->pollCommentRepository->findByMember($member),
                ),
                'created' => array_map(
                    static fn ($poll) => $poll->toGdprArray(),
                    $this->pollRepository->findPollsCreatedByMember($member),
                ),
                'approved' => array_map(
                    static fn ($poll) => $poll->toGdprArray(),
                    $this->pollRepository->findPollsApprovedByMember($member),
                ),
            ],
        ];
    }
}
