<?php

namespace App\Services;

use App\Models\UserProfile;
use Illuminate\Support\Carbon;

/**
 * 用户生日等特殊日（与公历节日分离）。
 */
final class UserSpecialDayService
{
    /**
     * @return array{
     *   is_birthday: bool,
     *   birthday_age: int|null,
     *   user_special_tags: list<string>
     * }
     */
    public function build(Carbon $onDate, ?UserProfile $profile): array
    {
        if (! $profile instanceof UserProfile || ! $profile->birthday instanceof Carbon) {
            return [
                'is_birthday' => false,
                'birthday_age' => null,
                'user_special_tags' => [],
            ];
        }

        $md = $onDate->format('m-d');
        $isBirthday = $profile->birthday->format('m-d') === $md;

        $age = null;
        if ($isBirthday) {
            $age = max(0, (int) $profile->birthday->diffInYears($onDate->copy()->startOfDay()));
        }

        $tags = [];
        if ($isBirthday) {
            $tags[] = '用户生日';
            $tags[] = '值得吃点好的';
            $tags[] = '生日仪式感';
            if ($age !== null && $age > 0) {
                $tags[] = '生日年龄:'.$age;
            }
        }

        return [
            'is_birthday' => $isBirthday,
            'birthday_age' => $age,
            'user_special_tags' => array_values(array_unique($tags)),
        ];
    }
}
