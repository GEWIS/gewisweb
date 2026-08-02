<?php

declare(strict_types=1);

namespace App\Controller\Career;

use App\Entity\Application\Enums\AlertTypes;
use App\Entity\Career\CompanyBannerPackage;
use App\Entity\User\CompanyUser;
use App\Entity\User\Enums\UserRoles;
use App\Form\Career\BannerImageType;
use App\Service\Career\CompanyBannerService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * A company's banner. Everyone who visits the site sees it, so a company does not swap it out on its own: it proposes
 * one and C4 decides. Whatever is already up stays up until they do.
 *
 * The panels are the ones C4 gets on its own side of the package, so neither side has a screen the other lacks; what
 * differs is only that C4 needs nobody's agreement and this side does.
 */
#[IsGranted(
    attribute: UserRoles::Company->value,
    message: 'You are not allowed to view companies.',
)]
#[Route(
    path: '/company/banner',
    name: 'company/',
)]
class CompanyBannerController extends AbstractController
{
    public function __construct(
        private readonly CompanyBannerService $bannerService,
        private readonly TranslatorInterface $translator,
    ) {
    }

    #[Route(
        path: '',
        name: 'banner',
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
        $package = $company->getActivePackage(CompanyBannerPackage::class);

        if (null === $package) {
            return $this->render(
                'career/company/banner.html.twig',
                [
                    'company' => $company,
                    'package' => null,
                    'form' => null,
                ],
            );
        }

        $form = $this->createForm(
            BannerImageType::class,
            options: ['format' => $package->getFormat()],
        )->handleRequest($request);

        $file = $form->get('image')->getData();
        if (
            $form->isSubmitted()
            && $form->isValid()
            && $file instanceof UploadedFile
        ) {
            if (
                $this->bannerService->propose(
                    $package,
                    $file,
                    $companyUser,
                )
            ) {
                $this->addFlash(
                    AlertTypes::Success->value,
                    $this->translator->trans('Your banner has been proposed. The committee will look at it.'),
                );

                return $this->redirectToRoute('company/banner');
            }

            $this->addFlash(
                AlertTypes::Danger->value,
                $this->translator->trans('That file could not be stored. Try a JPEG, PNG or WebP image.'),
            );
        }

        return $this->render(
            'career/company/banner.html.twig',
            [
                'company' => $company,
                'package' => $package,
                'form' => $form,
            ],
        );
    }
}
