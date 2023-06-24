<?php

declare(strict_types=1);

namespace Photo\Controller;

use Laminas\Http\Request;
use Laminas\Http\Response;
use Laminas\Http\Response\Stream;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\Mvc\I18n\Translator;
use Laminas\View\Model\JsonModel;
use Laminas\View\Model\ViewModel;
use Photo\Model\Album;
use Photo\Service\AclService;
use Photo\Service\Album as AlbumService;
use Photo\Service\Photo as PhotoService;
use User\Permissions\NotAllowedException;

use function array_filter;
use function array_map;
use function count;
use function date;
use function in_array;
use function max;

class PhotoController extends AbstractActionController
{
    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
     */
    public function __construct(
        private readonly Translator $translator,
        private readonly AclService $aclService,
        private readonly AlbumService $albumService,
        private readonly PhotoService $photoService,
        private readonly array $photoConfig,
    ) {
    }

    public function indexAction(): ViewModel
    {
        if (!$this->aclService->isAllowed('view', 'album')) {
            throw new NotAllowedException($this->translator->translate('Not allowed to view albums'));
        }

        $years = $this->albumService->getAlbumYears();
        $year = $this->params()->fromRoute('year');

        // If no year is supplied, use the latest year.
        if (null === $year) {
            if (0 === count($years)) {
                $year = (int) date('Y');
            } else {
                $year = max($years);
            }
        } else {
            $year = (int) $year;
        }

        $albums = $this->albumService->getAlbumsByYear($year);

        // If the membership of the member has ended, only show albums before the end date or in which they are tagged
        if (null !== ($membershipEndsOn = $this->aclService->getUserIdentity()->getMember()->getMembershipEndsOn())) {
            $memberAlbumIds = array_map(
                static function ($a) {
                    return $a['album_id'];
                },
                $this->albumService->getAlbumsByMember($this->aclService->getUserIdentity()->getMember()->getLidnr()),
            );
            $albums = array_filter(
                $albums,
                static function (Album $v) use ($membershipEndsOn, $memberAlbumIds) {
                    return $membershipEndsOn > $v->getStartDateTime()
                        || in_array($v->getId(), $memberAlbumIds);
                },
            );
        }

        return new ViewModel(
            [
                'years' => $years,
                'albums' => $albums,
            ],
        );
    }

    public function downloadAction(): ?Stream
    {
        if (!$this->aclService->isAllowed('download', 'photo')) {
            throw new NotAllowedException($this->translator->translate('Not allowed to download photos'));
        }

        $photoId = (int) $this->params()->fromRoute('photo_id');

        return $this->photoService->getPhotoDownload($photoId);
    }

    /**
     * Display the page containing previous pictures of the week.
     */
    public function weeklyAction(): ViewModel
    {
        if (!$this->aclService->isAllowed('view', 'photo')) {
            throw new NotAllowedException(
                $this->translator->translate('Not allowed to view previous photos of the week'),
            );
        }

        return new ViewModel(
            [
                'config' => $this->photoConfig,
                'photosOfTheWeek' => $this->albumService->getLastPhotosOfTheWeekPerYear(),
            ],
        );
    }

    /**
     * For setting a profile picture.
     */
    public function setProfilePhotoAction(): JsonModel|ViewModel
    {
        /** @var Request $request */
        $request = $this->getRequest();

        if ($request->isPost()) {
            $photoId = (int) $this->params()->fromRoute('photo_id');
            $this->photoService->setProfilePhoto($photoId);

            return new JsonModel(['success' => true]);
        }

        return $this->notFoundAction();
    }

    /**
     * For removing a profile picture.
     */
    public function removeProfilePhotoAction(): Response
    {
        $photoId = (int) $this->params()->fromRoute('photo_id');
        $this->photoService->removeProfilePhoto();

        if (0 !== $photoId) {
            return $this->redirect()->toRoute(
                'photo/photo',
                [
                    'photo_id' => $photoId,
                ],
            );
        }

        return $this->redirect()->toRoute('member/self');
    }

    /**
     * Store a vote for a photo.
     */
    public function voteAction(): JsonModel|ViewModel
    {
        if (!$this->aclService->isAllowed('add', 'vote')) {
            throw new NotAllowedException(
                $this->translator->translate('Not allowed to vote for a photo of the week'),
            );
        }

        /** @var Request $request */
        $request = $this->getRequest();

        if ($request->isPost()) {
            $photoId = (int) $this->params()->fromRoute('photo_id');
            $this->photoService->countVote($photoId);

            return new JsonModel(['success' => true]);
        }

        return $this->notFoundAction();
    }
}
