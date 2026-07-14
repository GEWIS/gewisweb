<?php

declare(strict_types=1);

namespace App\Service\Photo;

use App\Entity\Application\Enums\ImageProfile;
use App\Entity\Decision\AssociationYear;
use App\Entity\Photo\Photo;
use App\Entity\Photo\WeeklyPhoto;
use App\Message\Photo\ProcessImageVariantsMessage;
use App\Repository\Photo\PhotoRepository;
use App\Repository\Photo\VoteRepository;
use App\Repository\Photo\WeeklyPhotoRepository;
use App\Service\Application\FileStorage;
use DateInterval;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

use function array_map;
use function krsort;
use function max;
use function pathinfo;
use function sprintf;

use const PATHINFO_EXTENSION;

/**
 * Chooses the photo of the week and keeps its public copy in step. The winner is the highest-rated photo voted on
 * in the past week (votes up-weighted for recency and tags), never a photo that has already been photo of the week.
 * Because album originals are members-only, the chosen photo is copied into the public PhotoWeekly namespace so the
 * anonymous frontpage can serve it; hiding it (or a new week superseding it) removes that public copy, while
 * logged-in members keep seeing the signed original.
 */
final readonly class WeeklyPhotoService
{
    public function __construct(
        private PhotoRepository $photoRepository,
        private VoteRepository $voteRepository,
        private WeeklyPhotoRepository $weeklyPhotoRepository,
        private FileStorage $fileStorage,
        private EntityManagerInterface $entityManager,
        private MessageBusInterface $messageBus,
    ) {
    }

    /**
     * Choose and store the photo of the week from a week's votes (default: the week that just passed), publishing its
     * public copy. Returns null when no photo was voted on that week (not a failure). The previous public copy is
     * expired either way, so a logged-out visitor never sees a stale photo of the week: a week with no votes simply has
     * no public photo of the week.
     */
    public function generatePhotoOfTheWeek(?DateTime $weekStart = null): ?WeeklyPhoto
    {
        $begin = $weekStart ?? new DateTime()->sub(new DateInterval('P1W'));
        $end = (clone $begin)->add(new DateInterval('P1W'));

        $this->expireCurrentPublicCopy();

        $photo = $this->determinePhotoOfTheWeek(
            $begin,
            $end,
        );
        if (null === $photo) {
            return null;
        }

        return $this->store(
            $photo,
            $begin,
        );
    }

    /**
     * Force a specific photo as the photo of the week for a given week (default: the week that just passed), skipping
     * the vote-based selection. For an admin who wants to (re)choose the photo by hand. The previous public copy is
     * expired as with the automatic pick.
     */
    public function setPhotoOfTheWeek(
        Photo $photo,
        ?DateTime $weekStart = null,
    ): WeeklyPhoto {
        $this->expireCurrentPublicCopy();

        return $this->store(
            $photo,
            $weekStart ?? new DateTime()->sub(new DateInterval('P1W')),
        );
    }

    /**
     * Every photo of the week grouped by association year (keyed by the year's first number, most recent year first),
     * for the weekly archive page. Each year is a virtual "weekly album".
     *
     * @return array<int, WeeklyPhoto[]>
     */
    public function getPhotosByYear(): array
    {
        $grouped = [];
        foreach ($this->weeklyPhotoRepository->findAllByWeekDesc() as $weeklyPhoto) {
            $grouped[AssociationYear::fromDate($weeklyPhoto->getWeek())->getYear()][] = $weeklyPhoto;
        }

        krsort($grouped);

        return $grouped;
    }

    /**
     * The photos of the week of one association year (most recent first), for that year's virtual weekly album.
     *
     * @return Photo[]
     */
    public function getPhotosInYear(int $year): array
    {
        return array_map(
            static fn (WeeklyPhoto $weeklyPhoto): Photo => $weeklyPhoto->getPhoto(),
            $this->getPhotosByYear()[$year] ?? [],
        );
    }

    private function expireCurrentPublicCopy(): void
    {
        $current = $this->weeklyPhotoRepository->getCurrentPhotoOfTheWeek();
        if (null === $current) {
            return;
        }

        $this->fileStorage->remove($this->publicPathFor($current->getPhoto()));
    }

    private function store(
        Photo $photo,
        DateTime $week,
    ): WeeklyPhoto {
        $weeklyPhoto = new WeeklyPhoto();
        $weeklyPhoto->setWeek($week);
        $weeklyPhoto->setPhoto($photo);
        $this->entityManager->persist($weeklyPhoto);
        $this->entityManager->flush();

        $this->publish($photo);

        return $weeklyPhoto;
    }

    /**
     * Hide the photo of the week from anonymous visitors by removing its public copy. Logged-in members still see it
     * (they fetch the signed original), matching the legacy "hidden unless logged in" rule.
     */
    public function hide(WeeklyPhoto $weeklyPhoto): void
    {
        $weeklyPhoto->setHidden(true);
        $this->entityManager->flush();

        $this->fileStorage->remove($this->publicPathFor($weeklyPhoto->getPhoto()));
    }

    /**
     * The public (unsigned) stored path the photo of the week is copied to, and from which the frontpage serves it to
     * anonymous visitors.
     */
    public function publicPathFor(Photo $photo): string
    {
        return sprintf(
            'photos/weekly/%d.%s',
            $photo->getId(),
            pathinfo(
                $photo->getPath(),
                PATHINFO_EXTENSION,
            ),
        );
    }

    private function publish(Photo $photo): void
    {
        $publicPath = $this->publicPathFor($photo);
        $this->fileStorage->writeStream(
            $publicPath,
            $this->fileStorage->readStream($photo->getPath()),
        );

        // Pre-generate the frontpage variants; generate-on-miss would otherwise do it on the first visitor.
        $this->messageBus->dispatch(new ProcessImageVariantsMessage($publicPath, ImageProfile::AlbumPhoto));
    }

    /**
     * The highest-rated non-repeat photo voted on in the window, or null when nothing was voted on.
     */
    private function determinePhotoOfTheWeek(
        DateTime $begin,
        DateTime $end,
    ): ?Photo {
        $best = null;
        $bestRating = -1.0;
        foreach (
            $this->voteRepository->getVotesInRange(
                $begin,
                $end,
            ) as $row
        ) {
            $photo = $this->photoRepository->find($row[1]);
            if (
                null === $photo
                || $this->weeklyPhotoRepository->hasBeenPhotoOfTheWeek($photo)
            ) {
                continue;
            }

            $rating = $this->ratePhoto(
                $photo,
                $row[2],
            );
            if ($rating <= $bestRating) {
                continue;
            }

            $best = $photo;
            $bestRating = $rating;
        }

        return $best;
    }

    /**
     * Rate a photo by its vote count, up-weighted for recency (newer photos score higher) and by 50% when it carries
     * any tags. The age is floored at one day so a same-day photo does not divide by zero.
     */
    private function ratePhoto(
        Photo $photo,
        int $votes,
    ): float {
        $ageInDays = max(
            1,
            new DateTime()->diff(
                $photo->getDateTime(),
                true,
            )->days,
        );
        $rating = (float) $votes * (1.0 + 1.0 / (float) $ageInDays);

        return $photo->getTags()->isEmpty()
            ? $rating
            : 1.5 * $rating;
    }
}
