<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section>
            <x-slot name="heading">支付配置中心</x-slot>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">微信支付配置</x-slot>

            <form wire:submit.prevent="save" class="space-y-6">
                {{ $this->form }}

                <div class="flex flex-wrap gap-3">
                    <x-filament::button type="submit" color="primary">保存配置</x-filament::button>
                    <x-filament::button type="button" wire:click="validateConfig" color="warning">校验配置完整性</x-filament::button>
                </div>
            </form>
        </x-filament::section>
    </div>
</x-filament-panels::page>
