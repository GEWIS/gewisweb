<?php

declare(strict_types=1);

namespace App\Controller\Application;

use App\Entity\Application\AbstractRevisionComment;
use App\Entity\Application\RevisionInterface;
use App\Entity\User\CompanyUser;
use App\Entity\User\User;
use App\Form\Application\ReviewDecisionType;
use App\Service\Application\RevisionActionResolver;
use App\ViewModel\Application\RevisionActions;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Workflow\WorkflowInterface;
use Symfony\Contracts\Service\Attribute\Required;
use Symfony\Contracts\Translation\TranslatorInterface;

use function strval;
use function trim;

/**
 * The parts of driving a revision that no domain does differently: reading what may be done with it, building the
 * decision form, working out which button was pressed, and starting a thread entry.
 *
 * Deliberately holds no routes. Every action keeps its own `#[Route]`, `#[IsGranted]` and `#[IsCsrfTokenValid]`,
 * because the value resolver needs the concrete revision type to give an automatic 404, the career module serves two
 * aggregates with different route names from one class, and the company portal's token ids cannot be derived from a
 * revision argument it does not take.
 */
abstract class AbstractRevisionController extends AbstractController
{
    protected RevisionActionResolver $revisionActionResolver;

    protected EntityManagerInterface $entityManager;

    protected TranslatorInterface $translator;

    protected WorkflowInterface $revisionStateMachine;

    /**
     * Injected through a setter rather than a constructor so a concrete controller keeps its own constructor and its
     * own dependency list, exactly as {@see AbstractController} takes its container.
     */
    #[Required]
    public function setRevisionDependencies(
        RevisionActionResolver $revisionActionResolver,
        EntityManagerInterface $entityManager,
        TranslatorInterface $translator,
        #[Target('revisionStateMachine')]
        WorkflowInterface $revisionStateMachine,
    ): void {
        $this->revisionActionResolver = $revisionActionResolver;
        $this->entityManager = $entityManager;
        $this->translator = $translator;
        $this->revisionStateMachine = $revisionStateMachine;
    }

    protected function revisionActions(RevisionInterface $revision): RevisionActions
    {
        return $this->revisionActionResolver->resolve($revision);
    }

    /**
     * @return FormInterface<array<string, mixed>>
     */
    protected function createDecisionForm(RevisionActions $actions): FormInterface
    {
        return $this->createForm(
            ReviewDecisionType::class,
            null,
            $actions->toFormOptions(),
        );
    }

    /**
     * The clicked submit button names the transition to apply. `getClickedButton()` lives on the concrete Form, hence
     * the narrowing; an unsubmitted or button-less form yields an empty string.
     *
     * @param FormInterface<array<string, mixed>> $form
     */
    protected function clickedTransition(FormInterface $form): string
    {
        if (!$form instanceof Form) {
            return '';
        }

        $button = $form->getClickedButton();

        return $button instanceof FormInterface
            ? $button->getName()
            : '';
    }

    /**
     * The feedback or response typed alongside a decision. The field is only present for the transitions that carry
     * one, so its absence is normal rather than an error.
     *
     * @param FormInterface<array<string, mixed>> $form
     */
    protected function decisionMessage(FormInterface $form): string
    {
        if (!$form->has('message')) {
            return '';
        }

        return trim(strval($form->get('message')->getData()));
    }

    /**
     * A thread entry on this revision, authored by whichever kind of principal is signed in. The caller persists it,
     * so a comment left with a decision commits together with that decision.
     */
    protected function newComment(
        RevisionInterface $revision,
        User|CompanyUser $actor,
        string $body,
    ): AbstractRevisionComment {
        $class = $revision->getCommentClass();

        $comment = new $class();
        $comment->attachTo($revision);
        $comment->setBody($body);

        if ($actor instanceof User) {
            $comment->setAuthor($actor);
        } else {
            $comment->setAuthorCompanyUser($actor);
        }

        return $comment;
    }
}
