<x-filament-panels::page>
    <div class="space-y-6">
        <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
            <h2 class="text-base font-semibold text-gray-900 dark:text-white">Laravel 原生权限体系</h2>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                当前项目使用 Laravel Gate / Policy 做权限控制；角色字段使用 <code>users.role</code>，
                并由策略类约束资源访问。
            </p>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">角色枚举（AppRole）</h3>
            <div class="mt-3 overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-800">
                    <thead>
                        <tr class="text-left text-gray-600 dark:text-gray-300">
                            <th class="px-3 py-2">角色值</th>
                            <th class="px-3 py-2">中文说明</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach ($this->roleRows as $row)
                            <tr>
                                <td class="px-3 py-2 font-mono text-xs text-gray-800 dark:text-gray-200">{{ $row['value'] }}</td>
                                <td class="px-3 py-2 text-gray-700 dark:text-gray-300">{{ $row['label'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                角色可在“用户管理 &gt; 用户管理”中维护。
            </p>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">已注册 Policy 列表</h3>
            <div class="mt-3 overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-800">
                    <thead>
                        <tr class="text-left text-gray-600 dark:text-gray-300">
                            <th class="px-3 py-2">模型</th>
                            <th class="px-3 py-2">Policy</th>
                            <th class="px-3 py-2">能力方法</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse ($this->policyRows as $row)
                            <tr>
                                <td class="px-3 py-2 font-mono text-xs text-gray-800 dark:text-gray-200">{{ $row['model'] }}</td>
                                <td class="px-3 py-2 font-mono text-xs text-gray-800 dark:text-gray-200">{{ $row['policy'] }}</td>
                                <td class="px-3 py-2 text-gray-700 dark:text-gray-300">
                                    @if (count($row['abilities']) > 0)
                                        {{ implode(', ', $row['abilities']) }}
                                    @else
                                        <span class="text-gray-400 dark:text-gray-500">无公开能力方法</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-3 py-6 text-center text-gray-500 dark:text-gray-400">
                                    未读取到已注册 Policy。
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-filament-panels::page>
