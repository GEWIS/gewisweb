<?php

declare(strict_types=1);

namespace Photo\Mapper;

use Application\Mapper\BaseMapper;
use Exception;
use Override;
use Photo\Model\ProfilePhoto as ProfilePhotoModel;

/**
 * Mappers for ProfilePhoto.
 *
 * @template-extends BaseMapper<ProfilePhotoModel>
 */
class ProfilePhoto extends BaseMapper
{
    /**
     * Checks if the specified photo exists in the database already and returns
     * it if it does.
     *
     * @param int $lidnr The lidnr of the user to which the photo is assigned
     *
     * @throws Exception
     */
    public function getProfilePhotoByLidnr(int $lidnr): ?ProfilePhotoModel
    {
        return $this->getRepository()->findOneBy(
            [
                'member' => $lidnr,
            ],
        );
    }

    #[Override]
    protected function getRepositoryName(): string
    {
        return ProfilePhotoModel::class;
    }
}
