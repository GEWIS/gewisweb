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
use App\Entity\Decision\SubDecision\Discharge;
use App\Entity\Decision\SubDecision\Foundation;
use App\Entity\Decision\SubDecision\Installation;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Override;

use function implode;
use function range;
use function sprintf;
use function ucfirst;

class DecisionFixture extends Fixture implements DependentFixtureInterface
{
    #[Override]
    public function load(ObjectManager $manager): void
    {
        // Installment of GETÉST.
        $decision = new Decision();
        $decision->setMeeting($this->getReference('meeting-BV-0', Meeting::class));
        $decision->setPoint(1);
        $decision->setNumber(1);
        $decision->setContentEN('');
        $decision->setContentNL('');

        $manager->persist($decision);
        $this->addReference(
            'decision-BV-0-' . $decision->getPoint() . '-' . $decision->getNumber(),
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

        // Discharge of members of GETEST
        $decision = new Decision();
        $decision->setMeeting($this->getReference('meeting-BV-1', Meeting::class));
        $decision->setPoint(1);
        $decision->setNumber(1);
        $decision->setContentEN('');
        $decision->setContentNL('');

        $manager->persist($decision);
        $this->addReference(
            'decision-BV-1-' . $decision->getPoint() . '-' . $decision->getNumber(),
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
    }

    /**
     * A small second committee (chair, secretary, one member), so organ-scoped access can be told apart between
     * organs. Built like GETÉST but with distinct members from GETÉST.
     */
    private function loadSecondOrgan(ObjectManager $manager): void
    {
        $decision = new Decision();
        $decision->setMeeting($this->getReference('meeting-BV-0', Meeting::class));
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
