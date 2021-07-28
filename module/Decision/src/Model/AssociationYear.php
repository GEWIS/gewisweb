<?php

namespace Decision\Model;

use DateInterval;
use DateTime;

class AssociationYear
{
    /**
     * A GEWIS association year starts 01-07.
     */
    public const ASSOCIATION_YEAR_START_MONTH = 7;
    public const ASSOCIATION_YEAR_START_DAY = 1;

    /**
     * @var int the first calendar year of the association year
     */
    protected int $firstYear;

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
     * @param int $year first calendar year of the association year
     *
     * @return static
     */
    public static function fromYear(int $year): static
    {
        $inst = new static();
        $inst->firstYear = $year;

        return $inst;
    }

    /**
     * Returns an instance of AssociationYear.
     *
     * @param DateTime $dateTime date to find the AssociationYear for
     *
     * @return static
     */
    public static function fromDate(DateTime $dateTime): static
    {
        $inst = new static();
        if (
            $dateTime->format('n') < self::ASSOCIATION_YEAR_START_MONTH
            || (self::ASSOCIATION_YEAR_START_MONTH == $dateTime->format('n')
                && $dateTime->format('j') < self::ASSOCIATION_YEAR_START_DAY)
        ) {
            $inst->firstYear = (int) $dateTime->format('Y') - 1;
        } else {
            $inst->firstYear = (int) $dateTime->format('Y');
        }

        return $inst;
    }

    /**
     * @return int the first calendar year of the association year
     */
    public function getYear(): int
    {
        return $this->firstYear;
    }

    /**
     * Returns the Association year as a string.
     *
     * @return string the association year
     */
    public function getYearString(): string
    {
        return sprintf('%4d-%4d', $this->firstYear, $this->firstYear + 1);
    }

    /**
     * Returns the first day of the association year.
     *
     * @return DateTime
     */
    public function getStartDate(): DateTime
    {
        return DateTime::createFromFormat(
            'j-m-Y',
            sprintf('%d-%d-%d', self::ASSOCIATION_YEAR_START_DAY, self::ASSOCIATION_YEAR_START_MONTH, $this->firstYear)
        );
    }

    /**
     * Returns the last day of the association year.
     *
     * @return DateTime
     */
    public function getEndDate(): DateTime
    {
        return DateTime::createFromFormat(
            'j-m-Y',
            sprintf(
                '%d-%d-%d',
                self::ASSOCIATION_YEAR_START_DAY,
                self::ASSOCIATION_YEAR_START_MONTH,
                $this->firstYear + 1
            )
        )->sub(new DateInterval('P1D'));
    }
}
