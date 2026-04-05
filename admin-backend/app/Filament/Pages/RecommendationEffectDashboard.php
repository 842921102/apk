<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\EffectDestinyStyleTableWidget;
use App\Filament\Widgets\EffectFavoriteTagsTableWidget;
use App\Filament\Widgets\EffectImpressionTagsTableWidget;
use App\Filament\Widgets\EffectOverviewStatsWidget;
use App\Filament\Widgets\EffectRateStatsWidget;
use App\Filament\Widgets\EffectReasonStyleTableWidget;
use App\Filament\Widgets\EffectRejectTagsTableWidget;
use App\Filament\Widgets\EffectTopDishesByViewTableWidget;
use App\Filament\Widgets\EffectTopDishesSkippedTableWidget;
use App\Filament\Widgets\EffectTrendTableWidget;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\Widget;
use Filament\Widgets\WidgetConfiguration;
use UnitEnum;

class RecommendationEffectDashboard extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?string $navigationLabel = '推荐效果看板';

    protected static ?string $title = '推荐效果看板';

    protected static string|UnitEnum|null $navigationGroup = '数据管理';

    protected static ?int $navigationSort = 2;

    protected ?string $subheading = '基于 recommendation_events 埋点 · 与首页「库内统计」口径不同，可对照分析';

    /**
     * @return int | array<string, ?int>
     */
    protected function getEffectGridColumns(): int|array
    {
        return 2;
    }

    /**
     * @return array<class-string<Widget> | WidgetConfiguration>
     */
    protected function getEffectWidgets(): array
    {
        return [
            EffectOverviewStatsWidget::class,
            EffectRateStatsWidget::class,
            EffectTrendTableWidget::class,
            EffectImpressionTagsTableWidget::class,
            EffectFavoriteTagsTableWidget::class,
            EffectRejectTagsTableWidget::class,
            EffectDestinyStyleTableWidget::class,
            EffectReasonStyleTableWidget::class,
            EffectTopDishesByViewTableWidget::class,
            EffectTopDishesSkippedTableWidget::class,
        ];
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make($this->getEffectGridColumns())
                    ->schema(fn (): array => $this->getWidgetsSchemaComponents($this->getEffectWidgets())),
            ]);
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
