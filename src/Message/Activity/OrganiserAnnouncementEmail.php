<?php

declare(strict_types=1);

namespace App\Message\Activity;

/**
 * A message an organiser broadcasts to everyone signed up for an activity (NOT the confirmation a member receives when
 * they themselves sign up -- this is the organiser's own notify-my-attendees mechanism). The recipients are snapshotted
 * to {email, name} pairs at dispatch time, so the queued message sends deterministically even if a sign-up is later
 * changed or removed. It is dispatched as ONE message (a single, atomic enqueue from the request -- never a
 * partially-enqueued fan-out) and handled by {@see \App\MessageHandler\Activity\OrganiserAnnouncementEmailHandler},
 * which sends one personalised email per recipient and is resilient to an individual send failing. Routed to the bulk
 * transport.
 *
 * The email itself has no locale: its boilerplate is always English (the composing organiser's locale says nothing
 * about what each recipient reads); only the organiser's own subject/body text carries whatever language they wrote.
 */
class OrganiserAnnouncementEmail
{
    /**
     * @param list<array{email: string, name: string}> $recipients
     */
    public function __construct(
        private readonly string $subject,
        private readonly string $body,
        private readonly string $activityName,
        private readonly string $replyTo,
        private readonly array $recipients,
    ) {
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function getActivityName(): string
    {
        return $this->activityName;
    }

    public function getReplyTo(): string
    {
        return $this->replyTo;
    }

    /**
     * @return list<array{email: string, name: string}>
     */
    public function getRecipients(): array
    {
        return $this->recipients;
    }
}
