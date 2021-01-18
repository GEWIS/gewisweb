<?php

namespace Frontpage\Service;

use Activity\Form\ActivityCalendarProposal;
use Application\Service\AbstractAclService;
use Doctrine\Common\Proxy\Exception\InvalidArgumentException;
use Frontpage\Model\NewsItem;
use Activity\Model\Activity;

/**
 * Frontpage service.
 */
class Frontpage extends AbstractAclService
{

    /**
     * Retrieves all data which is needed on the home page
     */
    public function getHomePageData()
    {
        $birthdayInfo = $this->getBirthdayInfo();
        $activities = $this->getUpcomingActivities();
        $weeklyPhoto = $this->getPhotoService()->getCurrentPhotoOfTheWeek();
        $pollService = $this->getPollService();
        $poll = $pollService->getNewestPoll();
        $pollDetails = $pollService->getPollDetails($poll);
        $pollDetails['poll'] = $poll;
        $news = $this->getNewsItems();
        $companyBanner = $this->getCompanyService()->getCurrentBanner();

        return [
            'birthdays' => $birthdayInfo['birthdays'],
            'birthdayTag' => $birthdayInfo['tag'],
            'activities' => $activities,
            'weeklyPhoto' => $weeklyPhoto,
            'poll' => $pollDetails,
            'news' => $news,
            'companyBanner' => $companyBanner
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
        $birthdayMembers = $this->getMemberService()->getBirthdayMembers();
        $today = new \DateTime();
        $birthdays = [];
        $members = [];
        foreach ($birthdayMembers as $member) {
            $age = $today->diff($member->getBirth())->y;
            $members[] = $member;
            //TODO: check member's privacy settings
            $birthdays[] = ['member' => $member, 'age' => $age];
        }

        try {
            $tag = $this->getTagMapper()->getMostActiveMemberTag($members);
        } catch (\Doctrine\ORM\NoResultException $e) {
            $tag = null;
        }

        return [
            'birthdays' => $birthdays,
            'tag' => $tag
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
        $count = $this->getConfig()['news_count'];
        $activities = $this->getUpcomingActivities();
        $newsItems = $this->getNewsService()->getLatestNewsItems($count);
        $news = array_merge($activities, $newsItems);
        usort($news, function ($a, $b) {
            if (($a instanceof NewsItem) && ($b instanceof NewsItem)) {
                if ($a->getPinned() === $b->getPinned()) {
                    return ($this->getItemTimestamp($a) - $this->getItemTimestamp($b));
                }

                return $a->getPinned() ? -1 : 1;
            }

            if (($a instanceof Activity) && ($b instanceof Activity)) {
                return ($this->getItemTimestamp($a) - $this->getItemTimestamp($b));
            }

            return $a instanceof Activity ? 1 : -1;
        });

        return array_slice($news, 0, $count);
    }

    /**
     * Get a time stamp of a news item or activity for sorting
     *
     * @param $item
     * @return integer
     */
    public function getItemTimestamp($item)
    {
        $now = (new \DateTime())->getTimestamp();
        if ($item instanceof \Activity\Model\Activity) {
            return abs($item->getBeginTime()->getTimestamp() - $now);
        }

        if ($item instanceof \Frontpage\Model\NewsItem) {
            return abs($item->getDate()->getTimeStamp() - $now);
        }

        throw new InvalidArgumentException('The given item is neither an Activity or a NewsItem');
    }

    public function getUpcomingActivities()
    {
        $count = $this->getConfig()['activity_count'];
        $activities = $this->getActivityMapper()->getUpcomingActivities($count);

        return $activities;

    }

    /**
     * Get the frontpage config, as used by this service.
     *
     * @return array
     */
    public function getConfig()
    {
        $config = $this->sm->get('config');

        return $config['frontpage'];
    }

    /**
     * Get the photo module's tag mapper.
     *
     * @return \Photo\Mapper\Tag
     */
    public function getTagMapper()
    {
        return $this->sm->get('photo_mapper_tag');
    }

    /**
     * Get the activity module's activity mapper.
     *
     * @return \Activity\Mapper\Activity
     */
    public function getActivityMapper()
    {
        return $this->sm->get('activity_mapper_activity');
    }

    /**
     * Get the photo service.
     *
     * @return \Photo\Service\Photo
     */
    public function getPhotoService()
    {
        return $this->sm->get('photo_service_photo');
    }

    /**
     * Get the member service.
     *
     * @return \Decision\Service\Member
     */
    public function getMemberService()
    {
        return $this->sm->get('Decision_service_member');
    }

    /**
     * Get the poll service.
     *
     * @return \Frontpage\Service\Poll
     */
    public function getPollService()
    {
        return $this->sm->get('frontpage_service_poll');
    }

    /**
     * Get the news service.
     *
     * @return \Frontpage\Service\News
     */
    public function getNewsService()
    {
        return $this->sm->get('frontpage_service_news');
    }

    /**
     * Get the company service.
     *
     * @return \Company\Service\Company
     */
    public function getCompanyService()
    {
        return $this->sm->get('company_service_company');
    }

    /**
     * Get the Acl.
     *
     * @return \Zend\Permissions\Acl\Acl
     */
    public function getAcl()
    {
        return $this->getServiceManager()->get('frontpage_acl');
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
