<?php


namespace Decision\Model;


class AssociationYear
{
    /**
     * A GEWIS association year starts 01-07
     */
    const ASSOCIATION_YEAR_START_MONTH = 7;
    const ASSOCIATION_YEAR_START_DAY = 1;

    /**
     * @var int  the first calendar year of the association year
     */
    protected $firstYear;

    /**
     * Declare constructor private to enforce the use of the static methods.
     *
     * AssociationYear constructor.
     */
    private function __construct()
    {
        // never used
    }

    /**
     * Returns an instance of AssociationYear.
     *
     * @param $year int first calendar year of the association year
     * @return static
     */
    public static function fromYear($year)
    {
        $inst = new static();
        $inst->firstYear = $year;
        return $inst;
    }

    /**
     * Returns an instance of AssociationYear.
     *
     * @param \DateTime $dateTime date to find the AssociationYear for
     * @return static
     */
    public static function fromDate(\DateTime $dateTime)
    {
        $inst = new static();
        if ($dateTime->format('n') < self::ASSOCIATION_YEAR_START_MONTH
            || ($dateTime->format('n') == self::ASSOCIATION_YEAR_START_MONTH
                && $dateTime->format('j') < self::ASSOCIATION_YEAR_START_DAY)) {
            $inst->firstYear = $dateTime->format('Y') - 1;
        } else {
            $inst->firstYear = $dateTime->format('Y');
        }
        return $inst;
    }

    /**
     * @return int the first calendar year of the association year
     */
    public function getYear()
    {
        return $this->firstYear;
    }

    /**
     * Returns the Association year as a string
     *
     * @return string the association year
     */
    public function getYearString()
    {
        return sprintf('%4d-%4d', $this->firstYear, $this->firstYear + 1);
    }

    /**
     * Returns the first day of the association year.
     *
     * @return \DateTime
     */
    public function getStartDate()
    {
        return \DateTime::createFromFormat(
            'j-m-Y',
            sprintf('%d-%d-%d', self::ASSOCIATION_YEAR_START_DAY, self::ASSOCIATION_YEAR_START_MONTH, $this->firstYear)
        );
    }

    /**
     * Returns the last day of the association year.
     *
     * @return \DateTime
     */
    public function getEndDate()
    {
        return \DateTime::createFromFormat(
            'j-m-Y',
            sprintf('%d-%d-%d', self::ASSOCIATION_YEAR_START_DAY, self::ASSOCIATION_YEAR_START_MONTH, $this->firstYear + 1)
        )->sub(new \DateInterval('P1D'));
    }
}
