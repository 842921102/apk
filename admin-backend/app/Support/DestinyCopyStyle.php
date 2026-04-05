<?php

namespace App\Support;

/**
 * 食命文案产品线风格（由系统选定，模型在约束内生成）。
 */
enum DestinyCopyStyle: string
{
    case Healing = 'healing';
    case Daily = 'daily';
    case Ritual = 'ritual';
    case DestinyLight = 'destiny_light';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
