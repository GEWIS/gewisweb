<?php

namespace Frontpage\Service;

use Activity\Model\Activity;
use Application\Service\AbstractAclService;
use Company\Service\Company;
use DateTime;
use Decision\Service\Member;
use Doctrine\Common\Proxy\Exception\InvalidArgumentException;
use Frontpage\Model\NewsItem;
use Laminas\Mvc\I18n\Translator;
use Laminas\Permissions\Acl\Acl;
use Photo\Mapper\Tag;
use Photo\Service\Photo;
use User\Model\User;

/**
 * Frontpage service.
 */
class Frontpage extends AbstractAclService
{
    /**
     * @var Translator
     */
    private $translator;

    /**
     * @var User|string
     */
    private $userRole;

    /**
     * @var Acl
     */
    private $acl;

    /**
     * @var Poll
     */
    private $pollService;

    /**
     * @var News
     */
    private $newsService;

    /**
     * @var Member
     */
    private $memberService;

    /**
     * @var Company
     */
    private $companyService;

    /**
     * @var Photo
     */
    private $photoService;

    /**
     * @var Tag
     */
    private $tagMapper;

    /**
     * @var \Activity\Mapper\Activity
     */
    private $activityMapper;

    /**
     * @var array
     */
    private $frontpageConfig;

    public function __construct(
        Translator $translator,
        $userRole,
        Acl $acl,
        Poll $pollService,
        News $newsService,
        Member $memberService,
        Company $companyService,
        Photo $photoService,
        Tag $tagMapper,
        \Activity\Mapper\Activity $activityMapper,
        array $frontpageConfig
    ) {
        $this->translator = $translator;
        $this->userRole = $userRole;
        $this->acl = $acl;
        $this->pollService = $pollService;
        $this->newsService = $newsService;
        $this->memberService = $memberService;
        $this->companyService = $companyService;
        $this->photoService = $photoService;
        $this->tagMapper = $tagMapper;
        $this->activityMapper = $activityMapper;
        $this->frontpageConfig = $frontpageConfig;
    }

    public function getRole()
    {
        return $this->userRole;
    }

    /**
     * Get the translator.
     *
     * @return Translator
     */
    public function getTranslator()
    {
        return $this->translator;
    }

    /**
     * Retrieves all data which is needed on the home page.
     */
    public function getHomePageData()
    {
        $birthdayInfo = $this->getBirthdayInfo();
        $activities = $this->getUpcomingActivities();
        $weeklyPhoto = $this->photoService->getCurrentPhotoOfTheWeek();
        $poll = $this->pollService->getNewestPoll();
        $pollDetails = $this->pollService->getPollDetails($poll);
        $pollDetails['poll'] = $poll;
        $news = $this->getNewsItems();
        $companyBanner = $this->companyService->getCurrentBanner();

        return [
            'birthdays' => $birthdayInfo['birthdays'],
            'birthdayTag' => $birthdayInfo['tag'],
            'activities' => $activities,
            'weeklyPhoto' => $weeklyPhoto,
            'poll' => $pollDetails,
            'news' => $news,
            'companyBanner' => $companyBanner,
        ];
    }

    /**
     * Retrieves all birthdays happening today, which should be shown on the home page.
     * Includes the age and a recent tag of the most active member whom has a birthday.
     *
     * @return array
     */
    public function getBirthdayInfo()
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
    public function getNewsItems()
    {
        $count = $this->frontpageConfig['news_count'];
        $activities = $this->getUpcomingActivities();
        $newsItems = $this->newsService->getLatestNewsItems($count);
        $news = array_merge($activities, $newsItems);
        usort($news, function ($a, $b) {
            if (($a instanceof NewsItem) && ($b instanceof NewsItem)) {
                if ($a->getPinned() === $b->getPinned()) {
                    return $this->getItemTimestamp($a) - $this->getItemTimestamp($b);
                }

                return $a->getPinned() ? -1 : 1;
            }

            if (($a instanceof Activity) && ($b instanceof Activity)) {
                return $this->getItemTimestamp($a) - $this->getItemTimestamp($b);
            }

            return $a instanceof Activity ? 1 : -1;
        });

        return array_slice($news, 0, $count);
    }

    /**
     * Get a time stamp of a news item or activity for sorting.
     *
     * @param Activity|NewsItem $item
     *
     * @return int
     */
    public function getItemTimestamp($item)
    {
        $now = (new DateTime())->getTimestamp();
        if ($item instanceof Activity) {
            return abs($item->getBeginTime()->getTimestamp() - $now);
        }

        if ($item instanceof NewsItem) {
            return abs($item->getDate()->getTimeStamp() - $now);
        }

        throw new InvalidArgumentException('The given item is neither an Activity or a NewsItem');
    }

    public function getUpcomingActivities()
    {
        $count = $this->frontpageConfig['activity_count'];

        return $this->activityMapper->getUpcomingActivities($count);
    }

    /**
     * Get the Acl.
     *
     * @return Acl
     */
    public function getAcl()
    {
        return $this->acl;
    }

    /**
     * Get the default resource ID.
     *
     * @return string
     */
    protected function getDefaultResourceId()
    {
        return 'frontpage';
    }
}
