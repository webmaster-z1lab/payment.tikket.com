<?php

namespace Modules\Transaction\Services;

use Carbon\Carbon;

class WorkingDayService
{
    private $fixed_holidays = [
        '01-01',
        '04-21',
        '05-01',
        '09-07',
        '10-12',
        '11-02',
        '11-15',
        '12-25',
    ];

    /**
     * @param  \Carbon\Carbon  $date
     *
     * @return bool
     */
    private function isHoliday(Carbon $date) : bool
    {
        if (in_array($date->format('m-d'), $this->fixed_holidays))
            return TRUE;

        $year = now()->year;

        $easter = Carbon::createMidnightDate($year, 3, 21)->addDays(easter_days($year));

        if ($easter->isSameDay($date))
            return TRUE;

        $carnival = Carbon::createMidnightDate($year, $easter->month, $easter->day)->subDays(47);

        if ($carnival->isSameDay($date))
            return TRUE;

        $corpus_christi = Carbon::createMidnightDate($year, $easter->month, $easter->day)->addDays(60);

        return $corpus_christi->isSameDay($date);
    }

    /**
     * @param  \Carbon\Carbon  $date
     *
     * @param  int  $days
     *
     * @return \Carbon\Carbon
     */
    public function nextWorkingDay(Carbon $date, int $days = 1) : Carbon
    {
        for ($i = 0; $i < $days; $i++) {
            $date->addDay();
            do {
                if ($this->isHoliday($date)) {
                    $date->addDay();
                }
                if ($date->isWeekend()) {
                    $date = $date->next(Carbon::MONDAY);
                }
            } while ($this->isHoliday($date));
        }

        return $date->startOfDay();
    }

    /**
     * @param  \Carbon\Carbon  $date
     * @param  int  $days
     *
     * @return \Carbon\Carbon
     */
    public function previousWorkingDay(Carbon $date, int $days = 3) : Carbon
    {
        for ($i = 0; $i < $days; $i++) {
            $date->subDay();
            do {
                if ($this->isHoliday($date)) {
                    $date->subDay();
                }
                if ($date->isWeekend()) {
                    $date->previous(Carbon::FRIDAY);
                }
            } while ($this->isHoliday($date));
        }

        return $date->endOfDay();
    }

    /**
     * @param  \Carbon\Carbon  $first
     * @param  \Carbon\Carbon  $second
     *
     * @return int
     */
    public function diffInWorkingDays(Carbon $first, Carbon $second) : int
    {
        if ($first->lt($second)) {
            $min = $first;
            $max = $second;
        } else {
            $min = $second;
            $max = $first;
        }

        $days = 0;

        while ($min->isSameDay($max)) {
            $min->addDay();
            do {
                if ($this->isHoliday($min)) {
                    $min->addDay();
                }
                if ($min->isWeekend()) {
                    $min = $min->next(Carbon::MONDAY);
                }
            } while ($this->isHoliday($min));

            $days++;
        }

        return $days;
    }
}
