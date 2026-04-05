<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section>
            <x-slot name="heading">推荐策略配置</x-slot>
            <x-slot name="description">
                各区块为 JSON 对象，保存后写入 <code class="text-xs">recommendation_configs</code> 并清除缓存，推荐接口下一次请求即使用新参数。
                缺失的键会在保存时与文件默认值合并，避免误删字段。
            </x-slot>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">配置编辑</x-slot>
            <form wire:submit.prevent="save" class="space-y-6">
                {{ $this->form }}

                <div class="flex flex-wrap gap-3">
                    <x-filament::button type="submit" color="primary">保存全部</x-filament::button>
                </div>
            </form>
        </x-filament::section>
    </div>
</x-filament-panels::page>
