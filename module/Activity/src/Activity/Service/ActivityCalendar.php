<?php

namespace Activity\Service;

use Activity\Model\ActivityCalendarOption;
use Activity\Model\ActivityCalendarOption as OptionModel;
use Application\Service\AbstractAclService;
use Activity\Model\ActivityOptionProposal as ProposalModel;

class ActivityCalendar extends AbstractAclService
{

    /**
     * Gets all future options
     *
     */
    public function getUpcomingOptions()
    {
        return $this->getActivityCalendarOptionMapper()->getUpcomingOptions();
    }

    /**
     * Gets all future options which the current user is allowed to edit/delete
     *
     */
    public function getEditableUpcomingOptions()
    {
        if (!$this->isAllowed('delete_own')) {
            return [];
        }
        if ($this->isAllowed('delete_all')) {
            // Return all
            return $this->getActivityCalendarOptionMapper()->getUpcomingOptions(true);
        }
        $user = $this->sm->get('user_service_user')->getIdentity();

        return $this->getActivityCalendarOptionMapper()->getUpcomingOptionsByOrganOrUser(
            $this->getMemberMapper()->findOrgans($user->getMember()),
            $user
        );
    }

    public function sendOverdueNotifications()
    {
        $date = new \DateTime();
        $date->sub(new \DateInterval('P3W'));
        $oldOptions = $this->getActivityCalendarOptionMapper()->getPastOptions($date);
        if (!empty($oldOptions)) {
            $this->getEmailService()->sendEmail('activity_calendar', 'email/options-overdue',
                'Activiteiten kalender opties verlopen | Activity calendar options expired', ['options' => $oldOptions]);
        }
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
     * Retrieves the form for creating a new calendar activity option proposal.
     *
     * @return \Activity\Form\ActivityCalendarProposal
     */
    public function getCreateProposalForm()
    {
        if (!$this->isAllowed('create')) {
            throw new \User\Permissions\NotAllowedException(
                $this->getTranslator()->translate('Not allowed to create activity proposals.')
            );
        }

        return $this->sm->get('activity_form_calendar_proposal');
    }

    /**
     * Retrieves the form for creating a new calendar activity option proposal.
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
     * @return ProposalModel|bool
     * @throws \Exception
     */
    public function createProposal($data)
    {
        $form = $this->getCreateProposalForm();
        $proposal = new ProposalModel();
        $form->bind($proposal);
        $form->setData($data);

        if (!$form->isValid()) {
            return false;
        }

        $proposal->setCreationTime(new \DateTime());
        $em = $this->getEntityManager();
        $proposal->setCreator($this->sm->get('user_service_user')->getIdentity());
        $proposal->setOrgan($form->get('organ'));
        $em->persist($proposal);
        $em->flush();

        return $proposal;
    }

    /**
     * @param $data
     * @return OptionModel|bool
     * @throws \Exception
     */
    public function createOption($data)
    {
        $proposal = null;
        $form = $this->getCreateOptionForm();
        $option = new OptionModel();
        $form->bind($option);
        $form->setData($data);

        if (!$form->isValid()) {
            return false;
        }

        $em = $this->getEntityManager();
        $option->setProposal($proposal);
        $em->persist($option);
        $em->flush();

        return $option;
    }

    public function deleteOption($id)
    {
        $mapper = $this->getActivityCalendarOptionMapper();
        $option = $mapper->find($id);
        if (!$this->canDeleteOption($option)) {
            throw new \User\Permissions\NotAllowedException(
                $this->getTranslator()->translate('You are not allowed to delete this option')
            );
        }

        $em = $this->getEntityManager();
        $option->setModifiedBy($this->sm->get('user_service_user')->getIdentity());
        $option->setStatus('deleted');
        $em->flush();
    }

    public function approveOption($id)
    {
        $mapper = $this->getActivityCalendarOptionMapper();
        $option = $mapper->find($id);
        if (!$this->canDeleteOption($option)) {
            throw new \User\Permissions\NotAllowedException(
                $this->getTranslator()->translate('You are not allowed to approve this option')
            );
        }

        $em = $this->getEntityManager();
        $option->setModifiedBy($this->sm->get('user_service_user')->getIdentity());
        $option->setStatus('approved');
        $em->flush();

        $proposal = $option->getProposal();
        $options = $mapper->findOptionsByProposal($proposal);

        foreach ($options as $option) {
            // Can't add two options at the same time
            if ($option->getStatus() == null) {
                $this->deleteOption($option->getId());
            }
        }
    }

    protected function canDeleteOption($option)
    {
        if (!$this->isAllowed('delete_own')) {
            return false;
        }

        if ($this->isAllowed('delete_all')) {
            return true;
        }

        if ($option->getOrgan() === null
            && $option->getCreator()->getLidnr() === $this->sm->get('user_service_user')->getIdentity()->getLidnr()
        ) {
            return true;
        }

        if ($this->getOrganService()->canEditOrgan($option->getOrgan())) {
            return true;
        }

        return false;
    }

    /**
     * Returns whether a member may create a new activity proposal
     *
     * @param int $organ_id
     * @return bool
     * @throws \Exception
     */
    protected function canCreateProposal($organ_id)
    {
        if (!$this->isAllowed('create')) {
            return false;
        }

        $period = $this->getCurrentPeriod();
        if ($period == null) {
            return false;
        }

        if ($organ_id == null) {
            return false;
        }

        $max = $this->getMaxActivities($organ_id, $period->getId());
        $count = $this->getCurrentProposalCount($period, $organ_id);

        if ($count > $max) {
            return false;
        }

        return true;
    }

    /**
     * Get the current ActivityOptionCreationPeriod
     *
     * @return \Activity\Model\ActivityOptionCreationPeriod
     * @throws \Exception
     */
    protected function getCurrentPeriod() {
        $mapper = $this->getActivityOptionCreationPeriodMapper();
        return $mapper->getCurrentActivityOptionCreationPeriod();
    }

    /**
     * Get the current proposal count of an organ for the given period
     *
     * @param \Activity\Model\ActivityOptionCreationPeriod
     * @return int
     */
    protected function getCurrentProposalCount($period, $organ_id) {
        $mapper = $this->getActivityCalendarOptionMapper();
        $begin = $period->getBeginPlanningTime();
        $end = $period->getEndPlanningTime();
        $options = $mapper->getOptionsWithinPeriodAndOrgan($begin, $end, $organ_id);
        return len($options);
    }

    /**
     * Get the current ActivityOptionCreationPeriod
     *
     * @param int $proposal_id
     * @param int $organ_id
     * @return int
     */
    protected function getCurrentProposalOptionCount($proposal_id, $organ_id) {
        $mapper = $this->getActivityCalendarOptionMapper();
        $options = $mapper->findOptionsByProposalAndOrgan($proposal_id, $organ_id);
        return len($options);
    }

    /**
     * Get the max number of activity options an organ can create
     *
     * @param \Decision\Service\Organ $organ
     * @return int
     * @throws \Exception
     */
    protected function getMaxActivities($organ) {
        $mapper = $this->getActivityOptionCreationPeriodMapper();
        return $mapper->getCurrentActivityOptionCreationPeriod();
    }

    /**
     * Get the period mapper
     *
     * @return \Activity\Mapper\ActivityOptionCreationPeriod
     */
    public function getActivityOptionCreationPeriodMapper()
    {
        return $this->sm->get('activity_mapper_period');
    }

    /**
     * Get the max activities mapper
     *
     * @return \Activity\Mapper\MaxActivities
     */
    public function getMaxActivitiesMapper()
    {
        return $this->sm->get('activity_mapper_max_activities');
    }

    /**
     * Get the entity manager
     */
    public function getEntityManager()
    {
        return $this->sm->get('doctrine.entitymanager.orm_default');
    }

    /**
     * Get the member mapper.
     *
     * @return \Decision\Mapper\Member
     */
    public function getMemberMapper()
    {
        return $this->sm->get('decision_mapper_member');
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
     * Get the email service
     *
     * @return \Application\Service\Email
     */
    public function getEmailService()
    {
        return $this->sm->get('application_service_email');
    }

    /**
     * Get the default resource ID.
     *
     * @return string
     */
    protected function getDefaultResourceId()
    {
        return 'activity_calendar_proposal';
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
