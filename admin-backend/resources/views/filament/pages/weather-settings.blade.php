<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section>
            <x-slot name="heading">天气接口配置</x-slot>
            <x-slot name="description">用于小程序首页左上角城市和天气信息。开启后，前端会在用户授权定位后按经纬度拉取天气。</x-slot>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">供应商参数</x-slot>
            <x-slot name="description">当前支持和风天气（QWeather）与高德开放平台（Amap）。配置保存于 business_configs（config_key=miniapp_weather）。</x-slot>

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

