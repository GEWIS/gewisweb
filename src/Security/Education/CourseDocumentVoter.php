<?php

declare(strict_types=1);

namespace App\Security\Education;

use App\Entity\Education\CourseDocument;
use App\Entity\User\Enums\UserRoles;
use App\Service\Education\CampusNetworkChecker;
use Override;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Authorizes downloading a {@see CourseDocument}.
 *
 * The department shares its exams on the understanding that they stay within the university, so a document may be
 * downloaded by a logged-in member from anywhere, or by anyone from a machine on the TU/e campus network. Everyone else
 * still sees that it exists: browsing and searching the archive is open to all.
 *
 * Company users are deliberately not covered: a corporate account is not a member, and being logged in as one says
 * nothing about being entitled to course material.
 *
 * @extends Voter<string, CourseDocument>
 */
final class CourseDocumentVoter extends Voter
{
    public const string DOWNLOAD = 'COURSE_DOCUMENT_DOWNLOAD';

    public function __construct(
        private readonly Security $security,
        private readonly CampusNetworkChecker $campusNetworkChecker,
    ) {
    }

    #[Override]
    protected function supports(
        string $attribute,
        mixed $subject,
    ): bool {
        return self::DOWNLOAD === $attribute
            && $subject instanceof CourseDocument;
    }

    #[Override]
    protected function voteOnAttribute(
        string $attribute,
        mixed $subject,
        TokenInterface $token,
        ?Vote $vote = null,
    ): bool {
        return match ($attribute) {
            self::DOWNLOAD => $this->canDownload($subject),
            default => false,
        };
    }

    private function canDownload(CourseDocument $document): bool
    {
        // Nothing to hand out until the document has been rasterized: a download is rebuilt from its pages.
        if (!$document->isDownloadable()) {
            return false;
        }

        return $this->security->isGranted(UserRoles::User->value)
            || $this->campusNetworkChecker->isOnCampus();
    }
}
