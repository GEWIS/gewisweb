<?php

namespace Activity\Service;

use Activity\Form\ActivityCalendarOption;
use Activity\Form\ActivityCalendarProposal;
use Activity\Model\ActivityCalendarOption as OptionModel;
use Activity\Model\ActivityOptionProposal as ProposalModel;
use Application\Service\Email;
use DateInterval;
use DateTime;
use Decision\Mapper\Member;
use Decision\Service\Organ;
use Doctrine\ORM\EntityManager;
use Exception;
use Laminas\Mvc\I18n\Translator;
use User\Permissions\NotAllowedException;

class ActivityCalendar
{
    /**
     * @var Translator
     */
    private $translator;
    /**
     * @var EntityManager
     */
    private $entityManager;
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
    private AclService $aclService;
    private ActivityCalendarForm $calendarFormService;

    public function __construct(
        Translator $translator,
        EntityManager $entityManager,
        Organ $organService,
        Email $emailService,
        \Activity\Mapper\ActivityCalendarOption $calendarOptionMapper,
        Member $memberMapper,
        ActivityCalendarOption $calendarOptionForm,
        ActivityCalendarProposal $calendarProposalForm,
        AclService $aclService,
        ActivitycalendarForm $calendarFormService
    ) {
        $this->translator = $translator;
        $this->entityManager = $entityManager;
        $this->organService = $organService;
        $this->emailService = $emailService;
        $this->calendarOptionMapper = $calendarOptionMapper;
        $this->memberMapper = $memberMapper;
        $this->calendarOptionForm = $calendarOptionForm;
        $this->calendarProposalForm = $calendarProposalForm;
        $this->aclService = $aclService;
        $this->calendarFormService = $calendarFormService;
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
        if (!$this->aclService->isAllowed('delete_own', 'activity_calendar_proposal')) {
            return [];
        }
        if ($this->aclService->isAllowed('delete_all', 'activity_calendar_proposal')) {
            // Return all
            return $this->calendarOptionMapper->getUpcomingOptions(true);
        }
        $user = $this->aclService->getIdentityOrThrowException();

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
        if (!$this->aclService->isAllowed('create', 'activity_calendar_proposal')) {
            throw new NotAllowedException($this->translator->translate('Not allowed to create activity options.'));
        }

        return $this->calendarOptionForm;
    }

    /**
     * @param array $data
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
        if (!$this->calendarFormService->canOrganCreateProposal($organ)) {
            return false;
        }

        $proposal->setCreationTime(new DateTime());
        $em = $this->entityManager;
        $proposal->setCreator($this->aclService->getIdentityOrThrowException());
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
        if (!$this->aclService->isAllowed('create', 'activity_calendar_proposal')) {
            throw new NotAllowedException($this->translator->translate('Not allowed to create activity proposals.'));
        }

        return $this->calendarProposalForm;
    }

    /**
     * @param array $data
     * @param ProposalModel $proposal
     *
     * @return OptionModel
     *
     * @throws Exception
     */
    public function createOption($data, $proposal)
    {
        $option = new OptionModel();
        $validatedData = $data;

        $em = $this->entityManager;
        $option->setProposal($proposal);
        $beginTime = $this->calendarFormService->toDateTime($validatedData['beginTime']);
        $option->setBeginTime($beginTime);
        $endTime = $this->calendarFormService->toDateTime($validatedData['endTime']);
        $option->setEndTime($endTime);
        $type = $validatedData['type'];
        $option->setType($type);
        $em->persist($option);
        $em->flush();

        return $option;
    }

    public function approveOption($id)
    {
        if (!$this->canApproveOption()) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to approve this option'));
        }

        $mapper = $this->calendarOptionMapper;
        $option = $mapper->find($id);

        $em = $this->entityManager;
        $option->setModifiedBy($this->aclService->getIdentityOrThrowException());
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
        if ($this->aclService->isAllowed('approve_all', 'activity_calendar_proposal')) {
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
        $option->setModifiedBy($this->aclService->getIdentityOrThrowException());
        $option->setStatus('deleted');
        $em->flush();
    }

    protected function canDeleteOption($option)
    {
        if ($this->aclService->isAllowed('delete_all', 'activity_calendar_proposal')) {
            return true;
        }

        if (!$this->aclService->isAllowed('delete_own', 'activity_calendar_proposal')) {
            return false;
        }

        if (
            null === $option->getProposal()->getOrgan()
            && $option->getProposal()->getCreator()->getLidnr() === $this->aclService->getIdentityOrThrowException()->getLidnr()
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
     * Returns whether a member may create a new activity proposal.
     *
     * @return bool
     *
     * @throws Exception
     */
    public function canCreateProposal()
    {
        $organs = $this->calendarFormService->getEditableOrgans();

        return !empty($organs);
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
}
