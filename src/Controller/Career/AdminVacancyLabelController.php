<?php

declare(strict_types=1);

namespace App\Controller\Career;

use App\Entity\Application\Enums\AlertTypes;
use App\Entity\Career\CareerLocalisedText;
use App\Entity\Career\VacancyLabel;
use App\Entity\User\Enums\UserRoles;
use App\Form\Career\VacancyLabelType;
use App\Repository\Career\VacancyLabelRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * The labels a vacancy can be tagged with. Shared reference data rather than revisable content, so these are edited
 * directly; a label already in use cannot be removed, since that would quietly rewrite vacancies that were approved
 * carrying it.
 */
#[IsGranted(
    attribute: UserRoles::CompanyAdmin->value,
    message: 'You are not allowed to administer companies.',
)]
#[Route(
    path: '/admin/career/vacancies/labels',
    name: 'admin/career/vacancies/labels/',
)]
class AdminVacancyLabelController extends AbstractController
{
    public function __construct(
        private readonly VacancyLabelRepository $labelRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly TranslatorInterface $translator,
    ) {
    }

    #[Route(
        path: '',
        name: 'index',
        methods: [
            'GET',
            'POST',
        ],
    )]
    public function index(Request $request): Response
    {
        $label = new VacancyLabel();
        $label->setName(new CareerLocalisedText(
            null,
            null,
        ));
        $label->setAbbreviation(new CareerLocalisedText(
            null,
            null,
        ));

        $form = $this->createForm(
            VacancyLabelType::class,
            $label,
        )->handleRequest($request);

        if (
            $form->isSubmitted()
            && $form->isValid()
        ) {
            $this->entityManager->persist($label);
            $this->entityManager->flush();

            $this->addFlash(
                AlertTypes::Success->value,
                $this->translator->trans('The label was added.'),
            );

            return $this->redirectToRoute('admin/career/vacancies/labels/index');
        }

        return $this->render(
            'career/admin/vacancies/labels/index.html.twig',
            [
                'labels' => $this->labelRepository->findAllWithUsage(),
                'form' => $form,
            ],
        );
    }

    #[Route(
        path: '/{label}/edit',
        name: 'edit',
        requirements: ['label' => '\d+'],
        methods: [
            'GET',
            'POST',
        ],
    )]
    public function edit(
        Request $request,
        VacancyLabel $label,
    ): Response {
        $form = $this->createForm(
            VacancyLabelType::class,
            $label,
        )->handleRequest($request);

        if (
            !$form->isSubmitted()
            || !$form->isValid()
        ) {
            return $this->render(
                'career/admin/vacancies/labels/edit.html.twig',
                [
                    'form' => $form,
                    'label' => $label,
                ],
            );
        }

        $this->entityManager->flush();

        $this->addFlash(
            AlertTypes::Success->value,
            $this->translator->trans('The label was saved.'),
        );

        return $this->redirectToRoute('admin/career/vacancies/labels/index');
    }

    #[Route(
        path: '/{label}/delete',
        name: 'delete',
        requirements: ['label' => '\d+'],
        methods: ['POST'],
    )]
    #[IsCsrfTokenValid(
        id: new Expression('"vacancy_label_delete-" ~ args["label"].getId()'),
        tokenKey: '_csrf_token',
    )]
    public function delete(VacancyLabel $label): Response
    {
        // Removing a label that revisions still carry would change what was approved without anybody reviewing it, so
        // it has to be taken off those vacancies first.
        if (!$label->getRevisions()->isEmpty()) {
            $this->addFlash(
                AlertTypes::Warning->value,
                $this->translator->trans('This label is still used by a vacancy, so it cannot be removed.'),
            );

            return $this->redirectToRoute('admin/career/vacancies/labels/index');
        }

        $this->entityManager->remove($label);
        $this->entityManager->flush();

        $this->addFlash(
            AlertTypes::Success->value,
            $this->translator->trans('The label was removed.'),
        );

        return $this->redirectToRoute('admin/career/vacancies/labels/index');
    }
}
