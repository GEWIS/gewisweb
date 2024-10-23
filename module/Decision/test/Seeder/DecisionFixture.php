<?php

declare(strict_types=1);

namespace DecisionTest\Seeder;

use Decision\Model\Decision;
use Decision\Model\Enums\OrganTypes;
use Decision\Model\Meeting;
use Decision\Model\Member;
use Decision\Model\SubDecision\Foundation;
use Decision\Model\SubDecision\Installation;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

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

        $manager->persist($decision);

        $sequence = 1;
        $subdecisions = [];

        $foundation = new Foundation();
        $foundation->setAbbr('GETÉST');
        $foundation->setName('GEWIS\'ers Testen Éigenlijk Structureel Te-weinig');
        $foundation->setOrganType(OrganTypes::Committee);
        $foundation->setDecision($decision);
        $foundation->setSequence($sequence);
        $foundation->setContent(sprintf(
            '%s %s met afkorting %s wordt opgericht.',
            ucfirst($foundation->getOrganType()->value), // shortcut because getting the translator here for `getName()` sucks.
            $foundation->getName(),
            $foundation->getAbbr(),
        ));

        $manager->persist($foundation);
        $subdecisions[] = $foundation;
        $this->addReference('foundation-' . $foundation->getSequence(), $foundation);

        foreach (range(8005, 8024) as $lidnr) {
            $sequence++;
            $subdecisions[] = $this->createInstallation(
                'Lid',
                $lidnr,
                $sequence,
                $foundation,
                $decision,
                $manager,
            );

            // Additional roles for specific members.
            if ($lidnr == 8005) {
                $sequence++;
                $subdecisions[] = $this->createInstallation(
                    'Voorzitter',
                    $lidnr,
                    $sequence,
                    $foundation,
                    $decision,
                    $manager,
                );
            }

            if ($lidnr == 8006) {
                $sequence++;
                $subdecisions[] = $this->createInstallation(
                    'Secretaris',
                    $lidnr,
                    $sequence,
                    $foundation,
                    $decision,
                    $manager,
                );
            }

            // Will be discharged.
            if ($lidnr == 8020) {
                $sequence++;
                $subdecisions[] = $this->createInstallation(
                    'Penningmeester',
                    $lidnr,
                    $sequence,
                    $foundation,
                    $decision,
                    $manager,
                );
            }
        }

        $decision->addSubdecisions($subdecisions);
        $content = [];

        foreach ($decision->getSubdecisions() as $subdecision) {
            $content[] = $subdecision->getContent();
        }

        $decision->setContent(implode(' ', $content));
        $manager->persist($decision);

        $manager->flush();

        // Discharge of members of GETEST


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
            )
        );

        $manager->persist($installation);
        $this->addReference('installation-' . $installation->getSequence(), $installation);

        return $installation;
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
