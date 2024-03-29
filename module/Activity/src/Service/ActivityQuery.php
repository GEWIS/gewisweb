<?php

declare(strict_types=1);

namespace Activity\Service;

use Activity\Mapper\Activity as ActivityMapper;
use Activity\Mapper\Proposal as ProposalMapper;
use Activity\Model\Activity as ActivityModel;
use Activity\Model\ActivityUpdateProposal as ActivityUpdateProposalModel;
use DateTime;
use Decision\Model\AssociationYear;
use Decision\Model\Organ as OrganModel;
use Decision\Service\Organ as OrganService;
use DoctrineORMModule\Paginator\Adapter\DoctrinePaginator;
use Laminas\Mvc\I18n\Translator;
use User\Model\User as UserModel;
use User\Permissions\NotAllowedException;

use function range;

class ActivityQuery
{
    public function __construct(
        private readonly AclService $aclService,
        private readonly Translator $translator,
        private readonly OrganService $organService,
        private readonly ActivityMapper $activityMapper,
        private readonly ProposalMapper $proposalMapper,
    ) {
    }

    /**
     * Get the translator.
     */
    public function getTranslator(): Translator
    {
        return $this->translator;
    }

    /**
     * Get the information of one proposal from the database.
     *
     * @param int $id The proposal id to be searched for
     *
     * @return ActivityUpdateProposalModel|null or null if the proposal does not exist
     */
    public function getProposal(int $id): ?ActivityUpdateProposalModel
    {
        return $this->proposalMapper->find($id);
    }

    /**
     * Retrieve all update proposals from the database.
     *
     * @return ActivityUpdateProposalModel[]
     */
    public function getAllProposals(): array
    {
        return $this->proposalMapper->findAll();
    }

    /**
     * Get an array that states whether a language is available for
     * the provided $activity.
     *
     * @return array{
     *     nl: bool,
     *     en: bool,
     * }
     */
    public function getAvailableLanguages(ActivityModel $activity): array
    {
        return [
            'nl' => null !== $activity->getName()->getValueNL(),
            'en' => null !== $activity->getName()->getValueEN(),
        ];
    }

    /**
     * Get the activity with additional details.
     */
    public function getActivityWithDetails(int $id): ?ActivityModel
    {
        if (!$this->aclService->isAllowed('viewDetails', $this->getActivity($id))) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to view this activity'));
        }

        return $this->getActivity($id);
    }

    /**
     * Get the information of one activity from the database.
     *
     * @param int $id The activity id to be searched for
     *
     * @return ActivityModel|null Activity or null if the activity does not exist
     */
    public function getActivity(int $id): ?ActivityModel
    {
        if (!$this->aclService->isAllowed('view', 'activity')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to view the activities'));
        }

        return $this->activityMapper->find($id);
    }

    /**
     * Get all the activities that are yet to be approved.
     *
     * @return ActivityModel[]
     */
    public function getUnapprovedActivities(): array
    {
        if (!$this->aclService->isAllowed('viewUnapproved', 'activity')) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to view unapproved activities'),
            );
        }

        return $this->activityMapper->getAllUpcomingActivities(null, null, ActivityModel::STATUS_TO_APPROVE);
    }

    /**
     * Get all activities that are approved by the board.
     *
     * @return ActivityModel[]
     */
    public function getApprovedActivities(): array
    {
        if (!$this->aclService->isAllowed('view', 'activity')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to view activities'));
        }

        return $this->activityMapper->getAllUpcomingActivities(null, null, ActivityModel::STATUS_APPROVED);
    }

    /**
     * Get upcoming activities organized by the given organ.
     *
     * @return ActivityModel[]
     */
    public function getOrganActivities(
        OrganModel $organ,
        ?int $count = null,
    ): array {
        return $this->activityMapper->getUpcomingActivities($count, $organ);
    }

    /**
     * Get all activities that are disapproved by the board.
     *
     * @return ActivityModel[]
     */
    public function getDisapprovedActivities(): array
    {
        if (!$this->aclService->isAllowed('viewDisapproved', 'activity')) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to view the disapproved activities'),
            );
        }

        return $this->activityMapper->getAllUpcomingActivities(null, null, ActivityModel::STATUS_DISAPPROVED);
    }

    /**
     * Get all activities that are approved by the board and which occur in the future.
     *
     * @param string|null $category Type of activities requested
     *
     * @return ActivityModel[]
     */
    public function getUpcomingActivities(?string $category = null): array
    {
        if (!$this->aclService->isAllowed('view', 'activity')) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to view upcoming the activities'),
            );
        }

        if ('my' === $category) {
            if (!$this->aclService->isAllowed('view', 'myActivities')) {
                throw new NotAllowedException(
                    $this->translator->translate(
                        'You are not allowed to view upcoming activities coupled to a member account',
                    ),
                );
            }

            return $this->activityMapper->getUpcomingActivitiesForMember(
                $this->aclService->getUserIdentityOrThrowException(),
            );
        }

        return $this->activityMapper->getUpcomingActivities(category: $category);
    }

    /**
     * Gets the upcoming activities created by this user or its organs.
     * Or, when the user is an admin, retrieve all upcoming activities.
     *
     * @return ActivityModel[]
     */
    public function getUpcomingCreatedActivities(UserModel $user): array
    {
        if ($this->aclService->isAllowed('viewDetails', 'activity')) {
            //Only admins are allowed to unconditionally view activity details
            return $this->activityMapper->getAllUpcomingActivities();
        }

        $organs = $this->organService->getEditableOrgans();

        return $this->activityMapper->getAllUpcomingActivities($organs, $user);
    }

    /**
     * Gets a paginator for the old activities created by this user or its organs.
     * Or, when the user is an admin, retrieve all old activities.
     */
    public function getOldCreatedActivitiesPaginator(UserModel $user): DoctrinePaginator
    {
        if ($this->aclService->isAllowed('viewDetails', 'activity')) {
            //Only admins are allowed to unconditionally view activity details
            return $this->activityMapper->getOldActivityPaginatorAdapterByOrganizer();
        }

        $organs = $this->organService->getEditableOrgans();

        return $this->activityMapper->getOldActivityPaginatorAdapterByOrganizer($organs, $user);
    }

    /**
     * Get all the years activities have taken place in the past.
     *
     * @return int[]
     */
    public function getActivityArchiveYears(): array
    {
        $oldest = $this->activityMapper->getOldestActivity();

        if (null === $oldest) {
            return [];
        }

        $startYear = AssociationYear::fromDate($oldest->getBeginTime())->getYear();
        $endYear = AssociationYear::fromDate(new DateTime())->getYear();

        // We make the reasonable assumption that there is at least one activity
        return range($startYear, $endYear);
    }

    /**
     * Get all the activities that have finished in a year (and thus are archived)
     *
     * @param int $year First part of study year
     *
     * @return ActivityModel[]
     */
    public function getFinishedActivitiesByYear(int $year): array
    {
        if (!$this->aclService->isAllowed('view', 'activity')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to view the activities'));
        }

        $associationYear = AssociationYear::fromYear($year);

        $endDate = $associationYear->getEndDate() <= new DateTime() ? $associationYear->getEndDate() : new DateTime();

        return $this->activityMapper->getArchivedActivitiesInRange($associationYear->getStartDate(), $endDate);
    }
}
