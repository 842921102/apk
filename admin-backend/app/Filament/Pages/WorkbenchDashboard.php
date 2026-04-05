<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\RecommendationCountChartWidget;
use App\Filament\Widgets\RecommendationFavoriteRateChartWidget;
use App\Filament\Widgets\RecommendationHealthStatsWidget;
use App\Filament\Widgets\RecommendationOverviewStatsWidget;
use App\Filament\Widgets\RecommendationRerollRateChartWidget;
use App\Filament\Widgets\RecommendationStyleStatsTableWidget;
use App\Filament\Widgets\RecommendationTopDishesTableWidget;
use App\Filament\Widgets\RecommendationTopTagsTableWidget;
use App\Filament\Widgets\RecommendationWorstDishesTableWidget;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\Widget;
use Filament\Widgets\WidgetConfiguration;

class WorkbenchDashboard extends Dashboard
{
    protected static bool $isDiscovered = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHome;

    protected static ?string $navigationLabel = '工作台';

    protected static ?string $title = '推荐系统概览';

    protected static ?int $navigationSort = -3;

    protected ?string $subheading = '今日数据总览 · 最近 7 天趋势（登录用户 · 库内统计）';

    public static function getRoutePath(Panel $panel): string
    {
        return '/';
    }

    /**
     * @return array<class-string<Widget> | WidgetConfiguration>
     */
    public function getWidgets(): array
    {
        return [
            RecommendationOverviewStatsWidget::class,
            RecommendationCountChartWidget::class,
            RecommendationFavoriteRateChartWidget::class,
            RecommendationRerollRateChartWidget::class,
            RecommendationTopDishesTableWidget::class,
            RecommendationTopTagsTableWidget::class,
            RecommendationWorstDishesTableWidget::class,
            RecommendationStyleStatsTableWidget::class,
            RecommendationHealthStatsWidget::class,
        ];
    }

    /**
     * @return array<Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh')
                ->label('刷新数据')
                ->icon(Heroicon::OutlinedArrowPath)
                ->action(fn () => $this->redirect(static::getUrl())),
        ];
    }
}
