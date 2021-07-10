<?php

namespace Activity\Service;

use Activity\Form\Activity as ActivityForm;
use Activity\Model\Activity as ActivityModel;
use Activity\Model\ActivityUpdateProposal as ActivityProposalModel;
use Activity\Model\LocalisedText;
use Activity\Model\SignupField as SignupFieldModel;
use Activity\Model\SignupList as SignupListModel;
use Activity\Model\SignupOption as SignupOptionModel;
use Application\Service\AbstractAclService;
use Application\Service\Email;
use Company\Service\Company;
use DateTime;
use Decision\Model\Organ;
use Doctrine\ORM\EntityManager;
use User\Model\User;
use User\Permissions\NotAllowedException;
use Zend\Mvc\I18n\Translator;
use Zend\Permissions\Acl\Acl;
use Zend\Stdlib\Parameters;

class Activity extends AbstractAclService
{
    /**
     * @var Translator
     */
    private $translator;

    /**
     * @var User|string
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
     * @var ActivityCategory
     */
    private $categoryService;
    /**
     * @var \User\Service\User
     */
    private $userService;
    /**
     * @var \Decision\Service\Organ
     */
    private $organService;
    /**
     * @var Company
     */
    private $companyService;
    /**
     * @var Email
     */
    private $emailService;
    /**
     * @var ActivityForm
     */
    private $activityForm;

    public function __construct(
        Translator $translator,
        $userRole,
        Acl $acl,
        EntityManager $entityManager,
        ActivityCategory $categoryService,
        \User\Service\User $userService,
        \Decision\Service\Organ $organService,
        Company $companyService,
        Email $emailService,
        ActivityForm $activityForm
    )
    {
        $this->translator = $translator;
        $this->userRole = $userRole;
        $this->acl = $acl;
        $this->entityManager = $entityManager;
        $this->categoryService = $categoryService;
        $this->userService = $userService;
        $this->organService = $organService;
        $this->companyService = $companyService;
        $this->emailService = $emailService;
        $this->activityForm = $activityForm;
    }

    public function getRole()
    {
        return $this->userRole;
    }

    /**
     * Get the ACL.
     *
     * @return Acl
     */
    public function getAcl()
    {
        return $this->acl;
    }

    /**
     * Create an activity from the creation form.
     *
     * @pre $params is valid data of Activity\Form\Activity
     *
     * @param array $data Parameters describing activity
     *
     * @return bool Activity that was created.
     */
    public function createActivity($data)
    {
        if (!$this->isAllowed('create', 'activity')) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to create an activity')
            );
        }

        $form = $this->getActivityForm();
        $form->setData($data);

        if (!$form->isValid()) {
            return false;
        }

        // Find the creator
        $user = $this->userService->getIdentity();

        // Find the organ the activity belongs to, and see if the user has permission to create an activity
        // for this organ. If the id is 0, the activity belongs to no organ.
        $organId = intval($data['organ']);
        $organ = null;

        if ($organId !== 0) {
            $organ = $this->findOrgan($organId);
        }

        // Find the company the activity belongs to. If the id is 0, the activity belongs to no company.
        $companyId = intval($data['company']);
        $company = null;

        if ($companyId !== 0) {
            $company = $this->companyService->getCompanyById($companyId);
        }

        $activity = $this->saveActivityData($data, $user, $organ, $company, ActivityModel::STATUS_TO_APPROVE);

        // Send email to GEFLITST if user checked checkbox of GEFLITST
        if ($activity->getRequireGEFLITST()) {
            $this->requestGEFLITST($activity, $user, $organ);
        }

        return true;
    }

    /**
     * Return activity creation form
     *
     * @return ActivityForm
     */
    public function getActivityForm()
    {
        if (!$this->isAllowed('create', 'activity')) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to create an activity')
            );
        }

        return $this->activityForm;
    }

    /**
     * Find the organ the activity belongs to, and see if the user has permission to create an activity
     * for this organ.
     *
     * @param int $organId The id of the organ associated with the activity
     * @return Organ The organ associated with the activity, if the user is a member of that organ
     * @throws NotAllowedException if the user is not a member of the organ specified
     */
    protected function findOrgan($organId)
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
     * @param User $user The user that creates this activity
     * @param Organ $organ The organ this activity is associated with
     * @param Company $company The company this activity is associated with
     *
     * @return ActivityModel Activity that was created.
     */
    protected function saveActivityData($data, $user, $organ, $company, $status)
    {
        $activity = new ActivityModel();
        $activity->setBeginTime(new DateTime($data['beginTime']));
        $activity->setEndTime(new DateTime($data['endTime']));

        $activity->setName(new LocalisedText($data['nameEn'], $data['name']));
        $activity->setLocation(new LocalisedText($data['locationEn'], $data['location']));
        $activity->setCosts(new LocalisedText($data['costsEn'], $data['costs']));
        $activity->setDescription(new LocalisedText($data['descriptionEn'], $data['description']));

        $activity->setIsMyFuture($data['isMyFuture']);
        $activity->setRequireGEFLITST($data['requireGEFLITST']);

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
                // Zend\Stdlib\Parameters is required to prevent undefined indices.
                $signupList = $this->createSignupList(new Parameters($signupList), $activity);
                $em->persist($signupList);
            }
            $em->flush();
        }

        $em->persist($activity);
        $em->flush();

        // Send an email when a new Activity was created, but do not send one
        // when an activity is updated. This e-mail is handled in
        // `$this->createUpdateProposal()`.
        if ($status !== ActivityModel::STATUS_UPDATE) {
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
     * @param array|Parameters $data
     * @param ActivityModel $activity
     * @return SignupListModel
     */
    public function createSignupList($data, $activity)
    {
        $signupList = new SignupListModel();

        $signupList->setActivity($activity);
        $signupList->setName(new LocalisedText($data['nameEn'], $data['name']));
        $signupList->setOpenDate(new DateTime($data['openDate']));
        $signupList->setCloseDate(new DateTime($data['closeDate']));

        $signupList->setOnlyGEWIS($data['onlyGEWIS']);
        $signupList->setDisplaySubscribedNumber($data['displaySubscribedNumber']);

        if (isset($data['fields'])) {
            $em = $this->entityManager;

            foreach ($data['fields'] as $field) {
                // Zend\Stdlib\Parameters is required to prevent undefined indices.
                $field = $this->createSignupField(new Parameters($field), $signupList);
                $em->persist($field);
            }
            $em->flush();
        }

        return $signupList;
    }

    /**
     * Create a new field
     *
     * @pre $data is valid data of Activity\Form\SignupListFields
     *
     * @param array|Parameters $data Parameters for the new field.
     * @param SignupListModel $activity The SignupList the field belongs to.
     *
     * @return ActivityField The new field.
     */
    public function createSignupField($data, $signupList)
    {
        $field = new SignupFieldModel();

        $field->setSignupList($signupList);
        $field->setName(new LocalisedText($data['nameEn'], $data['name']));
        $field->setType($data['type']);

        if ($data['type'] === '2') {
            $field->setMinimumValue($data['minimumValue']);
            $field->setMaximumValue($data['maximumValue']);
        }

        if ($data['type'] === '3') {
            $this->createSignupOption($data, $field);
        }

        return $field;
    }

    /**
     * Creates options for both languages specified and adds it to $field.
     * If no languages are specified, this method does nothing.
     * @pre The options corresponding to the languages specified are filled in
     * $params. If both languages are specified, they must have the same amount of options.
     *
     * @param array $data The array containing the options strings.
     * @param SignupFieldModel $field The field to add the options to.
     */
    protected function createSignupOption($data, $field)
    {
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

        for ($i = 0; $i < $numOptions; $i++) {
            $option = new SignupOptionModel();
            $option->setValue(new LocalisedText(
                isset($data['optionsEn']) ? $optionsEn[$i] : null,
                isset($data['options']) ? $options[$i] : null
            ));
            $option->setField($field);
            $em->persist($option);
        }

        $em->flush();
    }

    /**
     * @param $activity ActivityModel
     * @param $user User
     * @param $organ Organ
     */
    private function requestGEFLITST($activity, $user, $organ)
    {
        // Default to an English title, otherwise use the Dutch title
        $activityTitle = $activity->getName()->getText('en');
        $activityTime = $activity->getBeginTime()->format('d-m-Y H:i');

        $type = 'activity_creation_require_GEFLITST';
        $view = 'email/activity_created_require_GEFLITST';

        if ($organ != null) {
            $subject = sprintf('%s: %s on %s', $organ->getAbbr(), $activityTitle, $activityTime);

            $organInfo = $organ->getApprovedOrganInformation();
            if ($organInfo != null && $organInfo->getEmail() != null) {
                $this->emailService->sendEmailAsOrgan(
                    $type,
                    $view,
                    $subject,
                    ['activity' => $activity, 'requester' => $organ->getName()],
                    $organInfo
                );
            } else {
                // The organ did not fill in it's email address, so send the email as the requested user.
                $this->emailService->sendEmailAsUser(
                    $type,
                    $view,
                    $subject,
                    ['activity' => $activity, 'requester' => $organ->getName()],
                    $user
                );
            }
        } else {
            $subject = sprintf('Member Initiative: %s on %s', $activityTitle, $activityTime);

            $this->emailService->sendEmailAsUser(
                $type,
                $view,
                $subject,
                ['activity' => $activity, 'requester' => $user->getMember()->getFullName()],
                $user
            );
        }
    }

    /**
     * Create a new update proposal from user form.
     *
     * @param ActivityModel $currentActivity
     * @param array $data
     * @return bool indicating whether the update was applied or is pending
     */
    public function createUpdateProposal(ActivityModel $currentActivity, Parameters $data)
    {
        if (!$this->isAllowed('update', $currentActivity)) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to update this activity')
            );
        }

        $form = $this->getActivityForm();
        $form->setData($data);

        if (!$form->isValid()) {
            return false;
        }

        // Find the creator
        $user = $this->userService->getIdentity();

        // Find the organ the activity belongs to, and see if the user has permission to create an activity
        // for this organ. If the id is 0, the activity belongs to no organ.
        $organId = intval($data['organ']);
        $organ = null;

        if ($organId !== 0) {
            $organ = $this->findOrgan($organId);
        }

        // Find the company the activity belongs to. If the id is 0, the activity belongs to no company.
        $companyId = intval($data['company']);
        $company = null;

        if ($companyId !== 0) {
            $company = $this->companyService->getCompanyById($companyId);
        }

        $currentActivityArray = $currentActivity->toArray();
        $proposalActivityArray = $data->toArray();

        $proposalActivityArray['company'] = is_null($company) ? null : $company->getId();
        $proposalActivityArray['organ'] = is_null($organ) ? null : $organ->getId();

        if (!$this->isUpdateProposalNew($currentActivityArray, $proposalActivityArray)) {
            return false;
        }

        $newActivity = $this->saveActivityData(
            $data,
            $user,
            $organ,
            $company,
            ActivityModel::STATUS_UPDATE
        );

        $em = $this->entityManager;

        // TODO: ->count and ->unwrap are undefined
        if ($currentActivity->getUpdateProposal()->count() !== 0) {
            $proposal = $currentActivity->getUpdateProposal()->unwrap()->first();
            //Remove old update proposal
            $oldUpdate = $proposal->getNew();
            $proposal->setNew($newActivity);
            $em->remove($oldUpdate);
            $em->flush();
        } else {
            $proposal = new ActivityProposalModel();
            $proposal->setOld($currentActivity);
            $proposal->setNew($newActivity);
            $em->persist($proposal);
            $em->flush();
        }

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
     * @return boolean
     */
    protected function isUpdateProposalNew($current, $proposal)
    {
        unset($current['id']);

        // Convert all DateTimes in the original Activity to strings.
        array_walk_recursive($current, function (&$v, $k) {
            if ($v instanceof DateTime) {
                $v = $v->format('Y/m/d H:i');
            }
        });

        // We do not need the ActivityCategory models, hence we replace it with the ids of each one. However, it is no
        // longer a model and requires array access to get the id.
        array_walk($current['categories'], function (&$v, $k) {
            $v = strval($v['id']);
        });

        // HTML forms do not know anything about booleans, hence we need to
        // convert the strings to something we can use.
        array_walk_recursive($proposal, function (&$v, $k) {
            if (in_array($k, ['isMyFuture', 'requireGEFLITST', 'onlyGEWIS', 'displaySubscribedNumber'], true)) {
                $v = boolval($v);
            }
        });

        // Options are a string after submission, not an array of strings. It is easier to explode the values of
        // `$proposal` instead of having to implode `$current` (which requires an extra `array_filter()`).
        if (isset($proposal['signupLists'])) {
            foreach ($proposal['signupLists'] as $keyOuter => $signupList) {
                foreach ($signupList['fields'] as $keyInner => $field) {
                    if (array_key_exists('options', $field)) {
                        $proposal['signupLists'][$keyOuter]['fields'][$keyInner]['options'] = explode(
                            ',',
                            $field['options']
                        );
                    }

                    if (array_key_exists('optionsEn', $field)) {
                        $proposal['signupLists'][$keyOuter]['fields'][$keyInner]['optionsEn'] = explode(
                            ',',
                            $field['optionsEn']
                        );
                    }
                }
            }
        }

        // Remove some of the form attributes.
        unset($proposal['language_dutch'], $proposal['language_english'], $proposal['submit']);

        // Get the difference between the original Activity and the update
        // proposal. We unset all `id`s after getting the diff to reduce the
        // number of calls.
        $diff = $this->array_diff_assoc_recursive($current, $proposal);
        $this->recursiveUnsetKey($diff, 'id');

        // Filter out all empty parts of the difference, if we an empty result
        // nothing has changed on form submission.
        if (empty($this->array_filter_recursive($diff))) {
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
     * @return array
     */
    protected function array_diff_assoc_recursive(array $array1, array $array2)
    {
        $difference = [];

        foreach ($array1 as $key => $value) {
            if (is_array($value)) {
                if (!array_key_exists($key, $array2) || !is_array($array2[$key])) {
                    $difference[$key] = $value;
                } else {
                    $newDifference = $this->array_diff_assoc_recursive($value, $array2[$key]);

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
    protected function recursiveUnsetKey(&$array, $key)
    {
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
     * @return array
     */
    protected function array_filter_recursive(array $array)
    {
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $array[$key] = $this->array_filter_recursive($array[$key]);
            }

            if (in_array($array[$key], ['', null, []], true)) {
                unset($array[$key]);
            }
        }

        return $array;
    }

    /**
     * Checks whether the current user is allowed to apply an update proposal for the given activity
     *
     * @param ActivityModel $activity
     * @return bool indicating whether the update may be applied
     */
    protected function canApplyUpdateProposal(ActivityModel $activity)
    {
        if ($this->isAllowed('update', 'activity')) {
            return true;
        }

        if (!$this->isAllowed('update', $activity)) {
            return false;
        }

        // If the activity has not been approved the update proposal can be applied.
        return $activity->getStatus() === ActivityModel::STATUS_TO_APPROVE;
    }

    /**
     * Apply a proposed activity update
     *
     * @param ActivityProposalModel $proposal
     */
    public function updateActivity(ActivityProposalModel $proposal)
    {
        $old = $proposal->getOld();
        $new = $proposal->getNew();

        // If the old activity was already approved, keep it approved.
        // Otherwise the status of the new Activity becomes
        // ActivityModel::STATUS_TO_APPROVE.
        if ($old->getStatus() !== ActivityModel::STATUS_APPROVED) {
            $new->setStatus(ActivityModel::STATUS_TO_APPROVE);
        } else {
            $new->setStatus(ActivityModel::STATUS_APPROVED);
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
     * (The potentially updated activity remains untouched)
     *
     * @param ActivityProposalModel $proposal
     */
    public function revokeUpdateProposal(ActivityProposalModel $proposal)
    {
        $em = $this->entityManager;
        $new = $proposal->getNew();
        $em->remove($proposal);
        $em->remove($new);
        $em->flush();
    }

    /**
     * Approve of an activity
     *
     * @param ActivityModel $activity
     */
    public function approve(ActivityModel $activity)
    {
        if (!$this->isAllowed('approve', 'activity')) {

            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to change the status of the activity')
            );
        }
        $activity->setStatus(ActivityModel::STATUS_APPROVED);
        $em = $this->entityManager;
        $em->persist($activity);
        $em->flush();
    }

    /**
     * Reset the approval status of an activity
     *
     * @param ActivityModel $activity
     */
    public function reset(ActivityModel $activity)
    {
        if (!$this->isAllowed('reset', 'activity')) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to change the status of the activity')
            );
        }

        $activity->setStatus(ActivityModel::STATUS_TO_APPROVE);
        $em = $this->entityManager;
        $em->persist($activity);
        $em->flush();
    }

    /**
     * Disapprove of an activity
     *
     * @param ActivityModel $activity
     */
    public function disapprove(ActivityModel $activity)
    {
        if (!$this->isAllowed('disapprove', 'activity')) {

            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to change the status of the activity')
            );
        }

        $activity->setStatus(ActivityModel::STATUS_DISAPPROVED);
        $em = $this->entityManager;
        $em->persist($activity);
        $em->flush();
    }

    /**
     * Get the default resource ID.
     *
     * This is used by {@link isAllowed()} when no resource is specified.
     *
     * @return string
     */
    protected function getDefaultResourceId()
    {
        return 'activity';
    }
}
