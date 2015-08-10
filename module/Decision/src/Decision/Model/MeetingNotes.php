<?php

namespace Decision\Model;

use Doctrine\ORM\Mapping as ORM;
use Zend\Permissions\Acl\Resource\ResourceInterface;

/**
 * Meeting notes.
 *
 * @ORM\Entity
 */
class MeetingNotes implements ResourceInterface
{

    /**
     * Meeting type.
     *
     * @ORM\Id
     * @ORM\Column(type="string")
     */
    protected $type;

    /**
     * Meeting number.
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     */
    protected $number;

    /**
     * The corresponding meeting for these notes.
     *
     * @ORM\OneToOne(targetEntity="Meeting", inversedBy="meetingNotes")
     * @ORM\JoinColumns({
     *  @ORM\JoinColumn(name="type", referencedColumnName="type"),
     *  @ORM\JoinColumn(name="number", referencedColumnName="number"),
     * })
     */
    protected $meeting;

    /**
     * The storage path
     *
     * @ORM\Column(type="string")
     */
    protected $path;

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @param \Decision\Model\Meeting $meeting
     */
    public function setMeeting($meeting)
    {
        $this->meeting = $meeting;
    }

    /**
     * @param string $path
     */
    public function setPath($path)
    {
        $this->path = $path;
    }


    /**
     * Get the resource ID.
     *
     * @return string
     */
    public function getResourceId()
    {
        return 'meeting_notes';
    }
}
