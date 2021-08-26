<?php

namespace Photo\Mapper;

use Application\Mapper\BaseMapper;
use Exception;
use Photo\Model\ProfilePhoto as ProfilePhotoModel;

/**
 * Mappers for ProfilePhoto.
 */
class ProfilePhoto extends BaseMapper
{
    /**
     * Checks if the specified photo exists in the database already and returns
     * it if it does.
     *
     * @param int $lidnr The Id of the user to which the photo is assigned
     *
     * @return ProfilePhotoModel|null
     *
     * @throws Exception
     */
    public function getProfilePhotoByLidnr($lidnr)
    {
        return $this->getRepository()->findOneBy(
            [
                'member' => $lidnr,
            ]
        );
    }

    protected function getRepositoryName(): string
    {
        return ProfilePhotoModel::class;
    }
}
