<?php

namespace Photo\Service;

use Application\Service\AbstractService;
use Zend\ServiceManager\ServiceManager;
use Zend\ServiceManager\ServiceManagerAwareInterface;
use Photo\Model\Album as AlbumModel;
use Photo\Model\Photo as PhotoModel;

/**
 * Metadata service. This service implements all functionality related to
 * gathering metadata about photos.
 */
class Metadata extends AbstractService
{

    /**
     * Populates the metadata of a photo based on the EXIF data of the photo
     *
     * @param \Photo\Model\Photo $photo the photo to add the metadata to.
     * @param $path The path where the actual image file is stored
     *
     * @return \Photo\Model\Photo the photo with the added metadata
     */
    public function populateMetadata($photo, $path)
    {
        $exif = read_exif_data($path, 'EXIF');
        if (isset($exif['Artist'])) {
            $photo->setArtist($exif['Artist']);
        } else {
            $photo->setArtist("Unknown"); //Needs to be t9n'd in the view
        }
        //I assume the exif data isn't deliberately stripped, so most values 
        //are assumed to exist.
        $photo->setCamera($exif['Model']);
        $photo->setDateTime(date_create($exif['DateTimeOriginal']));
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
     *
     * @param string $str the rational number, represented as num+'/'+den
     *
     * @return float the decimal number, represented as float
     */
    private function frac2dec($str)
    {
        if (strpos($str, '/') === false) {
            return $str;
        }
        list($n, $d) = explode('/', $str);

        return $n / $d;//I assume stuff like '234/0' is not supported by EXIF.
    }

    /**
     * Computes the shutter speed from the exif data.
     * @param array $exif the exif data extracted from the photo.
     *
     * @return string the shutter speed, represented as a rational string.
     */
    private function exifGetShutter($exif)
    {
        if (!isset($exif['ShutterSpeedValue'])) {
            return "unknown";
        }
        $apex = $this->frac2dec($exif['ShutterSpeedValue']);
        $shutter = pow(2, -$apex);
        if ($shutter == 0) {
            return "unknown";
        }
        if ($shutter >= 1) {
            return round($shutter) . 's';
        }

        return '1/' . round(1 / $shutter) . 's';
    }

    /**
     * Computes the aperture form the exif data.
     * @param array $exif the exif data extracted from the photo.
     *
     * @return string the aperture, represented as a rational string.
     */
    private function exifGetFstop($exif)
    {
        if (!isset($exif['ApertureValue'])) {
            return "unknown";
        }
        $apex = $this->frac2dec($exif['ApertureValue']);
        $fstop = pow(2, $apex / 2);
        if ($fstop == 0) {
            return "unknown";
        }

        return 'f/' . sprintf("%01.1f", $fstop);
    }

}
