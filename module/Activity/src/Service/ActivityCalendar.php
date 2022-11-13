<?php

namespace Activity\Service;

use Activity\Form\{
    ActivityCalendarPeriod as ActivityCalendarPeriodForm,
};
use Activity\Mapper\{
    ActivityCalendarOption as ActivityCalendarOptionMapper,
    ActivityOptionCreationPeriod as ActivityOptionCreationPeriodMapper,
};
use Activity\Model\{
    ActivityCalendarOption as OptionModel,
    ActivityCalendarOption as ActivityCalendarOptionModel,
    ActivityOptionCreationPeriod as ActivityOptionCreationPeriodModel,
    ActivityOptionProposal as ProposalModel,
    MaxActivities as MaxActivitiesModel,
};
use Application\Service\Email as EmailService;
use DateInterval;
use DateTime;
use Decision\Mapper\Member as MemberMapper;
use Decision\Service\Organ as OrganService;
use Doctrine\ORM\EntityManager;
use Exception;
use Laminas\Mvc\I18n\Translator;
use User\Permissions\NotAllowedException;

class ActivityCalendar
{
    public function __construct(
        private readonly AclService $aclService,
        private readonly Translator $translator,
        private readonly EntityManager $entityManager,
        private readonly OrganService $organService,
        private readonly EmailService $emailService,
        private readonly ActivityCalendarOptionMapper $calendarOptionMapper,
        private readonly MemberMapper $memberMapper,
        private readonly ActivityCalendarPeriodForm $calendarPeriodForm,
        private readonly ActivityOptionCreationPeriodMapper $calendarCreationPeriodMapper,
        private readonly ActivityCalendarForm $calendarFormService,
    ) {
    }

    /**
     * Gets all future options.
     *
     * @return array
     */
    public function getUpcomingOptions(): array
    {
        return $this->calendarOptionMapper->getUpcomingOptions(false);
    }

    /**
     * Gets all future options which the current user is allowed to edit/delete.
     *
     * @return array
     */
    public function getEditableUpcomingOptions(): array
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

    public function sendOverdueNotifications(): void
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
        $proposal->setCreator($this->aclService->getIdentityOrThrowException()->getMember());
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
            $this->createOption($proposal, $option);
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
    public function createOption(
        ProposalModel $proposal,
        array $data,
    ): OptionModel {
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

    /**
     * @param int $id
     */
    public function approveOption(int $id): void
    {
        if (!$this->canApproveOption()) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to approve this option'));
        }

        $option = $this->calendarOptionMapper->find($id);

        if (null === $option) {
            return;
        }

        $option->setModifiedBy($this->aclService->getIdentityOrThrowException()->getMember());
        $option->setStatus('approved');
        $this->calendarOptionMapper->flush();

        $proposal = $option->getProposal();
        $options = $this->calendarOptionMapper->findOptionsByProposal($proposal);

        foreach ($options as $option) {
            // Can't add two options at the same time
            if (null == $option->getStatus()) {
                $this->deleteOption($option->getId());
            }
        }
    }

    /**
     * @return bool
     */
    public function canApproveOption(): bool
    {
        if ($this->aclService->isAllowed('approve_all', 'activity_calendar_proposal')) {
            return true;
        }

        return false;
    }

    /**
     * @param int $id
     */
    public function deleteOption(int $id): void
    {
        $option = $this->calendarOptionMapper->find($id);

        if (null === $option) {
            return;
        }

        if (!$this->canDeleteOption($option)) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to delete this option'));
        }

        $option->setModifiedBy($this->aclService->getIdentityOrThrowException()->getMember());
        $option->setStatus('deleted');
        $this->calendarOptionMapper->flush();
    }

    /**
     * @param ActivityCalendarOptionModel $option
     *
     * @return bool
     */
    protected function canDeleteOption(ActivityCalendarOptionModel $option): bool
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
    public function canCreateProposal(): bool
    {
        $organs = $this->calendarFormService->getEditableOrgans();

        return !empty($organs);
    }

    /**
     * @param array $data
     *
     * @return bool
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

    /**
     * @param ActivityOptionCreationPeriodModel $activityOptionCreationPeriod
     * @param array $data
     *
     * @return bool
     */
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
