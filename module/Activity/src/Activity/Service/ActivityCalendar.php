<?php

namespace Activity\Service;

use Activity\Form\ActivityCalendarOption;
use Application\Service\AbstractAclService;
use Activity\Model\ActivityCalendarOption as OptionModel;
class ActivityCalendar extends AbstractAclService
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

    /**
     * Retrieves the form for creating a new calendar option.
     *
     * @return \Activity\Form\ActivityCalendarOption
     */
    public function getCreateOptionForm()
    {
        if (!$this->isAllowed('create')) {
            throw new \User\Permissions\NotAllowedException(
                $this->getTranslator()->translate('Not allowed to create activity options.')
            );
        }
        return $this->sm->get('activity_form_calendar_option');
    }


    /**
     * @param $data
     * @return OptionModel|bool
     */
    public function createOption($data)
    {
        $form = $this->getCreateOptionForm();
        $option = new OptionModel();
        $form->bind($option);
        $form->setData($data);

        if (!$form->isValid()) {
            return false;
        }
        if ($option->getOrgan() !== null && !$this->getOrganService()->canEditOrgan($option->getOrgan())) {
            throw new \User\Permissions\NotAllowedException(
                $this->getTranslator()->translate('You are not allowed to create options for this organ')
            );
        }
        $option->setCreationTime(new \DateTime());
        $em = $this->getEntityManager();
        $option->setCreator($em->merge($this->sm->get('user_role')));
        $em->persist($option);
        $em->flush();
        $form->setData([]);
        return $option;
    }

    /**
     * Get the entity manager
     */
    public function getEntityManager()
    {
        return $this->sm->get('doctrine.entitymanager.orm_default');
    }

    /**
     * Get the organ service
     *
     * @return \Decision\Service\Organ
     */
    public function getOrganService()
    {
        return $this->sm->get('decision_service_organ');
    }

    /**
     * Get the default resource ID.
     *
     * @return string
     */
    protected function getDefaultResourceId()
    {
        return 'activity_calendar_option';
    }

    /**
     * Get the Acl.
     *
     * @return \Zend\Permissions\Acl\Acl
     */
    public function getAcl()
    {
        return $this->sm->get('activity_acl');
    }

}
