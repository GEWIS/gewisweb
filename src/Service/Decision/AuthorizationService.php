<?php

declare(strict_types=1);

namespace App\Service\Decision;

use App\Entity\Decision\Authorization;
use App\Entity\Decision\Enums\MembershipTypes;
use App\Entity\Decision\Meeting;
use App\Entity\Decision\Member;
use App\Message\Decision\AuthorizationCreatedEmail;
use App\Message\Decision\AuthorizationRevokedEmail;
use App\Repository\Decision\AuthorizationRepository;
use App\Repository\Decision\MeetingRepository;
use App\Repository\Decision\MemberRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

use function count;

/**
 * GMM proxy authorizations: a member authorizes another member to vote on their behalf during the next GMM. The old
 * rules carry over: one active authorization per member, no self-authorization, recipients must be votable members,
 * and a member can represent at most two others.
 */
final readonly class AuthorizationService
{
    public const int MAX_RECEIVED = 2;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private AuthorizationRepository $authorizationRepository,
        private MemberRepository $memberRepository,
        private MeetingRepository $meetingRepository,
        private MessageBusInterface $messageBus,
        private TranslatorInterface $translator,
    ) {
    }

    /**
     * The upcoming GMMs an authorization can be granted for, soonest first.
     *
     * @return Meeting[]
     */
    public function getUpcomingALVs(): array
    {
        return $this->meetingRepository->findUpcomingALVs();
    }

    public function getCurrentAuthorization(
        Member $authorizer,
        Meeting $meeting,
    ): ?Authorization {
        return $this->authorizationRepository->findUserAuthorization(
            $meeting->getNumber(),
            $authorizer,
        );
    }

    /**
     * @throws RuntimeException when the recipient cannot receive the authorization.
     */
    public function authorize(
        Member $authorizer,
        int $recipientLidnr,
        Meeting $meeting,
    ): Authorization {
        $existing = $this->getCurrentAuthorization(
            $authorizer,
            $meeting,
        );

        if (null !== $existing) {
            return $existing;
        }

        $recipient = $this->memberRepository->find($recipientLidnr);

        if (
            null === $recipient
            || true === $recipient->getDeleted()
            || $recipient->isExpired()
            || MembershipTypes::Graduate === $recipient->getType()
        ) {
            throw new RuntimeException(
                $this->translator->trans('This member cannot receive authorizations.'),
            );
        }

        if ($recipient->getLidnr() === $authorizer->getLidnr()) {
            throw new RuntimeException(
                $this->translator->trans('You cannot authorize yourself.'),
            );
        }

        $received = count($this->authorizationRepository->findRecipientAuthorization(
            $meeting->getNumber(),
            $recipient,
        ));

        if ($received >= self::MAX_RECEIVED) {
            throw new RuntimeException(
                $this->translator->trans('This member already represents the maximum number of other members.'),
            );
        }

        $authorization = new Authorization();
        $authorization->setAuthorizer($authorizer);
        $authorization->setRecipient($recipient);
        $authorization->setMeetingNumber($meeting->getNumber());
        $authorization->setCreatedAt(new DateTime());

        $this->entityManager->persist($authorization);
        $this->entityManager->flush();

        $this->messageBus->dispatch(new AuthorizationCreatedEmail((int) $authorization->getId()));

        return $authorization;
    }

    public function revoke(
        Authorization $authorization,
        Member $authorizer,
    ): void {
        if (
            $authorization->getAuthorizer()->getLidnr() !== $authorizer->getLidnr()
            || null !== $authorization->getRevokedAt()
        ) {
            return;
        }

        $authorization->setRevokedAt(new DateTime());
        $this->entityManager->flush();

        $this->messageBus->dispatch(new AuthorizationRevokedEmail((int) $authorization->getId()));
    }
}
