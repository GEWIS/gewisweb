<?php

declare(strict_types=1);

namespace Photo\Service;

use Application\Service\FileStorage as FileStorageService;
use Exception;
use Imagick;
use InvalidArgumentException;
use Laminas\Mvc\I18n\Translator;
use Laminas\Validator\File\Extension;
use Laminas\Validator\File\IsImage;
use Photo\Mapper\Photo as PhotoMapper;
use Photo\Model\Album as AlbumModel;
use Photo\Model\Photo as PhotoModel;
use Photo\Service\Metadata as MetadataService;
use Photo\Service\Photo as PhotoService;
use Throwable;
use User\Permissions\NotAllowedException;

use function array_values;
use function explode;
use function getrandmax;
use function implode;
use function move_uploaded_file;
use function random_int;
use function sprintf;
use function sys_get_temp_dir;
use function unlink;

/**
 * Admin service for all photo admin related functions.
 */
class Admin
{
    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
     */
    public function __construct(
        private readonly AclService $aclService,
        private readonly Translator $translator,
        private readonly PhotoService $photoService,
        private readonly MetadataService $metadataService,
        private readonly FileStorageService $storageService,
        private readonly PhotoMapper $photoMapper,
        private readonly array $photoConfig,
    ) {
    }

    /**
     * Move the uploaded photo to the storage and store it in the database.
     * All upload actions should use this function to prevent "ghost" files
     * or database entries.
     *
     * @param string     $path        the temporary path of the uploaded photo
     * @param AlbumModel $targetAlbum the album to save the photo in
     * @param bool       $move        whether to move the photo instead of copying it
     */
    public function storeUploadedPhoto(
        string $path,
        AlbumModel $targetAlbum,
        bool $move = false,
    ): bool|PhotoModel {
        if (!$this->aclService->isAllowed('add', 'photo')) {
            throw new NotAllowedException($this->translator->translate('Not allowed to add photos.'));
        }

        $config = $this->photoConfig;
        $storagePath = $this->storageService->storeFile($path, false);
        //check if photo exists already in the database
        $photo = $this->photoMapper->getPhotoByData($storagePath, $targetAlbum);
        //if the returned object is null, then the photo doesn't exist
        if (null === $photo) {
            $photo = new PhotoModel();
            $photo->setAlbum($targetAlbum);
            $photo = $this->metadataService->populateMetaData($photo, $path);
            $photo->setPath($storagePath);

            $mapper = $this->photoMapper;
            $mapper->getConnection()->beginTransaction();
            try {
                /*
                 * Create and set the storage paths for thumbnails.
                 */
                $photo->setLargeThumbPath(
                    $this->createThumbnail(
                        $path,
                        $config['large_thumb_size']['width'],
                        $config['large_thumb_size']['height'],
                    ),
                );
                $photo->setSmallThumbPath(
                    $this->createThumbnail(
                        $path,
                        $config['small_thumb_size']['width'],
                        $config['small_thumb_size']['height'],
                    ),
                );

                if ($move) {
                    unlink($path);
                }

                $mapper->persist($photo);
                $mapper->flush();
                $mapper->getConnection()->commit();
            } catch (Throwable) {
                // Rollback if anything went wrong
                $mapper->getConnection()->rollBack();
                $this->photoService->deletePhotoFiles($photo);

                return false;
            }
        }

        return $photo;
    }

    /**
     * Creates and stores a thumbnail of specified maximum size from a stored
     * image.
     *
     * @param string $path   the path of the original image
     * @param int    $width  the maximum width of the thumbnail (in px)
     * @param int    $height the maximum height of the thumbnail (in px)
     *
     * @return string the path of the created thumbnail
     */
    protected function createThumbnail(
        string $path,
        int $width,
        int $height,
    ): string {
        $image = new Imagick($path);
        $image->thumbnailImage($width, $height, true);
        $image->setimageformat('png');

        $orientation = $image->getImageOrientation();

        switch ($orientation) {
            case imagick::ORIENTATION_BOTTOMRIGHT:
                $image->rotateimage('#000000', 180);
                break;

            case imagick::ORIENTATION_RIGHTTOP:
                $image->rotateimage('#000000', 90);
                break;

            case imagick::ORIENTATION_LEFTBOTTOM:
                $image->rotateimage('#000000', -90);
                break;
        }

        $image->setImageOrientation(imagick::ORIENTATION_TOPLEFT);

        //Tempfile is used to generate sha1, not sure this is the best method
        $tempFileName = sys_get_temp_dir() . '/ThumbImage' . random_int(0, getrandmax()) . '.png';
        $image->writeImage($tempFileName);

        return $this->storageService->storeFile($tempFileName);
    }

    /**
     * @param array $files
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
     */
    public function upload(
        array $files,
        AlbumModel $album,
    ): void {
        $this->checkUploadAllowed();

        $imageValidator = new IsImage(
            ['magicFile' => false],
        );
        $extensionValidator = new Extension(
            ['JPEG', 'JPG', 'JFIF', 'TIFF', 'RIF', 'GIF', 'BMP', 'PNG'],
        );

        if (0 !== $files['file']['error']) {
            throw new Exception(
                $this->translator->translate(
                    'An unknown error occurred during uploading (' . $files['file']['error'] . ')',
                ),
            );
        }

        /**
         * We re-add the original extension so it can be preserved later on
         * when moving the file.
         */
        $extension = explode('/', $files['file']['type'])[1];
        $path = $files['file']['tmp_name'] . '.' . $extension;
        move_uploaded_file($files['file']['tmp_name'], $path);

        if (!$imageValidator->isValid($path)) {
            throw new InvalidArgumentException(
                sprintf(
                    $this->translator->translate("The uploaded file is not a valid image \nError: %s"),
                    implode(
                        ',',
                        array_values($imageValidator->getMessages()),
                    ),
                ),
            );
        }

        if (!$extensionValidator->isValid($path)) {
            throw new InvalidArgumentException(
                $this->translator->translate('The uploaded file does not have a valid extension'),
            );
        }

        $this->storeUploadedPhoto($path, $album, true);
    }

    /**
     * Checks if the current user is allowed to upload photos.
     */
    public function checkUploadAllowed(): void
    {
        if (!$this->aclService->isAllowed('upload', 'photo')) {
            throw new NotAllowedException($this->translator->translate('Not allowed to upload photos.'));
        }
    }
}
