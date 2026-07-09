<?php

declare(strict_types=1);

namespace App\Tests\MessageHandler\Activity;

use App\Message\Activity\OrganiserAnnouncementEmail;
use App\MessageHandler\Activity\OrganiserAnnouncementEmailHandler;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

/**
 * The bulk-announcement handler sends one personalised email per recipient and must be resilient to a single recipient
 * failing: neither a malformed stored address (which makes {@see \Symfony\Component\Mime\Address} throw when built) nor
 * a transport error may abort the batch, or (on a Messenger retry) everyone already mailed would be mailed again.
 */
final class OrganiserAnnouncementEmailHandlerTest extends TestCase
{
    public function testAMalformedAddressSkipsOnlyThatRecipientAndTheRestStillSend(): void
    {
        $sent = [];
        $mailer = self::createStub(MailerInterface::class);
        $mailer->method('send')->willReturnCallback(
            static function (Email $email) use (&$sent): void {
                $sent[] = $email->getTo()[0]->getAddress();
            },
        );

        $handler = new OrganiserAnnouncementEmailHandler(
            $mailer,
            self::createStub(LoggerInterface::class),
        );

        // The middle recipient's address is malformed: `new Address()` throws inside the loop, and the handler must
        // catch it and carry on to the valid third recipient rather than aborting.
        $handler->__invoke(new OrganiserAnnouncementEmail(
            'Subject',
            'Body',
            'Activity',
            'organ@example.org',
            [
                [
                    'email' => 'first@example.org',
                    'name' => 'First',
                ],
                [
                    'email' => 'not a valid address',
                    'name' => 'Broken',
                ],
                [
                    'email' => 'third@example.org',
                    'name' => 'Third',
                ],
            ],
        ));

        self::assertSame(
            [
                'first@example.org',
                'third@example.org',
            ],
            $sent,
        );
    }
}
