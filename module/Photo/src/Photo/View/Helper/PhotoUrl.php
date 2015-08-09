<?php

namespace Photo\View\Helper;

use Zend\View\Helper\AbstractHelper;

class PhotoUrl extends AbstractHelper
{

    /**
     * Photo service.
     *
     * @var \Photo\Service\Photo
     */
    protected $photoService;

    /**
     * Get the photo URL.
     *
     * @param string $path
     *
     * @return string
     */
    public function __invoke($path)
    {
        $basedir = $this->getPhotoService()->getBaseDirectory();

        return $this->getView()->basePath() . $basedir . '/' . $path;
    }

    /**
     * Get the authentication service.
     *
     * @return \Photo\Service\Photo
     */
    public function getPhotoService()
    {
        return $this->photoService;
    }

    /**
     * Set the photo service.
     *
     * @param \Photo\Service\Photo $photoService
     */
    public function setPhotoService($photoService)
    {
        $this->photoService = $photoService;
    }
}
