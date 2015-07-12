<?php

namespace Photo\Model;

use Doctrine\ORM\Mapping as ORM;
use Zend\Permissions\Acl\Resource\ResourceInterface;

/**
 * PhotoHit, represents a .
 *
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 *
 */
class Hit implements ResourceInterface
{

    /**
     * Date and time when the photo was taken.
     *
     * @ORM\Column(type="datetime")
     */
    protected $dateTime;


    /**
     * Get the resource ID.
     *
     * @return string
     */
    public function getResourceId()
    {
        return 'hit';
    }
}