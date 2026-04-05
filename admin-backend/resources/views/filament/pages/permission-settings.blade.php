<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section>
            <x-slot name="heading">权限配置中心</x-slot>
            <x-slot name="description">
                与「支付设置」「业务配置」相同版式：本页为<strong class="font-medium text-gray-950 dark:text-white">只读概览</strong>。
                实际鉴权由 Laravel <code class="rounded bg-gray-100 px-1 py-0.5 text-xs dark:bg-white/10">Gate</code>
                /
                <code class="rounded bg-gray-100 px-1 py-0.5 text-xs dark:bg-white/10">Policy</code>
                完成；用户角色字段为
                <code class="rounded bg-gray-100 px-1 py-0.5 text-xs dark:bg-white/10">users.role</code>，
                具体策略类约束各资源访问。
            </x-slot>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">角色说明（AppRole）</x-slot>
            <x-slot name="description">
                下列为系统内约定的角色枚举值与中文含义，可在「用户管理 → 用户列表」中为用户指定角色。
            </x-slot>

            <div class="overflow-x-auto rounded-lg ring-1 ring-gray-950/5 dark:ring-white/10">
                <table class="w-full table-auto divide-y divide-gray-200 text-start text-sm dark:divide-white/10">
                    <thead class="bg-gray-50 dark:bg-white/5">
                        <tr>
                            <th
                                scope="col"
                                class="px-4 py-3 font-medium text-gray-700 dark:text-gray-200"
                            >
                                角色值
                            </th>
                            <th
                                scope="col"
                                class="px-4 py-3 font-medium text-gray-700 dark:text-gray-200"
                            >
                                中文说明
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-white/10">
                        @foreach ($this->roleRows as $row)
                            <tr class="bg-white dark:bg-gray-900">
                                <td class="px-4 py-3 font-mono text-xs text-gray-950 dark:text-white">
                                    {{ $row['value'] }}
                                </td>
                                <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                                    {{ $row['label'] }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <p class="mt-4 text-xs text-gray-500 dark:text-gray-400">
                常见角色：<span class="font-medium text-gray-700 dark:text-gray-300">user</span> 普通用户、
                <span class="font-medium text-gray-700 dark:text-gray-300">viewer</span> 只读审核、
                <span class="font-medium text-gray-700 dark:text-gray-300">operator</span> 运营、
                <span class="font-medium text-gray-700 dark:text-gray-300">super_admin</span> 超级管理员。
            </p>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">已注册 Policy 列表</x-slot>
            <x-slot name="description">
                来自 <code class="rounded bg-gray-100 px-1 py-0.5 text-xs dark:bg-white/10">Gate::policies()</code>
                的注册映射；「能力方法」为策略类上可对外鉴权的公开方法（不含构造与
                <code class="rounded bg-gray-100 px-1 py-0.5 text-xs dark:bg-white/10">before</code>）。
            </x-slot>

            <div class="overflow-x-auto rounded-lg ring-1 ring-gray-950/5 dark:ring-white/10">
                <table class="w-full table-auto divide-y divide-gray-200 text-start text-sm dark:divide-white/10">
                    <thead class="bg-gray-50 dark:bg-white/5">
                        <tr>
                            <th
                                scope="col"
                                class="px-4 py-3 font-medium text-gray-700 dark:text-gray-200"
                            >
                                模型
                            </th>
                            <th
                                scope="col"
                                class="px-4 py-3 font-medium text-gray-700 dark:text-gray-200"
                            >
                                Policy 类
                            </th>
                            <th
                                scope="col"
                                class="px-4 py-3 font-medium text-gray-700 dark:text-gray-200"
                            >
                                能力方法
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-white/10">
                        @forelse ($this->policyRows as $row)
                            <tr class="bg-white align-top dark:bg-gray-900">
                                <td class="px-4 py-3 font-mono text-xs text-gray-950 dark:text-white">
                                    {{ $row['model'] }}
                                </td>
                                <td class="px-4 py-3 font-mono text-xs text-gray-950 dark:text-white">
                                    {{ $row['policy'] }}
                                </td>
                                <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                                    @if (count($row['abilities']) > 0)
                                        <div class="flex flex-wrap gap-1.5">
                                            @foreach ($row['abilities'] as $ability)
                                                <span
                                                    class="inline-flex items-center rounded-md bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-800 dark:bg-white/10 dark:text-gray-200"
                                                >
                                                    {{ $ability }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="text-gray-400 dark:text-gray-500">无公开能力方法</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td
                                    class="px-4 py-8 text-center text-gray-500 dark:text-gray-400"
                                    colspan="3"
                                >
                                    未读取到已注册 Policy。
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
