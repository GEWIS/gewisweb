<?php

declare(strict_types=1);

namespace App\Controller\Decision;

use App\Entity\Decision\Enums\OrganTypes;
use App\Entity\Decision\Organ;
use App\Repository\Activity\ActivityRepository;
use App\Repository\Decision\OrganRepository;
use App\Service\Decision\OrganMemberService;
use App\ViewModel\Decision\BodyIteration;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

use function strlen;

/**
 * The bodies of the association, as anybody may read them: an overview per kind, and a page per body carrying whatever
 * that body wrote about itself.
 *
 * Only what the board has approved is shown; a page with nothing approved yet renders as a body with no page, which is
 * also what a body that never wrote one looks like.
 *
 * An abbreviation is reused over the years, so a body's address may carry the year it was founded. Without one the
 * newest body under those letters answers, which is what an old bookmark and a search result expect.
 */
class BodyController extends AbstractController
{
    /** How many upcoming activities fit beside a body's page. */
    private const int UPCOMING_ACTIVITIES = 5;

    public function __construct(
        private readonly OrganRepository $organRepository,
        private readonly ActivityRepository $activityRepository,
        private readonly OrganMemberService $organMembers,
    ) {
    }

    #[Route(
        path: '/association/committees',
        name: 'association/committees',
    )]
    public function committees(): Response
    {
        return $this->render('decision/bodies/committees.html.twig');
    }

    #[Route(
        path: '/association/history/committees',
        name: 'association/committees/history',
    )]
    public function historicalCommittees(): Response
    {
        return $this->render('decision/bodies/historical-committees.html.twig');
    }

    #[Route(
        path: '/association/fraternities',
        name: 'association/fraternities',
    )]
    public function fraternities(): Response
    {
        return $this->render('decision/bodies/fraternities.html.twig');
    }

    #[Route(
        path: '/association/gmm-bodies/gmm-committees',
        name: 'association/gmm-bodies/committees',
    )]
    public function gmmCommittees(): Response
    {
        return $this->gmmBodies(OrganTypes::AVC);
    }

    #[Route(
        path: '/association/gmm-bodies/gmm-taskforces',
        name: 'association/gmm-bodies/taskforces',
    )]
    public function gmmTaskforces(): Response
    {
        return $this->gmmBodies(OrganTypes::AVW);
    }

    #[Route(
        path: '/association/gmm-bodies/financial-audit-committees',
        name: 'association/gmm-bodies/financial-audit-committees',
    )]
    public function financialAuditCommittees(): Response
    {
        return $this->gmmBodies(OrganTypes::KCC);
    }

    #[Route(
        path: '/association/gmm-bodies/advisory-boards',
        name: 'association/gmm-bodies/advisory-boards',
    )]
    public function advisoryBoards(): Response
    {
        return $this->gmmBodies(OrganTypes::RvA);
    }

    #[Route(
        path: '/association/gmm-bodies/voting-committees',
        name: 'association/gmm-bodies/voting-committees',
    )]
    public function votingCommittees(): Response
    {
        return $this->gmmBodies(OrganTypes::SC);
    }

    /**
     * A body's own page. The trailing segment is how one body under a reused abbreviation is told from another: the
     * year it was founded, or its whole founding date when two of them were founded in the same year. The overviews
     * link with whichever of the two is unambiguous, so following one of those always lands on the body it named.
     */
    #[Route(
        path: '/association/{type}/{abbr}/{founded}',
        name: 'association/body',
        requirements: [
            'type' => 'committee|fraternity|avc|avw|rva|kcc|sc',
            'abbr' => '[^/]+',
            'founded' => '\d{4}(-\d{2}-\d{2})?',
        ],
        defaults: ['founded' => null],
    )]
    public function body(
        string $type,
        string $abbr,
        ?string $founded = null,
    ): Response {
        $iterations = $this->organRepository->findAllByAbbr(
            $abbr,
            OrganTypes::from($type),
        );
        if ([] === $iterations) {
            throw $this->createNotFoundException();
        }

        $organ = $this->pick(
            $iterations,
            $founded,
        );
        if (null === $organ) {
            throw $this->createNotFoundException();
        }

        return $this->render(
            'decision/bodies/body.html.twig',
            [
                'organ' => $organ,
                'information' => $organ->getOrganInformation(),
                'members' => $this->organMembers->membersOf($organ),
                'activities' => $organ->isAbrogated()
                    ? []
                    : $this->activityRepository->findUpcomingByOrgan(
                        $organ,
                        self::UPCOMING_ACTIVITIES,
                    ),
                'iterations' => BodyIteration::fromOrgans(
                    $iterations,
                    $organ,
                ),
            ],
        );
    }

    /**
     * The bodies a member may look through, which is all of them: a body's installations are a matter of record.
     */
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[Route(
        path: '/organs',
        name: 'organs/index',
    )]
    public function organs(): Response
    {
        return $this->render(
            'decision/bodies/organs.html.twig',
            [
                'organs' => $this->organRepository->findActive(),
                'historical' => false,
            ],
        );
    }

    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[Route(
        path: '/organs/history',
        name: 'organs/history',
    )]
    public function organsHistory(): Response
    {
        return $this->render(
            'decision/bodies/organs.html.twig',
            [
                'organs' => $this->organRepository->findAbrogated(),
                'historical' => true,
            ],
        );
    }

    /**
     * Everything the decisions say about one body: who was installed and discharged, and when.
     */
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[Route(
        path: '/organs/{organ}',
        name: 'organs/view',
        requirements: ['organ' => '\d+'],
    )]
    public function organ(Organ $organ): Response
    {
        return $this->render(
            'decision/bodies/organ.html.twig',
            [
                'organ' => $organ,
                'members' => $this->organMembers->membersOf($organ),
            ],
        );
    }

    private function gmmBodies(OrganTypes $type): Response
    {
        return $this->render(
            'decision/bodies/gmm-bodies.html.twig',
            ['organType' => $type],
        );
    }

    /**
     * Which of the bodies under this abbreviation is meant. Nothing at all means the newest one, which is what an old
     * bookmark and a search result expect. A year means the newest one founded in it: two bodies founded in the same
     * year are told apart by their whole founding date instead, which is what {@see BodyIteration} links them by.
     *
     * @param Organ[] $iterations newest first
     */
    private function pick(
        array $iterations,
        ?string $founded,
    ): ?Organ {
        if (null === $founded) {
            return $iterations[0];
        }

        foreach ($iterations as $organ) {
            $date = $organ->getFoundationDate();
            $matches = 4 === strlen($founded)
                ? $date->format('Y') === $founded
                : $date->format('Y-m-d') === $founded;

            if (!$matches) {
                continue;
            }

            return $organ;
        }

        return null;
    }
}
