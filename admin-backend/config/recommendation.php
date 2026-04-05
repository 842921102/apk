<?php

return [

    'weather' => [
        'enabled' => env('RECOMMENDATION_WEATHER_ENABLED', true),
        /** wttr.in 城市名，英文便于解析，如 Beijing、Shanghai */
        'city' => env('RECOMMENDATION_WEATHER_CITY', 'Beijing'),
        'timeout' => (int) env('RECOMMENDATION_WEATHER_TIMEOUT', 3),
    ],

];
