<?php

namespace App\Filament\Widgets\Concerns;

use App\Services\RecommendationAnalyticsService;
use Illuminate\Support\Carbon;

trait UsesRecommendationAnalytics
{
    protected function analytics(): RecommendationAnalyticsService
    {
        return app(RecommendationAnalyticsService::class);
    }

    protected function now(): Carbon
    {
        return Carbon::now();
    }

    protected function formatPct(?float $v): string
    {
        if ($v === null) {
            return '—';
        }

        return $v.'%';
    }
}
