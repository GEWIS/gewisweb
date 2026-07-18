<?php

declare(strict_types=1);

namespace App\Controller\User;

use App\Entity\Application\Enums\AlertTypes;
use App\Entity\User\Enums\UserRoles;
use App\Entity\User\ExternalApp;
use App\Form\User\ExternalAppType;
use App\Repository\User\ExternalAppRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Manage the external applications that may authenticate members. An application is retired by disabling it or setting
 * an expiry rather than deleting it, so the authentication history it is tied to is kept.
 */
#[IsGranted(
    attribute: UserRoles::Admin->value,
    message: 'You are not allowed to administer external applications.',
)]
#[Route(
    path: '/admin/users/apps',
    name: 'admin/users/apps/',
)]
class AdminExternalAppController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly TranslatorInterface $translator,
        private readonly ExternalAppRepository $externalAppRepository,
    ) {
    }

    #[Route(
        path: '',
        name: 'index',
    )]
    public function index(): Response
    {
        return $this->render(
            'user/admin/external-app/index.html.twig',
            [
                'apps' => $this->externalAppRepository->findBy(
                    [],
                    ['appId' => 'ASC'],
                ),
            ],
        );
    }

    #[Route(
        path: '/create',
        name: 'create',
        methods: [
            'GET',
            'POST',
        ],
    )]
    public function create(Request $request): Response
    {
        $externalApp = new ExternalApp();
        $form = $this->createForm(
            ExternalAppType::class,
            $externalApp,
        )->handleRequest($request);

        if (
            !$form->isSubmitted()
            || !$form->isValid()
        ) {
            return $this->render(
                'user/admin/external-app/form.html.twig',
                [
                    'form' => $form,
                    'externalApp' => null,
                ],
            );
        }

        $this->entityManager->persist($externalApp);
        $this->entityManager->flush();

        $this->addFlash(
            AlertTypes::Success->value,
            $this->translator->trans('The external application has been created.'),
        );

        return $this->redirectToRoute('admin/users/apps/index');
    }

    #[Route(
        path: '/{externalApp}/edit',
        name: 'edit',
        requirements: ['externalApp' => '\d+'],
        methods: [
            'GET',
            'POST',
        ],
    )]
    public function edit(
        Request $request,
        ExternalApp $externalApp,
    ): Response {
        $form = $this->createForm(
            ExternalAppType::class,
            $externalApp,
        )->handleRequest($request);

        if (
            !$form->isSubmitted()
            || !$form->isValid()
        ) {
            return $this->render(
                'user/admin/external-app/form.html.twig',
                [
                    'form' => $form,
                    'externalApp' => $externalApp,
                ],
            );
        }

        $this->entityManager->flush();

        $this->addFlash(
            AlertTypes::Success->value,
            $this->translator->trans('The external application has been updated.'),
        );

        return $this->redirectToRoute('admin/users/apps/index');
    }
}
