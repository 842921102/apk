<template>
  <div class="min-h-screen bg-yellow-400 px-2 md:px-4 py-6">
    <GlobalNavigation />

    <div class="max-w-7xl mx-auto">
      <div class="bg-white border-2 border-[#0A0910] rounded-xl p-4 md:p-6 mb-6 shadow-lg">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
          <div>
            <h1 class="flex items-center gap-3 text-2xl font-bold text-gray-800 md:text-3xl">
              <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-yellow-100 text-purple-700">
                <AppStrokeIcon name="wrench" :size="24" />
              </span>
              <span>平台管理后台</span>
            </h1>
            <p class="text-gray-500 text-sm mt-2">当前为云端数据模式，数据来源于 Supabase 数据库</p>
            <p class="mt-3 flex flex-wrap items-center gap-2">
              <span
                class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm font-bold border-2 border-[#0A0910] bg-yellow-100 text-gray-800"
              >
                当前管理员角色：{{ roleLabelCN(myRole) }}
              </span>
            </p>
          </div>

          <div class="flex flex-wrap gap-2">
            <button type="button" class="app-btn app-btn--primary app-btn--sm" @click="refreshData">
              刷新数据
            </button>
            <button type="button" class="app-btn app-btn--secondary app-btn--sm" @click="exportData">
              导出数据
            </button>
          </div>
        </div>
      </div>

      <div v-if="loading" class="text-center py-8 text-gray-600 font-medium">
        正在加载数据...
      </div>

      <div v-else>
        <div class="flex flex-col lg:flex-row gap-4">
          <aside class="lg:w-64 lg:sticky lg:top-4 h-fit">
            <div class="bg-white border-2 border-[#0A0910] rounded-xl p-3 shadow-lg">
              <div class="flex flex-col gap-2">
                <button
                  type="button"
                  class="flex w-full items-center gap-2 rounded-lg border-2 border-[#0A0910] px-4 py-2 text-left font-medium transition-all"
                  :class="[
                    activeTab === 'dashboard' ? 'bg-yellow-400 text-gray-800' : 'bg-white text-gray-700 hover:bg-gray-100'
                  ]"
                  @click="activeTab = 'dashboard'"
                >
                  <AppStrokeIcon name="chartBar" :size="18" class="shrink-0 opacity-90" />
                  仪表盘
                </button>
                <button
                  type="button"
                  class="flex w-full items-center gap-2 rounded-lg border-2 border-[#0A0910] px-4 py-2 text-left font-medium transition-all"
                  :class="[
                    activeTab === 'history' ? 'bg-blue-500 text-white' : 'bg-white text-gray-700 hover:bg-blue-50'
                  ]"
                  @click="activeTab = 'history'"
                >
                  <AppStrokeIcon name="scrollText" :size="18" class="shrink-0 opacity-90" />
                  历史记录管理
                </button>
                <button
                  type="button"
                  class="flex w-full items-center gap-2 rounded-lg border-2 border-[#0A0910] px-4 py-2 text-left font-medium transition-all"
                  :class="[
                    activeTab === 'favorite' ? 'bg-red-500 text-white' : 'bg-white text-gray-700 hover:bg-red-50'
                  ]"
                  @click="activeTab = 'favorite'"
                >
                  <AppStrokeIcon name="heart" :size="18" class="shrink-0 opacity-90" />
                  收藏管理
                </button>
                <button
                  type="button"
                  class="flex w-full items-center gap-2 rounded-lg border-2 border-[#0A0910] px-4 py-2 text-left font-medium transition-all"
                  :class="[
                    activeTab === 'user' ? 'bg-purple-500 text-white' : 'bg-white text-gray-700 hover:bg-purple-50'
                  ]"
                  @click="activeTab = 'user'"
                >
                  <AppStrokeIcon name="users" :size="18" class="shrink-0 opacity-90" />
                  用户管理
                </button>
                <button
                  type="button"
                  class="flex w-full items-center gap-2 rounded-lg border-2 border-[#0A0910] px-4 py-2 text-left font-medium transition-all"
                  :class="[
                    activeTab === 'logs' ? 'bg-amber-600 text-white' : 'bg-white text-gray-700 hover:bg-amber-50'
                  ]"
                  @click="activeTab = 'logs'"
                >
                  <AppStrokeIcon name="clipboardList" :size="18" class="shrink-0 opacity-90" />
                  操作日志
                </button>
                <button
                  type="button"
                  class="flex w-full items-center gap-2 rounded-lg border-2 border-[#0A0910] px-4 py-2 text-left font-medium transition-all"
                  :class="[
                    activeTab === 'settings' ? 'bg-teal-600 text-white' : 'bg-white text-gray-700 hover:bg-teal-50'
                  ]"
                  @click="activeTab = 'settings'"
                >
                  <AppStrokeIcon name="sliders" :size="18" class="shrink-0 opacity-90" />
                  系统设置
                </button>
                <button
                  type="button"
                  class="flex w-full items-center gap-2 rounded-lg border-2 border-[#0A0910] px-4 py-2 text-left font-medium transition-all"
                  :class="[
                    activeTab === 'modelConfig' ? 'bg-indigo-600 text-white' : 'bg-white text-gray-700 hover:bg-indigo-50'
                  ]"
                  @click="activeTab = 'modelConfig'"
                >
                  <AppStrokeIcon name="cpu" :size="18" class="shrink-0 opacity-90" />
                  模型配置
                </button>
                <router-link
                  to="/"
                  class="flex w-full items-center gap-2 rounded-lg border-2 border-[#0A0910] bg-white px-4 py-2 text-left font-medium text-gray-700 transition-all hover:bg-gray-100"
                >
                  <AppStrokeIcon name="arrowLeft" :size="18" class="shrink-0 opacity-80" />
                  返回前台
                </router-link>
              </div>
            </div>
          </aside>

          <section class="flex-1 min-w-0">
            <div v-if="activeTab === 'dashboard'" class="app-enter-up app-stagger-grid grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
              <div class="bg-white border-2 border-[#0A0910] rounded-xl p-4 shadow-lg">
                <div class="flex items-center justify-between">
                  <div>
                    <p class="text-sm text-gray-500">用户总数</p>
                    <p class="text-2xl font-bold text-gray-800 mt-1">{{ totalUserCount }}</p>
                  </div>
                  <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-purple-100 text-purple-700">
                    <AppStrokeIcon name="users" :size="24" />
                  </div>
                </div>
              </div>

              <div class="bg-white border-2 border-[#0A0910] rounded-xl p-4 shadow-lg">
                <div class="flex items-center justify-between">
                  <div>
                    <p class="text-sm text-gray-500">历史记录总数</p>
                    <p class="text-2xl font-bold text-gray-800 mt-1">{{ historyList.length }}</p>
                  </div>
                  <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-blue-100 text-blue-700">
                    <AppStrokeIcon name="scrollText" :size="24" />
                  </div>
                </div>
              </div>

              <div class="bg-white border-2 border-[#0A0910] rounded-xl p-4 shadow-lg">
                <div class="flex items-center justify-between">
                  <div>
                    <p class="text-sm text-gray-500">收藏总数</p>
                    <p class="text-2xl font-bold text-gray-800 mt-1">{{ favoriteList.length }}</p>
                  </div>
                  <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-red-100 text-red-600">
                    <AppStrokeIcon name="heart" :size="24" />
                  </div>
                </div>
              </div>

              <div class="bg-white border-2 border-[#0A0910] rounded-xl p-4 shadow-lg">
                <div class="flex items-center justify-between">
                  <div>
                    <p class="text-sm text-gray-500">今日新增记录数</p>
                    <p class="text-2xl font-bold text-gray-800 mt-1">{{ todayNewRecordCount }}</p>
                  </div>
                  <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-green-100 text-green-700">
                    <AppStrokeIcon name="calendar" :size="24" />
                  </div>
                </div>
              </div>
            </div>

            <div v-if="activeTab === 'history'" class="app-enter-up bg-white border-2 border-[#0A0910] rounded-xl shadow-lg p-4 md:p-6">
            <div class="flex flex-col gap-3 mb-4">
              <div class="flex flex-col xl:flex-row gap-3 xl:items-end xl:justify-between">
                <div class="flex flex-col sm:flex-row flex-wrap gap-3 flex-1 min-w-0">
                  <div class="flex-1 min-w-[200px]">
                    <label class="app-label">搜索</label>
                    <AppSearch
                      v-model="historyKeyword"
                      placeholder="标题 / 菜系 / 用户ID"
                      input-class="text-sm"
                    />
                  </div>
                  <div class="w-full sm:w-40">
                    <label class="app-label">菜系</label>
                    <select v-model="historyCuisineFilter" class="app-select text-sm">
                      <option value="">全部菜系</option>
                      <option v-for="c in historyCuisineOptions" :key="c" :value="c">{{ c }}</option>
                    </select>
                  </div>
                  <div class="w-full sm:w-40">
                    <label class="app-label">时间</label>
                    <select v-model="historyTimeFilter" class="app-select text-sm">
                      <option value="all">全部</option>
                      <option value="today">今日</option>
                      <option value="week">最近7天</option>
                    </select>
                  </div>
                </div>
                <div class="flex flex-wrap gap-2 shrink-0">
                  <button type="button" class="app-btn app-btn--secondary app-btn--sm" @click="refreshData">
                    刷新
                  </button>
                  <button type="button" class="app-btn app-btn--danger app-btn--sm" @click="clearHistory">
                    清空历史
                  </button>
                </div>
              </div>
            </div>

            <div v-if="historyList.length === 0" class="text-center py-12 text-gray-500">
              暂无历史记录
            </div>
            <div v-else-if="filteredHistory.length === 0" class="text-center py-12 text-gray-500">
              没有符合筛选条件的历史记录
            </div>

            <template v-else>
            <div class="overflow-x-auto rounded-lg border-2 border-[#0A0910] bg-white">
              <table class="admin-table w-full min-w-[960px] text-sm text-left">
                <thead>
                  <tr class="bg-gray-50 border-b-2 border-[#0A0910]">
                    <th class="font-bold px-3 py-3 border-r border-[#0A0910] text-gray-800">标题</th>
                    <th class="font-bold px-3 py-3 border-r border-[#0A0910] text-gray-800 w-28">菜系</th>
                    <th class="font-bold px-3 py-3 border-r border-[#0A0910] text-gray-800 min-w-[140px]">食材</th>
                    <th class="font-bold px-3 py-3 border-r border-[#0A0910] text-gray-800 min-w-[120px]">用户ID</th>
                    <th class="font-bold px-3 py-3 border-r border-[#0A0910] text-gray-800 whitespace-nowrap">创建时间</th>
                    <th class="font-bold px-3 py-3 text-gray-800 whitespace-nowrap w-40">操作</th>
                  </tr>
                </thead>
                <tbody class="app-tbody-enter">
                  <tr
                    v-for="item in pagedHistory"
                    :key="item.id"
                    class="border-b border-[#0A0910] bg-blue-50/40 hover:bg-blue-50"
                  >
                    <td class="px-3 py-2 border-r border-[#0A0910] align-top">
                      <span class="table-cell-ellipsis font-medium text-gray-800" :title="item.title || '未命名记录'">
                        {{ item.title || '未命名记录' }}
                      </span>
                    </td>
                    <td class="px-3 py-2 border-r border-[#0A0910] align-top text-gray-700">
                      {{ item.cuisine || '未知菜系' }}
                    </td>
                    <td class="px-3 py-2 border-r border-[#0A0910] align-top">
                      <span class="table-cell-ellipsis text-gray-700" :title="formatIngredients(item.ingredients)">
                        {{ formatIngredients(item.ingredients) }}
                      </span>
                    </td>
                    <td class="px-3 py-2 border-r border-[#0A0910] align-top">
                      <span class="table-cell-ellipsis font-mono text-xs text-gray-700" :title="String(item.user_id || '')">
                        {{ item.user_id || '未知用户' }}
                      </span>
                    </td>
                    <td class="px-3 py-2 border-r border-[#0A0910] align-top text-gray-600 whitespace-nowrap">
                      {{ formatTime(item.created_at) }}
                    </td>
                    <td class="px-3 py-2 align-top">
                      <div class="flex flex-wrap gap-2">
                        <button
                          @click="viewDetail(item)"
                          class="app-btn app-btn--secondary app-btn--xs"
                        >
                          查看
                        </button>
                        <button
                          @click="deleteHistoryItem(item)"
                          class="app-btn app-btn--danger app-btn--xs"
                        >
                          删除
                        </button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mt-4 pt-3 border-t-2 border-gray-200 text-sm text-gray-700">
              <span>共 {{ filteredHistory.length }} 条，每页 {{ PAGE_SIZE }} 条</span>
              <div class="flex flex-wrap items-center gap-2">
                <button
                  type="button"
                  :disabled="historyPage <= 1"
                  @click="historyPage--"
                  class="app-btn app-btn--secondary app-btn--xs disabled:opacity-40"
                >
                  上一页
                </button>
                <span class="font-medium px-1">第 {{ historyPage }} / {{ historyTotalPages }} 页</span>
                <button
                  type="button"
                  :disabled="historyPage >= historyTotalPages"
                  @click="historyPage++"
                  class="app-btn app-btn--secondary app-btn--xs disabled:opacity-40"
                >
                  下一页
                </button>
              </div>
            </div>
            </template>
          </div>

            <div v-if="activeTab === 'favorite'" class="app-enter-up bg-white border-2 border-[#0A0910] rounded-xl shadow-lg p-4 md:p-6">
            <div class="flex flex-col gap-3 mb-4">
              <div class="flex flex-col xl:flex-row gap-3 xl:items-end xl:justify-between">
                <div class="flex flex-col sm:flex-row flex-wrap gap-3 flex-1 min-w-0">
                  <div class="flex-1 min-w-[200px]">
                    <label class="app-label">搜索</label>
                    <AppSearch
                      v-model="favoriteKeyword"
                      placeholder="标题 / 菜系 / 用户ID"
                      input-class="text-sm"
                    />
                  </div>
                  <div class="w-full sm:w-40">
                    <label class="app-label">菜系</label>
                    <select v-model="favoriteCuisineFilter" class="app-select text-sm">
                      <option value="">全部菜系</option>
                      <option v-for="c in favoriteCuisineOptions" :key="c" :value="c">{{ c }}</option>
                    </select>
                  </div>
                  <div class="w-full sm:w-40">
                    <label class="app-label">时间</label>
                    <select v-model="favoriteTimeFilter" class="app-select text-sm">
                      <option value="all">全部</option>
                      <option value="today">今日</option>
                      <option value="week">最近7天</option>
                    </select>
                  </div>
                </div>
                <div class="flex flex-wrap gap-2 shrink-0">
                  <button type="button" class="app-btn app-btn--secondary app-btn--sm" @click="refreshData">
                    刷新
                  </button>
                  <button type="button" class="app-btn app-btn--danger app-btn--sm" @click="clearFavorite">
                    清空收藏
                  </button>
                </div>
              </div>
            </div>

            <div v-if="favoriteList.length === 0" class="text-center py-12 text-gray-500">
              暂无收藏记录
            </div>
            <div v-else-if="filteredFavorite.length === 0" class="text-center py-12 text-gray-500">
              没有符合筛选条件的收藏
            </div>

            <template v-else>
            <div class="overflow-x-auto rounded-lg border-2 border-[#0A0910] bg-white">
              <table class="admin-table w-full min-w-[960px] text-sm text-left">
                <thead>
                  <tr class="bg-gray-50 border-b-2 border-[#0A0910]">
                    <th class="font-bold px-3 py-3 border-r border-[#0A0910] text-gray-800">标题</th>
                    <th class="font-bold px-3 py-3 border-r border-[#0A0910] text-gray-800 w-28">菜系</th>
                    <th class="font-bold px-3 py-3 border-r border-[#0A0910] text-gray-800 min-w-[140px]">食材</th>
                    <th class="font-bold px-3 py-3 border-r border-[#0A0910] text-gray-800 min-w-[120px]">用户ID</th>
                    <th class="font-bold px-3 py-3 border-r border-[#0A0910] text-gray-800 whitespace-nowrap">创建时间</th>
                    <th class="font-bold px-3 py-3 text-gray-800 whitespace-nowrap w-40">操作</th>
                  </tr>
                </thead>
                <tbody class="app-tbody-enter">
                  <tr
                    v-for="item in pagedFavorite"
                    :key="item.id"
                    class="border-b border-[#0A0910] bg-red-50/40 hover:bg-red-50"
                  >
                    <td class="px-3 py-2 border-r border-[#0A0910] align-top">
                      <span class="table-cell-ellipsis font-medium text-gray-800" :title="item.title || '未命名收藏'">
                        {{ item.title || '未命名收藏' }}
                      </span>
                    </td>
                    <td class="px-3 py-2 border-r border-[#0A0910] align-top text-gray-700">
                      {{ item.cuisine || '未知菜系' }}
                    </td>
                    <td class="px-3 py-2 border-r border-[#0A0910] align-top">
                      <span class="table-cell-ellipsis text-gray-700" :title="formatIngredients(item.ingredients)">
                        {{ formatIngredients(item.ingredients) }}
                      </span>
                    </td>
                    <td class="px-3 py-2 border-r border-[#0A0910] align-top">
                      <span class="table-cell-ellipsis font-mono text-xs text-gray-700" :title="String(item.user_id || '')">
                        {{ item.user_id || '未知用户' }}
                      </span>
                    </td>
                    <td class="px-3 py-2 border-r border-[#0A0910] align-top text-gray-600 whitespace-nowrap">
                      {{ formatTime(item.created_at) }}
                    </td>
                    <td class="px-3 py-2 align-top">
                      <div class="flex flex-wrap gap-2">
                        <button
                          @click="viewDetail(item)"
                          class="app-btn app-btn--secondary app-btn--xs"
                        >
                          查看
                        </button>
                        <button
                          @click="deleteFavoriteItem(item)"
                          class="app-btn app-btn--danger app-btn--xs"
                        >
                          删除
                        </button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mt-4 pt-3 border-t-2 border-gray-200 text-sm text-gray-700">
              <span>共 {{ filteredFavorite.length }} 条，每页 {{ PAGE_SIZE }} 条</span>
              <div class="flex flex-wrap items-center gap-2">
                <button
                  type="button"
                  :disabled="favoritePage <= 1"
                  @click="favoritePage--"
                  class="app-btn app-btn--secondary app-btn--xs disabled:opacity-40"
                >
                  上一页
                </button>
                <span class="font-medium px-1">第 {{ favoritePage }} / {{ favoriteTotalPages }} 页</span>
                <button
                  type="button"
                  :disabled="favoritePage >= favoriteTotalPages"
                  @click="favoritePage++"
                  class="app-btn app-btn--secondary app-btn--xs disabled:opacity-40"
                >
                  下一页
                </button>
              </div>
            </div>
            </template>
          </div>

            <div v-if="activeTab === 'user'" class="app-enter-up bg-white border-2 border-[#0A0910] rounded-xl shadow-lg p-4 md:p-6">
            <div class="flex flex-col gap-3 mb-4">
              <div class="flex flex-col xl:flex-row gap-3 xl:items-end xl:justify-between">
                <div class="flex flex-col sm:flex-row flex-wrap gap-3 flex-1 min-w-0">
                  <div class="flex-1 min-w-[200px]">
                    <label class="app-label">搜索</label>
                    <AppSearch
                      v-model="userKeyword"
                      placeholder="邮箱 / 用户ID"
                      input-class="text-sm"
                    />
                  </div>
                  <div class="w-full sm:w-40">
                    <label class="app-label">角色</label>
                    <select v-model="userRoleFilter" class="app-select text-sm">
                      <option value="all">全部</option>
                      <option value="user">user</option>
                      <option value="operator">operator</option>
                      <option value="super_admin">super_admin</option>
                    </select>
                  </div>
                </div>
                <div class="shrink-0">
                  <button type="button" class="app-btn app-btn--secondary app-btn--sm" @click="refreshData">
                    刷新
                  </button>
                </div>
              </div>
            </div>

            <div v-if="userList.length === 0" class="text-center py-12 text-gray-500">
              暂无用户记录
            </div>
            <div v-else-if="filteredUsers.length === 0" class="text-center py-12 text-gray-500">
              没有符合筛选条件的用户
            </div>

            <template v-else>
            <div class="overflow-x-auto rounded-lg border-2 border-[#0A0910] bg-white">
              <table class="admin-table w-full min-w-[880px] text-sm text-left">
                <thead>
                  <tr class="bg-gray-50 border-b-2 border-[#0A0910]">
                    <th class="font-bold px-3 py-3 border-r border-[#0A0910] text-gray-800 min-w-[160px]">邮箱</th>
                    <th class="font-bold px-3 py-3 border-r border-[#0A0910] text-gray-800 min-w-[120px]">用户ID</th>
                    <th class="font-bold px-3 py-3 border-r border-[#0A0910] text-gray-800 w-24">角色</th>
                    <th class="font-bold px-3 py-3 border-r border-[#0A0910] text-gray-800 whitespace-nowrap">创建时间</th>
                    <th class="font-bold px-3 py-3 text-gray-800 whitespace-nowrap min-w-[200px]">操作</th>
                  </tr>
                </thead>
                <tbody class="app-tbody-enter">
                  <tr
                    v-for="user in pagedUsers"
                    :key="user.id"
                    class="border-b border-[#0A0910] bg-purple-50/40 hover:bg-purple-50"
                  >
                    <td class="px-3 py-2 border-r border-[#0A0910] align-top">
                      <span class="table-cell-ellipsis text-gray-700" :title="user.email || '未知邮箱'">
                        {{ user.email || '未知邮箱' }}
                      </span>
                    </td>
                    <td class="px-3 py-2 border-r border-[#0A0910] align-top">
                      <span class="table-cell-ellipsis font-mono text-xs text-gray-700" :title="String(user.user_id || '')">
                        {{ user.user_id || '未知用户' }}
                      </span>
                    </td>
                    <td class="px-3 py-2 border-r border-[#0A0910] align-top font-medium text-gray-800">
                      <span :title="String(user.role || '')">{{ roleLabelCN(normalizeRole(user.role)) }}</span>
                      <span class="block text-xs font-normal text-gray-500 font-mono mt-0.5">
                        {{ normalizeRole(user.role) }}
                      </span>
                    </td>
                    <td class="px-3 py-2 border-r border-[#0A0910] align-top text-gray-600 whitespace-nowrap">
                      {{ formatTime(user.created_at) }}
                    </td>
                    <td class="px-3 py-2 align-top">
                      <template v-if="isSuperAdmin">
                        <select
                          :key="`${user.id}-${user.role}`"
                          :value="normalizeRole(user.role)"
                          :disabled="isLastSuperAdmin(user)"
                          class="app-select app-select--sm max-w-[11rem] font-mono disabled:cursor-not-allowed"
                          @change="onUserRoleChange(user, $event)"
                        >
                          <option value="user">user</option>
                          <option value="operator">operator</option>
                          <option value="super_admin">super_admin</option>
                        </select>
                        <p v-if="isLastSuperAdmin(user)" class="text-[11px] text-amber-800 mt-1">
                          至少保留一名超级管理员
                        </p>
                      </template>
                      <span v-else class="text-xs text-gray-500">仅可查看</span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mt-4 pt-3 border-t-2 border-gray-200 text-sm text-gray-700">
              <span>共 {{ filteredUsers.length }} 条，每页 {{ PAGE_SIZE }} 条</span>
              <div class="flex flex-wrap items-center gap-2">
                <button
                  type="button"
                  :disabled="userPage <= 1"
                  @click="userPage--"
                  class="app-btn app-btn--secondary app-btn--xs disabled:opacity-40"
                >
                  上一页
                </button>
                <span class="font-medium px-1">第 {{ userPage }} / {{ userTotalPages }} 页</span>
                <button
                  type="button"
                  :disabled="userPage >= userTotalPages"
                  @click="userPage++"
                  class="app-btn app-btn--secondary app-btn--xs disabled:opacity-40"
                >
                  下一页
                </button>
              </div>
            </div>
            </template>
          </div>

            <div v-if="activeTab === 'logs'" class="app-enter-up bg-white border-2 border-[#0A0910] rounded-xl shadow-lg p-4 md:p-6">
              <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
                <p class="text-sm text-gray-600">
                  记录管理员在后台的关键操作（删除、角色变更、批量清空等）
                </p>
                <button type="button" class="app-btn app-btn--secondary app-btn--sm shrink-0" @click="refreshData">
                  刷新
                </button>
              </div>

              <div v-if="logList.length === 0" class="text-center py-12 text-gray-500">
                暂无操作日志
              </div>

              <template v-else>
                <div class="overflow-x-auto rounded-lg border-2 border-[#0A0910] bg-white">
                  <table class="admin-table w-full min-w-[960px] text-sm text-left">
                    <thead>
                      <tr class="bg-gray-50 border-b-2 border-[#0A0910]">
                        <th class="font-bold px-3 py-3 border-r border-[#0A0910] text-gray-800 min-w-[140px]">操作人</th>
                        <th class="font-bold px-3 py-3 border-r border-[#0A0910] text-gray-800 whitespace-nowrap w-32">操作类型</th>
                        <th class="font-bold px-3 py-3 border-r border-[#0A0910] text-gray-800 whitespace-nowrap w-28">目标类型</th>
                        <th class="font-bold px-3 py-3 border-r border-[#0A0910] text-gray-800 min-w-[100px]">目标ID</th>
                        <th class="font-bold px-3 py-3 border-r border-[#0A0910] text-gray-800 min-w-[180px]">详情</th>
                        <th class="font-bold px-3 py-3 text-gray-800 whitespace-nowrap">操作时间</th>
                      </tr>
                    </thead>
                    <tbody class="app-tbody-enter">
                      <tr
                        v-for="row in pagedLogs"
                        :key="row.id"
                        class="border-b border-[#0A0910] bg-amber-50/30 hover:bg-amber-50/50"
                      >
                        <td class="px-3 py-2 border-r border-[#0A0910] align-top">
                          <span class="table-cell-ellipsis text-gray-700" :title="row.operator_email || ''">
                            {{ row.operator_email || '—' }}
                          </span>
                        </td>
                        <td class="px-3 py-2 border-r border-[#0A0910] align-top text-gray-800 font-medium whitespace-nowrap">
                          {{ formatLogActionType(row.action_type) }}
                        </td>
                        <td class="px-3 py-2 border-r border-[#0A0910] align-top text-gray-700 whitespace-nowrap">
                          {{ formatLogTargetType(row.target_type) }}
                        </td>
                        <td class="px-3 py-2 border-r border-[#0A0910] align-top">
                          <span class="table-cell-ellipsis font-mono text-xs text-gray-700" :title="row.target_id || ''">
                            {{ row.target_id ?? '—' }}
                          </span>
                        </td>
                        <td class="px-3 py-2 border-r border-[#0A0910] align-top">
                          <span class="block max-w-md text-gray-700 break-words" :title="row.detail || ''">
                            {{ row.detail || '—' }}
                          </span>
                        </td>
                        <td class="px-3 py-2 align-top text-gray-600 whitespace-nowrap">
                          {{ formatTime(row.created_at) }}
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mt-4 pt-3 border-t-2 border-gray-200 text-sm text-gray-700">
                  <span>共 {{ logList.length }} 条，每页 {{ PAGE_SIZE }} 条</span>
                  <div class="flex flex-wrap items-center gap-2">
                    <button
                      type="button"
                      :disabled="logPage <= 1"
                      @click="logPage--"
                      class="app-btn app-btn--secondary app-btn--xs disabled:opacity-40"
                    >
                      上一页
                    </button>
                    <span class="font-medium px-1">第 {{ logPage }} / {{ logTotalPages }} 页</span>
                    <button
                      type="button"
                      :disabled="logPage >= logTotalPages"
                      @click="logPage++"
                      class="app-btn app-btn--secondary app-btn--xs disabled:opacity-40"
                    >
                      下一页
                    </button>
                  </div>
                </div>
              </template>
            </div>

            <div v-if="activeTab === 'settings'" class="app-enter-up bg-white border-2 border-[#0A0910] rounded-xl shadow-lg p-4 md:p-6">
              <h2 class="text-lg font-bold text-gray-800 mb-1">系统设置</h2>
              <p class="text-sm text-gray-600 mb-2">
                站点与功能开关（第一版）。当前保存在浏览器本地，后续可对接数据库而不改字段结构。
              </p>
              <p v-if="!isSuperAdmin" class="text-sm text-amber-800 font-medium mb-4">
                您为运营人员：可查看配置，仅超级管理员可编辑与保存。
              </p>

              <div class="space-y-4 max-w-xl">
                <div>
                  <label class="app-label" for="admin-site-name">站点名称</label>
                  <input
                    id="admin-site-name"
                    v-model="systemForm.site_name"
                    type="text"
                    placeholder="例如：今天吃什么"
                    :disabled="!isSuperAdmin"
                    class="app-input text-sm disabled:cursor-not-allowed"
                  />
                </div>
                <div>
                  <label class="app-label" for="admin-site-subtitle">站点副标题</label>
                  <input
                    id="admin-site-subtitle"
                    v-model="systemForm.site_subtitle"
                    type="text"
                    placeholder="一句话介绍"
                    :disabled="!isSuperAdmin"
                    class="app-input text-sm disabled:cursor-not-allowed"
                  />
                </div>
                <AppSwitch
                  id="admin-enable-gallery"
                  v-model="systemForm.enable_gallery"
                  label="启用图鉴"
                  :disabled="!isSuperAdmin"
                />
                <AppSwitch
                  id="admin-enable-fortune"
                  v-model="systemForm.enable_fortune"
                  label="启用玄学厨房"
                  :disabled="!isSuperAdmin"
                />
              </div>

              <button
                v-if="isSuperAdmin"
                type="button"
                class="app-btn app-btn--primary app-btn--md mt-6"
                @click="handleSaveSystemSettings"
              >
                保存系统设置
              </button>
              <p v-else class="mt-6 text-sm text-gray-500">保存按钮仅对超级管理员可见。</p>
            </div>

            <div v-if="activeTab === 'modelConfig'" class="app-enter-up bg-white border-2 border-[#0A0910] rounded-xl shadow-lg p-4 md:p-6">
              <h2 class="text-lg font-bold text-gray-800 mb-1">模型配置</h2>
              <p class="text-sm text-gray-600 mb-2">
                大模型调用参数（第一版）。当前保存在浏览器本地；对接服务端时请避免把密钥写进版本库。
              </p>
              <p v-if="!isSuperAdmin" class="text-sm text-amber-800 font-medium mb-4">
                您为运营人员：可查看配置，仅超级管理员可编辑与保存。
              </p>

              <div class="space-y-4 max-w-xl">
                <div>
                  <label class="app-label" for="admin-model-name">模型名称</label>
                  <input
                    id="admin-model-name"
                    v-model="modelForm.model_name"
                    type="text"
                    placeholder="例如：gpt-4o"
                    :disabled="!isSuperAdmin"
                    class="app-input text-sm disabled:cursor-not-allowed"
                  />
                </div>
                <div>
                  <label class="app-label" for="admin-base-url">Base URL</label>
                  <input
                    id="admin-base-url"
                    v-model="modelForm.base_url"
                    type="url"
                    placeholder="https://api.example.com/v1"
                    :disabled="!isSuperAdmin"
                    class="app-input text-sm disabled:cursor-not-allowed"
                  />
                </div>
                <div>
                  <label class="app-label" for="admin-api-key">API Key</label>
                  <input
                    id="admin-api-key"
                    v-model="modelForm.api_key"
                    type="password"
                    autocomplete="off"
                    placeholder="留空表示暂不配置"
                    :disabled="!isSuperAdmin"
                    class="app-input font-mono text-sm disabled:cursor-not-allowed"
                  />
                </div>
                <div>
                  <label class="app-label" for="admin-temperature">Temperature</label>
                  <input
                    id="admin-temperature"
                    v-model.number="modelForm.temperature"
                    type="number"
                    min="0"
                    max="2"
                    step="0.1"
                    :disabled="!isSuperAdmin"
                    class="app-input text-sm disabled:cursor-not-allowed"
                  />
                </div>
                <div>
                  <label class="app-label" for="admin-timeout">超时（毫秒）</label>
                  <input
                    id="admin-timeout"
                    v-model.number="modelForm.timeout"
                    type="number"
                    min="1000"
                    step="1000"
                    :disabled="!isSuperAdmin"
                    class="app-input text-sm disabled:cursor-not-allowed"
                  />
                </div>
              </div>

              <button
                v-if="isSuperAdmin"
                type="button"
                class="app-btn app-btn--primary app-btn--md mt-6"
                @click="handleSaveModelConfig"
              >
                保存模型配置
              </button>
              <p v-else class="mt-6 text-sm text-gray-500">保存按钮仅对超级管理员可见。</p>
            </div>
          </section>
        </div>
      </div>
    </div>

    <div
      v-if="detailVisible"
      class="fixed inset-0 z-[90] flex items-end justify-center bg-black/45 p-4 backdrop-blur-[2px] sm:items-center"
      @click.self="detailVisible = false"
    >
      <div
        class="max-h-[90vh] w-full max-w-3xl overflow-hidden rounded-[22px] bg-app-card shadow-[0_20px_60px_rgba(0,0,0,0.15)] ring-1 ring-app-line sm:max-h-[85vh]"
        @click.stop
      >
        <div
          class="flex items-center justify-between gap-3 border-b border-app-line bg-gradient-to-r from-app-accent-soft/95 to-app-page px-5 py-4"
        >
          <h3 class="text-[17px] font-bold text-app-fg">详情查看</h3>
          <button
            type="button"
            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white/90 text-xl text-app-muted ring-1 ring-app-line hover:bg-app-page hover:text-app-fg"
            aria-label="关闭"
            @click="detailVisible = false"
          >
            ×
          </button>
        </div>

        <div class="max-h-[calc(90vh-5rem)] overflow-y-auto p-5 md:p-6">
          <div v-if="detailData">
            <div class="mb-4">
              <div class="mb-2 text-xl font-bold text-app-fg">{{ detailData.title || '未命名' }}</div>
              <div class="mb-3 flex flex-wrap gap-2">
                <span class="app-tag app-tag--accent">{{ detailData.cuisine || '未知菜系' }}</span>
                <span class="app-tag app-tag--muted">{{ formatTime(detailData.created_at) }}</span>
              </div>
              <div class="text-sm text-app-fg">
                <span class="font-medium text-app-muted">食材：</span>
                {{ formatIngredients(detailData.ingredients) }}
              </div>
            </div>

            <div
              class="whitespace-pre-wrap rounded-[14px] bg-app-page p-4 text-sm leading-relaxed text-app-fg ring-1 ring-app-line"
            >
              {{ detailData.response_content || detailData.recipe_content || '暂无内容' }}
            </div>
          </div>
        </div>
      </div>
    </div>

    <GlobalFooter />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import GlobalNavigation from '@/components/GlobalNavigation.vue'
import GlobalFooter from '@/components/GlobalFooter.vue'
import { getAllHistories, deleteAnyHistory } from '@/services/adminHistoryService'
import { getAllFavorites, deleteAnyFavorite } from '@/services/adminFavoriteService'
import { getAllUsers, updateUserRole, type ProfileRole } from '@/services/adminUserService'
import { getCurrentUserRole } from '@/services/userProfileService'
import { normalizeRole, roleLabelCN, type AppRole } from '@/lib/appRoles'
import { showAppToast } from '@/utils/showAppToast'
import { showAppConfirm } from '@/utils/showAppConfirm'
import AppSearch from '@/components/ui/AppSearch.vue'
import AppSwitch from '@/components/ui/AppSwitch.vue'
import AppStrokeIcon from '@/components/icons/AppStrokeIcon.vue'
import {
  getAllAdminLogs,
  addAdminLog,
  type AdminLog,
  type AdminLogInput
} from '@/services/adminLogService'
import {
  loadSystemSettings,
  saveSystemSettings,
  loadModelConfig,
  saveModelConfig,
  type SystemSettings,
  type ModelConfig
} from '@/services/adminConfigService'

type CommonRecord = {
  id?: string | number
  title?: string
  cuisine?: string
  ingredients?: string[] | string
  response_content?: string
  recipe_content?: string
  created_at?: string
  [key: string]: any
}

type UserProfile = {
  id: number | string
  user_id: string
  email: string | null
  role: string | null
  created_at: string
}

const PAGE_SIZE = 10

const activeTab = ref<
  'dashboard' | 'history' | 'favorite' | 'user' | 'logs' | 'settings' | 'modelConfig'
>('dashboard')
const historyKeyword = ref('')
const historyCuisineFilter = ref('')
const historyTimeFilter = ref<'all' | 'today' | 'week'>('all')
const historyPage = ref(1)

const favoriteKeyword = ref('')
const favoriteCuisineFilter = ref('')
const favoriteTimeFilter = ref<'all' | 'today' | 'week'>('all')
const favoritePage = ref(1)

const userKeyword = ref('')
const userRoleFilter = ref<'all' | 'user' | 'operator' | 'super_admin'>('all')
const userPage = ref(1)

const logPage = ref(1)

const historyList = ref<CommonRecord[]>([])
const favoriteList = ref<CommonRecord[]>([])
const userList = ref<UserProfile[]>([])
const logList = ref<AdminLog[]>([])
const loading = ref(false)

const systemForm = ref<SystemSettings>(loadSystemSettings())
const modelForm = ref<ModelConfig>(loadModelConfig())

const myRawRole = ref<string | null>(null)
const myRole = computed(() => normalizeRole(myRawRole.value))
const isSuperAdmin = computed(() => myRole.value === 'super_admin')

const detailVisible = ref(false)
const detailData = ref<CommonRecord | null>(null)

const formatIngredients = (ingredients: string[] | string | undefined) => {
  if (!ingredients) return '无'
  if (Array.isArray(ingredients)) return ingredients.join('、')
  return ingredients
}

const formatTime = (timeValue?: string) => {
  if (!timeValue) return '未知时间'
  const d = new Date(timeValue)
  if (Number.isNaN(d.getTime())) return timeValue
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')} ${String(d.getHours()).padStart(2, '0')}:${String(d.getMinutes()).padStart(2, '0')}`
}

const LOG_ACTION_LABELS: Record<string, string> = {
  set_admin: '设为管理员',
  unset_admin: '取消管理员',
  update_user_role: '修改用户角色',
  delete_history: '删除历史记录',
  delete_favorite: '删除收藏',
  clear_history: '清空历史记录',
  clear_favorites: '清空收藏'
}

const LOG_TARGET_LABELS: Record<string, string> = {
  user: '用户',
  recipe_history: '历史记录',
  favorite: '收藏',
  recipe_histories: '历史记录（批量）',
  favorites: '收藏（批量）'
}

const formatLogActionType = (t: string) => LOG_ACTION_LABELS[t] || t
const formatLogTargetType = (t: string) => LOG_TARGET_LABELS[t] || t

const isToday = (timeValue?: string) => {
  if (!timeValue) return false
  const d = new Date(timeValue)
  if (Number.isNaN(d.getTime())) return false
  const now = new Date()
  return d.getFullYear() === now.getFullYear() && d.getMonth() === now.getMonth() && d.getDate() === now.getDate()
}

const isWithinLast7Days = (timeValue?: string) => {
  if (!timeValue) return false
  const d = new Date(timeValue)
  if (Number.isNaN(d.getTime())) return false
  const cutoff = Date.now() - 7 * 24 * 60 * 60 * 1000
  return d.getTime() >= cutoff
}

const totalUserCount = computed(() => {
  return userList.value.length
})

const superAdminUserCount = computed(
  () => userList.value.filter(user => normalizeRole(user.role) === 'super_admin').length
)

const todayNewRecordCount = computed(() =>
  [...historyList.value, ...favoriteList.value].filter(item => isToday(item.created_at)).length
)

const historyCuisineOptions = computed(() => {
  const set = new Set<string>()
  historyList.value.forEach(item => {
    const c = (item.cuisine || '').trim()
    if (c) set.add(c)
  })
  return Array.from(set).sort()
})

const favoriteCuisineOptions = computed(() => {
  const set = new Set<string>()
  favoriteList.value.forEach(item => {
    const c = (item.cuisine || '').trim()
    if (c) set.add(c)
  })
  return Array.from(set).sort()
})

const filteredHistory = computed(() => {
  return historyList.value.filter(item => {
    if (historyTimeFilter.value === 'today' && !isToday(item.created_at)) return false
    if (historyTimeFilter.value === 'week' && !isWithinLast7Days(item.created_at)) return false

    if (historyCuisineFilter.value) {
      const c = (item.cuisine || '').trim()
      if (c !== historyCuisineFilter.value) return false
    }

    const keyword = historyKeyword.value.trim().toLowerCase()
    if (!keyword) return true

    const title = (item.title || '').toLowerCase()
    const cuisine = (item.cuisine || '').toLowerCase()
    const uid = String(item.user_id || '').toLowerCase()
    return title.includes(keyword) || cuisine.includes(keyword) || uid.includes(keyword)
  })
})

const filteredFavorite = computed(() => {
  return favoriteList.value.filter(item => {
    if (favoriteTimeFilter.value === 'today' && !isToday(item.created_at)) return false
    if (favoriteTimeFilter.value === 'week' && !isWithinLast7Days(item.created_at)) return false

    if (favoriteCuisineFilter.value) {
      const c = (item.cuisine || '').trim()
      if (c !== favoriteCuisineFilter.value) return false
    }

    const keyword = favoriteKeyword.value.trim().toLowerCase()
    if (!keyword) return true

    const title = (item.title || '').toLowerCase()
    const cuisine = (item.cuisine || '').toLowerCase()
    const uid = String(item.user_id || '').toLowerCase()
    return title.includes(keyword) || cuisine.includes(keyword) || uid.includes(keyword)
  })
})

const filteredUsers = computed(() => {
  return userList.value.filter(user => {
    if (userRoleFilter.value !== 'all') {
      const r = normalizeRole(user.role)
      if (r !== userRoleFilter.value) return false
    }

    const kw = userKeyword.value.trim().toLowerCase()
    if (!kw) return true

    const email = (user.email || '').toLowerCase()
    const uid = String(user.user_id || '').toLowerCase()
    return email.includes(kw) || uid.includes(kw)
  })
})

const historyTotalPages = computed(() =>
  Math.max(1, Math.ceil(filteredHistory.value.length / PAGE_SIZE))
)

const favoriteTotalPages = computed(() =>
  Math.max(1, Math.ceil(filteredFavorite.value.length / PAGE_SIZE))
)

const userTotalPages = computed(() =>
  Math.max(1, Math.ceil(filteredUsers.value.length / PAGE_SIZE))
)

const pagedHistory = computed(() => {
  const start = (historyPage.value - 1) * PAGE_SIZE
  return filteredHistory.value.slice(start, start + PAGE_SIZE)
})

const pagedFavorite = computed(() => {
  const start = (favoritePage.value - 1) * PAGE_SIZE
  return filteredFavorite.value.slice(start, start + PAGE_SIZE)
})

const pagedUsers = computed(() => {
  const start = (userPage.value - 1) * PAGE_SIZE
  return filteredUsers.value.slice(start, start + PAGE_SIZE)
})

const logTotalPages = computed(() =>
  Math.max(1, Math.ceil(logList.value.length / PAGE_SIZE))
)

const pagedLogs = computed(() => {
  const start = (logPage.value - 1) * PAGE_SIZE
  return logList.value.slice(start, start + PAGE_SIZE)
})

const safeRecordAdminLog = async (log: AdminLogInput) => {
  try {
    await addAdminLog(log)
  } catch (e) {
    console.error('写入操作日志失败:', e)
  }
}

const reloadAdminLogs = async () => {
  try {
    logList.value = await getAllAdminLogs()
  } catch (e) {
    console.error('加载操作日志失败:', e)
  }
}

watch([historyKeyword, historyCuisineFilter, historyTimeFilter], () => {
  historyPage.value = 1
})

watch([favoriteKeyword, favoriteCuisineFilter, favoriteTimeFilter], () => {
  favoritePage.value = 1
})

watch([userKeyword, userRoleFilter], () => {
  userPage.value = 1
})

watch([historyTotalPages, historyPage], () => {
  if (historyPage.value > historyTotalPages.value) {
    historyPage.value = historyTotalPages.value
  }
})

watch([favoriteTotalPages, favoritePage], () => {
  if (favoritePage.value > favoriteTotalPages.value) {
    favoritePage.value = favoriteTotalPages.value
  }
})

watch([userTotalPages, userPage], () => {
  if (userPage.value > userTotalPages.value) {
    userPage.value = userTotalPages.value
  }
})

watch([logTotalPages, logPage], () => {
  if (logPage.value > logTotalPages.value) {
    logPage.value = logTotalPages.value
  }
})

const refreshData = async () => {
  loading.value = true
  try {
    historyList.value = await getAllHistories()
    favoriteList.value = await getAllFavorites()
    userList.value = await getAllUsers()
    try {
      logList.value = await getAllAdminLogs()
    } catch (logErr) {
      console.error('加载操作日志失败（请确认已在 Supabase 创建 admin_logs 表）:', logErr)
      logList.value = []
    }
  } catch (error) {
    console.error('刷新后台数据失败:', error)
    showAppToast('刷新数据失败，请先确认已登录', 'error')
  } finally {
    loading.value = false
  }
}

const deleteHistoryItem = async (item: CommonRecord) => {
  if (!item.id) {
    showAppToast('这条历史没有 id，无法删除', 'error')
    return
  }

  const ok = await showAppConfirm({
    title: '删除历史记录',
    message: '确定删除这条记录吗？此操作不可恢复。',
    confirmText: '删除',
    cancelText: '取消',
    danger: true
  })
  if (!ok) return

  try {
    await deleteAnyHistory(Number(item.id))
    await safeRecordAdminLog({
      action_type: 'delete_history',
      target_type: 'recipe_history',
      target_id: String(item.id),
      detail: `标题: ${item.title || '未命名'}`
    })
    await refreshData()
    showAppToast('历史记录已删除', 'success')
  } catch (error) {
    console.error('删除历史记录失败:', error)
    showAppToast('删除历史记录失败', 'error')
  }
}

const deleteFavoriteItem = async (item: CommonRecord) => {
  if (!item.id) {
    showAppToast('这条收藏没有 id，无法删除', 'error')
    return
  }

  const ok = await showAppConfirm({
    title: '删除收藏',
    message: '确定删除这条收藏吗？此操作不可恢复。',
    confirmText: '删除',
    cancelText: '取消',
    danger: true
  })
  if (!ok) return

  try {
    await deleteAnyFavorite(Number(item.id))
    await safeRecordAdminLog({
      action_type: 'delete_favorite',
      target_type: 'favorite',
      target_id: String(item.id),
      detail: `标题: ${item.title || '未命名'}`
    })
    await refreshData()
    showAppToast('收藏已删除', 'success')
  } catch (error) {
    console.error('删除收藏失败:', error)
    showAppToast('删除收藏失败', 'error')
  }
}

const clearHistory = async () => {
  if (historyList.value.length === 0) {
    showAppToast('没有可清空的历史记录', 'info')
    return
  }

  const historyCount = historyList.value.length
  const ok = await showAppConfirm({
    title: '清空全部历史记录',
    message: `将删除共 ${historyCount} 条历史记录，此操作不可恢复。`,
    confirmText: '清空',
    cancelText: '取消',
    danger: true
  })
  if (!ok) return

  try {
    for (const item of historyList.value) {
      if (item.id) {
        await deleteAnyHistory(Number(item.id))
      }
    }
    await safeRecordAdminLog({
      action_type: 'clear_history',
      target_type: 'recipe_histories',
      target_id: null,
      detail: `共删除 ${historyCount} 条历史记录`
    })
    await refreshData()
    showAppToast('历史记录已清空', 'success')
  } catch (error) {
    console.error('清空历史记录失败:', error)
    showAppToast('清空历史记录失败', 'error')
  }
}

const clearFavorite = async () => {
  if (favoriteList.value.length === 0) {
    showAppToast('没有可清空的收藏记录', 'info')
    return
  }

  const favoriteCount = favoriteList.value.length
  const ok = await showAppConfirm({
    title: '清空全部收藏',
    message: `将删除共 ${favoriteCount} 条收藏，此操作不可恢复。`,
    confirmText: '清空',
    cancelText: '取消',
    danger: true
  })
  if (!ok) return

  try {
    for (const item of favoriteList.value) {
      if (item.id) {
        await deleteAnyFavorite(Number(item.id))
      }
    }
    await safeRecordAdminLog({
      action_type: 'clear_favorites',
      target_type: 'favorites',
      target_id: null,
      detail: `共删除 ${favoriteCount} 条收藏`
    })
    await refreshData()
    showAppToast('收藏已清空', 'success')
  } catch (error) {
    console.error('清空收藏失败:', error)
    showAppToast('清空收藏失败', 'error')
  }
}

const viewDetail = (item: CommonRecord) => {
  detailData.value = item
  detailVisible.value = true
}

const isLastSuperAdmin = (user: UserProfile) => {
  return normalizeRole(user.role) === 'super_admin' && superAdminUserCount.value <= 1
}

function roleChangeConfirmCopy(prev: AppRole, next: AppRole) {
  const prevL = roleLabelCN(prev)
  const nextL = roleLabelCN(next)
  const wasStaff = prev === 'operator' || prev === 'super_admin'
  const willStaff = next === 'operator' || next === 'super_admin'
  if (!wasStaff && willStaff) {
    return {
      title: '授予管理权限',
      message: `确定将用户从「${prevL}」调整为「${nextL}」？对方将能登录并使用管理后台。`,
      danger: false
    }
  }
  if (wasStaff && !willStaff) {
    return {
      title: '取消管理权限',
      message: `确定将用户从「${prevL}」调整为「${nextL}」？将立即无法访问管理后台。`,
      danger: true
    }
  }
  return {
    title: '确认修改角色',
    message: `将角色由「${prevL}」改为「${nextL}」？`,
    danger: next === 'user'
  }
}

const onUserRoleChange = async (user: UserProfile, e: Event) => {
  const sel = e.target as HTMLSelectElement
  const nextRole = sel.value as ProfileRole
  const prev = normalizeRole(user.role)
  if (prev === nextRole) return

  const { title, message, danger } = roleChangeConfirmCopy(prev, nextRole)
  const ok = await showAppConfirm({
    title,
    message,
    confirmText: '确认变更',
    cancelText: '取消',
    danger
  })
  if (!ok) {
    sel.value = prev
    return
  }
  await applyUserRole(user, nextRole)
}

const applyUserRole = async (user: UserProfile, nextRole: ProfileRole) => {
  if (!isSuperAdmin.value) {
    showAppToast('仅超级管理员可修改用户角色', 'warning')
    return
  }

  const current = normalizeRole(user.role)
  if (current === nextRole) return

  if (isLastSuperAdmin(user) && current === 'super_admin' && nextRole !== 'super_admin') {
    showAppToast('系统至少需要保留一名超级管理员', 'warning')
    return
  }

  try {
    await updateUserRole(user.user_id, nextRole)
    await safeRecordAdminLog({
      action_type: 'update_user_role',
      target_type: 'user',
      target_id: user.user_id,
      detail: `目标用户: ${user.email || user.user_id}, 新角色: ${nextRole}`
    })
    await reloadAdminLogs()
    showAppToast(`用户角色已更新为 ${nextRole}`, 'success')
    userList.value = await getAllUsers()
    await syncMyRole()
  } catch (error) {
    console.error('更新用户角色失败:', error)
    showAppToast('更新用户角色失败', 'error')
    userList.value = await getAllUsers()
  }
}

const exportData = () => {
  const exportObj = {
    exportTime: new Date().toISOString(),
    history: historyList.value,
    favorite: favoriteList.value
  }

  const blob = new Blob([JSON.stringify(exportObj, null, 2)], {
    type: 'application/json;charset=utf-8'
  })

  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = `today-eat-admin-export-${Date.now()}.json`
  a.click()
  URL.revokeObjectURL(url)

  showAppToast('数据导出成功', 'success')
}

const handleSaveSystemSettings = () => {
  if (!isSuperAdmin.value) {
    showAppToast('仅超级管理员可保存系统设置', 'warning')
    return
  }
  saveSystemSettings(systemForm.value)
  systemForm.value = loadSystemSettings()
  showAppToast('系统设置已保存（本地暂存）', 'success')
}

const handleSaveModelConfig = () => {
  if (!isSuperAdmin.value) {
    showAppToast('仅超级管理员可保存模型配置', 'warning')
    return
  }
  saveModelConfig(modelForm.value)
  modelForm.value = loadModelConfig()
  showAppToast('模型配置已保存（本地暂存）', 'success')
}

const syncMyRole = async () => {
  myRawRole.value = await getCurrentUserRole()
}

onMounted(async () => {
  systemForm.value = loadSystemSettings()
  modelForm.value = loadModelConfig()
  await syncMyRole()
  await refreshData()
})
</script>

<style scoped>
.line-clamp-box {
  display: -webkit-box;
  -webkit-line-clamp: 4;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.admin-table {
  border-collapse: collapse;
}

.table-cell-ellipsis {
  display: block;
  max-width: 14rem;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

@media (min-width: 1024px) {
  .table-cell-ellipsis {
    max-width: 18rem;
  }
}

</style>