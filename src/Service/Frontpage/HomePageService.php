<?php

declare(strict_types=1);

namespace App\Service\Frontpage;

use App\Entity\Activity\Activity;
use App\Entity\Decision\Member;
use App\Entity\Frontpage\NewsItem;
use App\Entity\Frontpage\Poll;
use App\Entity\Photo\MemberTag;
use App\Entity\Photo\WeeklyPhoto;
use App\Entity\User\Enums\UserRoles;
use App\Repository\Activity\ActivityRepository;
use App\Repository\Decision\MemberRepository;
use App\Repository\Frontpage\NewsItemRepository;
use App\Repository\Frontpage\PollRepository;
use App\Repository\Photo\MemberTagRepository;
use App\Repository\Photo\WeeklyPhotoRepository;
use App\Service\Application\FileStorage;
use App\Service\Photo\WeeklyPhotoService;
use App\Service\User\PrivacyService;
use DateTime;
use Symfony\Bundle\SecurityBundle\Security;

use function array_map;
use function array_values;

/**
 * Gathers the home-page blocks: the news feed the page leads with, the agenda of what is coming up, the current photo
 * of the week (with the public path the anonymous frontpage serves it from, when that copy exists), today's birthdays
 * with a photo of each of them there is one of, and whatever the association is being asked at the moment.
 */
final readonly class HomePageService
{
    private const int NEWS_LIMIT = 6;

    public function __construct(
        private ActivityRepository $activityRepository,
        private NewsItemRepository $newsItemRepository,
        private PollRepository $pollRepository,
        private WeeklyPhotoRepository $weeklyPhotoRepository,
        private MemberRepository $memberRepository,
        private MemberTagRepository $memberTagRepository,
        private WeeklyPhotoService $weeklyPhotoService,
        private FileStorage $fileStorage,
        private PrivacyService $privacyService,
        private Security $security,
    ) {
    }

    /**
     * @return array{
     *     activities: Activity[],
     *     newsFeed: NewsItem[],
     *     currentPoll: Poll|null,
     *     weeklyPhoto: WeeklyPhoto|null,
     *     weeklyPublicPath: string|null,
     *     birthdayTags: MemberTag[],
     *     birthdays: list<array{member: Member, age: int|null}>,
     * }
     */
    public function getHomePageData(): array
    {
        $weeklyPhoto = $this->weeklyPhotoRepository->getCurrentPhotoOfTheWeek();

        // Who has a birthday today is only shown to members, and finding out costs a date calculation over every
        // member plus a photo lookup for each of them, so a passer-by is not made to pay for a panel they never see.
        $birthdayMembers = $this->security->isGranted(UserRoles::User->value)
            ? $this->memberRepository->findBirthdayMembers(0)
            : [];
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

        return [
            'activities' => $this->activityRepository->findUpcoming(),
            'newsFeed' => $this->newsItemRepository->findFeed(limit: self::NEWS_LIMIT),
            'currentPoll' => $this->pollRepository->findCurrentPoll(),
            'weeklyPhoto' => $weeklyPhoto,
            'weeklyPublicPath' => null === $weeklyPhoto
                ? null
                : $this->publicPathIfAvailable($weeklyPhoto),
            'birthdayTags' => $this->memberTagRepository->findMostRecentTagPerMember($birthdayMembers),
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
