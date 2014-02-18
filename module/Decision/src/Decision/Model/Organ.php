<?php

namespace Decision\Model;

use Doctrine\ORM\Mapping as ORM;

use Zend\Permissions\Acl\Resource\ResourceInterface;

/**
 * An organ of GEWIS.
 *
 * NOTE: The actual data for this model in the database will probably be
 * generated from the actual GEWIS database, which is maintained by the
 * secretary of the board. Hence, this data should not be modified.
 *
 * @ORM\Entity
 */
class Organ implements ResourceInterface
{

    const TYPE_COMMITTEE = 'committee';
    const TYPE_FRATERNITY = 'fraternity';

    /**
     * Id of the organ.
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * Abbreviation.
     *
     * @ORM\Column(type="string")
     */
    protected $abbr;

    /**
     * Full name.
     *
     * @ORM\Column(type="string")
     */
    protected $name;

    /**
     * Type
     *
     * @ORM\Column(type="string")
     */
    protected $type = self::TYPE_COMMITTEE;


    /**
     * Get the id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get the abbreviation.
     *
     * @return string
     */
    public function getAbbr()
    {
        return $this->abbr;
    }

    /**
     * Get the full name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get the type.
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set the abbreviation.
     *
     * @param string $abbr
     */
    public function setAbbr($abbr)
    {
        $this->abbr = $abbr;
}

    /**
     * Set the name.
     *
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Set the type.
     *
     * @param string $type
     *
     * @throws InvalidArgumentException when the wrong type is given
     */
    public function setType($type)
    {
        if (!in_array($type, array(self::TYPE_COMMITTEE, self::TYPE_FRATERNITY))) {
            throw new \InvalidArgumentException("Nonexisting type given.");
        }
        $this->type = $type;
    }

    /**
     * Get the resource ID.
     *
     * @return string
     */
    public function getResourceId()
    {
        return 'organ';
    }
}
