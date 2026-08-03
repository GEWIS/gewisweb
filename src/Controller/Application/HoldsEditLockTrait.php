<?php

declare(strict_types=1);

namespace App\Controller\Application;

use App\Entity\Application\RevisableInterface;
use App\Entity\User\CompanyUser;
use App\Entity\User\User;
use App\Security\Application\RevisionVoter;
use App\Service\Application\EditLockService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\Service\Attribute\Required;

/**
 * The two endpoints every edit screen needs behind it: the browser saying it is still there, and saying it has gone.
 * The `edit-lock` Stimulus controller reads `held` and `released`, so the answers are the same wherever the screen is.
 *
 * The routes stay on the concrete actions: each screen has its own path and its own CSRF token id, and only the
 * concrete controller can name the resource its value resolver hands it.
 */
trait HoldsEditLockTrait
{
    protected EditLockService $editLockService;

    /**
     * Injected through a setter rather than a constructor so a controller using this keeps its own constructor and its
     * own dependency list, the way {@see AbstractRevisionController} takes its own.
     */
    #[Required]
    public function setEditLockService(EditLockService $editLockService): void
    {
        $this->editLockService = $editLockService;
    }

    /**
     * Whether the lock is still held after this, which is what tells the screen to stop asking.
     */
    protected function pingLock(
        RevisableInterface $resource,
        User|CompanyUser $principal,
    ): JsonResponse {
        $this->denyAccessUnlessGranted(
            RevisionVoter::SUBMIT,
            $resource,
        );

        return new JsonResponse([
            'held' => $this->editLockService->ping(
                $resource,
                $principal,
            ),
        ]);
    }

    /**
     * Releasing is idempotent: a screen that is closed twice, or that was never holding the lock, answers the same.
     */
    protected function releaseLock(
        RevisableInterface $resource,
        User|CompanyUser $principal,
    ): JsonResponse {
        $this->denyAccessUnlessGranted(
            RevisionVoter::SUBMIT,
            $resource,
        );

        $this->editLockService->release(
            $resource,
            $principal,
        );

        return new JsonResponse(['released' => true]);
    }
}
