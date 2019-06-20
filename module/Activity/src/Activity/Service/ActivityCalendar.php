<?php

namespace Activity\Service;

use Activity\Model\ActivityCalendarOption;
use Activity\Model\ActivityCalendarOption as OptionModel;
use Application\Service\AbstractAclService;
use Activity\Model\ActivityOptionProposal as ProposalModel;
use Decision\Model\Organ as OrganModel;

class ActivityCalendar extends AbstractAclService
{

    /**
     * Gets all future options
     *
     */
    public function getUpcomingOptions()
    {
        return $this->getActivityCalendarOptionMapper()->getUpcomingOptions(true);
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

        return $this->getActivityCalendarOptionMapper()->getUpcomingOptionsByOrgans(
            $this->getMemberMapper()->findOrgans($user->getMember())
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
        $form->setData($data);

        if (!$form->isValid()) {
            return false;
        }
        $validatedData = $form->getData();

        $organ = $validatedData['organ'];
        if (!$this->canOrganCreateProposal($organ)) {
            return false;
        }

        $proposal->setCreationTime(new \DateTime());
        $em = $this->getEntityManager();
        $proposal->setCreator($this->sm->get('user_service_user')->getIdentity());
        $name = $validatedData['name'];
        $proposal->setName($name);
        $description = $validatedData['description'];
        $proposal->setDescription($description);
        $proposal->setOrgan($this->sm->get('decision_service_organ')->getOrgan($organ));
        $em->persist($proposal);
        $em->flush();

        $options = $validatedData['options'];
        foreach ($options as $option) {
            $result = $this->createOption($option, $proposal);
            if ($result == false) {
                return false;
            }
        }

        return $proposal;
    }

    /**
     * @param $data
     * @param int $proposal_id
     * @return OptionModel|bool
     * @throws \Exception
     */
    public function createOption($data, $proposal)
    {
//        $form = $this->getCreateOptionForm();
        $option = new OptionModel();
//        $form->setData($data);
//
//        if (!$form->isValid()) {
//            return false;
//        }
//        $validatedData = $form->getData();
        $validatedData = $data;

        $em = $this->getEntityManager();
        $option->setProposal($proposal);
        $beginTime = $this->toDateTime($validatedData['beginTime']);
        $option->setBeginTime($beginTime);
        $endTime = $this->toDateTime($validatedData['endTime']);
        $option->setEndTime($endTime);
        $type = $validatedData['type'];
        $option->setType($type);
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
     * Retrieves all organs which the current user is allowed to edit and for which the organs can still create proposals
     *
     * @return array
     * @throws \Exception
     */
    public function getEditableOrgans()
    {
        $all_organs = $this->getOrganService()->getEditableOrgans();
        $organs = array();
        foreach ($all_organs as $organ) {
            if ($this->canOrganCreateProposal($organ->getId())) {
                array_push($organs, $organ);
            }
        }
        return $organs;
    }

    /**
     * Returns whether an organ may create a new activity proposal
     *
     * @param int $organ_id
     * @return bool
     * @throws \Exception
     */
    protected function canOrganCreateProposal($organ_id)
    {
        if ($this->isAllowed('create_always')) {
            return true;
        }

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
     * Returns whether a user may create an option with given start time
     *
     * @param \DateTime $begin_time
     * @return bool
     * @throws \Exception
     */
    public function canCreateOption($begin_time)
    {
        if ($this->isAllowed('create_always')) {
            return true;
        }

        $period = $this->getCurrentPeriod();
        $begin = $period->getBeginOptionTime();
        $end = $period->getEndOptionTime();

        if ($begin > $begin_time) {
            return false;
        }
        if ($begin_time > $end) {
            return false;
        }

        return true;
    }

    /**
     * Returns whether a member may create a new activity proposal
     *
     * @return bool
     * @throws \Exception
     */
    public function canCreateProposal()
    {
        $organs = $this->getEditableOrgans();

        return (!empty($organs));
    }

    /**
     * Get the current ActivityOptionCreationPeriod
     *
     * @return \Activity\Model\ActivityOptionCreationPeriod
     * @throws \Exception
     */
    public function getCurrentPeriod() {
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
        $mapper = $this->getActivityOptionProposalMapper();
        $begin = $period->getBeginPlanningTime();
        $end = $period->getEndPlanningTime();
        $options = $mapper->getNonClosedProposalsWithinPeriodAndOrgan($begin, $end, $organ_id);
        return count($options);
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
        return count($options);
    }

    /**
     * Get the max number of activity options an organ can create
     *
     * @param int $organ_id
     * @param int $period_id
     * @return int
     * @throws \Exception
     */
    protected function getMaxActivities($organ_id, $period_id) {
        $mapper = $this->getMaxActivitiesMapper();
        $maxActivities = $mapper->getMaxActivityOptionsByOrganPeriod($organ_id, $period_id);
        $max = 0;
        if ($maxActivities != null) {
            $max = $maxActivities->getValue();
        }
        return $max;
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
     * Get the period mapper
     *
     * @return \Activity\Mapper\ActivityOptionProposal
     */
    public function getActivityOptionProposalMapper()
    {
        return $this->sm->get('activity_mapper_option_proposal');
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

    public function toDateTime($value, $format = 'd/m/Y')
    {
        return \DateTime::createFromFormat($format, $value);
    }
}
