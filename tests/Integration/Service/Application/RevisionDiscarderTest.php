<?php

declare(strict_types=1);

namespace App\Tests\Integration\Service\Application;

use App\Entity\Activity\Activity;
use App\Entity\Application\AbstractRevisionComment;
use App\Entity\Application\RevisionInterface;
use App\Entity\Career\Company;
use App\Entity\Career\Vacancy;
use App\Entity\User\User;
use App\Service\Application\RevisionDiscarder;
use App\Tests\Integration\DatabaseTestCase;
use App\Workflow\RevisionClonerRegistry;
use Doctrine\DBAL\Types\Types;

use function intval;

/**
 * Discarding a draft re-edit must put the aggregate back exactly as it was: it points at its approved (live) revision
 * again and the abandoned draft is gone together with its review thread. The comments matter because they reference the
 * revision with a NON-cascading foreign key, so a plain `remove($revision)` would fail on them; the service removes
 * them first.
 *
 * Exercised against all three revisable domains. The discarder reads the comment class off the revision, so a domain
 * that answers that wrong loses its thread rows silently — which is exactly what the career half of this had no
 * coverage for.
 */
final class RevisionDiscarderTest extends DatabaseTestCase
{
    public function testAnActivityFallsBackToItsApprovedRevision(): void
    {
        $this->assertDiscardRevertsTo($this->anApprovedActivityWithoutSignupLists());
    }

    public function testACompanyProfileFallsBackToItsApprovedRevision(): void
    {
        $company = $this->entityManager->getRepository(Company::class)->findOneBy(['slugName' => 'nexunt']);
        self::assertInstanceOf(
            Company::class,
            $company,
        );

        $this->assertDiscardRevertsTo($company);
    }

    public function testAVacancyFallsBackToItsApprovedRevision(): void
    {
        $vacancy = $this->entityManager->createQueryBuilder()
            ->select('v')
            ->from(
                Vacancy::class,
                'v',
            )
            ->where('v.currentRevision = v.liveRevision')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
        self::assertInstanceOf(
            Vacancy::class,
            $vacancy,
            'The seed is expected to contain an approved vacancy.',
        );

        $this->assertDiscardRevertsTo($vacancy);
    }

    /**
     * Spawn a draft off the aggregate's live revision the way the application does, comment on it, discard it, and
     * assert both the fallback and the removal.
     */
    private function assertDiscardRevertsTo(Activity|Company|Vacancy $revisable): void
    {
        $live = $revisable->getLiveRevision();
        self::assertNotNull(
            $live,
            'The subject is expected to have an approved revision to fall back to.',
        );

        $draft = self::getContainer()->get(RevisionClonerRegistry::class)->cloneAsDraft($live);
        $this->entityManager->persist($draft);
        $comment = $this->commentOn($draft);
        $this->entityManager->flush();

        $draftId = (int) $draft->getId();
        $commentId = (int) $comment->getId();
        $revisionClass = $draft::class;
        $commentClass = $draft->getCommentClass();

        self::assertSame(
            $draft,
            $revisable->getCurrentRevision(),
        );

        self::getContainer()->get(RevisionDiscarder::class)->discardToLive($draft);
        $this->entityManager->flush();

        self::assertSame(
            $live,
            $revisable->getCurrentRevision(),
        );
        self::assertSame(
            0,
            $this->countRows(
                $revisionClass,
                $draftId,
            ),
        );
        self::assertSame(
            0,
            $this->countRows(
                $commentClass,
                $commentId,
            ),
        );
    }

    /**
     * Whether a row of the given entity class still exists. Queried rather than fetched through a repository because
     * the class only becomes known at runtime.
     *
     * @param class-string $class
     */
    private function countRows(
        string $class,
        int $id,
    ): int {
        return intval($this->entityManager->createQueryBuilder()
            ->select('COUNT(e.id)')
            ->from(
                $class,
                'e',
            )
            ->where('e.id = :id')
            ->setParameter(
                'id',
                $id,
                Types::INTEGER,
            )
            ->getQuery()
            ->getSingleScalarResult());
    }

    /**
     * An approved activity whose live revision carries no sign-up lists, so cloning it into a draft needs no extra
     * cascade handling and the discard path stays the subject under test.
     */
    private function anApprovedActivityWithoutSignupLists(): Activity
    {
        $activity = $this->entityManager->createQueryBuilder()
            ->select('a')
            ->from(
                Activity::class,
                'a',
            )
            ->join(
                'a.liveRevision',
                'lr',
            )
            ->where('a.currentRevision = a.liveRevision')
            ->andWhere('SIZE(lr.signupLists) = 0')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
        self::assertInstanceOf(
            Activity::class,
            $activity,
            'The seed is expected to contain an approved activity without sign-up lists.',
        );

        return $activity;
    }

    private function commentOn(RevisionInterface $revision): AbstractRevisionComment
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy([]);
        self::assertInstanceOf(
            User::class,
            $user,
        );

        $class = $revision->getCommentClass();
        $comment = new $class();
        $comment->attachTo($revision);
        $comment->setAuthor($user);
        $comment->setBody('Please reconsider this.');
        $this->entityManager->persist($comment);

        return $comment;
    }
}
