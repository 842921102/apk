<?php

namespace App\Services;

use Illuminate\Support\Carbon;

/**
 * 公历节日、节气与 special_day_tags（不含用户生日，见 UserSpecialDayService）。
 */
final class FestivalContextService
{
    /**
     * @return array<string, mixed>
     */
    public function build(Carbon $date): array
    {
        $md = $date->format('m-d');
        $map = [
            '01-01' => ['key' => 'new_year', 'label' => '元旦'],
            '02-14' => ['key' => 'valentine', 'label' => '情人节'],
            '05-01' => ['key' => 'labour', 'label' => '劳动节'],
            '06-01' => ['key' => 'children', 'label' => '儿童节'],
            '10-01' => ['key' => 'national', 'label' => '国庆节'],
            '12-25' => ['key' => 'christmas', 'label' => '圣诞节'],
        ];

        $events = isset($map[$md]) ? [$map[$md]] : [];
        $primary = $events[0] ?? null;
        $isFestival = $primary !== null;
        $festivalName = $isFestival ? (string) $primary['label'] : null;

        $solarTerm = $this->resolveSolarTerm($date);
        $specialTags = [];

        if ($isFestival) {
            $specialTags[] = '公历节日';
            $specialTags[] = '仪式感';
        }
        if ($solarTerm !== null) {
            $specialTags[] = '节气：'.$solarTerm['name'];
            if ($solarTerm['key'] === 'dongzhi') {
                $specialTags[] = '冬至温补';
            }
        }

        return [
            'is_festival' => $isFestival,
            'festival_name' => $festivalName,
            'festival_key' => $primary['key'] ?? null,
            'solar_term' => $solarTerm,
            'special_day_tags' => array_values(array_unique($specialTags)),
            /** 兼容旧结构 */
            'solar_calendar_events' => $events,
            'note' => '公历节日为简化清单；节气见 config/recommendation_solar_terms.php，按年维护。',
        ];
    }

    /**
     * @return array{key: string, name: string}|null
     */
    private function resolveSolarTerm(Carbon $date): ?array
    {
        $y = (int) $date->format('Y');
        $candidates = [];

        foreach ([$y - 1, $y] as $yy) {
            $rows = config('recommendation_solar_terms.'.(string) $yy);
            if (! is_array($rows)) {
                continue;
            }
            foreach ($rows as $row) {
                if (! is_array($row) || ! isset($row['month'], $row['day'], $row['key'], $row['name'])) {
                    continue;
                }
                try {
                    $candidates[] = [
                        'at' => Carbon::create($yy, (int) $row['month'], (int) $row['day'])->startOfDay(),
                        'key' => (string) $row['key'],
                        'name' => (string) $row['name'],
                    ];
                } catch (\Throwable) {
                    continue;
                }
            }
        }

        if ($candidates === []) {
            return null;
        }

        usort($candidates, fn ($a, $b) => $a['at']->timestamp <=> $b['at']->timestamp);

        $day = $date->copy()->startOfDay();
        $picked = null;
        foreach ($candidates as $c) {
            if ($c['at']->lte($day)) {
                $picked = $c;
            }
        }

        if ($picked === null) {
            return null;
        }

        return [
            'key' => $picked['key'],
            'name' => $picked['name'],
        ];
    }
}
