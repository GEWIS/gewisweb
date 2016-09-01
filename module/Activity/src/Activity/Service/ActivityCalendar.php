<?php

namespace Activity\Service;

use Application\Service\AbstractService;

class ActivityCalendar extends AbstractService
{

    /**
     * Gets all future options
     *
     */
    public function getUpcomingOptions() {
        return $this->getActivityCalendarOptionMapper()->getUpcomingOptions();
    }

    /**
     * Get calendar configuration.
     *
     * @return array
     */
    public function getConfig()
    {
        $config = $this->sm->get('config');
        return $config['calendar'];
    }

    /**
     * Get the activity calendar option mapper.
     *
     * @return \Activity\Mapper\ActivityCalendarOption
     */
    public function getActivityCalendarOptionMapper()
    {
        return $this->sm->get('activity_mapper_calendar_option');
    }

}
