<?php

namespace App\Filament\Pages;

use App\Services\RecommendationConfigService;
use BackedEnum;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use JsonException;
use UnitEnum;

class RecommendationStrategySettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAdjustmentsHorizontal;

    protected static ?string $navigationLabel = '推荐策略配置';

    protected static ?string $title = '推荐策略配置';

    protected static string|UnitEnum|null $navigationGroup = '系统配置';

    protected static ?int $navigationSort = 5;

    protected string $view = 'filament.pages.recommendation-strategy-settings';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public function mount(RecommendationConfigService $config): void
    {
        $this->form->fill([
            'payload' => $this->encodePayload($config),
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function encodePayload(RecommendationConfigService $config): array
    {
        $sections = $config->sectionsForAdminForm();
        $fill = [];
        foreach (array_keys(config('recommendation_strategy_defaults', [])) as $key) {
            $fill[(string) $key] = json_encode(
                $sections[(string) $key] ?? [],
                JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
            );
        }

        return $fill;
    }

    public function form(Schema $schema): Schema
    {
        $defs = [
            'tag_weights' => ['标签权重', '口味/目标/当日状态等匹配加分'],
            'feedback_apply_weights' => ['反馈加减分', '短期反馈在打分中的权重'],
            'recent_similarity' => ['近期相似惩罚', '与最近吃过的菜重复度过高时降分'],
            'favorites_overlap' => ['收藏食材重叠加分', '与收藏偏好食材重叠'],
            'learned_weights' => ['行为学习层', '长期偏好信号的阈值与缩放'],
            'diversity_guard' => ['学习多样性保护', '偏好过强时防止越推越窄'],
            'cooking_scene' => ['做饭频率与场景', '与画像场景、频率匹配'],
            'scoring_core' => ['打分核心', '基础分与排除分（强过滤）'],
            'feature_switches' => ['功能开关', '各子策略启用/关闭（布尔 JSON）'],
            'user_preference_signal_decay' => ['偏好信号衰减', 'user_preference_signals 表衰减与条数上限'],
            'feedback_signal_builder' => ['反馈信号构建', '反馈记录查询窗口与条数'],
            'diversity_control' => ['候选池与多样性截断', '主候选/备选池、菜系上限、相似度阈值等'],
            'reroll_strategy' => ['换一换（reroll）', 'pivot 顺序与各轴文案'],
        ];

        $makeTextarea = function (string $key, string $label, string $desc) {
            return Section::make($label)
                ->schema([
                    Textarea::make('payload.'.$key)
                        ->hiddenLabel()
                        ->rows(14)
                        ->required()
                        ->extraInputAttributes(['class' => 'font-mono text-xs leading-normal']),
                ]);
        };

        return $schema
            ->schema([
                Tabs::make('strategyTabs')
                    ->tabs([
                        Tab::make('tag_feedback')
                            ->label('标签与反馈')
                            ->schema([
                                $makeTextarea('tag_weights', $defs['tag_weights'][0], $defs['tag_weights'][1]),
                                $makeTextarea('feedback_apply_weights', $defs['feedback_apply_weights'][0], $defs['feedback_apply_weights'][1]),
                                $makeTextarea('recent_similarity', $defs['recent_similarity'][0], $defs['recent_similarity'][1]),
                                $makeTextarea('favorites_overlap', $defs['favorites_overlap'][0], $defs['favorites_overlap'][1]),
                            ]),
                        Tab::make('learn_div')
                            ->label('学习与多样性')
                            ->schema([
                                $makeTextarea('learned_weights', $defs['learned_weights'][0], $defs['learned_weights'][1]),
                                $makeTextarea('diversity_guard', $defs['diversity_guard'][0], $defs['diversity_guard'][1]),
                            ]),
                        Tab::make('scene_core')
                            ->label('场景与核心')
                            ->schema([
                                $makeTextarea('cooking_scene', $defs['cooking_scene'][0], $defs['cooking_scene'][1]),
                                $makeTextarea('scoring_core', $defs['scoring_core'][0], $defs['scoring_core'][1]),
                            ]),
                        Tab::make('switches_signals')
                            ->label('开关与信号')
                            ->schema([
                                $makeTextarea('feature_switches', $defs['feature_switches'][0], $defs['feature_switches'][1]),
                                $makeTextarea('user_preference_signal_decay', $defs['user_preference_signal_decay'][0], $defs['user_preference_signal_decay'][1]),
                                $makeTextarea('feedback_signal_builder', $defs['feedback_signal_builder'][0], $defs['feedback_signal_builder'][1]),
                            ]),
                        Tab::make('pools_reroll')
                            ->label('候选池与换一换')
                            ->schema([
                                $makeTextarea('diversity_control', $defs['diversity_control'][0], $defs['diversity_control'][1]),
                                $makeTextarea('reroll_strategy', $defs['reroll_strategy'][0], $defs['reroll_strategy'][1]),
                            ]),
                    ])
                    ->columnSpanFull(),
            ])
            ->statePath('data');
    }

    public function save(RecommendationConfigService $config): void
    {
        $state = $this->form->getState();
        /** @var array<string, mixed> $payload */
        $payload = is_array($state['payload'] ?? null) ? $state['payload'] : [];
        $decoded = [];
        foreach (array_keys(config('recommendation_strategy_defaults', [])) as $key) {
            $k = (string) $key;
            $raw = $payload[$k] ?? null;
            if (! is_string($raw)) {
                Notification::make()
                    ->title('保存失败')
                    ->body('区块 '.$k.' 缺失或格式错误')
                    ->danger()
                    ->send();

                return;
            }
            try {
                $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException $e) {
                Notification::make()
                    ->title('JSON 解析失败：'.$k)
                    ->body($e->getMessage())
                    ->danger()
                    ->send();

                return;
            }
            if (! is_array($data)) {
                Notification::make()
                    ->title('保存失败')
                    ->body('区块 '.$k.' 必须是 JSON 对象')
                    ->danger()
                    ->send();

                return;
            }
            $decoded[$k] = $data;
        }

        $config->saveSections($decoded);

        Notification::make()
            ->title('推荐策略已保存')
            ->body('配置已写入数据库并刷新缓存，即时生效。')
            ->success()
            ->send();
    }
}
