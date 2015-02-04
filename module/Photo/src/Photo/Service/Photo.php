<?php

namespace Photo\Service;

use Application\Service\AbstractService;
use Zend\ServiceManager\ServiceManager;
use Zend\ServiceManager\ServiceManagerAwareInterface;
use Photo\Model\Album as AlbumModel;
use Photo\Model\Photo as PhotoModel;

/**
 * Photo service.
 */
class Photo extends AbstractService
{

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
     * 
     * @param integer $id the id of the album
     * @return Photo\Model\Photo photo matching the given id
     */
    public function getPhoto($id)
    {
        return $this->getPhotoMapper()->getPhotoById($id);
    }

    /**
     * 
     * @param string $path
     * @return the path at which the photo should be saved
     */
    protected function generateStoragePath($path)
    {
        $config = $this->getConfig();
        //TODO: check if this is fast enough
        $hash = sha1_file($path);
        /**
         * the hash is split to obtain a path 
         * like 92/cfceb39d57d914ed8b14d0e37643de0797ae56.jpg
         */
        $directory = substr($hash, 0, 2);
        if (!file_exists($config['upload_dir'] . '/' . $directory)) {
            mkdir($config['upload_dir'] . '/' . $directory);
        }
        $storage_path = $directory . '/' . substr($hash, 2) . '.' . strtolower(end(explode('.', $path)));
        return $storage_path;
    }

    /**
     * Move the uploaded photo to the storage and store it in the database.
     * All upload actions should use this function to prevent "ghost" files 
     * or database entries
     * @param string $path the tempoary path of the uploaded photo
     * @param \Photo\Model\Album $target_album the album to save the photo in
     * @return \Photo\Model\Photo
     */
    public function storeUploadedPhoto($path, $target_album)
    {
        $config = $this->getConfig();
        $storage_path = $this->generateStoragePath($path);
        $photo = new PhotoModel();
        $photo->setAlbum($target_album);
        $photo = $this->populateMetaData($photo, $path);
        $photo->setPath($storage_path);

        $mapper = $this->getPhotoMapper();
        /**
         * TODO: optionally we could use a transactional query here to make it
         * completely failsafe in case something goes wrong when moving the
         * photo in the storeUploadedPhoto function. However it's very unlikely
         * anything will go wrong when moving the photo.
         */
        $mapper->persist($photo);
        $mapper->flush();
        rename($path, $config['upload_dir'] . '/' . $storage_path);
        return $photo;
    }

    /**
     * 
     * @param \Photo\Model\Photo $photo the photo to add the metadata to
     * @return \Photo\Model\Photo the photo with the added metadata
     */
    protected function populateMetadata($photo, $path)
    {
        $exif = \read_exif_data($path, 'EXIF');
        if (isset($exif['Artist'])) {
            $photo->setArtist($exif['Artist']);
        } else {
            $photo->setArtist("Unknown"); //Needs to be t9n'd in the view
        }
        //I assume the exif data isn't deliberately stripped, so most values 
        //are assumed to exist.
        $photo->setCamera($exif['Model']);
        $photo->setDateTime(\date_create($exif['DateTimeOriginal']));
        $photo->setFlash($exif['Flash'] != 0);
        $photo->setFocalLength($this->frac2dec($exif['FocalLength']));
        $photo->setExposureTime($this->frac2dec($exif['ExposureTime']));
        $photo->setShutterSpeed($this->exifGetShutter($exif));
        $photo->setAperture($this->exifGetFstop($exif));
        $photo->setIso($exif['ISOSpeedRatings']);
        return $photo;
    }

    /*
     * NOTE: Most code in the following part is copied from 
     * the old site, mostly because I lack knowledge in photography.
     */

    /**
     * Convert a string representing a rational number to a string representing 
     * the corresponding decimal approximation. 
     * @param string $str the rational number, represented as num+'/'+den
     * @return float the decimal number, represented as float
     */
    private function frac2dec($str)
    {
        list($n, $d) = \explode('/', $str);
        //Old site suppressed errors of previous line. No clue why.
        if (!empty($d)) {
            return $n / $d;
        }
        return $str;
    }

    /**
     * Computes the shutter speed from the exif data.
     * @param array $exif the exif data extracted from the photo.
     * @return string the shutter speed, represented as a rational string.
     */
    private function exifGetShutter($exif)
    {
        if (!isset($exif['ShutterSpeedValue'])) {
            return "unknown";
        }
        $apex = $this->frac2dec($exif['ShutterSpeedValue']);
        $shutter = \pow(2, -$apex);
        if ($shutter == 0) {
            return "unknown";
        }
        if ($shutter >= 1) {
            return \round($shutter) . 's';
        }
        return '1/' . \round(1 / $shutter) . 's';
    }

    /**
     * Computes the aperture form the exif data.
     * @param array $exif the exif data extracted from the photo.
     * @return string the aperture, respresented as a rational string.
     */
    private function exifGetFstop($exif)
    {
        if (!isset($exif['ApertureValue'])) {
            return "unknown";
        }
        $apex = $this->frac2dec($exif['ApertureValue']);
        $fstop = \pow(2, $apex / 2);
        if ($fstop == 0) {
            return "unknown";
        }
        return 'f/' . \sprintf("%01.1f", $fstop);
    }

    /**
     * Returns the next photo in the album to display
     * 
     * @param \Photo\Model\Photo $photo 
     */
    public function getNextPhoto($photo)
    {
        return $this->getPhotoMapper()->getNextPhoto($photo);
    }

    /**
     * Returns the previous photo in the album to display
     * 
     * @param \Photo\Model\Photo $photo 
     */
    public function getPreviousPhoto($photo)
    {
        return $this->getPhotoMapper()->getPreviousPhoto($photo);
    }

    /**
     * 
     * @param type $id the id of the photo to retrieve
     * @return array of data about the photo, which is usefull inside a view
     */
    public function getPhotoData($id)
    {
        $photo = $this->getPhoto($id);
        if (!is_null($photo)) {
            $next = $this->getNextPhoto($photo);
            $previous = $this->getPreviousPhoto($photo);
        }
        //we'll fix this ugly thing later vv
        $basedir = str_replace("public", "", $this->getConfig()['upload_dir']);

        return array(
            'photo' => $photo,
            'basedir' => $basedir,
            'next' => $next,
            'previous' => $previous
        );
    }

    /**
     * Get the photo config, as used by this service.
     *
     * @return array
     */
    public function getConfig()
    {
        $config = $this->sm->get('config');
        return $config['photo'];
    }

}
