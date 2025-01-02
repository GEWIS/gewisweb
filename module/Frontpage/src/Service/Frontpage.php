<?php

declare(strict_types=1);

namespace Frontpage\Service;

use Activity\Mapper\Activity as ActivityMapper;
use Activity\Model\Activity as ActivityModel;
use Company\Model\CompanyBannerPackage as CompanyBannerPackageModel;
use Company\Service\Company as CompanyService;
use DateTime;
use Decision\Model\Member as MemberModel;
use Decision\Service\AclService;
use Decision\Service\Member as MemberService;
use Frontpage\Model\NewsItem as NewsItemModel;
use Frontpage\Model\Poll as PollModel;
use Frontpage\Model\PollVote as PollVoteModel;
use Laminas\Mvc\I18n\Translator;
use Photo\Mapper\Tag as TagMapper;
use Photo\Model\Photo as PhotoModel;
use Photo\Model\Tag as TagModel;
use Photo\Model\WeeklyPhoto as WeeklyPhotoModel;
use Photo\Service\Photo as PhotoService;

use function abs;
use function array_merge;
use function array_slice;
use function count;
use function shuffle;
use function usort;

/**
 * Frontpage service.
 *
 * @psalm-type BirthdaysArrayType = array<array-key, array{
 *     member: MemberModel,
 *     age: int,
 * }>
 */
class Frontpage
{
    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
     */
    public function __construct(
        private readonly Translator $translator,
        private readonly AclService $aclService,
        private readonly Poll $pollService,
        private readonly News $newsService,
        private readonly MemberService $memberService,
        private readonly CompanyService $companyService,
        private readonly PhotoService $photoService,
        private readonly TagMapper $tagMapper,
        private readonly ActivityMapper $activityMapper,
        private readonly array $frontpageConfig,
        private readonly array $photoConfig,
    ) {
    }

    /**
     * Get the translator.
     */
    public function getTranslator(): Translator
    {
        return $this->translator;
    }

    /**
     * Retrieves all data which is needed on the home page.
     *
     * @return array{
     *     birthdays: BirthdaysArrayType,
     *     birthdayPhoto: ?PhotoModel,
     *     activities: ActivityModel[],
     *     weeklyPhoto: ?WeeklyPhotoModel,
     *     poll: array{
     *         canVote: bool,
     *         poll: ?PollModel,
     *         userVote: ?PollVoteModel,
     *     },
     *     news: array<array-key, ActivityModel|NewsItemModel>,
     *     companyBanner: ?CompanyBannerPackageModel,
     *     photoConfig: mixed[],
     * }
     */
    public function getHomePageData(): array
    {
        $birthdayInfo = $this->getBirthdayInfo();
        $activities = $this->getUpcomingActivities();
        $weeklyPhoto = $this->photoService->getCurrentPhotoOfTheWeek();
        $poll = $this->pollService->getNewestPoll();
        $pollDetails = $this->pollService->getPollDetails($poll);
        $pollDetails['poll'] = $poll;
        $news = $this->getNewsItems($activities);
        $companyBanner = $this->companyService->getCurrentBanner();

        return [
            'birthdays' => $birthdayInfo['birthdays'],
            'birthdayPhoto' => $birthdayInfo['tag']?->getPhoto(),
            'activities' => $activities,
            'weeklyPhoto' => $weeklyPhoto,
            'poll' => $pollDetails,
            'news' => $news,
            'companyBanner' => $companyBanner,
            'photoConfig' => $this->photoConfig,
        ];
    }

    /**
     * Retrieves all birthdays happening today, which should be shown on the home page.
     * Includes the age and a recent tag of the most active member whom has a birthday.
     *
     * @return array{
     *     birthdays: BirthdaysArrayType,
     *     tag: ?TagModel,
     * }
     */
    public function getBirthdayInfo(): array
    {
        if (!$this->aclService->isAllowed('birthdays', 'member')) {
            return [
                'birthdays' => [],
                'tag' => null,
            ];
        }

        $birthdayMembers = $this->memberService->getBirthdayMembers();
        $today = new DateTime();
        $birthdays = [];
        $members = [];
        foreach ($birthdayMembers as $member) {
            $age = $today->diff($member->getBirth())->y;
            $members[] = $member;
            //TODO: check member's privacy settings
            // getBirthdayMembers() already takes hidden members into account
            $birthdays[] = ['member' => $member, 'age' => $age];
        }

        $tag = $this->tagMapper->getMostActiveMemberTag($members);

        return [
            'birthdays' => $birthdays,
            'tag' => $tag,
        ];
    }

    /**
     * Returns a mixed array of news items and activities to display in the
     * news section.
     *
     * @param ActivityModel[] $activities
     *
     * @return array<array-key, ActivityModel|NewsItemModel>
     */
    public function getNewsItems(array $activities): array
    {
        $count = $this->frontpageConfig['news_count'];
        $newsItems = $this->newsService->getLatestNewsItems($count);

        usort($newsItems, function ($a, $b) {
            if ($a->getPinned() === $b->getPinned()) {
                return $this->getItemTimestamp($a) - $this->getItemTimestamp($b);
            }

            return $a->getPinned() ? -1 : 1;
        });

        $newsCount = count($newsItems);

        if ($newsCount < $count) {
            $remainingCount = $count - $newsCount;
            shuffle($activities);
            $newsItems = array_merge($newsItems, array_slice($activities, 0, $remainingCount));
        }

        return $newsItems;
    }

    /**
     * Get a time stamp of a news item or activity for sorting.
     */
    public function getItemTimestamp(ActivityModel|NewsItemModel $item): int
    {
        $now = (new DateTime())->getTimestamp();

        if ($item instanceof ActivityModel) {
            return abs($item->getBeginTime()->getTimestamp() - $now);
        }

        return abs($item->getDate()->getTimeStamp() - $now);
    }

    /**
     * @return ActivityModel[]
     */
    public function getUpcomingActivities(): array
    {
        return $this->activityMapper->getUpcomingActivities($this->frontpageConfig['activity_count']);
    }
}
