<?php

declare(strict_types=1);

namespace App\DataFixtures\Career;

use App\Entity\Application\Enums\RevisionStatus;
use App\Entity\Career\CareerLocalisedText;
use App\Entity\Career\Company;
use App\Entity\Career\CompanyBannerPackage;
use App\Entity\Career\CompanyRevision;
use App\Entity\Career\CompanyRevisionComment;
use App\Entity\Career\Vacancy;
use App\Entity\Career\VacancyRevision;
use App\Entity\Career\VacancyRevisionComment;
use App\Entity\User\CompanyUser;
use App\Entity\User\CompanyUserInvite;
use App\Entity\User\User;
use DateInterval;
use DateTime;
use DateTimeImmutable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Override;

use function assert;
use function bin2hex;
use function hash;
use function random_bytes;

/**
 * Puts the career review surfaces in every state somebody has to look at, so the approval queue, the status pages and
 * the timeline all have something to show without anybody having to click a company through the workflow by hand.
 *
 * Statuses are set directly rather than driven through the state machine: the guards want an authenticated reviewer,
 * which a fixture has no business inventing, and what these seed is the resulting state rather than the route to it.
 */
class CareerReviewFixture extends Fixture implements DependentFixtureInterface
{
    /** The slogan Orbit Analytics submitted, and the line on the banner they proposed alongside it. */
    private const string SUBMITTED_SLOGAN = 'Turning data into decisions, faster';

    public function __construct(private readonly CompanyImageGenerator $imageGenerator)
    {
    }

    #[Override]
    public function load(ObjectManager $manager): void
    {
        $orbit = $this->getReference(
            'career-company-orbit-analytics',
            Company::class,
        );
        $delta = $this->getReference(
            'career-company-delta-robotics',
            Company::class,
        );
        $orbitRepresentative = $this->getReference(
            'career-company-user-recruitment@orbit-analytics.example.com',
            CompanyUser::class,
        );
        $deltaRepresentative = $this->getReference(
            'career-company-user-recruitment@delta-robotics.example.com',
            CompanyUser::class,
        );
        $reviewer = $this->getReference(
            'user-8003',
            User::class,
        );

        $this->submitProfile(
            $manager,
            $orbit,
            $orbitRepresentative,
        );
        $this->rejectProfile(
            $manager,
            $delta,
            $deltaRepresentative,
            $reviewer,
        );
        $this->requestChangesOnAVacancy(
            $manager,
            $deltaRepresentative,
            $reviewer,
        );
        $this->proposeABanner(
            $manager,
            $orbit,
            $orbitRepresentative,
        );
        $this->inviteSomebody(
            $manager,
            $delta,
            $reviewer,
        );

        $manager->flush();
    }

    /**
     * A profile waiting for the committee: the queue's happy path.
     */
    private function submitProfile(
        ObjectManager $manager,
        Company $company,
        CompanyUser $author,
    ): void {
        $draft = $this->nextProfileDraft($company);
        $draft->setStatus(RevisionStatus::Submitted);
        $draft->setSubmittedAt(new DateTime('-2 days'));
        $draft->setAuthorCompanyUser($author);
        $draft->setSlogan(new CareerLocalisedText(
            self::SUBMITTED_SLOGAN,
            'Sneller van data naar beslissingen',
        ));

        $manager->persist($draft);
    }

    /**
     * A profile the committee turned down, with the reason still attached, so a rejected chain has something to read.
     */
    private function rejectProfile(
        ObjectManager $manager,
        Company $company,
        CompanyUser $author,
        User $reviewer,
    ): void {
        $draft = $this->nextProfileDraft($company);
        $draft->setStatus(RevisionStatus::Rejected);
        $draft->setAuthorCompanyUser($author);
        $draft->setReviewer($reviewer->getMember());
        $draft->setReviewedAt(new DateTime('-3 days'));
        $draft->setDescription(new CareerLocalisedText(
            'Delta Robotics builds cobots. Contact us at info@example.com for a quote.',
            'Delta Robotics bouwt cobots. Neem contact op via info@example.com voor een offerte.',
        ));

        $comment = new CompanyRevisionComment();
        $comment->setRevision($draft);
        $comment->setAuthor($reviewer);
        $comment->setBody(
            'The description reads as an advertisement rather than as a profile. Please tell our members what it is '
            . 'like to work with you instead.',
        );

        $manager->persist($draft);
        $manager->persist($comment);
    }

    /**
     * The most involved case: a vacancy that came back with changes requested, the next draft already spawned, and the
     * conversation between the two still readable.
     */
    private function requestChangesOnAVacancy(
        ObjectManager $manager,
        CompanyUser $author,
        User $reviewer,
    ): void {
        $vacancy = $this->getReference(
            'career-vacancy-controls-engineer',
            Vacancy::class,
        );
        $live = $vacancy->getLiveRevision();
        if (null === $live) {
            return;
        }

        $reviewed = $this->cloneVacancyRevision(
            $vacancy,
            $live,
        );
        $reviewed->setStatus(RevisionStatus::ChangesRequested);
        $reviewed->setAuthorCompanyUser($author);
        $reviewed->setReviewer($reviewer->getMember());
        $reviewed->setReviewedAt(new DateTime('-5 days'));

        $feedback = new VacancyRevisionComment();
        $feedback->setRevision($reviewed);
        $feedback->setAuthor($reviewer);
        $feedback->setBody('Could you say something about the salary range? Our members ask us about it every year.');

        $next = $this->cloneVacancyRevision(
            $vacancy,
            $reviewed,
        );
        $next->setStatus(RevisionStatus::Draft);
        $next->setAuthorCompanyUser($author);
        $vacancy->setCurrentRevision($next);

        $reply = new VacancyRevisionComment();
        $reply->setRevision($next);
        $reply->setAuthorCompanyUser($author);
        $reply->setBody('Added the range to the description. Let us know if it needs to be more specific.');

        $manager->persist($reviewed);
        $manager->persist($feedback);
        $manager->persist($next);
        $manager->persist($reply);
    }

    /**
     * New artwork waiting for the committee next to the banner that is still running. It carries the slogan the
     * company submitted along with it, which is the ordinary reason to redo a banner in the first place.
     */
    private function proposeABanner(
        ObjectManager $manager,
        Company $company,
        CompanyUser $author,
    ): void {
        foreach ($company->getPackages() as $package) {
            if (!$package instanceof CompanyBannerPackage) {
                continue;
            }

            $package->proposeImage(
                $this->imageGenerator->storeBanner(
                    $company,
                    $package->getFormat(),
                    self::SUBMITTED_SLOGAN,
                ),
                $author,
            );
            $manager->persist($package);

            return;
        }
    }

    private function inviteSomebody(
        ObjectManager $manager,
        Company $company,
        User $invitedBy,
    ): void {
        $verifier = bin2hex(random_bytes(32));

        $manager->persist(new CompanyUserInvite(
            $company,
            'new-recruiter@delta-robotics.example.com',
            'Tessa Vermeulen',
            $invitedBy,
            bin2hex(random_bytes(16)),
            hash(
                CompanyUserInvite::HASH_ALGO,
                $verifier,
            ),
            new DateTimeImmutable('now')->add(new DateInterval('P7D')),
        ));
    }

    /**
     * A fresh draft on top of the company's working head, linked into the chain and made the working head itself.
     */
    private function nextProfileDraft(Company $company): CompanyRevision
    {
        $source = $company->getCurrentRevision();
        assert($source instanceof CompanyRevision);

        $draft = new CompanyRevision();
        $draft->setRevisionNumber($source->getRevisionNumber() + 1);
        $draft->setPreviousRevision($source);
        $draft->setSlogan($source->getSlogan()->copy());
        $draft->setWebsite($source->getWebsite()->copy());
        $draft->setDescription($source->getDescription()->copy());
        $draft->setSquareLogo($source->getSquareLogo());
        $draft->setBannerLogo($source->getBannerLogo());
        $draft->setContactName($source->getContactName());
        $draft->setContactEmail($source->getContactEmail());
        $draft->setContactPhone($source->getContactPhone());
        $draft->setContactAddress($source->getContactAddress());

        $company->addRevision($draft);
        $company->setCurrentRevision($draft);

        return $draft;
    }

    private function cloneVacancyRevision(
        Vacancy $vacancy,
        VacancyRevision $source,
    ): VacancyRevision {
        $draft = new VacancyRevision();
        $draft->setRevisionNumber($source->getRevisionNumber() + 1);
        $draft->setPreviousRevision($source);
        $draft->setName($source->getName()->copy());
        $draft->setLocation($source->getLocation()->copy());
        $draft->setWebsite($source->getWebsite()->copy());
        $draft->setDescription($source->getDescription()->copy());
        $draft->setAttachment($source->getAttachment()->copy());
        $draft->setContactName($source->getContactName());
        $draft->setContactPhone($source->getContactPhone());
        $draft->setContactEmail($source->getContactEmail());
        $draft->setCategory($source->getCategory());
        $draft->setStartDate($source->getStartDate());
        $draft->setEndDate($source->getEndDate());
        $draft->addLabels($source->getLabels()->toArray());

        $vacancy->addRevision($draft);

        return $draft;
    }

    /**
     * @return array<array-key, class-string<Fixture>>
     */
    #[Override]
    public function getDependencies(): array
    {
        return [
            CompanyUserFixture::class,
        ];
    }
}
