<?php

declare(strict_types=1);

namespace App\Controller\Application;

use App\Entity\Application\Enums\AlertTypes;
use App\Entity\Application\RevisionInterface;
use App\Entity\User\CompanyUser;
use App\Entity\User\User;
use App\Security\Application\RevisionVoter;
use App\Security\User\SudoVoter;
use App\ViewModel\Application\Review\RevisionAudience;
use App\ViewModel\Application\RevisionActions;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use function strval;
use function trim;

/**
 * The review screen itself, for every domain that has one: who may open it, what the decision buttons do, and how a
 * comment is left. What is being reviewed, how it is laid out and where each decision sends the reader stay with the
 * domain, because those are the parts that genuinely differ.
 *
 * The same screen serves the reviewer and the author who submitted; which buttons appear is decided by the workflow
 * guards rather than here, so an author never has to be told apart from a reviewer in this code.
 */
abstract class AbstractRevisionReviewController extends AbstractRevisionController
{
    /**
     * The template this domain renders its review screen with.
     */
    abstract protected function reviewTemplate(): string;

    /**
     * Everything the template needs beyond the revision, its predecessor, the decision form and whether the draft may
     * be discarded, which {@see self::renderReview()} supplies already.
     *
     * @return array<string, mixed>
     */
    abstract protected function reviewContext(
        RevisionInterface $revision,
        RevisionActions $actions,
    ): array;

    /**
     * Back to this revision's own review screen. The career module answers with a different route per aggregate.
     */
    abstract protected function reviewResponse(RevisionInterface $revision): Response;

    /**
     * Who this domain's review screen is written for. An admin surface is read by somebody deciding; the company
     * portal shows the same revision to the author who wrote it, and is told apart here rather than in the template.
     */
    protected function reviewAudience(): RevisionAudience
    {
        return RevisionAudience::ReviewerOnly;
    }

    /**
     * Opening a review screen is gated on being allowed to see it, and reviewers additionally on sudo. This is a GET,
     * so the sudo listener brings them back here afterwards. Somebody who can only submit their own draft is never
     * asked.
     */
    protected function assertMayReview(RevisionInterface $revision): void
    {
        $this->denyAccessUnlessGranted(
            RevisionVoter::VIEW,
            $revision,
        );

        if (
            !$this->isGranted(
                RevisionVoter::APPROVE,
                $revision,
            )
        ) {
            return;
        }

        $this->denyAccessUnlessGranted(SudoVoter::ATTRIBUTE);
    }

    /**
     * @param FormInterface<array<string, mixed>|null> $form the submitted form when re-rendering after an error
     */
    protected function renderReview(
        RevisionInterface $revision,
        ?FormInterface $form = null,
    ): Response {
        $actions = $this->revisionActions($revision);
        $form ??= $this->createDecisionForm($actions);
        $previous = $revision->getPreviousRevision();

        return $this->render(
            $this->reviewTemplate(),
            [
                'revision' => $revision,
                'previous' => $previous,
                'sections' => $this->revisionDescribers->describe(
                    $revision,
                    $previous,
                )->sectionsFor($this->reviewAudience()),
                'decisionForm' => $form->createView(),
                'canDiscard' => $actions->isDiscardable,
                ...$this->reviewContext(
                    $revision,
                    $actions,
                ),
            ],
        );
    }

    /**
     * The whole decision round-trip: bind what was pressed, come back with the review screen on any error, apply it,
     * say what it did and send the reader on. Every domain does exactly this; the wording and the destinations are
     * the parts that differ, and they are asked for below.
     */
    protected function handleDecision(
        Request $request,
        RevisionInterface $revision,
        User|CompanyUser $actor,
    ): Response {
        $this->denyAccessUnlessGranted(
            RevisionVoter::VIEW,
            $revision,
        );

        $form = $this->createDecisionForm($this->revisionActions($revision))->handleRequest($request);

        // The clicked button names the transition; the form's validation groups make feedback mandatory for a
        // rejection or a request for changes. On any error the review screen comes back with it.
        if (
            !$form->isSubmitted()
            || !$form->isValid()
        ) {
            return $this->renderReview(
                $revision,
                $form,
            );
        }

        $transition = $this->applyDecision(
            $form,
            $revision,
            $actor,
        );
        if (null === $transition) {
            return $this->reviewResponse($revision);
        }

        $this->addFlash(
            AlertTypes::Success->value,
            $this->decisionFlash($transition),
        );

        return $this->decisionResponse(
            $revision,
            $transition,
        );
    }

    /**
     * What the reader is told the decision did, in this domain's words.
     */
    abstract protected function decisionFlash(string $transition): string;

    /**
     * Where a decision leaves the reader. Staying on the screen they decided from is the answer that needs no
     * knowledge of the domain, so it is the default; a queue to return to is not.
     */
    protected function decisionResponse(
        RevisionInterface $revision,
        string $transition,
    ): Response {
        return $this->reviewResponse($revision);
    }

    /**
     * Apply the decision the reader pressed, together with whatever they typed alongside it. Returns the transition
     * that was applied, or null when it could not be — in which case a flash already says why and the caller should
     * send the reader back to the review screen.
     *
     * @param FormInterface<array<string, mixed>|null> $form
     */
    protected function applyDecision(
        FormInterface $form,
        RevisionInterface $revision,
        User|CompanyUser $actor,
    ): ?string {
        $transition = $this->clickedTransition($form);

        if (
            !$this->revisionStateMachine->can(
                $revision,
                $transition,
            )
        ) {
            $this->addFlash(
                AlertTypes::Warning->value,
                $this->translator->trans('That action is not available for this revision.'),
            );

            return null;
        }

        // Everything but the author's own submit is a reviewer action, so it needs a fresh sudo grant. Opening the
        // screen already asked, so this normally passes; it fires when that grant has lapsed in the meantime.
        if ('submit' !== $transition) {
            $this->denyAccessUnlessGranted(SudoVoter::ATTRIBUTE);
        }

        $message = $this->decisionMessage($form);
        if ('' !== $message) {
            $this->entityManager->persist($this->newComment(
                $revision,
                $actor,
                $message,
            ));
        }

        $this->revisionStateMachine->apply(
            $revision,
            $transition,
        );
        $this->entityManager->flush();

        return $transition;
    }

    /**
     * Add whatever was typed in the discussion box. Posted as a plain field rather than a form, so an empty box is
     * simply ignored.
     */
    protected function handleCommentPost(
        Request $request,
        RevisionInterface $revision,
        User|CompanyUser $actor,
    ): void {
        $this->denyAccessUnlessGranted(
            RevisionVoter::COMMENT,
            $revision,
        );

        $message = trim(strval($request->request->get('message', '')));
        if ('' === $message) {
            return;
        }

        $this->entityManager->persist($this->newComment(
            $revision,
            $actor,
            $message,
        ));
        $this->entityManager->flush();
    }
}
