<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section>
            <x-slot name="heading">业务配置中心</x-slot>

            <form wire:submit.prevent="saveTencentCos" class="space-y-6">
                {{ $this->form }}

                <div class="flex flex-wrap gap-3">
                    <x-filament::button type="submit" color="primary">保存配置</x-filament::button>
                    <x-filament::button type="button" wire:click="testTencentCosConnection" color="warning">测试连接</x-filament::button>
                    <x-filament::button type="button" wire:click="testTencentCosUpload" color="gray">测试上传</x-filament::button>
                </div>
            </form>
        </x-filament::section>
    </div>
</x-filament-panels::page>
