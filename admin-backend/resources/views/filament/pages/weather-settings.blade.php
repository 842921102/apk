<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section>
            <x-slot name="heading">天气接口配置</x-slot>

            <form wire:submit.prevent="save" class="space-y-6">
                {{ $this->form }}

                <div class="flex flex-wrap gap-3">
                    <x-filament::button type="submit" color="primary">保存配置</x-filament::button>
                    <x-filament::button type="button" wire:click="testAmbient" color="warning">测试默认城市天气</x-filament::button>
                </div>
            </form>
        </x-filament::section>
    </div>
</x-filament-panels::page>

