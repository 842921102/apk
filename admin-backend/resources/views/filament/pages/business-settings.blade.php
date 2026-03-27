<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section>
            <x-slot name="heading">业务配置中心</x-slot>
            <x-slot name="description">当前已接入腾讯云对象存储 COS，后续可在同页扩展支付、短信等业务配置。</x-slot>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">配置表单</x-slot>
            <x-slot name="description">页面字段已切换为 Filament 原生表单组件，交互和样式与资源页保持一致。</x-slot>

            <form wire:submit.prevent="saveTencentCos" class="space-y-6">
                {{ $this->form }}

                <div class="flex flex-wrap gap-3">
                    <x-filament::button type="submit" color="primary">保存配置</x-filament::button>
                    <x-filament::button type="button" wire:click="testTencentCosConnection" color="warning">测试连接</x-filament::button>
                    <x-filament::button type="button" wire:click="testTencentCosUpload" color="gray">测试上传</x-filament::button>
                </div>
            </form>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">预留扩展分组</x-slot>
            <x-slot name="description">后续可在此继续新增支付配置、短信配置、内容审核等业务配置卡片。</x-slot>
            <div class="flex flex-wrap gap-2 text-xs text-gray-500 dark:text-gray-400">
                <span class="rounded bg-gray-100 px-2 py-1 dark:bg-gray-800">支付配置（预留）</span>
                <span class="rounded bg-gray-100 px-2 py-1 dark:bg-gray-800">短信配置（预留）</span>
                <span class="rounded bg-gray-100 px-2 py-1 dark:bg-gray-800">对象存储扩展（预留）</span>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
