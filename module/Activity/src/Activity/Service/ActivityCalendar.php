<?php

namespace Activity\Service;

use Activity\Form\ActivityCalendarOption;
use Activity\Form\ActivityCalendarProposal;
use Activity\Mapper\ActivityOptionProposal;
use Activity\Mapper\MaxActivities;
use Activity\Model\ActivityCalendarOption as OptionModel;
use Activity\Model\ActivityOptionCreationPeriod;
use Activity\Model\ActivityOptionProposal as ProposalModel;
use Application\Service\AbstractAclService;
use Application\Service\Email;
use DateInterval;
use DateTime;
use Decision\Mapper\Member;
use Decision\Service\Organ;
use Exception;
use User\Permissions\NotAllowedException;
use Zend\Permissions\Acl\Acl;

class ActivityCalendar extends AbstractAclService
{

    /**
     * Gets all future options
     *
     */
    public function getUpcomingOptions()
    {
        return $this->getActivityCalendarOptionMapper()->getUpcomingOptions(false);
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

    /**
     * Get the member mapper.
     *
     * @return Member
     */
    public function getMemberMapper()
    {
        return $this->sm->get('decision_mapper_member');
    }

    public function sendOverdueNotifications()
    {
        $date = new DateTime();
        $date->sub(new DateInterval('P3W'));
        $oldOptions = $this->getActivityCalendarOptionMapper()->getPastOptions($date);
        if (!empty($oldOptions)) {
            $this->getEmailService()->sendEmail('activity_calendar', 'email/options-overdue',
                'Activiteiten kalender opties verlopen | Activity calendar options expired', ['options' => $oldOptions]);
        }
    }

    /**
     * Get the email service
     *
     * @return Email
     */
    public function getEmailService()
    {
        return $this->sm->get('application_service_email');
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
     * @return ActivityCalendarOption
     */
    public function getCreateOptionForm()
    {
        if (!$this->isAllowed('create')) {
            throw new NotAllowedException(
                $this->getTranslator()->translate('Not allowed to create activity options.')
            );
        }

        return $this->sm->get('activity_form_calendar_option');
    }

    /**
     * @param $data
     * @return ProposalModel|bool
     * @throws Exception
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

        $proposal->setCreationTime(new DateTime());
        $em = $this->getEntityManager();
        $proposal->setCreator($this->sm->get('user_service_user')->getIdentity());
        $name = $validatedData['name'];
        $proposal->setName($name);
        $description = $validatedData['description'];
        $proposal->setDescription($description);
//        See /Activity/Form/ActivityCalendarProposal for more details on the definition of these options
        if ($organ > -1) {
            $proposal->setOrgan($this->sm->get('decision_service_organ')->getOrgan($organ));
        } elseif ($organ == -1) {
            $proposal->setOrganAlt("Board");
        } elseif ($organ == -2) {
            $proposal->setOrganAlt("Other");
        }
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
     * Retrieves the form for creating a new calendar activity option proposal.
     *
     * @return ActivityCalendarProposal
     */
    public function getCreateProposalForm()
    {
        if (!$this->isAllowed('create')) {
            throw new NotAllowedException(
                $this->getTranslator()->translate('Not allowed to create activity proposals.')
            );
        }

        return $this->sm->get('activity_form_calendar_proposal');
    }

    /**
     * Returns whether an organ may create a new activity proposal
     *
     * @param int $organId
     * @return bool
     * @throws Exception
     */
    protected function canOrganCreateProposal($organId)
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

        if ($organId == null) {
            return false;
        }

        $max = $this->getMaxActivities($organId, $period->getId());
        $count = $this->getCurrentProposalCount($period, $organId);

        if ($count >= $max) {
            return false;
        }

        return true;
    }

    /**
     * Get the current ActivityOptionCreationPeriod
     *
     * @return ActivityOptionCreationPeriod
     * @throws Exception
     */
    public function getCurrentPeriod()
    {
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
     * Get the max number of activity options an organ can create
     *
     * @param int $organId
     * @param int $periodId
     * @return int
     * @throws Exception
     */
    protected function getMaxActivities($organId, $periodId)
    {
        $mapper = $this->getMaxActivitiesMapper();
        $maxActivities = $mapper->getMaxActivityOptionsByOrganPeriod($organId, 1);
        $max = 0;
        if ($maxActivities) {
            $max = $maxActivities->getValue();
        }
        return $max;
    }

    /**
     * Get the max activities mapper
     *
     * @return MaxActivities
     */
    public function getMaxActivitiesMapper()
    {
        return $this->sm->get('activity_mapper_max_activities');
    }

    /**
     * Get the current proposal count of an organ for the given period
     *
     * @param $period
     * @param $organId
     * @return int
     */
    protected function getCurrentProposalCount($period, $organId)
    {
        $mapper = $this->getActivityOptionProposalMapper();
        $begin = $period->getBeginPlanningTime();
        $end = $period->getEndPlanningTime();
        $options = $mapper->getNonClosedProposalsWithinPeriodAndOrgan($begin, $end, $organId);
        return count($options);
    }

    /**
     * Get the period mapper
     *
     * @return ActivityOptionProposal
     */
    public function getActivityOptionProposalMapper()
    {
        return $this->sm->get('activity_mapper_option_proposal');
    }

    /**
     * Get the entity manager
     */
    public function getEntityManager()
    {
        return $this->sm->get('doctrine.entitymanager.orm_default');
    }

    /**
     * @param $data
     * @param ProposalModel $proposal
     * @return OptionModel|bool
     * @throws Exception
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

    public function toDateTime($value, $format = 'd/m/Y')
    {
        return DateTime::createFromFormat($format, $value);
    }

    public function approveOption($id)
    {
        if (!$this->canApproveOption()) {
            throw new NotAllowedException(
                $this->getTranslator()->translate('You are not allowed to approve this option')
            );
        }

        $mapper = $this->getActivityCalendarOptionMapper();
        $option = $mapper->find($id);

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
        if ($this->isAllowed('delete_all')) {
            return true;
        }

        if (!$this->isAllowed('delete_own')) {
            return false;
        }

        if ($option->getProposal()->getOrgan() === null
            && $option->getProposal()->getCreator()->getLidnr() === $this->sm->get('user_service_user')->getIdentity()->getLidnr()
        ) {
            return true;
        }

        $organ = $option->getProposal()->getOrgan();
        if ($organ !== null && $this->getOrganService()->canEditOrgan($organ)) {
            return true;
        }

        return false;
    }

    public function canApproveOption()
    {
        if ($this->isAllowed('approve_all')) {
            return true;
        }

        return false;
    }

    /**
     * Get the organ service
     *
     * @return Organ
     */
    public function getOrganService()
    {
        return $this->sm->get('decision_service_organ');
    }

    public function deleteOption($id)
    {
        $mapper = $this->getActivityCalendarOptionMapper();
        $option = $mapper->find($id);
        if (!$this->canDeleteOption($option)) {
            throw new NotAllowedException(
                $this->getTranslator()->translate('You are not allowed to delete this option')
            );
        }

        $em = $this->getEntityManager();
        $option->setModifiedBy($this->sm->get('user_service_user')->getIdentity());
        $option->setStatus('deleted');
        $em->flush();
    }

    /**
     * Returns whether a user may create an option with given start time
     *
     * @param DateTime $beginTime
     * @return bool
     * @throws Exception
     */
    public function canCreateOption($beginTime)
    {
        if ($this->isAllowed('create_always')) {
            return true;
        }

        $period = $this->getCurrentPeriod();
        $begin = $period->getBeginOptionTime();
        $end = $period->getEndOptionTime();

        if ($begin > $beginTime) {
            return false;
        }
        if ($beginTime > $end) {
            return false;
        }

        return true;
    }

    /**
     * Returns whether a member may create a new activity proposal
     *
     * @return bool
     * @throws Exception
     */
    public function canCreateProposal()
    {
        $organs = $this->getEditableOrgans();

        return (!empty($organs));
    }

    /**
     * Retrieves all organs which the current user is allowed to edit and for which the organs can still create proposals
     *
     * @return array
     * @throws Exception
     */
    public function getEditableOrgans()
    {
        $allOrgans = $this->getOrganService()->getEditableOrgans();
        $organs = array();
        foreach ($allOrgans as $organ) {
            $organId = $organ->getId();
            if ($this->canOrganCreateProposal($organId)) {
                array_push($organs, $organ);
            }
        }
        return $organs;
    }

    /**
     * Get the Acl.
     *
     * @return Acl
     */
    public function getAcl()
    {
        return $this->sm->get('activity_acl');
    }

    /**
     * Get the current ActivityOptionCreationPeriod
     *
     * @param int $proposalId
     * @param int $organId
     * @return int
     */
    protected function getCurrentProposalOptionCount($proposalId, $organId)
    {
        $mapper = $this->getActivityCalendarOptionMapper();
        $options = $mapper->findOptionsByProposalAndOrgan($proposalId, $organId);
        return count($options);
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
}
