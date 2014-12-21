<?php

namespace Photo\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class AlbumController extends AbstractActionController
{

    public function indexAction()
    {
        $album_id = $this->params()->fromRoute('album_id');
        $activepage = (int)$this->params()->fromRoute('page');
        $album_service = $this->getAlbumService();
        $album = $album_service->getAlbum($album_id);
        $albums = $album_service->getAlbums($album);
        $photos = $album_service->getPhotos($album, $activepage);
        $config = $album_service->getConfig();
        //we'll fix this ugly thing later vv
        $basedir = str_replace("public", "", $config['upload_dir']);
        /**
         * TODO: fetch the real last page based on the photo count in the album
         * the album model doesn't contain the photo count yet, therefore this
         * is not possible
         */
        $lastpage = 9;
        /**
         * This code determines which set of pages to show the user to
         * navigate to. The base idea is to show the two pages before and the 
         * two pages following the currently active page. With special
         * conditions for when the last and the first page are reached.
         */
        $pages=array();
        $startpage=$activepage-2;
        $endpage=$activepage+2;
        if($startpage<0)
        {
            $endpage-=$startpage;
            $startpage=0;
        }
        if($endpage>$lastpage)
        {
            if($startpage>0)
            {
             $startpage -= min($endpage-$lastpage,$startpage);   
            }
            $endpage = $lastpage;
        }
        for($i=$startpage;$i<=$endpage;$i++)
        {
               $pages[]=$i;    
        }
        return new ViewModel(array(
            'album' => $album,
            'albums' => $albums,
            'photos' => $photos,
            'basedir' => $basedir,
            'activepage' => $activepage,
            'pages' => $pages,
            'lastpage' => $lastpage
        ));
    }

    /**
     * Gets the album service.
     * 
     * @return Photo\Service\Album
     */
    public function getAlbumService()
    {
        return $this->getServiceLocator()->get("photo_service_album");
    }

    /**
     * Gets the photo service.
     * 
     * @return Photo\Service\Photo
     */
    public function getPhotoService()
    {
        return $this->getServiceLocator()->get("photo_service_photo");
    }

}
