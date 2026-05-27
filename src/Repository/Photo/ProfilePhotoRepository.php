<?php

declare(strict_types=1);

namespace App\Repository\Photo;

use App\Entity\Photo\ProfilePhoto;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Exception;

/**
 * @extends ServiceEntityRepository<ProfilePhoto>
 */
class ProfilePhotoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(
            $registry,
            ProfilePhoto::class,
        );
    }

    /**
     * Checks if the specified photo exists in the database already and returns
     * it if it does.
     *
     * @param int $lidnr The lidnr of the user to which the photo is assigned
     *
     * @throws Exception
     */
    public function getProfilePhotoByLidnr(int $lidnr): ?ProfilePhoto
    {
        return $this->findOneBy(
            [
                'member' => $lidnr,
            ],
        );
    }
}
