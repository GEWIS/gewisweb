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
     * Hit ID.
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * Date and time when the photo was taken.
     *
     * @ORM\Column(type="datetime")
     */
    protected $dateTime;

    /**
     * @ORM\ManyToOne(targetEntity="Photo\Model\Photo", inversedBy="hits")
     * @ORM\JoinColumn(name="photo_id", referencedColumnName="id")
     */
    protected $photo;

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