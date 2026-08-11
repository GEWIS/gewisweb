<?php

declare(strict_types=1);

namespace App\DataFixtures\Decision;

use App\Entity\Decision\Decision;
use App\Entity\Decision\Enums\InstallationFunctions;
use App\Entity\Decision\Enums\OrganTypes;
use App\Entity\Decision\Meeting;
use App\Entity\Decision\Member;
use App\Entity\Decision\Organ;
use App\Entity\Decision\OrganMember;
use App\Entity\Decision\SubDecision\Annulment;
use App\Entity\Decision\SubDecision\Discharge;
use App\Entity\Decision\SubDecision\Foundation;
use App\Entity\Decision\SubDecision\Installation;
use DateTime;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Override;

use function assert;
use function implode;
use function range;
use function sprintf;
use function ucfirst;

class DecisionFixture extends Fixture implements DependentFixtureInterface
{
    #[Override]
    public function load(ObjectManager $manager): void
    {
        // Installment of GETÉST, at the oldest BM.
        $decision = new Decision();
        $decision->setMeeting($this->getReference('meeting-BV-1800', Meeting::class));
        $decision->setPoint(1);
        $decision->setNumber(1);
        $decision->setContentEN('');
        $decision->setContentNL('');

        $manager->persist($decision);
        $this->addReference(
            'decision-BV-1800-' . $decision->getPoint() . '-' . $decision->getNumber(),
            $decision,
        );

        $sequence = 1;
        $iSubdecisions = [];

        $foundation = new Foundation();
        $foundation->setAbbr('GETÉST');
        $foundation->setName('GEWIS\'ers Testen Éigenlijk Structureel Te-weinig');
        $foundation->setOrganType(OrganTypes::Committee);
        $foundation->setDecision($decision);
        $foundation->setSequence($sequence);
        $foundation->setContentEN('');
        $foundation->setContentNL(sprintf(
            '%s %s met afkorting %s wordt opgericht.',
            ucfirst($foundation->getOrganType()->value), // shortcut as getting the translator for `getName()` sucks.
            $foundation->getName(),
            $foundation->getAbbr(),
        ));

        $manager->persist($foundation);
        $iSubdecisions[] = $foundation;
        $this->addReference(
            'foundation-' . $foundation->getSequence(),
            $foundation,
        );

        // phpcs:disable SlevomatCodingStandard.ControlStructures.EarlyExit.EarlyExitNotUsed
        foreach (
            range(
                8005,
                8024,
            ) as $lidnr
        ) {
            $sequence++;
            $iSubdecisions[] = $this->createInstallation(
                InstallationFunctions::Member,
                $lidnr,
                $sequence,
                $foundation,
                $decision,
                $manager,
            );

            // Additional roles for specific members.
            if (8005 === $lidnr) {
                $sequence++;
                $iSubdecisions[] = $this->createInstallation(
                    InstallationFunctions::Chair,
                    $lidnr,
                    $sequence,
                    $foundation,
                    $decision,
                    $manager,
                );
            }

            if (8006 === $lidnr) {
                $sequence++;
                $iSubdecisions[] = $this->createInstallation(
                    InstallationFunctions::Secretary,
                    $lidnr,
                    $sequence,
                    $foundation,
                    $decision,
                    $manager,
                );
            }

            // Will be discharged.
            if (8020 === $lidnr) {
                $sequence++;
                $iSubdecisions[] = $this->createInstallation(
                    InstallationFunctions::Treasurer,
                    $lidnr,
                    $sequence,
                    $foundation,
                    $decision,
                    $manager,
                );
            }
        }

        // phpcs:enable SlevomatCodingStandard.ControlStructures.EarlyExit.EarlyExitNotUsed

        $content = [];
        foreach ($decision->getSubdecisions() as $subdecision) {
            $content[] = $subdecision->getContentNL();
        }

        $decision->setContentNL(implode('. ', $content));
        $manager->persist($decision);

        $manager->flush();

        // Discharge of members of GETEST, a few weeks later.
        $decision = new Decision();
        $decision->setMeeting($this->getReference('meeting-BV-1806', Meeting::class));
        $decision->setPoint(1);
        $decision->setNumber(1);
        $decision->setContentEN('');
        $decision->setContentNL('');

        $manager->persist($decision);
        $this->addReference(
            'decision-BV-1806-' . $decision->getPoint() . '-' . $decision->getNumber(),
            $decision,
        );

        $sequence = 1;
        $dSubdecisions = [];

        foreach (
            range(
                8020,
                8024,
            ) as $lidnr
        ) {
            // Order of discharge matters, the discharge from a special function comes before `Lid`.
            if (8020 === $lidnr) {
                $dSubdecisions[] = $this->createDischarge(
                    $sequence,
                    $sequence + 18, // TODO: find a better way to calculate this.
                    $decision,
                    $manager,
                );
                $sequence++;
            }

            $dSubdecisions[] = $this->createDischarge(
                $sequence,
                $sequence + 18, // TODO: find a better way to calculate this.
                $decision,
                $manager,
            );
            $sequence++;
        }

        $content = [];
        foreach ($decision->getSubdecisions() as $dSubdecision) {
            $content[] = $dSubdecision->getContentNL();
        }

        $decision->setContentNL(implode('. ', $content));
        $manager->persist($decision);

        $manager->flush();

        // Creation of the actual organ and its members here as well. This is because Doctrine sucks and breaks in the
        // opposite way with the custom mapping type.

        // Foundation
        $organ = new Organ();
        $organ->setName($foundation->getName());
        $organ->setAbbr($foundation->getAbbr());
        $organ->setFoundation($foundation);
        $organ->setType($foundation->getOrganType());
        $organ->setFoundationDate($foundation->getDecision()->getMeeting()->getDate());

        $manager->persist($organ);
        $manager->flush();

        $this->addReference(
            'organ-getest',
            $organ,
        );

        // Installations
        foreach ($iSubdecisions as $installation) {
            if (!($installation instanceof Installation)) {
                continue;
            }

            $organMember = new OrganMember();
            $organMember->setOrgan($organ);
            $organMember->setMember($installation->getMember());
            $organMember->setInstallation($installation);
            $organMember->setFunction($installation->getFunction());
            $organMember->setInstallDate($installation->getFoundation()->getDecision()->getMeeting()->getDate());

            $manager->persist($organMember);
            $this->addReference(
                'organMember-' . $installation->getSequence(),
                $organMember,
            );
        }

        $manager->flush();

        // Discharges
        foreach ($dSubdecisions as $discharge) {
            $organMember = $this->getReference(
                'organMember-' . $discharge->getInstallation()->getSequence(),
                OrganMember::class,
            );
            $organMember->setDischargeDate($discharge->getDecision()->getMeeting()->getDate());

            $manager->persist($organMember);
        }

        $manager->flush();

        $this->loadSecondOrgan($manager);
        $this->loadFormerOrgan($manager);
        $this->loadBoardDecisions($manager);
        $this->loadMeetingTextDecisions($manager);
    }

    /**
     * A small second committee (chair, secretary, one member), so organ-scoped access can be told apart between
     * organs. Built like GETÉST but with distinct members from GETÉST.
     */
    private function loadSecondOrgan(ObjectManager $manager): void
    {
        $decision = new Decision();
        $decision->setMeeting($this->getReference('meeting-BV-1800', Meeting::class));
        $decision->setPoint(2);
        $decision->setNumber(1);
        $decision->setContentEN('');
        $decision->setContentNL('');

        $manager->persist($decision);

        $foundation = new Foundation();
        $foundation->setAbbr('KEUR');
        $foundation->setName('Keuringscommissie');
        $foundation->setOrganType(OrganTypes::Committee);
        $foundation->setDecision($decision);
        $foundation->setSequence(1);
        $foundation->setContentEN('');
        $foundation->setContentNL(sprintf(
            '%s %s met afkorting %s wordt opgericht.',
            ucfirst($foundation->getOrganType()->value),
            $foundation->getName(),
            $foundation->getAbbr(),
        ));

        $manager->persist($foundation);

        $functions = [
            8025 => InstallationFunctions::Chair,
            8026 => InstallationFunctions::Secretary,
            8027 => InstallationFunctions::Member,
        ];

        $installations = [];
        $sequence = 1;
        foreach ($functions as $lidnr => $function) {
            $sequence++;
            $installation = new Installation();
            $installation->setFunction($function);
            $installation->setMember($this->getReference('member-' . $lidnr, Member::class));
            $installation->setSequence($sequence);
            $installation->setFoundation($foundation);
            $installation->setDecision($decision);
            $installation->setContentEN('');
            $installation->setContentNL(sprintf(
                '%s wordt geïnstalleerd als %s van %s',
                $installation->getMember()->getFullName(),
                $installation->getFunction()->value,
                $foundation->getAbbr(),
            ));

            $manager->persist($installation);
            $installations[] = $installation;
        }

        $content = [];
        foreach ($decision->getSubdecisions() as $subdecision) {
            $content[] = $subdecision->getContentNL();
        }

        $decision->setContentNL(implode('. ', $content));
        $manager->persist($decision);

        $manager->flush();

        $organ = new Organ();
        $organ->setName($foundation->getName());
        $organ->setAbbr($foundation->getAbbr());
        $organ->setFoundation($foundation);
        $organ->setType($foundation->getOrganType());
        $organ->setFoundationDate($foundation->getDecision()->getMeeting()->getDate());

        $manager->persist($organ);
        $manager->flush();

        foreach ($installations as $installation) {
            $organMember = new OrganMember();
            $organMember->setOrgan($organ);
            $organMember->setMember($installation->getMember());
            $organMember->setInstallation($installation);
            $organMember->setFunction($installation->getFunction());
            $organMember->setInstallDate($installation->getFoundation()->getDecision()->getMeeting()->getDate());

            $manager->persist($organMember);
        }

        $manager->flush();

        $this->addReference(
            'organ-keur',
            $organ,
        );
    }

    /**
     * A committee that went by the same letters as GETÉST does now, founded and abrogated years earlier. Bodies reuse
     * an abbreviation, and the pages of both have to be reachable, so the seed carries a pair that only the year tells
     * apart.
     */
    private function loadFormerOrgan(ObjectManager $manager): void
    {
        $decision = new Decision();
        $decision->setMeeting($this->getReference('meeting-BV-1800', Meeting::class));
        $decision->setPoint(3);
        $decision->setNumber(1);
        $decision->setContentEN('');
        $decision->setContentNL('');

        $manager->persist($decision);

        $foundation = new Foundation();
        $foundation->setAbbr('GETÉST');
        $foundation->setName('GEWIS\'ers Testten Éigenlijk Structureel Te-weinig');
        $foundation->setOrganType(OrganTypes::Committee);
        $foundation->setDecision($decision);
        $foundation->setSequence(1);
        $foundation->setContentEN('');
        $foundation->setContentNL(sprintf(
            '%s %s met afkorting %s wordt opgericht.',
            ucfirst($foundation->getOrganType()->value),
            $foundation->getName(),
            $foundation->getAbbr(),
        ));

        $manager->persist($foundation);
        $manager->flush();

        // Dated by hand rather than from the meeting it hangs off: the seeded calendar only covers the last few
        // months, and two bodies founded in the same year would be indistinguishable by year.
        $organ = new Organ();
        $organ->setName($foundation->getName());
        $organ->setAbbr($foundation->getAbbr());
        $organ->setFoundation($foundation);
        $organ->setType($foundation->getOrganType());
        $organ->setFoundationDate(new DateTime('-9 years'));
        $organ->setAbrogationDate(new DateTime('-6 years'));

        $manager->persist($organ);
        $manager->flush();

        $this->addReference(
            'organ-getest-former',
            $organ,
        );
    }

    /**
     * The day-to-day board decisions: budgets, grants, and confirmations spread over the past BMs, plus one BM
     * decision that annuls an earlier budget.
     */
    private function loadBoardDecisions(ObjectManager $manager): void
    {
        $keyGrantee = $this->getReference(
            'member-8010',
            Member::class,
        );

        $texts = [
            [
                'meeting-BV-1801',
                1,
                1,
                'Het bestuur besluit de begroting van de wisselactiviteit van GETÉST ter hoogte van € 250,00 goed'
                . ' te keuren.',
            ],
            [
                'meeting-BV-1802',
                1,
                1,
                sprintf(
                    'Het bestuur besluit %s sleutelrechten toe te kennen tot het einde van het verenigingsjaar.',
                    $keyGrantee->getFullName(),
                ),
            ],
            [
                'meeting-BV-1803',
                1,
                1,
                'Het bestuur besluit de notulen van de vorige bestuursvergadering vast te stellen.',
            ],
            [
                'meeting-BV-1803',
                1,
                2,
                'Het bestuur besluit het activiteitenbeleid ter instemming voor te leggen aan de ALV.',
            ],
            [
                'meeting-BV-1805',
                1,
                1,
                'Het bestuur besluit de begroting van het introductieweekend ter hoogte van € 1.250,00 goed te keuren.',
            ],
            [
                'meeting-BV-1807',
                2,
                1,
                'Het bestuur besluit de samenwerkingsovereenkomst met de faculteit te bekrachtigen.',
            ],
            [
                'meeting-BV-1808',
                1,
                1,
                'Het bestuur besluit een bijdrage van € 75,00 toe te kennen aan de constitutieborrel van KEUR.',
            ],
            [
                'meeting-BV-1810',
                1,
                1,
                'Het bestuur besluit de declaratierichtlijn per direct te actualiseren.',
            ],
            [
                'meeting-BV-1811',
                1,
                1,
                'Het bestuur besluit de jaarplanning van GETÉST vast te stellen.',
            ],
        ];

        $annulmentTarget = null;
        foreach ($texts as [$meetingReference, $point, $number, $content]) {
            $decision = $this->createTextDecision(
                $manager,
                $meetingReference,
                $point,
                $number,
                $content,
            );

            if ('meeting-BV-1801' !== $meetingReference) {
                continue;
            }

            $annulmentTarget = $decision;
        }

        $decision = new Decision();
        $decision->setMeeting($this->getReference('meeting-BV-1804', Meeting::class));
        $decision->setPoint(1);
        $decision->setNumber(1);
        $decision->setContentEN('');
        $decision->setContentNL('');
        $manager->persist($decision);

        assert($annulmentTarget instanceof Decision);

        $annulment = new Annulment();
        $annulment->setTarget($annulmentTarget);
        $annulment->setSequence(1);
        $annulment->setDecision($decision);
        $annulment->setContentEN('');
        $annulment->setContentNL('Besluit BV 1801.1.1 wordt nietig verklaard.');
        $manager->persist($annulment);

        $decision->setContentNL($annulment->getContentNL());
        $manager->persist($decision);

        $manager->flush();
    }

    /**
     * Decisions of the complete GMM, lined up with (and deliberately once without) its agenda points, plus a
     * correction recorded in a virtual meeting. CMs take no decisions.
     */
    private function loadMeetingTextDecisions(ObjectManager $manager): void
    {
        $gmmTexts = [
            [
                2,
                1,
                'De agenda van de vergadering wordt vastgesteld.',
            ],
            [
                3,
                1,
                'De notulen van de vorige ALV worden goedgekeurd.',
            ],
            [
                5,
                1,
                'De motie van orde over de vergaderduur wordt aangenomen.',
            ],
            [
                7,
                1,
                'De begroting voor het komende verenigingsjaar wordt vastgesteld.',
            ],
        ];

        foreach ($gmmTexts as [$point, $number, $content]) {
            $this->createTextDecision(
                $manager,
                'meeting-gmm-complete',
                $point,
                $number,
                $content,
            );
        }

        $this->createTextDecision(
            $manager,
            'meeting-Virt-2',
            1,
            1,
            'Rectificatie: de in BV 1805.1.1 genoemde begroting betreft het introductieweekend van het komende'
            . ' verenigingsjaar.',
        );

        $manager->flush();
    }

    private function createTextDecision(
        ObjectManager $manager,
        string $meetingReference,
        int $point,
        int $number,
        string $contentNL,
    ): Decision {
        $decision = new Decision();
        $decision->setMeeting($this->getReference(
            $meetingReference,
            Meeting::class,
        ));
        $decision->setPoint($point);
        $decision->setNumber($number);
        $decision->setContentEN('');
        $decision->setContentNL($contentNL);

        $manager->persist($decision);

        return $decision;
    }

    private function createInstallation(
        InstallationFunctions $function,
        int $lidnr,
        int $sequence,
        Foundation $foundation,
        Decision $decision,
        ObjectManager $manager,
    ): Installation {
        $installation = new Installation();
        $installation->setFunction($function);
        $installation->setMember($this->getReference('member-' . $lidnr, Member::class));
        $installation->setSequence($sequence);
        $installation->setFoundation($foundation);
        $installation->setDecision($decision);
        $installation->setContentEN('');
        $installation->setContentNL(
            sprintf(
                '%s wordt geïnstalleerd als %s van %s',
                $installation->getMember()->getFullName(),
                $installation->getFunction()->value, // shortcut as getting the translator for `getName()` sucks.
                $installation->getFoundation()->getAbbr(),
            ),
        );

        $manager->persist($installation);
        $this->addReference(
            'installation-' . $installation->getSequence(),
            $installation,
        );

        return $installation;
    }

    private function createDischarge(
        int $sequence,
        int $installationSequence,
        Decision $decision,
        ObjectManager $manager,
    ): Discharge {
        $discharge = new Discharge();
        $discharge->setInstallation($this->getReference('installation-' . $installationSequence, Installation::class));
        $discharge->setSequence($sequence);
        $discharge->setDecision($decision);
        $discharge->setContentEN('');
        $discharge->setContentNL(
            sprintf(
                '%s wordt gedechargeerd als %s van %s',
                $discharge->getInstallation()->getMember()->getFullName(),
                $discharge->getInstallation()->getFunction()->value,
                $discharge->getInstallation()->getFoundation()->getAbbr(),
            ),
        );

        $manager->persist($discharge);
        $this->addReference(
            'discharge-' . $discharge->getSequence(),
            $discharge,
        );

        return $discharge;
    }

    /**
     * @return class-string<Fixture>[]
     */
    #[Override]
    public function getDependencies(): array
    {
        return [
            MeetingFixture::class,
            MemberFixture::class,
        ];
    }
}
