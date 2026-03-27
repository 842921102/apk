<?php

namespace App\Filament\Pages;

use App\Support\AppRole;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Gate;
use ReflectionClass;
use UnitEnum;

class PermissionSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static ?string $navigationLabel = '权限设置';

    protected static string|UnitEnum|null $navigationGroup = '系统管理';

    protected static ?int $navigationSort = 111;

    protected string $view = 'filament.pages.permission-settings';

    /**
     * @var array<int, array{model: string, policy: string, abilities: array<int, string>}>
     */
    public array $policyRows = [];

    /**
     * @var array<int, array{value: string, label: string}>
     */
    public array $roleRows = [];

    public function mount(): void
    {
        $this->policyRows = $this->buildPolicyRows();
        $this->roleRows = array_map(
            static fn (string $role): array => ['value' => $role, 'label' => AppRole::labelCn($role)],
            AppRole::VALUES,
        );
    }

    /**
     * @return array<int, array{model: string, policy: string, abilities: array<int, string>}>
     */
    private function buildPolicyRows(): array
    {
        /** @var array<class-string, class-string> $policies */
        $policies = Gate::policies();
        ksort($policies);
        $rows = [];

        foreach ($policies as $model => $policy) {
            $rows[] = [
                'model' => $model,
                'policy' => $policy,
                'abilities' => $this->extractAbilities($policy),
            ];
        }

        return $rows;
    }

    /**
     * @return array<int, string>
     */
    private function extractAbilities(string $policyClass): array
    {
        if (! class_exists($policyClass)) {
            return [];
        }

        $ref = new ReflectionClass($policyClass);
        $methods = collect($ref->getMethods())
            ->filter(fn ($m): bool => $m->isPublic() && $m->class === $policyClass)
            ->map(fn ($m): string => $m->name)
            ->reject(fn (string $name): bool => in_array($name, ['__construct', 'before'], true))
            ->values()
            ->all();

        return array_values($methods);
    }
}
