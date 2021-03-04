<?php

namespace Decision\Model\SubDecision\Board;

use Doctrine\ORM\Mapping as ORM;

use Decision\Model\SubDecision;

/**
 * Release from board duties.
 *
 * This decision references to an installation. The duties of this installation
 * are released by this release.
 *
 * @ORM\Entity
 */
class Release extends SubDecision
{
    /**
     * Reference to the installation of a member.
     *
     * @ORM\OneToOne(targetEntity="Installation",inversedBy="release")
     * @ORM\JoinColumns({
     *  @ORM\JoinColumn(name="r_meeting_type", referencedColumnName="meeting_type"),
     *  @ORM\JoinColumn(name="r_meeting_number", referencedColumnName="meeting_number"),
     *  @ORM\JoinColumn(name="r_decision_point", referencedColumnName="decision_point"),
     *  @ORM\JoinColumn(name="r_decision_number", referencedColumnName="decision_number"),
     *  @ORM\JoinColumn(name="r_number", referencedColumnName="number")
     * })
     */
    protected $installation;

    /**
     * Date of the discharge.
     *
     * @ORM\Column(type="date")
     */
    protected $date;

    /**
     * Get installation.
     *
     * @return Installation
     */
    public function getInstallation()
    {
        return $this->installation;
    }

    /**
     * Set the installation.
     *
     * @param Installation $installation
     */
    public function setInstallation(Installation $installation)
    {
        $this->installation = $installation;
    }

    /**
     * Get the date.
     *
     * @return \DateTime
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Set the date.
     *
     * @param \DateTime $date
     */
    public function setDate($date)
    {
        $this->date = $date;
    }

    /**
     * Get the content.
     *
     * @return string
     */
    public function getContent()
    {
        $member = $this->getInstallation()->getMember()->getFullName();
        $function = $this->getInstallation()->getFunction();

        $zh = $this->getInstallation()->getMember()->getGender() == 'm' ? 'zijn' : 'haar';

        return $member . ' wordt per ' . $this->formatDate($this->getDate())
            . ' ontheven uit ' . $zh . ' functie als ' . $function
            . ' der s.v. GEWIS.';
    }

    /**
     * Format the date.
     *
     * returns the localized version of $date->format('d F Y')
     *
     * @param DateTime $date
     *
     * @return string Formatted date
     */
    protected function formatDate(\DateTime $date)
    {
        $formatter = new \IntlDateFormatter(
            'nl_NL', // yes, hardcoded :D
            \IntlDateFormatter::NONE,
            \IntlDateFormatter::NONE,
            \date_default_timezone_get(),
            null,
            'd MMMM Y'
        );
        return $formatter->format($date);
    }
}
