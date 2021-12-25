<?php

namespace Frontpage\Service;

use Activity\Mapper\Activity as ActivityMapper;
use Activity\Model\Activity as ActivityModel;
use Company\Service\Company as CompanyService;
use DateTime;
use Decision\Service\Member as MemberService;
use Frontpage\Model\NewsItem as NewsItemModel;
use Frontpage\Service\{
    News as NewsService,
    Poll as PollService,
};
use Laminas\Mvc\I18n\Translator;
use Photo\Mapper\Tag as TagMapper;
use Photo\Service\Photo as PhotoService;

/**
 * Frontpage service.
 */
class Frontpage
{
    /**
     * @var Translator
     */
    private Translator $translator;

    /**
     * @var PollService
     */
    private Poll $pollService;

    /**
     * @var NewsService
     */
    private News $newsService;

    /**
     * @var MemberService
     */
    private MemberService $memberService;

    /**
     * @var CompanyService
     */
    private CompanyService $companyService;

    /**
     * @var PhotoService
     */
    private PhotoService $photoService;

    /**
     * @var TagMapper
     */
    private TagMapper $tagMapper;

    /**
     * @var ActivityMapper
     */
    private ActivityMapper $activityMapper;

    /**
     * @var array
     */
    private array $frontpageConfig;

    /**
     * @var array
     */
    private array $photoConfig;

    /**
     * @param Translator $translator
     * @param Poll $pollService
     * @param News $newsService
     * @param MemberService $memberService
     * @param CompanyService $companyService
     * @param PhotoService $photoService
     * @param TagMapper $tagMapper
     * @param ActivityMapper $activityMapper
     * @param array $frontpageConfig
     * @param array $photoConfig
     */
    public function __construct(
        Translator $translator,
        Poll $pollService,
        News $newsService,
        MemberService $memberService,
        CompanyService $companyService,
        PhotoService $photoService,
        TagMapper $tagMapper,
        ActivityMapper $activityMapper,
        array $frontpageConfig,
        array $photoConfig,
    ) {
        $this->translator = $translator;
        $this->pollService = $pollService;
        $this->newsService = $newsService;
        $this->memberService = $memberService;
        $this->companyService = $companyService;
        $this->photoService = $photoService;
        $this->tagMapper = $tagMapper;
        $this->activityMapper = $activityMapper;
        $this->frontpageConfig = $frontpageConfig;
        $this->photoConfig = $photoConfig;
    }

    /**
     * Get the translator.
     *
     * @return Translator
     */
    public function getTranslator(): Translator
    {
        return $this->translator;
    }

    /**
     * Retrieves all data which is needed on the home page.
     *
     * @return array
     */
    public function getHomePageData(): array
    {
        $birthdayInfo = $this->getBirthdayInfo();
        $activities = $this->getUpcomingActivities();
        $weeklyPhoto = $this->photoService->getCurrentPhotoOfTheWeek();
        $poll = $this->pollService->getNewestPoll();
        $pollDetails = $this->pollService->getPollDetails($poll);
        $pollDetails['poll'] = $poll;
        $news = $this->getNewsItems();
        $companyBanner = $this->companyService->getCurrentBanner();
        $photoConfig = $this->photoConfig;

        return [
            'birthdays' => $birthdayInfo['birthdays'],
            'birthdayPhoto' => $birthdayInfo['tag']?->getPhoto(),
            'activities' => $activities,
            'weeklyPhoto' => $weeklyPhoto?->getPhoto(),
            'poll' => $pollDetails,
            'news' => $news,
            'companyBanner' => $companyBanner,
            'photoConfig' => $photoConfig,
        ];
    }

    /**
     * Retrieves all birthdays happening today, which should be shown on the home page.
     * Includes the age and a recent tag of the most active member whom has a birthday.
     *
     * @return array
     */
    public function getBirthdayInfo(): array
    {
        $birthdayMembers = $this->memberService->getBirthdayMembers();
        $today = new DateTime();
        $birthdays = [];
        $members = [];
        foreach ($birthdayMembers as $member) {
            $age = $today->diff($member->getBirth())->y;
            $members[] = $member;
            //TODO: check member's privacy settings
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
     * @return array
     */
    public function getNewsItems(): array
    {
        $count = $this->frontpageConfig['news_count'];
        $activities = $this->getUpcomingActivities();
        $newsItems = $this->newsService->getLatestNewsItems($count);
        $news = array_merge($activities, $newsItems);
        usort($news, function ($a, $b) {
            if (($a instanceof NewsItemModel) && ($b instanceof NewsItemModel)) {
                if ($a->getPinned() === $b->getPinned()) {
                    return $this->getItemTimestamp($a) - $this->getItemTimestamp($b);
                }

                return $a->getPinned() ? -1 : 1;
            }

            if (($a instanceof ActivityModel) && ($b instanceof ActivityModel)) {
                return $this->getItemTimestamp($a) - $this->getItemTimestamp($b);
            }

            return $a instanceof ActivityModel ? 1 : -1;
        });

        return array_slice($news, 0, $count);
    }

    /**
     * Get a time stamp of a news item or activity for sorting.
     *
     * @param ActivityModel|NewsItemModel $item
     *
     * @return int
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
     * @return array
     */
    public function getUpcomingActivities(): array
    {
        return $this->activityMapper->getUpcomingActivities($this->frontpageConfig['activity_count']);
    }
}
