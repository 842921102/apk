<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section>
            <x-slot name="heading">支付配置中心</x-slot>
            <x-slot name="description">用于维护微信小程序支付（JSAPI）的一期最小闭环参数。</x-slot>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">微信支付配置</x-slot>
            <x-slot name="description">配置将保存在 business_configs（config_key=wechat_pay），不会下发到前端。</x-slot>

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
