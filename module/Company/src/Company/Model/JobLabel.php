<?php

namespace Company\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Job Label model.
 *
 * @ORM\Entity
 */
class JobLabel
{
    /**
     * The label id.
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * The name of the label
     *
     * @ORM\Column(type="string")
     */
    protected $name;

    /**
     * The slug of the label
     *
     * @ORM\Column(type="string")
     */
    protected $slug;

    /**
     * The language of the label
     *
     * @ORM\Column(type="string")
     */
    protected $language;

    /**
     * The label id.
     *
     * @ORM\Column(type="integer")
     */
    protected $languageNeutralId;

    /**
     * The Activities this Category belongs to.
     *
     * @ORM\ManyToMany(targetEntity="Company\Model\Job", mappedBy="labels", cascade={"persist"})
     */
    protected $jobs;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->jobs = new ArrayCollection();
    }

    /**
     * Get's the id
     */
    public function getLanguageNeutralId()
    {
        return $this->languageNeutralId;
    }

    /**
     * Set's the id
     */
    public function setLanguageNeutralId($languageNeutralId)
    {
        $this->languageNeutralId = $languageNeutralId;
    }

    /**
     * Get's the id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set's the id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * Get's the name
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set's the name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Get's the slug
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * Set's the slug
     */
    public function setSlug($slug)
    {
        $this->slug = $slug;
    }

    /**
     * Get's the language
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * Set's the language
     */
    public function setLanguage($language)
    {
        $this->language = $language;
    }

    /**
     * @param Job $job
     */
    public function addJob($job)
    {
        if ($this->jobs->contains($job)) {
            return;
        }

        $this->jobs->add($job);
    }

    /**
     * @param Job $job
     */
    public function removeJob($job)
    {
        if (!$this->jobs->contains($job)) {
            return;
        }

        $this->jobs->removeElement($job);
    }

    /**
     * @return array
     */
    public function getJobs()
    {
        return $this->jobs->toArray();
    }
}
