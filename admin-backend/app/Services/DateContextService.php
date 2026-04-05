<?php

namespace App\Services;

use Illuminate\Support\Carbon;

/**
 * 日历与用餐时段（系统标准化，禁止由模型臆造）。
 */
final class DateContextService
{
    /**
     * @return array{
     *   current_date: string,
     *   weekday: int,
     *   weekday_cn: string,
     *   month: int,
     *   season: string,
     *   meal_period: string,
     *   is_weekend: bool,
     *   iso_weekday: int
     * }
     */
    public function build(Carbon $calendarDate, ?Carbon $atInstant = null): array
    {
        $atInstant ??= Carbon::now(config('app.timezone'));

        $d = $calendarDate->copy()->startOfDay();
        $month = (int) $d->format('n');
        $weekday = (int) $d->format('N');

        return [
            'current_date' => $d->toDateString(),
            'weekday' => $weekday,
            'weekday_cn' => $this->weekdayCn($weekday),
            'month' => $month,
            'season' => $this->seasonForMonth($month),
            'meal_period' => $this->mealPeriod($atInstant),
            'is_weekend' => $weekday >= 6,
            'iso_weekday' => $weekday,
            /** @deprecated 兼容旧上下文 */
            'date' => $d->toDateString(),
        ];
    }

    private function weekdayCn(int $n): string
    {
        return ['一', '二', '三', '四', '五', '六', '日'][$n - 1] ?? '未知';
    }

    private function seasonForMonth(int $month): string
    {
        return match (true) {
            in_array($month, [3, 4, 5], true) => 'spring',
            in_array($month, [6, 7, 8], true) => 'summer',
            in_array($month, [9, 10, 11], true) => 'autumn',
            default => 'winter',
        };
    }

    private function mealPeriod(Carbon $at): string
    {
        $h = (int) $at->format('G');
        $m = (int) $at->format('i');
        $minutes = $h * 60 + $m;

        if ($minutes >= 5 * 60 + 30 && $minutes < 10 * 60 + 30) {
            return 'breakfast';
        }
        if ($minutes >= 10 * 60 + 30 && $minutes < 14 * 60) {
            return 'lunch';
        }
        if ($minutes >= 14 * 60 && $minutes < 17 * 60) {
            return 'afternoon_tea';
        }
        if ($minutes >= 17 * 60 && $minutes < 21 * 60 + 30) {
            return 'dinner';
        }
        if ($minutes >= 21 * 60 + 30 || $minutes < 1 * 60) {
            return 'late_night';
        }

        return 'snack';
    }
}
