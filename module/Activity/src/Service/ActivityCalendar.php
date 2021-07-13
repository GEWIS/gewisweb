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
use Doctrine\ORM\EntityManager;
use Exception;
use Laminas\Mvc\I18n\Translator;
use Laminas\Permissions\Acl\Acl;
use User\Permissions\NotAllowedException;
use User\Service\User;

class ActivityCalendar extends AbstractAclService
{
    /**
     * @var Translator
     */
    private $translator;

    /**
     * @var \User\Model\User|string
     */
    private $userRole;
    /**
     * @var Acl
     */
    private $acl;
    /**
     * @var EntityManager
     */
    private $entityManager;
    /**
     * @var User
     */
    private $userService;
    /**
     * @var Organ
     */
    private $organService;
    /**
     * @var Email
     */
    private $emailService;
    /**
     * @var \Activity\Mapper\ActivityCalendarOption
     */
    private $calendarOptionMapper;
    /**
     * @var ActivityOptionProposal
     */
    private $optionProposalMapper;
    /**
     * @var \Activity\Mapper\ActivityOptionCreationPeriod
     */
    private $periodMapper;
    /**
     * @var MaxActivities
     */
    private $maxActivitiesMapper;
    /**
     * @var Member
     */
    private $memberMapper;
    /**
     * @var ActivityCalendarOption
     */
    private $calendarOptionForm;
    /**
     * @var ActivityCalendarProposal
     */
    private $calendarProposalForm;

    public function __construct(
        Translator $translator,
        $userRole,
        Acl $acl,
        EntityManager $entityManager,
        User $userService,
        Organ $organService,
        Email $emailService,
        \Activity\Mapper\ActivityCalendarOption $calendarOptionMapper,
        ActivityOptionProposal $optionProposalMapper,
        \Activity\Mapper\ActivityOptionCreationPeriod $periodMapper,
        MaxActivities $maxActivitiesMapper,
        Member $memberMapper,
        ActivityCalendarOption $calendarOptionForm,
        ActivityCalendarProposal $calendarProposalForm
    )
    {
        $this->translator = $translator;
        $this->userRole = $userRole;
        $this->acl = $acl;
        $this->entityManager = $entityManager;
        $this->userService = $userService;
        $this->organService = $organService;
        $this->emailService = $emailService;
        $this->calendarOptionMapper = $calendarOptionMapper;
        $this->optionProposalMapper = $optionProposalMapper;
        $this->periodMapper = $periodMapper;
        $this->maxActivitiesMapper = $maxActivitiesMapper;
        $this->memberMapper = $memberMapper;
        $this->calendarOptionForm = $calendarOptionForm;
        $this->calendarProposalForm = $calendarProposalForm;
    }

    public function getRole()
    {
        return $this->userRole;
    }

    /**
     * Gets all future options.
     */
    public function getUpcomingOptions()
    {
        return $this->calendarOptionMapper->getUpcomingOptions(false);
    }

    /**
     * Gets all future options which the current user is allowed to edit/delete.
     */
    public function getEditableUpcomingOptions()
    {
        if (!$this->isAllowed('delete_own')) {
            return [];
        }
        if ($this->isAllowed('delete_all')) {
            // Return all
            return $this->calendarOptionMapper->getUpcomingOptions(true);
        }
        $user = $this->userService->getIdentity();

        return $this->calendarOptionMapper->getUpcomingOptionsByOrgans(
            $this->memberMapper->findOrgans($user->getMember())
        );
    }

    public function sendOverdueNotifications()
    {
        $date = new DateTime();
        $date->sub(new DateInterval('P3W'));
        $oldOptions = $this->calendarOptionMapper->getPastOptions($date);
        if (!empty($oldOptions)) {
            $this->emailService->sendEmail(
                'activity_calendar',
                'email/options-overdue',
                'Activiteiten kalender opties verlopen | Activity calendar options expired',
                ['options' => $oldOptions]
            );
        }
    }

    /**
     * Retrieves the form for creating a new calendar activity option proposal.
     *
     * @return ActivityCalendarOption
     */
    public function getCreateOptionForm()
    {
        if (!$this->isAllowed('create')) {
            throw new NotAllowedException($this->translator->translate('Not allowed to create activity options.'));
        }

        return $this->calendarOptionForm;
    }

    /**
     * @param $data
     *
     * @return ProposalModel|bool
     *
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
        $em = $this->entityManager;
        $proposal->setCreator($this->userService->getIdentity());
        $name = $validatedData['name'];
        $proposal->setName($name);
        $description = $validatedData['description'];
        $proposal->setDescription($description);
//        See /Activity/Form/ActivityCalendarProposal for more details on the definition of these options
        if ($organ > -1) {
            $proposal->setOrgan($this->organService->getOrgan($organ));
        } elseif (-1 == $organ) {
            $proposal->setOrganAlt('Board');
        } elseif (-2 == $organ) {
            $proposal->setOrganAlt('Other');
        }
        $em->persist($proposal);
        $em->flush();

        $options = $validatedData['options'];
        foreach ($options as $option) {
            $result = $this->createOption($option, $proposal);
            if (false == $result) {
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
            throw new NotAllowedException($this->translator->translate('Not allowed to create activity proposals.'));
        }

        return $this->calendarProposalForm;
    }

    /**
     * Returns whether an organ may create a new activity proposal.
     *
     * @param int $organId
     *
     * @return bool
     *
     * @throws Exception
     */
    protected function canOrganCreateProposal($organId)
    {
        if ($this->isAllowed('create_always')) {
            return true;
        }

        $period = $this->getCurrentPeriod();

        if (
            null == $period
            || !$this->isAllowed('create')
            || null == $organId
        ) {
            return false;
        }

        $max = $this->getMaxActivities($organId, $period->getId());
        $count = $this->getCurrentProposalCount($period, $organId);

        return $count < $max;
    }

    /**
     * Get the current ActivityOptionCreationPeriod.
     *
     * @return ActivityOptionCreationPeriod
     *
     * @throws Exception
     */
    public function getCurrentPeriod()
    {
        $mapper = $this->periodMapper;

        return $mapper->getCurrentActivityOptionCreationPeriod();
    }

    /**
     * Get the max number of activity options an organ can create.
     *
     * @param int $organId
     * @param int $periodId
     *
     * @return int
     *
     * @throws Exception
     */
    protected function getMaxActivities($organId, $periodId)
    {
        $mapper = $this->maxActivitiesMapper;
        $maxActivities = $mapper->getMaxActivityOptionsByOrganPeriod($organId, $periodId);
        // TODO: The initial value of $max below represents a default value for when no appropriate MaxActivities instance exists.
        $max = 2;
        if ($maxActivities) {
            $max = $maxActivities->getValue();
        }

        return $max;
    }

    /**
     * Get the current proposal count of an organ for the given period.
     *
     * @param $period
     * @param $organId
     *
     * @return int
     */
    protected function getCurrentProposalCount($period, $organId)
    {
        $mapper = $this->optionProposalMapper;
        $begin = $period->getBeginPlanningTime();
        $end = $period->getEndPlanningTime();
        $options = $mapper->getNonClosedProposalsWithinPeriodAndOrgan($begin, $end, $organId);

        return count($options);
    }

    /**
     * @param $data
     * @param ProposalModel $proposal
     *
     * @return OptionModel|bool
     *
     * @throws Exception
     */
    public function createOption($data, $proposal)
    {
        $option = new OptionModel();
        $validatedData = $data;

        $em = $this->entityManager;
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
            throw new NotAllowedException($this->translator->translate('You are not allowed to approve this option'));
        }

        $mapper = $this->calendarOptionMapper;
        $option = $mapper->find($id);

        $em = $this->entityManager;
        $option->setModifiedBy($this->userService->getIdentity());
        $option->setStatus('approved');
        $em->flush();

        $proposal = $option->getProposal();
        $options = $mapper->findOptionsByProposal($proposal);

        foreach ($options as $option) {
            // Can't add two options at the same time
            if (null == $option->getStatus()) {
                $this->deleteOption($option->getId());
            }
        }
    }

    public function canApproveOption()
    {
        if ($this->isAllowed('approve_all')) {
            return true;
        }

        return false;
    }

    public function deleteOption($id)
    {
        $mapper = $this->calendarOptionMapper;
        $option = $mapper->find($id);
        if (!$this->canDeleteOption($option)) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to delete this option'));
        }

        $em = $this->entityManager;
        $option->setModifiedBy($this->userService->getIdentity());
        $option->setStatus('deleted');
        $em->flush();
    }

    protected function canDeleteOption($option)
    {
        if ($this->isAllowed('delete_all')) {
            return true;
        }

        if (!$this->isAllowed('delete_own')) {
            return false;
        }

        if (
            null === $option->getProposal()->getOrgan()
            && $option->getProposal()->getCreator()->getLidnr() === $this->userService->getIdentity()->getLidnr()
        ) {
            return true;
        }

        $organ = $option->getProposal()->getOrgan();
        if (null !== $organ && $this->organService->canEditOrgan($organ)) {
            return true;
        }

        return false;
    }

    /**
     * Returns whether a user may create an option with given start time.
     *
     * @param DateTime $beginTime
     *
     * @return bool
     *
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

        if ($begin > $beginTime || $beginTime > $end) {
            return false;
        }

        return true;
    }

    /**
     * Returns whether a member may create a new activity proposal.
     *
     * @return bool
     *
     * @throws Exception
     */
    public function canCreateProposal()
    {
        $organs = $this->getEditableOrgans();

        return !empty($organs);
    }

    /**
     * Retrieves all organs which the current user is allowed to edit and for which the organs can still create proposals.
     *
     * @return array
     *
     * @throws Exception
     */
    public function getEditableOrgans()
    {
        $allOrgans = $this->organService->getEditableOrgans();
        $organs = [];
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
        return $this->acl;
    }

    /**
     * Get the current ActivityOptionCreationPeriod.
     *
     * @param int $proposalId
     * @param int $organId
     *
     * @return int
     */
    protected function getCurrentProposalOptionCount($proposalId, $organId)
    {
        $mapper = $this->calendarOptionMapper;
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
