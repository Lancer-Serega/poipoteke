<?php

namespace App\Services;

use Silex\Application;
use App\Repositories;

class RequestDateCalculator
{
    const DAY_LENGTH = 86400;
    const HOUR_LENGTH = 3600;
    const MINUTE_LENGTH = 60;
    const WEEK_DAY_IDX_FIX = 1;

    private $app;

    public function __construct(Application $app) {
        $this->app = $app;
    }

    /**
     * @param int $created Дата создания заявки в unix timestamp
     * @param int $preparationTime prices_report.time
     * @param array $workWeek
     *
     * @return int
     */
    public function calculateReadyTimestamp($created, $preparationTime, $workWeek) {
        $workWeek = $this->extendWorkWeek($workWeek);
        $workWeek = $this->sortDays($workWeek);

        $dayStartIdx = (int)date('N', $created) - self::WEEK_DAY_IDX_FIX;
        $dayStartTime = mktime(0, 0, 0, date('n', $created), date('j', $created), date('Y', $created));

        $readyDate = $created;
        $remainingWorkTime = $preparationTime;
        $firstDay = true;

        while ($remainingWorkTime > 0) {
            $workWeekRearranged = $this->rearrangeWorkWeek($dayStartIdx, $workWeek);
            $workDayIdx = $this->getNextWorkDayIdx($dayStartTime, $workWeekRearranged);

            $workDayStartOffset = $this->calculateWorkDayStartOffset($workDayIdx, $workWeekRearranged);
            $workDayEndOffset = $this->calculateWorkDayEndOffset($workDayIdx, $workWeekRearranged);
            $workDayStartPlan = $dayStartTime + $workDayStartOffset;
            $workDayStartReal = $workDayStartPlan > $created ? $workDayStartPlan : $created;

            $workDayStart =  mktime(0, 0, 0, date('n', $workDayStartPlan), date('j', $workDayStartPlan),
                date('Y', $workDayStartPlan));

            if (!$firstDay && $remainingWorkTime > self::DAY_LENGTH) {
                $workDayStartReal = $workDayStart;
            }

            $workDayEndPlan = $dayStartTime + $workDayEndOffset;
            $workDayEndReal = $workDayStart + self::DAY_LENGTH;

            $workTimeRealPlan = $workDayEndPlan - $workDayStartReal;
            $workTimeRealFull = $workDayEndReal - $workDayStartReal;

            $remainingWorkTimePlan = $remainingWorkTime - $workTimeRealPlan;
            $remainingWorkTimeFull = $remainingWorkTime - $workTimeRealFull;

            if ($remainingWorkTimeFull > 0) {
                $remainingWorkTime = $remainingWorkTimeFull;
            } else if ($remainingWorkTimePlan > 0) {
                /**
                 * >= 0 or >= self::HOUR_LENGTH
                 * last hours
                 * $remainingWorkTime = $remainingWorkTimePlan;
                 */
                $remainingWorkTime = $remainingWorkTimePlan;
            } else {
                $readyDate = $workDayStartReal + $remainingWorkTime > $dayStartTime + $workDayStartOffset
                    ? $workDayStartReal + $remainingWorkTime
                    : $dayStartTime + $workDayStartOffset;
                $remainingWorkTime = $remainingWorkTimePlan;
            }

            $dayStartTime = $workDayStart + self::DAY_LENGTH;
            $dayStartIdx = (int)date('N', $dayStartTime) - self::WEEK_DAY_IDX_FIX;
            $firstDay = false;
        }

        return $readyDate;
    }

    private function extendWorkWeek($workWeek) {
        /** @var $workTimeRepository Repositories\WorkTimeRepository */
        $workTimeRepository = $this->app['work_time.repository'];

        $extraWorkDays = [];
        $weekDaysNames = array_values($workTimeRepository->getWeekDaysNames());

        foreach ($weekDaysNames as $dayName => $dayValue) {
            $dayExists = false;

            foreach ($workWeek as $wtItemKey => $wtItemValue) {
                if ($wtItemValue['dayOfWeek'] === $dayValue) {
                    $dayExists = true;

                    break;
                }
            }

            if (!$dayExists) {
                $extraWorkDays[] = ['dayOfWeek' => $dayValue, 'start' => 0, 'end' => 0];
            }
        }

        $workWeek = array_merge($workWeek, $extraWorkDays);

        return $workWeek;
    }

    private function sortDays($workWeek) {
        /** @var $workTimeRepository Repositories\WorkTimeRepository */
        $workTimeRepository = $this->app['work_time.repository'];

        $weekDaysNamesPlain = array_values($workTimeRepository->getWeekDaysNames());
        $workWeekSorted = array_fill(0,7,[]);

        foreach ($workWeek as $key => $value) {
            $dayIdx = (int)array_search($value['dayOfWeek'], $weekDaysNamesPlain);
            $workWeekSorted[$dayIdx] = $value;
        }

        return $workWeekSorted;
    }

    private function rearrangeWorkWeek($startDayIdx, $workWeek) {
        $weekStart = array_slice($workWeek, $startDayIdx);
        $weekEnd = array_slice($workWeek, 0, $startDayIdx);
        $workWeekRearranged = array_merge($weekStart, $weekEnd);

        return $workWeekRearranged;
    }

    private function getNextWorkDayIdx($startsFrom, $workWeek) {
        $workDayIdx = -1;
        $dayStartTimestamp = mktime(0, 0, 0, date('n', $startsFrom), date('j', $startsFrom), date('Y', $startsFrom));

        foreach ($workWeek as $workItemKey => $workItem) {
            $workDayEndTimestamp = $dayStartTimestamp + $workItem['end'];

            if ($workItem['end'] && $startsFrom <= $workDayEndTimestamp) {
                $workDayIdx = $workItemKey;

                break;
            } else {
                $dayStartTimestamp += self::DAY_LENGTH;
            }
        }

        return $workDayIdx;
    }

    public function calculateWorkDayStartOffset($dayIdx, $workWeek) {
        return self::DAY_LENGTH * $dayIdx + $workWeek[$dayIdx]['start'];
    }

    public function calculateWorkDayEndOffset($dayIdx, $workWeek) {
        return self::DAY_LENGTH * $dayIdx + $workWeek[$dayIdx]['end'];
    }
}
