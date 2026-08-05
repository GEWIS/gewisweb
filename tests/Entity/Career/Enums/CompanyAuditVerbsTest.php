<?php

declare(strict_types=1);

namespace App\Tests\Entity\Career\Enums;

use App\Entity\Career\Enums\CompanyAuditVerbs;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Translation\IdentityTranslator;

final class CompanyAuditVerbsTest extends TestCase
{
    public function testEveryVerbReadsAsASentenceAboutWhoDidWhat(): void
    {
        $translator = new IdentityTranslator();

        foreach (CompanyAuditVerbs::cases() as $verb) {
            self::assertNotSame(
                '',
                $verb->message(
                    'Ada Lovelace',
                    'job',
                )->trans($translator),
            );
        }
    }

    public function testTheActorAndWhatTheyActedOnAreBothFilledIn(): void
    {
        self::assertSame(
            'Ada Lovelace invited rep@example.com to represent this company',
            CompanyAuditVerbs::RepresentativeInvited->message(
                'Ada Lovelace',
                'rep@example.com',
            )->trans(new IdentityTranslator()),
        );
    }

    /**
     * An accepted invitation is the representative's own doing, so the sentence names them rather than whoever invited
     * them.
     */
    public function testJoiningIsAttributedToWhoeverJoined(): void
    {
        self::assertSame(
            'Bram de Wit accepted their invitation',
            CompanyAuditVerbs::RepresentativeJoined->message(
                '',
                'Bram de Wit',
            )->trans(new IdentityTranslator()),
        );
    }
}
