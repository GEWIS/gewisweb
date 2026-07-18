<?php

declare(strict_types=1);

namespace App\MessageHandler\User;

use App\Message\User\ExportUserDataMessage;
use App\Repository\Decision\MemberRepository;
use App\Service\Application\FileStorage;
use App\Service\User\GdprService;
use DateTimeImmutable;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

use function json_encode;
use function sprintf;

use const JSON_PRETTY_PRINT;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

/**
 * Builds a member's data export, stores it privately, and emails them a link to download it. Runs on the bulk queue
 * because collecting and serialising everything is not latency sensitive.
 */
#[AsMessageHandler]
class ExportUserDataHandler
{
    /**
     * How long a generated export stays downloadable before it is cleaned up and has to be requested again. Keep the
     * "3 days" wording in the email and settings copy in sync with this.
     */
    public const int RETENTION_DAYS = 3;

    public function __construct(
        private readonly MemberRepository $memberRepository,
        private readonly GdprService $gdprService,
        private readonly FileStorage $fileStorage,
        private readonly MailerInterface $mailer,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function __invoke(ExportUserDataMessage $message): void
    {
        $member = $this->memberRepository->findByLidnr($message->getLidnr());
        if (null === $member) {
            return;
        }

        $this->fileStorage->write(
            self::exportPath($member->getLidnr()),
            json_encode(
                $this->gdprService->collectMemberData($member),
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
            ),
        );

        $email = $member->getEmail();
        if (null === $email) {
            return;
        }

        $this->mailer->send(
            new TemplatedEmail()
                ->to($email)
                ->subject('Your GEWIS data export is ready')
                ->htmlTemplate('emails/user/data-export.html.twig')
                ->context([
                    'fullName' => $member->getFullName(),
                    'downloadUrl' => $this->urlGenerator->generate(
                        'user_settings_data_export_download',
                        ['_locale' => 'en'],
                        UrlGeneratorInterface::ABSOLUTE_URL,
                    ),
                ]),
        );
    }

    /**
     * The stored path of a member's export, shared with the controller that serves the download so the two agree.
     */
    public static function exportPath(int $lidnr): string
    {
        return sprintf(
            'gdpr-export/%d.json',
            $lidnr,
        );
    }

    /**
     * Whether the member has an export they can still download: one exists and it is within the retention window.
     */
    public static function isAvailable(
        FileStorage $fileStorage,
        int $lidnr,
    ): bool {
        $path = self::exportPath($lidnr);

        return $fileStorage->exists($path)
            && $fileStorage->lastModified($path) >= new DateTimeImmutable(
                '-' . self::RETENTION_DAYS . ' days',
            )->getTimestamp();
    }
}
