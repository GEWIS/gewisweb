<?php

declare(strict_types=1);

namespace App\Service\Frontpage;

use App\Entity\Decision\Member;
use App\Entity\Photo\Photo;
use App\Entity\Photo\WeeklyPhoto;
use App\Repository\Decision\MemberRepository;
use App\Repository\Photo\MemberTagRepository;
use App\Repository\Photo\WeeklyPhotoRepository;
use App\Service\Application\FileStorage;
use App\Service\Photo\WeeklyPhotoService;
use App\Service\User\PrivacyService;
use DateTime;

use function array_map;
use function array_values;

/**
 * Gathers the home-page blocks: the current photo of the week (with the public path the anonymous frontpage serves it
 * from, when that copy exists) and today's birthdays with the most-tagged member's photo.
 */
final readonly class HomePageService
{
    public function __construct(
        private WeeklyPhotoRepository $weeklyPhotoRepository,
        private MemberRepository $memberRepository,
        private MemberTagRepository $memberTagRepository,
        private WeeklyPhotoService $weeklyPhotoService,
        private FileStorage $fileStorage,
        private PrivacyService $privacyService,
    ) {
    }

    /**
     * @return array{
     *     weeklyPhoto: WeeklyPhoto|null,
     *     weeklyPublicPath: string|null,
     *     birthdayPhoto: Photo|null,
     *     birthdays: list<array{member: Member, age: int|null}>,
     * }
     */
    public function getHomePageData(): array
    {
        $weeklyPhoto = $this->weeklyPhotoRepository->getCurrentPhotoOfTheWeek();

        $birthdayMembers = $this->memberRepository->findBirthdayMembers(0);
        // The age is withheld (null) when the current viewer may not see this member's year of birth; the member still
        // appears on the panel, only ageless.
        $ageVisibility = $this->privacyService->yearOfBirthVisibilityFor($birthdayMembers);
        $birthdays = array_values(array_map(
            static fn (Member $member): array => [
                'member' => $member,
                'age' => $ageVisibility[$member->getLidnr()] ?? false
                    ? new DateTime()->diff($member->getBirth())->y
                    : null,
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
                : $this->publicPathIfAvailable($weeklyPhoto),
            'birthdayPhoto' => $birthdayTag?->getPhoto(),
            'birthdays' => $birthdays,
        ];
    }

    /**
     * The public copy's path for the anonymous frontpage, but only when that copy actually exists (it is written by the
     * weekly command); otherwise null, so a logged-out visitor is never shown a broken image.
     */
    private function publicPathIfAvailable(WeeklyPhoto $weeklyPhoto): ?string
    {
        $path = $this->weeklyPhotoService->publicPathFor($weeklyPhoto->getPhoto());

        return $this->fileStorage->exists($path)
            ? $path
            : null;
    }
}
