<?php

declare(strict_types=1);

namespace App\Controller\Career;

use App\Entity\Application\Enums\AlertTypes;
use App\Entity\Career\CompanyHighlightPackage;
use App\Entity\Career\Enums\CompanyAuditVerbs;
use App\Entity\User\CompanyUser;
use App\Entity\User\Enums\UserRoles;
use App\Form\Career\HighlightSelectionType;
use App\Service\Career\CompanyAuditLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Which of a company's vacancies go on the career landing page. Only offered while the company has a highlight package
 * running; the choice takes effect immediately, since it decides what is shown rather than what a vacancy says.
 */
#[IsGranted(
    attribute: UserRoles::Company->value,
    message: 'You are not allowed to view companies.',
)]
#[Route(
    path: '/company/highlights',
    name: 'company/',
)]
class CompanyHighlightController extends AbstractController
{
    public function __construct(
        private readonly CompanyAuditLogger $auditLogger,
        private readonly EntityManagerInterface $entityManager,
        private readonly TranslatorInterface $translator,
    ) {
    }

    #[Route(
        path: '',
        name: 'highlights',
        methods: [
            'GET',
            'POST',
        ],
    )]
    public function index(
        Request $request,
        #[CurrentUser]
        CompanyUser $companyUser,
    ): Response {
        $company = $companyUser->getCompany();
        $package = $companyUser->getCompany()->getActivePackage(CompanyHighlightPackage::class);

        if (null === $package) {
            return $this->render(
                'career/company/highlights.html.twig',
                [
                    'company' => $company,
                    'form' => null,
                    'package' => null,
                ],
            );
        }

        $form = $this->createForm(
            HighlightSelectionType::class,
            $package,
            ['company' => $company],
        )->handleRequest($request);

        if (
            $form->isSubmitted()
            && $form->isValid()
        ) {
            $this->auditLogger->log(
                $company,
                $companyUser,
                CompanyAuditVerbs::HighlightSelectionChanged,
            );
            $this->entityManager->flush();

            $this->addFlash(
                AlertTypes::Success->value,
                $this->translator->trans('Your highlighted vacancies are saved.'),
            );

            return $this->redirectToRoute('company/highlights');
        }

        return $this->render(
            'career/company/highlights.html.twig',
            [
                'company' => $company,
                'form' => $form,
                'package' => $package,
            ],
        );
    }
}
