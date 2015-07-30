<?php

namespace Photo\Service;

use Application\Service\AbstractService;
use Photo\Model\Photo as PhotoModel;
use Photo\Model\Hit as HitModel;
use Photo\Model\Tag as TagModel;
use Imagick;

/**
 * Photo service.
 */
class Photo extends AbstractService
{
    /**
     * Retrieves a photo by an id.
     *
     * @param integer $id the id of the album
     * @return \Photo\Model\Photo photo matching the given id
     */
    public function getPhoto($id)
    {
        return $this->getPhotoMapper()->getPhotoById($id);
    }

    /**
     * Returns the next photo in the album to display
     *
     * @param \Photo\Model\Photo $photo
     *
     * @return \Photo\Model\Photo The next photo.
     */
    public function getNextPhoto($photo)
    {
        return $this->getPhotoMapper()->getNextPhoto($photo);
    }

    /**
     * Returns the previous photo in the album to display
     *
     * @param \Photo\Model\Photo $photo
     *
     * @return \Photo\Model\Photo The next photo.
     */
    public function getPreviousPhoto($photo)
    {
        return $this->getPhotoMapper()->getPreviousPhoto($photo);
    }

    /**
     * Get all photos in an album
     *
     * @param \Photo\Model\Album $album the album to get the photos from
     * @param integer $start the result to start at
     * @param integer $maxResults max amount of results to return, null for infinite
     *
     * @return array of Photo\Model\Album
     */
    public function getPhotos($album, $start = 0, $maxResults = null)
    {
        return $this->getPhotoMapper()->getAlbumPhotos($album, $start, $maxResults);
    }

    /**
     * Returns a unique file name for a photo.
     *
     * @param \Photo\Model\Photo $photo the photo to get a name for
     *
     * @return string
     */
    public function getPhotoFileName($photo)
    {
        // filtering is required to prevent invalid characters in file names.
        $filter = new \Zend\I18n\Filter\Alnum(true);
        $albumName = $filter->filter($photo->getAlbum()->getName());

        // don't put spaces in file names
        $albumName = str_replace(' ', '-', $albumName);

        $extension = substr($photo->getPath(), strpos($photo->getPath(), '.'));

        $photoName = $albumName . '-' . $photo->getDateTime()->format('Y') . '-' . $photo->getId() . $extension;

        return $photoName;
    }

    /**
     * Returns a zend response to be used for downloading a photo.
     *
     * @param integer $photoId
     * @return \Zend\Http\Response\Stream
     */
    public function getPhotoDownload($photoId)
    {
        $photo = $this->getPhoto($photoId);
        $config = $this->getConfig();
        $file = $config['upload_dir'] . '/' . $photo->getPath();
        $fileName = $this->getPhotoFileName($photo);
        //TODO: ACL
        $response = new \Zend\Http\Response\Stream();
        $response->setStream(fopen($file, 'r'));
        $response->setStatusCode(200);
        $response->setStreamName($fileName);
        $headers = new \Zend\Http\Headers();
        $headers->addHeaders(array(
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
            'Content-Type' => 'application/octet-stream',
            'Content-Length' => filesize($file),
            // zf2 parses date as a string for a \DateTime() object:
            'Expires' => '@0',
            'Cache-Control' => 'must-revalidate',
            'Pragma' => 'public'
        ));
        $response->setHeaders($headers);

        return $response;
    }

    /**
     * Get the photo data belonging to a certain photo
     *
     * @param int $photoId the id of the photo to retrieve
     *
     * @return array|null of data about the photo, which is useful inside a view
     *          or null if the photo was not found
     */
    public function getPhotoData($photoId)
    {
        $photo = $this->getPhoto($photoId);

        // photo does not exist
        if (is_null($photo)) {
            return null;
        }

        $next = $this->getNextPhoto($photo);
        $previous = $this->getPreviousPhoto($photo);

        $basedir = $this->getBaseDirectory();

        return array(
            'photo' => $photo,
            'basedir' => $basedir,
            'next' => $next,
            'previous' => $previous
        );
    }

    /**
     * Count a hit for the specified photo. Should be called whenever a photo is viewed.
     *
     * @param \Photo\Model\Photo $photo
     */
    public function countHit($photo)
    {
        $hit = new HitModel();
        $hit->setDateTime(new \DateTime());
        $photo->addHit($hit);

        $this->getPhotoMapper()->flush();
    }
    /**
     * Retrieves a tag if it exists.
     *
     * @param integer $photoId
     * @param integer $lidnr
     *
     * @return \Photo\Model\Tag|null
     */
    public function findTag($photoId, $lidnr)
    {
        return $this->getTagMapper()->findTag($photoId, $lidnr);
    }

    /**
     * Tags a user in the specified photo.
     *
     * @param integer $photoId
     * @param integer $lidnr
     *
     * @return \Photo\Model\Tag|null
     */
    public function addTag($photoId, $lidnr)
    {
        if (is_null($this->findTag($photoId, $lidnr))) {
            $photo = $this->getPhoto($photoId);
            $member = $this->getMemberService()->findMemberByLidnr($lidnr);
            $tag = new TagModel();
            $tag->setMember($member);
            $photo->addTag($tag);

            $this->getPhotoMapper()->flush();

            return $tag;
        } else {
            // Tag exists
            return null;
        }
    }

    /**
     * Removes a tag
     *
     * @param integer $photoId
     * @param integer $lidnr
     *
     * @return boolean indicating whether removing the tag succeeded.
     */
    public function removeTag($photoId, $lidnr)
    {
        $tag = $this->findTag($photoId, $lidnr);
        if (!is_null($tag)) {
            $this->getTagMapper()->remove($tag);
            $this->getTagMapper()->flush();

            return true;
        } else {
            return false;
        }
    }

    /**
     * Gets the base directory from which the photo paths should be requested
     *
     * @return string
     */
    public function getBaseDirectory()
    {
        $config = $this->getConfig();

        return str_replace('public', '', $config['upload_dir']);
    }

    /**
     * Get the photo config, as used by this service.
     *
     * @return array containing the config for the module
     */
    public function getConfig()
    {
        $config = $this->sm->get('config');

        return $config['photo'];
    }

    /**
     * Get the photo mapper.
     *
     * @return \Photo\Mapper\Photo
     */
    public function getPhotoMapper()
    {
        return $this->sm->get('photo_mapper_photo');
    }

    /**
     * Get the album mapper.
     *
     * @return \Photo\Mapper\Album
     */
    public function getAlbumMapper()
    {
        return $this->sm->get('photo_mapper_album');
    }

    /**
     * Get the tag mapper.
     *
     * @return \Photo\Mapper\Tag
     */
    public function getTagMapper()
    {
        return $this->sm->get('photo_mapper_tag');
    }

    /**
     * Gets the metadata service.
     *
     * @return \Photo\Service\Metadata
     */
    public function getMetadataService()
    {
        return $this->sm->get('photo_service_metadata');
    }

    /**
     * Gets the album service.
     *
     * @return \Photo\Service\Album
     */
    public function getAlbumService()
    {
        return $this->sm->get('photo_service_album');
    }

    /**
     * Get the member service.
     */
    public function getMemberService()
    {
        return $this->sm->get('decision_service_member');
    }

    /**
     * Gets the album service.
     *
     * @return \Photo\Service\Album
     */
    public function getPhotoService()
    {
        return $this->sm->get('photo_service_photo');
    }

}
