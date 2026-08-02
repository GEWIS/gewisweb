<?php

declare(strict_types=1);

namespace App\Controller\Career;

use App\Entity\Application\Enums\AlertTypes;
use App\Entity\Application\Enums\NotificationType;
use App\Entity\Career\Company;
use App\Entity\Career\CompanyBannerPackage;
use App\Entity\Career\CompanyJobPackage;
use App\Entity\Career\CompanyPackage;
use App\Entity\Career\Enums\CompanyAuditVerbs;
use App\Entity\Career\Enums\CompanyPackageTypes;
use App\Entity\Career\Vacancy;
use App\Entity\User\Enums\UserRoles;
use App\Entity\User\User;
use App\Form\Career\BannerImageType;
use App\Form\Career\CompanyPackageType;
use App\Repository\Application\NotificationRepository;
use App\Repository\Career\CompanyPackageRepository;
use App\Security\User\SudoVoter;
use App\Service\Application\EditLockService;
use App\Service\Application\FileStorage;
use App\Service\Application\RevisionDiscarder;
use App\Service\Career\CareerOverviewCountsProvider;
use App\Service\Career\CompanyAuditLogger;
use App\Service\Career\CompanyBannerService;
use DateInterval;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

use function usort;

/**
 * What companies have bought: an overview of what is about to run out or start, the packages of one company, and the
 * banner of a package, both the one on the site and whatever is waiting to be taken or left.
 */
#[IsGranted(
    attribute: UserRoles::CompanyAdmin->value,
    message: 'You are not allowed to administer companies.',
)]
#[Route(
    path: '/admin/career/packages',
    name: 'admin/career/packages/',
)]
class AdminPackageController extends AbstractController
{
    /**
     * How far ahead the overview looks for packages about to run out or start. Two months is roughly the notice the
     * committee needs to get a renewal signed before anything disappears.
     */
    private const string HORIZON = 'P60D';

    public function __construct(
        private readonly CompanyPackageRepository $packageRepository,
        private readonly NotificationRepository $notificationRepository,
        private readonly CareerOverviewCountsProvider $overviewCounts,
        private readonly CompanyAuditLogger $auditLogger,
        private readonly CompanyBannerService $bannerService,
        private readonly EditLockService $editLockService,
        private readonly RevisionDiscarder $revisionDiscarder,
        private readonly FileStorage $fileStorage,
        private readonly EntityManagerInterface $entityManager,
        private readonly TranslatorInterface $translator,
    ) {
    }

    #[Route(
        path: '',
        name: 'index',
    )]
    public function index(): Response
    {
        $horizon = new DateTime()->add(new DateInterval(self::HORIZON));

        $expiring = [];
        $starting = [];
        foreach (CompanyPackageTypes::cases() as $type) {
            foreach (
                $this->packageRepository->findFuturePackageExpirationsBeforeDate(
                    $type,
                    $horizon,
                ) as $package
            ) {
                $expiring[] = $package;
            }

            foreach (
                $this->packageRepository->findFuturePackageStartsBeforeDate(
                    $type,
                    $horizon,
                ) as $package
            ) {
                $starting[] = $package;
            }
        }

        // Each kind of package is asked for separately, so the lists arrive grouped by kind rather than by date; what
        // the overview is for is what happens next.
        usort(
            $expiring,
            static function (
                CompanyPackage $a,
                CompanyPackage $b,
            ): int {
                return $a->getExpirationDate() <=> $b->getExpirationDate();
            },
        );
        usort(
            $starting,
            static function (
                CompanyPackage $a,
                CompanyPackage $b,
            ): int {
                return $a->getStartingDate() <=> $b->getStartingDate();
            },
        );

        return $this->render(
            'career/admin/packages/index.html.twig',
            [
                'expiring' => $expiring,
                'starting' => $starting,
                'horizon' => $horizon,
                'counts' => $this->overviewCounts->counts(),
            ],
        );
    }

    #[Route(
        path: '/company/{company}',
        name: 'company',
        requirements: ['company' => '\d+'],
        methods: ['GET'],
    )]
    public function forCompany(Company $company): Response
    {
        return $this->render(
            'career/admin/packages/company.html.twig',
            ['company' => $company],
        );
    }

    #[Route(
        path: '/company/{company}/create/{type}',
        name: 'create',
        requirements: [
            'company' => '\d+',
            'type' => 'banner|featured|highlight|job',
        ],
        methods: [
            'GET',
            'POST',
        ],
    )]
    public function create(
        Request $request,
        Company $company,
        string $type,
        #[CurrentUser]
        User $user,
    ): Response {
        $packageType = CompanyPackageTypes::from($type);
        $class = CompanyPackageTypes::entityClass($packageType);

        $package = new $class();
        $package->setCompany($company);
        $package->setStartingDate(new DateTime('today'));
        $package->setExpirationDate(new DateTime('today +1 year'));
        $package->setPublished(true);

        $form = $this->createForm(
            CompanyPackageType::class,
            $package,
            ['package_type' => $packageType],
        )->handleRequest($request);

        if (
            !$form->isSubmitted()
            || !$form->isValid()
        ) {
            return $this->render(
                'career/admin/packages/create.html.twig',
                [
                    'form' => $form,
                    'company' => $company,
                    'packageType' => $packageType,
                ],
            );
        }

        $this->entityManager->persist($package);
        $this->auditLogger->log(
            $company,
            $user,
            CompanyAuditVerbs::PackageCreated,
            $packageType->value,
        );
        $this->entityManager->flush();

        $this->addFlash(
            AlertTypes::Success->value,
            $this->translator->trans('The package was added.'),
        );

        return $this->backToCompany($company);
    }

    #[Route(
        path: '/{package}/edit',
        name: 'edit',
        requirements: ['package' => '\d+'],
        methods: [
            'GET',
            'POST',
        ],
    )]
    public function edit(
        Request $request,
        CompanyPackage $package,
        #[CurrentUser]
        User $user,
    ): Response {
        $form = $this->createForm(
            CompanyPackageType::class,
            $package,
            ['package_type' => $package->getType()],
        )->handleRequest($request);

        if (
            !$form->isSubmitted()
            || !$form->isValid()
        ) {
            return $this->render(
                'career/admin/packages/edit.html.twig',
                [
                    'form' => $form,
                    'package' => $package,
                ],
            );
        }

        $this->auditLogger->log(
            $package->getCompany(),
            $user,
            CompanyAuditVerbs::PackageUpdated,
            $package->getType()->value,
        );
        $this->entityManager->flush();

        $this->addFlash(
            AlertTypes::Success->value,
            $this->translator->trans('The package was saved.'),
        );

        return $this->backToCompany($package->getCompany());
    }

    #[IsGranted('SUDO')]
    #[Route(
        path: '/{package}/delete',
        name: 'delete',
        requirements: ['package' => '\d+'],
        methods: ['POST'],
    )]
    #[IsCsrfTokenValid(
        id: new Expression('"company_package_delete-" ~ args["package"].getId()'),
        tokenKey: '_csrf_token',
    )]
    public function delete(
        CompanyPackage $package,
        #[CurrentUser]
        User $user,
    ): Response {
        $company = $package->getCompany();

        $this->auditLogger->log(
            $company,
            $user,
            CompanyAuditVerbs::PackageDeleted,
            $package->getType()->value,
        );

        // The vacancies go with the package, but their revision chains do not follow on their own: the review
        // comments and the previous-revision links have no cascade, and a vacancy points back at the revision it
        // shows. Those references are dropped first, in their own flush, so the removals that follow are unambiguous;
        // one transaction, so a crash in between cannot leave a vacancy without its chain.
        $this->entityManager->wrapInTransaction(function () use ($package): void {
            if ($package instanceof CompanyJobPackage) {
                foreach ($package->getVacancies() as $vacancy) {
                    $this->unhookVacancy($vacancy);
                }

                $this->entityManager->flush();

                foreach ($package->getVacancies() as $vacancy) {
                    foreach ($vacancy->getRevisions() as $revision) {
                        $this->revisionDiscarder->removeRevision($revision);
                    }
                }
            }

            $this->entityManager->remove($package);
            $this->entityManager->flush();
        });

        $this->addFlash(
            AlertTypes::Success->value,
            $this->translator->trans('The package was removed.'),
        );

        return $this->backToCompany($company);
    }

    /**
     * The banner of one package: what is on the site, what the company has proposed, and the form that puts a new
     * image up. It is the same surface the company gets in its own portal, so C4 is never stuck waiting for a
     * representative to upload something on its behalf; the only difference is that what C4 uploads is already
     * decided on and goes straight up.
     *
     * Everything on the page changes what the whole site shows, so the sudo grant is asked for on the way in rather
     * than per button: a GET carries the visitor back here afterwards, where a POST would have dropped their upload.
     */
    #[IsGranted(SudoVoter::ATTRIBUTE)]
    #[Route(
        path: '/{package}/banner',
        name: 'banner',
        requirements: ['package' => '\d+'],
        methods: [
            'GET',
            'POST',
        ],
    )]
    public function banner(
        Request $request,
        CompanyPackage $package,
        #[CurrentUser]
        User $user,
    ): Response {
        if (!$package instanceof CompanyBannerPackage) {
            throw new NotFoundHttpException();
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
                $this->bannerService->publish(
                    $package,
                    $file,
                    $user,
                )
            ) {
                $this->addFlash(
                    AlertTypes::Success->value,
                    $this->translator->trans('The banner is now on the website.'),
                );

                return $this->redirectToRoute(
                    'admin/career/packages/banner',
                    ['package' => $package->getId()],
                );
            }

            $this->addFlash(
                AlertTypes::Danger->value,
                $this->translator->trans('That file could not be stored. Try a JPEG, PNG or WebP image.'),
            );
        }

        return $this->render(
            'career/admin/packages/banner.html.twig',
            [
                'package' => $package,
                'form' => $form,
            ],
        );
    }

    /**
     * A banner shows across the whole site, so taking one is a reviewer action and asks for a fresh sudo grant, the
     * same as every decision on a revision does.
     */
    #[IsGranted(SudoVoter::ATTRIBUTE)]
    #[Route(
        path: '/{package}/banner/approve',
        name: 'banner/approve',
        requirements: ['package' => '\d+'],
        methods: ['POST'],
    )]
    #[IsCsrfTokenValid(
        id: new Expression('"company_banner_decide-" ~ args["package"].getId()'),
        tokenKey: '_csrf_token',
    )]
    public function approveBanner(
        CompanyPackage $package,
        #[CurrentUser]
        User $user,
    ): Response {
        $banner = $this->requirePendingBanner($package);

        // The banner it replaced is no longer referenced by anything, so the bytes can go.
        $this->settleBanner(
            $banner,
            $user,
            CompanyAuditVerbs::BannerApproved,
            $banner->acceptPendingImage(),
        );

        $this->addFlash(
            AlertTypes::Success->value,
            $this->translator->trans('The banner is now live.'),
        );

        return $this->backToApprovals();
    }

    #[IsGranted(SudoVoter::ATTRIBUTE)]
    #[Route(
        path: '/{package}/banner/reject',
        name: 'banner/reject',
        requirements: ['package' => '\d+'],
        methods: ['POST'],
    )]
    #[IsCsrfTokenValid(
        id: new Expression('"company_banner_decide-" ~ args["package"].getId()'),
        tokenKey: '_csrf_token',
    )]
    public function rejectBanner(
        CompanyPackage $package,
        #[CurrentUser]
        User $user,
    ): Response {
        $banner = $this->requirePendingBanner($package);

        // Nothing points at the rejected proposal any more, so reclaim it rather than leave it lying around.
        $this->settleBanner(
            $banner,
            $user,
            CompanyAuditVerbs::BannerRejected,
            $banner->rejectPendingImage(),
        );

        $this->addFlash(
            AlertTypes::Success->value,
            $this->translator->trans('The proposed banner was rejected.'),
        );

        return $this->backToApprovals();
    }

    /**
     * What both decisions do once the banner itself has been settled: record who decided, reclaim the image nothing
     * points at any more, and take the queue notification down so the company's next proposal is announced again.
     */
    private function settleBanner(
        CompanyBannerPackage $banner,
        User $user,
        CompanyAuditVerbs $verb,
        ?string $discardedImage,
    ): void {
        $this->auditLogger->log(
            $banner->getCompany(),
            $user,
            $verb,
        );
        $this->entityManager->flush();

        if (null !== $discardedImage) {
            $this->fileStorage->remove($discardedImage);
        }

        $id = $banner->getId();
        if (null === $id) {
            return;
        }

        $this->notificationRepository->removeForSubject(
            NotificationType::CompanyBannerAwaitingReview,
            $id,
        );
    }

    private function backToApprovals(): Response
    {
        return $this->redirectToRoute('admin/career/approvals/index');
    }

    /**
     * Drops everything that would keep a vacancy's rows in place: its edit lock (which has no foreign key of its own),
     * the revision it shows and the links between the revisions in its chain.
     */
    private function unhookVacancy(Vacancy $vacancy): void
    {
        $this->editLockService->purge($vacancy);

        $vacancy->setCurrentRevision(null);
        $vacancy->setLiveRevision(null);

        foreach ($vacancy->getRevisions() as $revision) {
            $revision->setPreviousRevision(null);
        }
    }

    private function requirePendingBanner(CompanyPackage $package): CompanyBannerPackage
    {
        if (
            !$package instanceof CompanyBannerPackage
            || !$package->hasPendingImage()
        ) {
            throw new NotFoundHttpException();
        }

        return $package;
    }

    private function backToCompany(Company $company): Response
    {
        return $this->redirectToRoute(
            'admin/career/packages/company',
            ['company' => $company->getId()],
        );
    }
}
