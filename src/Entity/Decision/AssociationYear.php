<?php

declare(strict_types=1);

namespace App\Entity\Decision;

use DateInterval;
use DateTime;

use function intval;
use function sprintf;

class AssociationYear
{
    /**
     * A GEWIS association year starts 01-07.
     */
    public const int ASSOCIATION_YEAR_START_MONTH = 7;
    public const int ASSOCIATION_YEAR_START_DAY = 1;

    /** @var int the first calendar year of the association year */
    private int $firstYear;

    /**
     * Declare constructor private to enforce the use of the static methods.
     */
    final private function __construct()
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
        // The association year starts on the first of the month, so the month alone decides which one a date is in.
        if (intval($dateTime->format('n')) < self::ASSOCIATION_YEAR_START_MONTH) {
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
        return sprintf(
            '%4d-%4d',
            $this->firstYear,
            $this->firstYear + 1,
        );
    }

    /**
     * Returns the first day of the association year.
     */
    public function getStartDate(): DateTime
    {
        return new DateTime()->setDate(
            $this->firstYear,
            self::ASSOCIATION_YEAR_START_MONTH,
            self::ASSOCIATION_YEAR_START_DAY,
        )->setTime(
            0,
            0,
        );
    }

    /**
     * Returns the last day of the association year.
     */
    public function getEndDate(): DateTime
    {
        return new DateTime()->setDate(
            $this->firstYear + 1,
            self::ASSOCIATION_YEAR_START_MONTH,
            self::ASSOCIATION_YEAR_START_DAY,
        )->sub(new DateInterval('P1D'))->setTime(
            23,
            59,
            59,
            999999,
        );
    }
}
