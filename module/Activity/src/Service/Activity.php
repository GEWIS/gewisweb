<?php

declare(strict_types=1);

namespace Activity\Service;

use Activity\Form\Activity as ActivityForm;
use Activity\Model\{
    Activity as ActivityModel,
    ActivityLocalisedText,
    ActivityUpdateProposal as ActivityProposalModel,
    SignupField as SignupFieldModel,
    SignupList as SignupListModel,
    SignupOption as SignupOptionModel,
};
use Activity\Service\ActivityCategory as ActivityCategoryService;
use Application\Service\Email as EmailService;
use Company\Model\Company as CompanyModel;
use Company\Service\Company as CompanyService;
use DateTime;
use Decision\Model\Member as MemberModel;
use Decision\Model\Organ as OrganModel;
use Decision\Service\Organ as OrganService;
use Doctrine\ORM\{
    EntityManager,
    OptimisticLockException,
    Exception\ORMException,
};
use Laminas\Mvc\I18n\Translator;
use User\Model\User as UserModel;
use User\Permissions\NotAllowedException;

class Activity
{
    public function __construct(
        private readonly AclService $aclService,
        private readonly Translator $translator,
        private readonly EntityManager $entityManager,
        private readonly ActivityCategory $categoryService,
        private readonly OrganService $organService,
        private readonly CompanyService $companyService,
        private readonly EmailService $emailService,
        private readonly ActivityForm $activityForm,
    ) {
    }

    /**
     * Create an activity from the creation form.
     *
     * @pre $params is valid data of Activity\Form\Activity
     *
     * @param array $data Parameters describing activity
     *
     * @return bool activity that was created
     */
    public function createActivity(array $data): bool
    {
        if (!$this->aclService->isAllowed('create', 'activity')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to create an activity'));
        }

        // Find the creator
        $member = $this->aclService->getUserIdentityOrThrowException()->getMember();

        // Find the organ the activity belongs to, and see if the user has permission to create an activity
        // for this organ. If the id is 0, the activity belongs to no organ.
        $organId = intval($data['organ']);
        $organ = null;

        if (0 !== $organId) {
            $organ = $this->findOrgan($organId);
        }

        // Find the company the activity belongs to. If the id is 0, the activity belongs to no company.
        $companyId = intval($data['company']);
        $company = null;

        if (0 !== $companyId) {
            $company = $this->companyService->getCompanyById($companyId);
        }

        $activity = $this->saveActivityData($data, $member, $organ, $company, ActivityModel::STATUS_TO_APPROVE);

        // Send email to GEFLITST if user checked checkbox of GEFLITST
        if ($activity->getRequireGEFLITST()) {
            $this->requestGEFLITST($activity, $member, $organ);
        }

        return true;
    }

    /**
     * Return activity creation form.
     *
     * @return ActivityForm
     */
    public function getActivityForm(): ActivityForm
    {
        if (!$this->aclService->isAllowed('create', 'activity')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to create an activity'));
        }

        try {
            $organs = $this->organService->getEditableOrgans();
        } catch (NotAllowedException) {
            $organs = [];
        }

        try {
            $companies = $this->companyService->getHiddenCompanyList();
        } catch (NotAllowedException) {
            $companies = [];
        }

        try {
            $categories = $this->categoryService->findAll();
        } catch (NotAllowedException) {
            $categories = [];
        }

        return $this->activityForm->setAllOptions($organs, $companies, $categories);
    }

    /**
     * Find the organ the activity belongs to, and see if the user has permission to create an activity
     * for this organ.
     *
     * @param int $organId The id of the organ associated with the activity
     *
     * @return OrganModel The organ associated with the activity, if the user is a member of that organ
     *
     * @throws NotAllowedException if the user is not a member of the organ specified
     */
    protected function findOrgan(int $organId): OrganModel
    {
        $organ = $this->organService->getOrgan($organId);

        if (!$this->organService->canEditOrgan($organ)) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to create an activity for this organ')
            );
        }

        return $organ;
    }

    /**
     * Create an activity from parameters.
     *
     * @pre $data is valid data of Activity\Form\Activity
     *
     * @param array $data Parameters describing activity
     * @param MemberModel $user The user that creates this activity
     * @param OrganModel|null $organ The organ this activity is associated with
     * @param CompanyModel|null $company The company this activity is associated with
     * @param int $status
     *
     * @return ActivityModel activity that was created
     */
    protected function saveActivityData(
        array $data,
        MemberModel $user,
        ?OrganModel $organ,
        ?CompanyModel $company,
        int $status,
    ): ActivityModel {
        $activity = new ActivityModel();
        $activity->setBeginTime(new DateTime($data['beginTime']));
        $activity->setEndTime(new DateTime($data['endTime']));

        $activity->setName(new ActivityLocalisedText($data['nameEn'], $data['name']));
        $activity->setLocation(new ActivityLocalisedText($data['locationEn'], $data['location']));
        $activity->setCosts(new ActivityLocalisedText($data['costsEn'], $data['costs']));
        $activity->setDescription(new ActivityLocalisedText($data['descriptionEn'], $data['description']));

        $activity->setIsMyFuture(boolval($data['isMyFuture']));
        $activity->setRequireGEFLITST(boolval($data['requireGEFLITST']));

        // Not user provided input
        $activity->setCreator($user);
        $activity->setOrgan($organ);
        $activity->setCompany($company);
        $activity->setStatus($status);

        $em = $this->entityManager;

        if (isset($data['categories'])) {
            foreach ($data['categories'] as $category) {
                $category = $this->categoryService->getCategoryById($category);

                if (!is_null($category)) {
                    $activity->addCategory($category);
                }
            }
        }

        if (isset($data['signupLists'])) {
            foreach ($data['signupLists'] as $signupList) {
                $signupList = $this->createSignupList($signupList, $activity);
                $em->persist($signupList);
            }
            $em->flush();
        }

        $em->persist($activity);
        $em->flush();

        // Send an email when a new Activity was created, but do not send one
        // when an activity is updated. This e-mail is handled in
        // `$this->createUpdateProposal()`.
        if (ActivityModel::STATUS_UPDATE !== $status) {
            $this->emailService->sendEmail(
                'activity_creation',
                'email/activity',
                'Nieuwe activiteit aangemaakt op de GEWIS website | New activity was created on the GEWIS website',
                ['activity' => $activity]
            );
        }

        return $activity;
    }

    /**
     * Creates a SignupList for the specified Activity.
     *
     * @param array $data
     * @param ActivityModel $activity
     *
     * @return SignupListModel
     */
    public function createSignupList(
        array $data,
        ActivityModel $activity,
    ): SignupListModel {
        $signupList = new SignupListModel();

        $signupList->setActivity($activity);
        $signupList->setName(new ActivityLocalisedText($data['nameEn'], $data['name']));
        $signupList->setOpenDate(new DateTime($data['openDate']));
        $signupList->setCloseDate(new DateTime($data['closeDate']));

        $signupList->setOnlyGEWIS(boolval($data['onlyGEWIS']));
        $signupList->setDisplaySubscribedNumber(boolval($data['displaySubscribedNumber']));
        $signupList->setLimitedCapacity(boolval($data['limitedCapacity']));

        if (isset($data['fields'])) {
            $em = $this->entityManager;

            foreach ($data['fields'] as $field) {
                $field = $this->createSignupField($field, $signupList);
                $em->persist($field);
            }
            $em->flush();
        }

        return $signupList;
    }

    /**
     * Create a new field.
     *
     * @pre $data is valid data of Activity\Form\SignupListFields
     *
     * @param array $data parameters for the new field
     * @param SignupListModel $signupList the SignupList the field belongs to
     *
     * @return SignupFieldModel the new field
     */
    public function createSignupField(
        array $data,
        SignupListModel $signupList,
    ): SignupFieldModel {
        $field = new SignupFieldModel();

        $field->setSignupList($signupList);
        $field->setName(new ActivityLocalisedText($data['nameEn'], $data['name']));
        $field->setType(intval($data['type']));

        if ('2' === $data['type']) {
            $field->setMinimumValue(intval($data['minimumValue']));
            $field->setMaximumValue(intval($data['maximumValue']));
        }

        if ('3' === $data['type']) {
            $this->createSignupOption($data, $field);
        }

        return $field;
    }

    /**
     * Creates options for both languages specified and adds it to $field.
     * If no languages are specified, this method does nothing.
     *
     * @pre The options corresponding to the languages specified are filled in
     * $params. If both languages are specified, they must have the same amount of options.
     *
     * @param array $data the array containing the options strings
     * @param SignupFieldModel $field the field to add the options to
     */
    protected function createSignupOption(
        array $data,
        SignupFieldModel $field,
    ): void {
        $numOptions = 0;
        $em = $this->entityManager;

        if (isset($data['options'])) {
            $options = explode(',', $data['options']);
            $options = array_map('trim', $options);
            $numOptions = count($options);
        }

        if (isset($data['optionsEn'])) {
            $optionsEn = explode(',', $data['optionsEn']);
            $optionsEn = array_map('trim', $optionsEn);
            $numOptions = count($optionsEn);
        }

        for ($i = 0; $i < $numOptions; ++$i) {
            $option = new SignupOptionModel();
            $option->setValue(new ActivityLocalisedText(
                isset($optionsEn) ? $optionsEn[$i] : null,
                isset($options) ? $options[$i] : null
            ));
            $option->setField($field);
            $em->persist($option);
        }

        $em->flush();
    }

    /**
     * @param ActivityModel $activity
     * @param MemberModel $user
     * @param OrganModel|null $organ
     */
    private function requestGEFLITST(
        ActivityModel $activity,
        MemberModel $user,
        ?OrganModel $organ,
    ): void {
        // Default to an English title, otherwise use the Dutch title
        $activityTitle = $activity->getName()->getText('en');
        $activityTime = $activity->getBeginTime()->format('d-m-Y H:i');

        $type = 'activity_creation_require_GEFLITST';
        $view = 'email/activity_created_require_GEFLITST';

        if (null !== $organ) {
            $subject = sprintf('%s: %s on %s', $organ->getAbbr(), $activityTitle, $activityTime);

            $organInfo = $organ->getApprovedOrganInformation();
            if (null !== $organInfo && null !== $organInfo->getEmail()) {
                $this->emailService->sendEmailAsOrgan(
                    $type,
                    $view,
                    $subject,
                    ['activity' => $activity, 'requester' => $organ->getName()],
                    $organInfo,
                );
            } else {
                // The organ did not fill in it's email address, so send the email as the requested user.
                $this->emailService->sendEmailAsUser(
                    $type,
                    $view,
                    $subject,
                    ['activity' => $activity, 'requester' => $organ->getName()],
                    $user,
                );
            }
        } else {
            $subject = sprintf('Member Initiative: %s on %s', $activityTitle, $activityTime);

            $this->emailService->sendEmailAsUser(
                $type,
                $view,
                $subject,
                ['activity' => $activity, 'requester' => $user->getFullName()],
                $user,
            );
        }
    }

    /**
     * Create a new update proposal from user form.
     *
     * @param ActivityModel $currentActivity
     * @param array $data
     *
     * @return bool indicating whether the update was applied or is pending
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function createUpdateProposal(
        ActivityModel $currentActivity,
        array $data,
    ): bool {
        if (!$this->aclService->isAllowed('create', 'activity_calendar_proposal')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to create activity option proposals'));
        }

        // Find the creator
        $member = $this->aclService->getUserIdentityOrThrowException()->getMember();

        // Find the organ the activity belongs to, and see if the user has permission to create an activity
        // for this organ. If the id is 0, the activity belongs to no organ.
        $organId = intval($data['organ']);
        $organ = null;

        if (0 !== $organId) {
            $organ = $this->findOrgan($organId);
        }

        // Find the company the activity belongs to. If the id is 0, the activity belongs to no company.
        $companyId = intval($data['company']);
        $company = null;

        if (0 !== $companyId) {
            $company = $this->companyService->getCompanyById($companyId);
        }

        $currentActivityArray = $currentActivity->toArray();

        $data['company'] = is_null($company) ? null : $company->getId();
        $data['organ'] = is_null($organ) ? null : $organ->getId();

        if (!$this->isUpdateProposalNew($currentActivityArray, $data)) {
            return false;
        }

        $newActivity = $this->saveActivityData(
            $data,
            $member,
            $organ,
            $company,
            ActivityModel::STATUS_UPDATE
        );

        $em = $this->entityManager;

        if (0 !== $currentActivity->getUpdateProposal()->count()) {
            $proposal = $currentActivity->getUpdateProposal()->first();
            //Remove old update proposal
            $oldUpdate = $proposal->getNew();
            $proposal->setNew($newActivity);
            $em->remove($oldUpdate);
        } else {
            $proposal = new ActivityProposalModel();
            $proposal->setOld($currentActivity);
            $proposal->setNew($newActivity);
            $em->persist($proposal);
        }
        $em->flush();

        // Try to directly update the proposal.
        if ($this->canApplyUpdateProposal($currentActivity)) {
            $this->updateActivity($proposal);

            // Send an e-mail stating that the activity was updated.
            $this->emailService->sendEmail(
                'activity_creation',
                'email/activity-updated',
                'Activiteit aangepast op de GEWIS website | Activity was updated on the GEWIS website',
                ['activity' => $newActivity]
            );

            return true;
        }

        // Send an e-mail stating that an activity update proposal has been made.
        $this->emailService->sendEmail(
            'activity_creation',
            'email/activity-update-proposed',
            'Activiteit aanpassingsvoorstel op de GEWIS website | Activity update proposed on the GEWIS website',
            ['activity' => $newActivity, 'proposal' => $proposal]
        );

        return true;
    }

    /**
     * Check if an update proposal is actually an update.
     *
     * @param array $current
     * @param array $proposal
     *
     * @return bool
     */
    protected function isUpdateProposalNew(
        array $current,
        array $proposal,
    ): bool {
        unset($current['id']);

        // Convert all DateTimes in the original Activity to strings.
        array_walk_recursive($current, function (&$v, $k): void {
            if ($v instanceof DateTime) {
                $v = $v->format('Y/m/d H:i');
            }
        });

        // Change Organs and Companies to be their ids to prevent the form from accidentally submitting, as the Activity
        // entity uses the Organ and Company entities.
        if (isset($current['organ'])) {
            $current['organ'] = $current['organ']->getId();
        }

        if (isset($current['company'])) {
            $current['company'] = $current['company']->getId();
        }

        // We do not need the ActivityCategory models, hence we replace it with the ids of each one. However, it is no
        // longer a model and requires array access to get the id.
        array_walk($current['categories'], function (&$v, $k): void {
            $v = strval($v['id']);
        });

        // HTML forms do not know anything about booleans, hence we need to
        // convert the strings to something we can use.
        array_walk_recursive($proposal, function (&$v, $k): void {
            if (in_array($k, ['isMyFuture', 'requireGEFLITST', 'onlyGEWIS', 'displaySubscribedNumber'], true)) {
                $v = boolval($v);
            }
        });

        // Options are a string after submission, not an array of strings. It is easier to explode the values of
        // `$proposal` instead of having to implode `$current` (which requires an extra `array_filter()`).
        if (isset($proposal['signupLists'])) {
            foreach ($proposal['signupLists'] as $keyOuter => $signupList) {
                if (isset($signupList['fields'])) {
                    foreach ($signupList['fields'] as $keyInner => $field) {
                        // Make sure that if `options` is defined in the field it is not `null` (because passing `null`
                        // to `explode()` is deprecated).
                        if (
                            array_key_exists('options', $field)
                            && null !== $field['options']
                        ) {
                            $proposal['signupLists'][$keyOuter]['fields'][$keyInner]['options'] = explode(
                                ',',
                                $field['options'],
                            );
                        }

                        // Make sure that if `optionsEn` is defined in the field it is not `null` (because passing
                        // `null` to `explode()` is deprecated).
                        if (
                            array_key_exists('optionsEn', $field)
                            && null !== $field['optionsEn']
                        ) {
                            $proposal['signupLists'][$keyOuter]['fields'][$keyInner]['optionsEn'] = explode(
                                ',',
                                $field['optionsEn'],
                            );
                        }
                    }
                }
            }
        }

        // Remove some of the form attributes.
        unset($proposal['language_dutch'], $proposal['language_english'], $proposal['submit']);

        // Get the difference between the original Activity and the update
        // proposal. Because we want to detect additions and deletions in
        // the activity data, we actually have to check both ways. After
        // this unset all `id`s after getting the diff to reduce the number
        // of calls.
        $diff1 = $this->arrayDiffAssocRecursive($current, $proposal);
        $diff2 = $this->arrayDiffAssocRecursive($proposal, $current);
        $this->recursiveUnsetKey($diff1, 'id');
        $this->recursiveUnsetKey($diff2, 'id');

        // Filter out all empty parts of the differences, if both are empty
        // nothing has changed on form submission.
        if (
            empty($this->arrayFilterRecursive($diff1))
            && empty($this->arrayFilterRecursive($diff2))
        ) {
            return false;
        }

        return true;
    }

    /**
     * `array_diff_assoc` but recursively. Used to compare an update proposal of an activity
     * to the original activity.
     *
     * Adapted from https://www.php.net/manual/en/function.array-diff-assoc.php#usernotes.
     *
     * @param array $array1
     * @param array $array2
     *
     * @return array
     */
    protected function arrayDiffAssocRecursive(
        array $array1,
        array $array2,
    ): array {
        $difference = [];

        foreach ($array1 as $key => $value) {
            if (is_array($value)) {
                if (!array_key_exists($key, $array2) || !is_array($array2[$key])) {
                    $difference[$key] = $value;
                } else {
                    $newDifference = $this->arrayDiffAssocRecursive($value, $array2[$key]);

                    if (!empty($newDifference)) {
                        $difference[$key] = $newDifference;
                    }
                }
            } elseif (!array_key_exists($key, $array2) || $array2[$key] !== $value) {
                $difference[$key] = $value;
            }
        }

        return $difference;
    }

    /**
     * Recursively unset a key in an array (by reference).
     *
     * @param array $array
     * @param string $key
     */
    protected function recursiveUnsetKey(
        array &$array,
        string $key,
    ): void {
        unset($array[$key]);

        foreach ($array as &$value) {
            if (is_array($value)) {
                $this->recursiveUnsetKey($value, $key);
            }
        }
    }

    /**
     * `array_filter` but recursively. Used to compare an update proposal of an activity
     * to the original activity.
     *
     * @param array $array
     *
     * @return array
     */
    protected function arrayFilterRecursive(array $array): array
    {
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $array[$key] = $this->arrayFilterRecursive($value);
            }

            if (in_array($array[$key], ['', null, []], true)) {
                unset($array[$key]);
            }
        }

        return $array;
    }

    /**
     * Checks whether the current user is allowed to apply an update proposal for the given activity.
     *
     * @return bool indicating whether the update may be applied
     */
    protected function canApplyUpdateProposal(ActivityModel $activity): bool
    {
        if ($this->aclService->isAllowed('update', 'activity')) {
            return true;
        }

        if (!$this->aclService->isAllowed('update', $activity)) {
            return false;
        }

        // If the activity has not been approved the update proposal can be applied.
        return ActivityModel::STATUS_TO_APPROVE === $activity->getStatus();
    }

    /**
     * Apply a proposed activity update.
     */
    public function updateActivity(ActivityProposalModel $proposal): void
    {
        $old = $proposal->getOld();
        $new = $proposal->getNew();

        // If the old activity was already approved, keep it approved but update who approved it.
        // Otherwise, the status of the new Activity becomes ActivityModel::STATUS_TO_APPROVE without an approver.
        if (ActivityModel::STATUS_APPROVED !== $old->getStatus()) {
            $new->setStatus(ActivityModel::STATUS_TO_APPROVE);
            $new->setApprover(null);
        } else {
            $new->setStatus(ActivityModel::STATUS_APPROVED);
            $new->setApprover($this->aclService->getUserIdentityOrThrowException()->getMember());
        }

        $em = $this->entityManager;

        // The proposal association is no longer needed and can safely be
        // removed. The old Activity is also removed, as we would otherwise have
        // to switch all attributes from the new Activity to the old one (which
        // can only cause problems).
        $em->remove($proposal);
        $em->remove($old);
        $em->flush();
    }

    /**
     * Revoke a proposed activity update
     * NB: This action permanently removes the proposal, so this cannot be undone.
     * (The potentially updated activity remains untouched).
     */
    public function revokeUpdateProposal(ActivityProposalModel $proposal): void
    {
        $em = $this->entityManager;
        $new = $proposal->getNew();
        $em->remove($proposal);
        $em->remove($new);
        $em->flush();
    }

    /**
     * Approve of an activity.
     */
    public function approve(ActivityModel $activity): void
    {
        if (!$this->aclService->isAllowed('approve', 'activity')) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to change the status of the activity')
            );
        }

        $activity->setStatus(ActivityModel::STATUS_APPROVED);
        $activity->setApprover($this->aclService->getUserIdentityOrThrowException()->getMember());
        $em = $this->entityManager;
        $em->persist($activity);
        $em->flush();
    }

    /**
     * Reset the approval status of an activity.
     */
    public function reset(ActivityModel $activity): void
    {
        if (!$this->aclService->isAllowed('reset', 'activity')) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to change the status of the activity')
            );
        }

        $activity->setStatus(ActivityModel::STATUS_TO_APPROVE);
        $activity->setApprover(null);
        $em = $this->entityManager;
        $em->persist($activity);
        $em->flush();
    }

    /**
     * Disapprove of an activity.
     */
    public function disapprove(ActivityModel $activity): void
    {
        if (!$this->aclService->isAllowed('disapprove', 'activity')) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to change the status of the activity')
            );
        }

        $activity->setStatus(ActivityModel::STATUS_DISAPPROVED);
        $activity->setApprover($this->aclService->getUserIdentityOrThrowException()->getMember());
        $em = $this->entityManager;
        $em->persist($activity);
        $em->flush();
    }
}
