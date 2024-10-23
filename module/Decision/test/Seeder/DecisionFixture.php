<?php

declare(strict_types=1);

namespace DecisionTest\Seeder;

use Decision\Model\Decision;
use Decision\Model\Enums\OrganTypes;
use Decision\Model\Meeting;
use Decision\Model\Member;
use Decision\Model\Organ;
use Decision\Model\OrganMember;
use Decision\Model\SubDecision\Discharge;
use Decision\Model\SubDecision\Foundation;
use Decision\Model\SubDecision\Installation;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

use function implode;
use function range;
use function sprintf;
use function ucfirst;

class DecisionFixture extends AbstractFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        // Installment of GETÉST.
        $decision = new Decision();
        $decision->setMeeting($this->getReference('meeting-BV-0', Meeting::class));
        $decision->setPoint(1);
        $decision->setNumber(1);
        $decision->setContent('');

        $manager->persist($decision);
        $this->addReference('decision-BV-0-' . $decision->getPoint() . '-' . $decision->getNumber(), $decision);

        $sequence = 1;
        $iSubdecisions = [];

        $foundation = new Foundation();
        $foundation->setAbbr('GETÉST');
        $foundation->setName('GEWIS\'ers Testen Éigenlijk Structureel Te-weinig');
        $foundation->setOrganType(OrganTypes::Committee);
        $foundation->setDecision($decision);
        $foundation->setSequence($sequence);
        $foundation->setContent(sprintf(
            '%s %s met afkorting %s wordt opgericht.',
            ucfirst($foundation->getOrganType()->value), // shortcut as getting the translator for `getName()` sucks.
            $foundation->getName(),
            $foundation->getAbbr(),
        ));

        $manager->persist($foundation);
        $iSubdecisions[] = $foundation;
        $this->addReference('foundation-' . $foundation->getSequence(), $foundation);

        // phpcs:disable SlevomatCodingStandard.ControlStructures.EarlyExit.EarlyExitNotUsed
        foreach (range(8005, 8024) as $lidnr) {
            $sequence++;
            $iSubdecisions[] = $this->createInstallation(
                'Lid',
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
                    'Voorzitter',
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
                    'Secretaris',
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
                    'Penningmeester',
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
            $content[] = $subdecision->getContent();
        }

        $decision->setContent(implode('. ', $content));
        $manager->persist($decision);

        $manager->flush();

        // Discharge of members of GETEST
        $decision = new Decision();
        $decision->setMeeting($this->getReference('meeting-BV-1', Meeting::class));
        $decision->setPoint(1);
        $decision->setNumber(1);
        $decision->setContent('');

        $manager->persist($decision);
        $this->addReference('decision-BV-1-' . $decision->getPoint() . '-' . $decision->getNumber(), $decision);

        $sequence = 1;
        $dSubdecisions = [];

        foreach (range(8020, 8024) as $lidnr) {
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
            $content[] = $dSubdecision->getContent();
        }

        $decision->setContent(implode('. ', $content));
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
            $this->addReference('organMember-' . $installation->getSequence(), $organMember);
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
    }

    private function createInstallation(
        string $function,
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
        $installation->setContent(
            sprintf(
                '%s wordt geïnstalleerd als %s van %s',
                $installation->getMember()->getFullName(),
                $installation->getFunction(),
                $installation->getFoundation()->getAbbr(),
            ),
        );

        $manager->persist($installation);
        $this->addReference('installation-' . $installation->getSequence(), $installation);

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
        $discharge->setContent(
            sprintf(
                '%s wordt gedechargeerd als %s van %s',
                $discharge->getInstallation()->getMember()->getFullName(),
                $discharge->getInstallation()->getFunction(),
                $discharge->getInstallation()->getFoundation()->getAbbr(),
            ),
        );

        $manager->persist($discharge);
        $this->addReference('discharge-' . $discharge->getSequence(), $discharge);

        return $discharge;
    }

    /**
     * @return class-string[]
     */
    public function getDependencies(): array
    {
        return [
            MeetingFixture::class,
        ];
    }
}
