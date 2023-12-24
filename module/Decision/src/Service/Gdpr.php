<?php

declare(strict_types=1);

namespace Decision\Service;

use Activity\Mapper\Activity as ActivityMapper;
use Activity\Mapper\Signup as SignupMapper;
use Activity\Model\Activity as ActivityModel;
use Activity\Model\Signup as SignupModel;
use Company\Mapper\Company as CompanyMapper;
use Company\Mapper\Job as JobMapper;
use Decision\Mapper\Authorization as AuthorizationMapper;
use Decision\Mapper\Member as MemberMapper;
use Decision\Mapper\SubDecision as SubDecisionMapper;
use Decision\Model\Address as AddressModel;
use Decision\Model\Authorization as AuthorizationModel;
use Decision\Model\MailingList as MailingListModel;
use Decision\Model\Member as MemberModel;
use Decision\Model\SubDecision as SubDecisionModel;
use Education\Mapper\CourseDocument as CourseDocumentMapper;
use Education\Model\CourseDocument as CourseDocumentModel;
use Frontpage\Mapper\Poll as PollMapper;
use Frontpage\Mapper\PollComment as PollCommentMapper;
use Frontpage\Model\Poll as PollModel;
use Frontpage\Model\PollComment as PollCommentModel;
use Frontpage\Model\PollVote as PollVoteModel;
use Laminas\Mvc\I18n\Translator;
use Photo\Mapper\Photo as PhotoMapper;
use Photo\Mapper\ProfilePhoto as ProfilePhotoMapper;
use Photo\Mapper\Tag as TagMapper;
use Photo\Mapper\Vote as VoteMapper;
use Photo\Model\Photo as PhotoModel;
use Photo\Model\ProfilePhoto as ProfilePhotoModel;
use Photo\Model\Tag as TagModel;
use Photo\Model\Vote as VoteModel;
use User\Mapper\ApiAppAuthentication as ApiAppAuthenticationMapper;
use User\Mapper\LoginAttempt as LoginAttemptMapper;
use User\Mapper\User as UserMapper;
use User\Model\ApiAppAuthentication as ApiAppAuthenticationModel;
use User\Model\LoginAttempt as LoginAttemptModel;
use User\Model\User as UserModel;
use User\Permissions\NotAllowedException;

/**
 * GDPR service.
 *
 * @psalm-import-type ActivityGdprArrayType from ActivityModel as ImportedActivityGdprArrayType
 * @psalm-import-type AddressGdprArrayType from AddressModel as ImportedAddressGdprArrayType
 * @psalm-import-type ApiAppAuthenticationGdprArrayType from ApiAppAuthenticationModel as ImportedApiAppAuthenticationGdprArrayType
 * @psalm-type ImportedApprovableTraitGdprArrayType = array{
 *     id: int,
 *     approved: int,
 *     approvedAt: ?string,
 *     approvableText: ?string,
 * } // Psalm does not (yet) support importing types from traits, as such this is re-defined here.
 * @psalm-import-type AuthorizationGdprArrayType from AuthorizationModel as ImportedAuthorizationGdprArrayType
 * @psalm-import-type CourseDocumentGdprArrayType from CourseDocumentModel as ImportedCourseDocumentGdprArrayType
 * @psalm-import-type LoginAttemptGdprArrayType from LoginAttemptModel as ImportedLoginAttemptGdprArrayType
 * @psalm-import-type MailingListGdprArrayType from MailingListModel as ImportedMailingListGdprArrayType
 * @psalm-import-type MemberGdprArrayType from MemberModel as ImportedMemberGdprArrayType
 * @psalm-import-type PhotoGdprArrayType from PhotoModel as ImportedPhotoGdprArrayType
 * @psalm-import-type PollGdprArrayType from PollModel as ImportedPollGdprArrayType
 * @psalm-import-type PollCommentGdprArrayType from PollCommentModel as ImportedPollCommentGdprArrayType
 * @psalm-import-type PollVoteGdprArrayType from PollVoteModel as ImportedPollVoteGdprArrayType
 * @psalm-import-type ProfilePhotoGdprArrayType from ProfilePhotoModel as ImportedProfilePhotoGdprArrayType
 * @psalm-import-type SignupGdprArrayType from SignupModel as ImportedSignupGdprArrayType
 * @psalm-import-type SubDecisionGdprArrayType from SubDecisionModel as ImportedSubDecisionGdprArrayType
 * @psalm-import-type TagGdprArrayType from TagModel as ImportedTagGdprArrayType
 * @psalm-import-type UserGdprArrayType from UserModel as ImportedUserGdprArrayType
 * @psalm-import-type VoteGdprArrayType from VoteModel as ImportedVoteGdprArrayType
 */
class Gdpr
{
    public function __construct(
        private readonly AclService $aclService,
        private readonly Translator $translator,
        private readonly ActivityMapper $activityMapper,
        private readonly ApiAppAuthenticationMapper $apiAppAuthenticationMapper,
        private readonly AuthorizationMapper $authorizationMapper,
        private readonly CompanyMapper $companyMapper,
        private readonly CourseDocumentMapper $courseDocumentMapper,
        private readonly JobMapper $jobMapper,
        private readonly LoginAttemptMapper $loginAttemptMapper,
        private readonly MemberMapper $memberMapper,
        private readonly PollMapper $pollMapper,
        private readonly PollCommentMapper $pollCommentMapper,
        private readonly PhotoMapper $photoMapper,
        private readonly ProfilePhotoMapper $profilePhotoMapper,
        private readonly SignupMapper $signupMapper,
        private readonly SubDecisionMapper $subDecisionMapper,
        private readonly TagMapper $tagMapper,
        private readonly UserMapper $userMapper,
        private readonly VoteMapper $voteMapper,
    ) {
    }

    /**
     * @return array<never, never>|array{
     *     member: array{
     *         information: ImportedMemberGdprArrayType,
     *         user_information: ?ImportedUserGdprArrayType,
     *         profile_photo: ?ImportedProfilePhotoGdprArrayType,
     *         addresses: ImportedAddressGdprArrayType[],
     *         lists: ImportedMailingListGdprArrayType[],
     *         login_attempts: ImportedLoginAttemptGdprArrayType[],
     *         app_authentications: ImportedApiAppAuthenticationGdprArrayType[],
     *     },
     *     activities: array{
     *         signups: ImportedSignupGdprArrayType[],
     *         created: ImportedActivityGdprArrayType[],
     *         approved: array<array-key, array{id: int}>,
     *     },
     *     companies: array{
     *         approved: array{
     *             companies: ImportedApprovableTraitGdprArrayType[],
     *             jobs: ImportedApprovableTraitGdprArrayType[],
     *         },
     *     },
     *     decisions: array{
     *         sub_decisions: ImportedSubDecisionGdprArrayType[],
     *         meeting_authorizations: array{
     *             sent: ImportedAuthorizationGdprArrayType[],
     *             received: ImportedAuthorizationGdprArrayType[],
     *         },
     *     },
     *     education: array{
     *         authored_documents: ImportedCourseDocumentGdprArrayType[],
     *     },
     *     photos: array{
     *         tags: ImportedTagGdprArrayType[],
     *         votes: ImportedVoteGdprArrayType[],
     *         photographer: ImportedPhotoGdprArrayType[],
     *     },
     *     polls: array{
     *         votes: ImportedPollVoteGdprArrayType[],
     *         comments: ImportedPollCommentGdprArrayType[],
     *         created: ImportedPollGdprArrayType[],
     *         approved: ImportedPollGdprArrayType[],
     *     },
     * }
     */
    public function getMemberData(int $lidnr): array
    {
        if (!$this->aclService->isAllowed('export', 'gdpr')) {
            // This is a very sensitive action, so we do this ACL check twice.
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to export member data'),
            );
        }

        $member = $this->memberMapper->findByLidnr($lidnr);

        if (null === $member) {
            return [];
        }

        /// MEMBER INFORMATION
        /** @var ImportedAddressGdprArrayType[] $addresses */
        $addresses = [];
        foreach ($member->getAddresses() as $address) {
            $addresses[] = $address->toGdprArray();
        }

        /** @var ImportedMailingListGdprArrayType[] $lists */
        $lists = [];
        foreach ($member->getLists() as $list) {
            $lists[] = $list->toGdprArray();
        }

        /** @var ImportedLoginAttemptGdprArrayType[] $loginAttempts */
        $loginAttempts = [];
        foreach ($this->loginAttemptMapper->getAttemptsByMember($member) as $loginAttempt) {
            $loginAttempts[] = $loginAttempt->toGdprArray();
        }

        /** @var ImportedApiAppAuthenticationGdprArrayType[] $apiAppAuthentications */
        $apiAppAuthentications = [];
        foreach ($this->apiAppAuthenticationMapper->getMemberAuthenticationsPerApiApp($member) as $appAuthentication) {
            $apiAppAuthentications[] = $appAuthentication->toGdprArray();
        }

        /// ACTIVITY INFORMATION
        /** @var ImportedSignupGdprArrayType[] $signups */
        $signups = [];
        foreach ($this->signupMapper->findSignupsByMember($member) as $signup) {
            $signups[] = $signup->toGdprArray();
        }

        /** @var ImportedActivityGdprArrayType[] $createdActivities */
        $createdActivities = [];
        foreach ($this->activityMapper->findAllActivitiesCreatedByMember($member) as $activity) {
            $createdActivities[] = $activity->toGdprArray();
        }

        /** @var array<array-key, array{id: int}> $approvedActivities */
        $approvedActivities = [];
        foreach ($this->activityMapper->findAllActivitiesApprovedByMember($member) as $activity) {
            // TODO: When implementing GH-1685 update this to be similar to how it is done in the company module.
            $approvedActivities[] = ['id' => $activity->getId()];
        }

        /// COMPANY INFORMATION
        /** @var ImportedApprovableTraitGdprArrayType[] $approvedCompanies */
        $approvedCompanies = [];
        foreach ($this->companyMapper->findAllCompaniesApprovedByMember($member) as $company) {
            $approvedCompanies[] = $company->toGdprArray();
        }

        /** @var ImportedApprovableTraitGdprArrayType[] $approvedJobs */
        $approvedJobs = [];
        foreach ($this->jobMapper->findAllJobsApprovedByMember($member) as $job) {
            $approvedJobs[] = $job->toGdprArray();
        }

        /// DECISION INFORMATION
        /** @var ImportedSubDecisionGdprArrayType[] $subDecisions */
        $subDecisions = [];
        foreach ($this->subDecisionMapper->findByMember($member) as $subDecision) {
            $subDecisions[] = $subDecision->toGdprArray();
        }

        /** @var ImportedAuthorizationGdprArrayType[] $sentAuthorizations */
        $sentAuthorizations = [];
        foreach ($this->authorizationMapper->findByMember($member, true) as $authorization) {
            $sentAuthorizations[] = $authorization->toGdprArray();
        }

        /** @var ImportedAuthorizationGdprArrayType[] $receivedAuthorizations */
        $receivedAuthorizations = [];
        foreach ($this->authorizationMapper->findByMember($member, false) as $authorization) {
            $receivedAuthorizations[] = $authorization->toGdprArray();
        }

        /// EDUCATION INFORMATION
        /** @var ImportedCourseDocumentGdprArrayType[] $summaries */
        $summaries = [];
        foreach ($this->courseDocumentMapper->findSummariesByAuthor($member) as $summary) {
            $summaries[] = $summary->toGdprArray();
        }

        /// PHOTO INFORMATION
        /** @var ImportedTagGdprArrayType[] $tags */
        $tags = [];
        foreach ($this->tagMapper->getTagsByLidnr($lidnr) as $tag) {
            $tags[] = $tag->toGdprArray();
        }

        /** @var ImportedVoteGdprArrayType[] $votes */
        $votes = [];
        foreach ($this->voteMapper->getVotesByLidnr($lidnr) as $vote) {
            $votes[] = $vote->toGdprArray();
        }

        /** @var ImportedPhotoGdprArrayType[] $photos */
        $photos = [];
        foreach ($this->photoMapper->findPhotosByMember($member) as $photo) {
            $photos[] = $photo->toGdprArray();
        }

        /// POLL INFORMATION
        /** @var ImportedPollVoteGdprArrayType[] $pollVotes */
        $pollVotes = [];
        foreach ($this->pollMapper->findVotesByMember($member) as $pollVote) {
            $pollVotes[] = $pollVote->toGdprArray();
        }

        /** @var ImportedPollCommentGdprArrayType[] $pollComments */
        $pollComments = [];
        foreach ($this->pollCommentMapper->findByMember($member) as $pollComment) {
            $pollComments[] = $pollComment->toGdprArray();
        }

        /** @var ImportedPollGdprArrayType[] $createdPolls */
        $createdPolls = [];
        foreach ($this->pollMapper->findPollsCreatedByMember($member) as $poll) {
            $createdPolls[] = $poll->toGdprArray();
        }

        /** @var ImportedPollGdprArrayType[] $approvedPolls */
        $approvedPolls = [];
        foreach ($this->pollMapper->findPollsApprovedByMember($member) as $poll) {
            $approvedPolls[] = $poll->toGdprArray();
        }

        return [
            'member' => [
                'information' => $member->toGdprArray(),
                'user_information' => $this->userMapper->find($member->getLidnr())?->toGdprArray(),
                'profile_photo' => $this->profilePhotoMapper->getProfilePhotoByLidnr($lidnr)?->toGdprArray() ?? null,
                'addresses' => $addresses,
                'lists' => $lists,
                'login_attempts' => $loginAttempts,
                'app_authentications' => $apiAppAuthentications,
            ],
            'activities' => [
                'signups' => $signups,
                'created' => $createdActivities,
                'approved' => $approvedActivities,
            ],
            'companies' => [
                'approved' => [
                    'companies' => $approvedCompanies,
                    'jobs' => $approvedJobs,
                ],
            ],
            'decisions' => [
                'sub_decisions' => $subDecisions,
                'meeting_authorizations' => [
                    'sent' => $sentAuthorizations,
                    'received' => $receivedAuthorizations,
                ],
            ],
            'education' => [
                'authored_documents' => $summaries,
            ],
            'photos' => [
                'tags' => $tags,
                'votes' => $votes,
                'photographer' => $photos,
            ],
            'polls' => [
                'votes' => $pollVotes,
                'comments' => $pollComments,
                'created' => $createdPolls,
                'approved' => $approvedPolls,
            ],
        ];
    }
}
