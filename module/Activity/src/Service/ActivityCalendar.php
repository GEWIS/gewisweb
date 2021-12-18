<?php

namespace Activity\Service;

use Activity\Form\{
    ActivityCalendarOption,
    ActivityCalendarPeriod as ActivityCalendarPeriodForm,
};
use Activity\Mapper\{ActivityOptionCreationPeriod, ActivityOptionCreationPeriod as ActivityOptionCreationPeriodMapper};
use Activity\Model\{
    ActivityCalendarOption as OptionModel,
    ActivityOptionCreationPeriod as ActivityOptionCreationPeriodModel,
    ActivityOptionProposal as ProposalModel,
    MaxActivities as MaxActivitiesModel,
};
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
     * @var ActivityCalendarPeriodForm
     */
    private ActivityCalendarPeriodForm $calendarPeriodForm;

    /**
     * @var ActivityOptionCreationPeriodMapper
     */
    private ActivityOptionCreationPeriodMapper $calendarCreationPeriodMapper;

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
        ActivityCalendarPeriodForm $calendarPeriodForm,
        ActivityOptionCreationPeriodMapper $calendarCreationPeriodMapper,
        AclService $aclService,
        ActivityCalendarForm $calendarFormService
    ) {
        $this->translator = $translator;
        $this->entityManager = $entityManager;
        $this->organService = $organService;
        $this->emailService = $emailService;
        $this->calendarOptionMapper = $calendarOptionMapper;
        $this->memberMapper = $memberMapper;
        $this->calendarOptionForm = $calendarOptionForm;
        $this->calendarPeriodForm = $calendarPeriodForm;
        $this->calendarCreationPeriodMapper = $calendarCreationPeriodMapper;
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
        $oldOptions = $this->calendarOptionMapper->getOverdueOptions($date);
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
    public function createProposal(array $data): bool|ProposalModel
    {
        $proposal = new ProposalModel();

        $organ = $data['organ'];
        if (!$this->calendarFormService->canOrganCreateProposal($organ)) {
            return false;
        }

        $proposal->setCreationTime(new DateTime());
        $em = $this->entityManager;
        $proposal->setCreator($this->aclService->getIdentityOrThrowException());
        $name = $data['name'];
        $proposal->setName($name);
        $description = $data['description'];
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

        $options = $data['options'];
        foreach ($options as $option) {
            $this->createOption($option, $proposal);
        }

        return $proposal;
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

        $em = $this->entityManager;
        $option->setProposal($proposal);
        $beginTime = $this->calendarFormService->toDateTime($data['beginTime']);
        $option->setBeginTime($beginTime);
        $endTime = $this->calendarFormService->toDateTime($data['endTime']);
        $option->setEndTime($endTime);
        $type = $data['type'];
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
        $option = $mapper->findOption($id);

        $em = $this->entityManager;
        $option->setModifiedBy($this->aclService->getIdentityOrThrowException());
        $option->setStatus('approved');
        $em->flush();

        $proposal = $option->getProposal();
        $options = $mapper->findOptionsByProposal($proposal->getId());

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
        $option = $mapper->findOption($id);
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

    /**
     * @param array $data
     */
    public function createOptionPlanningPeriod(array $data): bool
    {
        $activityOptionCreationPeriod = new ActivityOptionCreationPeriodModel();

        $activityOptionCreationPeriod->setBeginPlanningTime(new DateTime($data['beginPlanningTime']));
        $activityOptionCreationPeriod->setEndPlanningTime(new DateTime($data['endPlanningTime']));
        $activityOptionCreationPeriod->setBeginOptionTime(new DateTime($data['beginOptionTime']));
        $activityOptionCreationPeriod->setEndOptionTime(new DateTime($data['endOptionTime']));

        // Persist the ActivityOptionCreationPeriodModel here, such that we can use its id for the MaxActivitiesModel.
        $this->calendarCreationPeriodMapper->persist($activityOptionCreationPeriod);
        $this->calendarCreationPeriodMapper->flush();

        foreach ($data['maxActivities'] as $maxActivity) {
            // Check if the organ really exists.
            $organ = $this->organService->findActiveOrganById($maxActivity['id']);

            if (null !== $organ) {
                $maxActivities = new MaxActivitiesModel();

                $maxActivities->setValue($maxActivity['value']);
                $maxActivities->setOrgan($organ);
                $maxActivities->setPeriod($activityOptionCreationPeriod);

                $this->calendarCreationPeriodMapper->persist($maxActivities);
            }
        }

        // Flush all MaxActivitiesModels.
        $this->calendarCreationPeriodMapper->flush();

        return true;
    }

    public function updateOptionPlanningPeriod(
        ActivityOptionCreationPeriodModel $activityOptionCreationPeriod,
        array $data,
    ): bool {
        $activityOptionCreationPeriod->setBeginPlanningTime(new DateTime($data['beginPlanningTime']));
        $activityOptionCreationPeriod->setEndPlanningTime(new DateTime($data['endPlanningTime']));
        $activityOptionCreationPeriod->setBeginOptionTime(new DateTime($data['beginOptionTime']));
        $activityOptionCreationPeriod->setEndOptionTime(new DateTime($data['endOptionTime']));

        // Update maxActivities, if the form has been altered (by hand) those changes will not be persisted. We get an
        // array indexed by the organ ids, last value is used if organ is present more than once (i.e. someone tampered
        // with the form).
        $ids = array_flip(array_map(function ($val) {
            return $val['id'];
        }, $data['maxActivities']));
        foreach ($activityOptionCreationPeriod->getMaxActivities() as $maxActivity) {
            $organId = $maxActivity->getOrgan()->getId();

            if (array_key_exists($organId, $ids)) {
                $offset = $ids[$organId];
                $maxActivity->setValue($data['maxActivities'][$offset]['value']);
            }
        }

        $this->calendarCreationPeriodMapper->flush();

        return true;
    }

    /**
     * @param int $id
     *
     * @return ActivityOptionCreationPeriodModel|null
     */
    public function getOptionCreationPeriod(int $id): ?ActivityOptionCreationPeriodModel
    {
        return $this->calendarCreationPeriodMapper->find($id);
    }

    /**
     * TODO: How do we actually want to delete the OptionCreationPeriod, does this include OptionProposals,
     * MaxActivities, etc.? And should there be a limited with regards to the current time (and the defined periods).
     *
     * @param ActivityOptionCreationPeriodModel $activityOptionCreationPeriod
     */
    public function deleteOptionCreationPeriod(ActivityOptionCreationPeriodModel $activityOptionCreationPeriod): void
    {
        $this->calendarCreationPeriodMapper->remove($activityOptionCreationPeriod);
    }

    /**
     * @return ActivityCalendarPeriodForm
     */
    public function getCalendarPeriodForm(): ActivityCalendarPeriodForm
    {
        return $this->calendarPeriodForm;
    }
}
