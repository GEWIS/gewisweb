<?php

declare(strict_types=1);

namespace App\Service\Frontpage;

use App\Entity\Decision\Member;
use App\Entity\Photo\Photo;
use App\Entity\Photo\WeeklyPhoto;
use App\Repository\Decision\MemberRepository;
use App\Repository\Photo\MemberTagRepository;
use App\Repository\Photo\WeeklyPhotoRepository;
use App\Service\Photo\WeeklyPhotoService;
use DateTime;

use function array_map;
use function array_values;

/**
 * Gathers the members-only home-page blocks: the current photo of the week (with the public path the anonymous
 * frontpage serves it from) and today's birthdays with the most-tagged member's photo.
 */
final readonly class HomePageService
{
    public function __construct(
        private WeeklyPhotoRepository $weeklyPhotoRepository,
        private MemberRepository $memberRepository,
        private MemberTagRepository $memberTagRepository,
        private WeeklyPhotoService $weeklyPhotoService,
    ) {
    }

    /**
     * @return array{
     *     weeklyPhoto: WeeklyPhoto|null,
     *     weeklyPublicPath: string|null,
     *     birthdayPhoto: Photo|null,
     *     birthdays: list<array{member: Member, age: int}>,
     * }
     */
    public function getHomePageData(): array
    {
        $weeklyPhoto = $this->weeklyPhotoRepository->getCurrentPhotoOfTheWeek();

        $birthdayMembers = $this->memberRepository->findBirthdayMembers(0);
        $birthdays = array_values(array_map(
            static fn (Member $member): array => [
                'member' => $member,
                'age' => new DateTime()->diff($member->getBirth())->y,
            ],
            $birthdayMembers,
        ));
        $birthdayTag = [] === $birthdayMembers
            ? null
            : $this->memberTagRepository->getMostActiveMemberTag($birthdayMembers);

        return [
            'weeklyPhoto' => $weeklyPhoto,
            'weeklyPublicPath' => null === $weeklyPhoto
                ? null
                : $this->weeklyPhotoService->publicPathFor($weeklyPhoto->getPhoto()),
            'birthdayPhoto' => $birthdayTag?->getPhoto(),
            'birthdays' => $birthdays,
        ];
    }
}
