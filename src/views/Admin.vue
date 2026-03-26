<template>
  <div class="admin-page min-h-screen">
      <div class="admin-shell">
      <header class="admin-topbar">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
          <div>
            <p class="admin-breadcrumb">后台管理 / {{ activeTabLabel }}</p>
            <h1 class="flex items-center gap-3 text-2xl font-bold text-gray-800 md:text-3xl">
              <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-[#E9F1FF] text-[#4F7BFF]">
                <AppStrokeIcon name="wrench" :size="24" />
              </span>
              <span>平台管理后台</span>
            </h1>
            <p class="text-[#6B7280] text-sm mt-2">当前为云端数据模式，数据来源于 Supabase 数据库</p>
            <p class="mt-3 flex flex-wrap items-center gap-2">
              <span
                class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm font-semibold border border-[#E5E7EB] bg-white text-[#1F2937]"
              >
                当前管理员角色：{{ roleLabelCN(myRole) }}
              </span>
            </p>
          </div>

          <div class="flex flex-wrap items-center gap-2">
            <button type="button" class="admin-toolbar-icon" aria-label="通知">
              <AppStrokeIcon name="bell" :size="18" />
            </button>
            <button type="button" class="admin-toolbar-icon" aria-label="设置">
              <AppStrokeIcon name="cog" :size="18" />
            </button>
            <div class="admin-user-chip">
              <span class="admin-user-avatar">A</span>
              <span class="admin-user-name">管理员</span>
            </div>
            <button type="button" class="admin-btn admin-btn--primary" @click="refreshData">
              刷新数据
            </button>
            <button type="button" class="admin-btn admin-btn--secondary" @click="exportData">
              导出数据
            </button>
          </div>
        </div>
      </header>

      <div v-if="loading" class="admin-content-card admin-state admin-state--loading">
        正在加载数据...
      </div>

      <div v-else>
        <div class="admin-layout">
          <aside class="admin-sidebar">
            <div class="admin-sidebar-inner">
              <div class="space-y-3">
                <div v-if="isSuperAdmin">
                  <button type="button" class="admin-menu-group-title" @click="onMenuGroupClick('dashboard')">
                    <span>仪表盘</span>
                    <span>{{ menuExpanded.dashboard ? '−' : '+' }}</span>
                  </button>
                  <div v-if="menuExpanded.dashboard" class="mt-2 flex flex-col gap-1.5 pl-4">
                    <button type="button" class="admin-menu-item admin-menu-item--sub" :class="isTabActive('dashboard') ? 'admin-menu-item--active' : 'admin-menu-item--inactive'" @click="goTab('dashboard')">
                      <AppStrokeIcon name="chartBar" :size="16" class="shrink-0 opacity-90" />
                      仪表盘首页
                    </button>
                  </div>
                </div>

                <div v-if="isSuperAdmin">
                  <button type="button" class="admin-menu-group-title" @click="onMenuGroupClick('user_permission')">
                    <span>用户与权限</span>
                    <span>{{ menuExpanded.user_permission ? '−' : '+' }}</span>
                  </button>
                  <div v-if="menuExpanded.user_permission" class="mt-2 flex flex-col gap-1.5 pl-4">
                    <button type="button" class="admin-menu-item admin-menu-item--sub" :class="isTabActive('user') ? 'admin-menu-item--active' : 'admin-menu-item--inactive'" @click="goTab('user')"><AppStrokeIcon name="users" :size="16" class="shrink-0 opacity-90" />用户管理</button>
                    <button type="button" class="admin-menu-item admin-menu-item--sub" :class="isTabActive('rolePermission') ? 'admin-menu-item--active' : 'admin-menu-item--inactive'" @click="goTab('rolePermission')"><AppStrokeIcon name="users" :size="16" class="shrink-0 opacity-90" />角色权限（预留）</button>
                    <button type="button" class="admin-menu-item admin-menu-item--sub" :class="isTabActive('logs') ? 'admin-menu-item--active' : 'admin-menu-item--inactive'" @click="goTab('logs')"><AppStrokeIcon name="clipboardList" :size="16" class="shrink-0 opacity-90" />操作日志</button>
                  </div>
                </div>

                <div>
                  <button type="button" class="admin-menu-group-title" @click="onMenuGroupClick('content_ops')">
                    <span>内容与运营</span>
                    <span>{{ menuExpanded.content_ops ? '−' : '+' }}</span>
                  </button>
                  <div v-if="menuExpanded.content_ops" class="mt-2 flex flex-col gap-1.5 pl-4">
                    <button type="button" class="admin-menu-item admin-menu-item--sub" :class="isTabActive('modelConfig') && configCenterSection === 'frontend_basic' ? 'admin-menu-item--active' : 'admin-menu-item--inactive'" @click="goConfigTab('frontend_basic')"><AppStrokeIcon name="cpu" :size="16" class="shrink-0 opacity-90" />前端基础配置</button>
                    <button type="button" class="admin-menu-item admin-menu-item--sub" :class="isTabActive('modelConfig') && configCenterSection === 'page_ops' ? 'admin-menu-item--active' : 'admin-menu-item--inactive'" @click="goConfigTab('page_ops')"><AppStrokeIcon name="chartBar" :size="16" class="shrink-0 opacity-90" />首页运营位配置</button>
                    <button type="button" class="admin-menu-item admin-menu-item--sub" :class="isTabActive('modelConfig') && configCenterSection === 'page_ops' ? 'admin-menu-item--active' : 'admin-menu-item--inactive'" @click="goConfigTab('page_ops')"><AppStrokeIcon name="wrench" :size="16" class="shrink-0 opacity-90" />功能广场配置</button>
                    <button type="button" class="admin-menu-item admin-menu-item--sub" :class="isTabActive('modelConfig') && configCenterSection === 'page_ops' ? 'admin-menu-item--active' : 'admin-menu-item--inactive'" @click="goConfigTab('page_ops')"><AppStrokeIcon name="users" :size="16" class="shrink-0 opacity-90" />我的页面配置</button>
                    <button type="button" class="admin-menu-item admin-menu-item--sub" :class="isTabActive('modelConfig') && configCenterSection === 'page_ops' ? 'admin-menu-item--active' : 'admin-menu-item--inactive'" @click="goConfigTab('page_ops')"><AppStrokeIcon name="bookmark" :size="16" class="shrink-0 opacity-90" />收藏/历史页面配置</button>
                    <button type="button" class="admin-menu-item admin-menu-item--sub" :class="isTabActive('modelConfig') && configCenterSection === 'global_copy' ? 'admin-menu-item--active' : 'admin-menu-item--inactive'" @click="goConfigTab('global_copy')"><AppStrokeIcon name="clipboardList" :size="16" class="shrink-0 opacity-90" />全局文案配置</button>
                  </div>
                </div>

                <div>
                  <button type="button" class="admin-menu-group-title" @click="onMenuGroupClick('data')">
                    <span>功能数据管理</span>
                    <span>{{ menuExpanded.data ? '−' : '+' }}</span>
                  </button>
                  <div v-if="menuExpanded.data" class="mt-2 flex flex-col gap-1.5 pl-4">
                    <button type="button" class="admin-menu-item admin-menu-item--sub" :class="isTabActive('history') ? 'admin-menu-item--active' : 'admin-menu-item--inactive'" @click="goTab('history')"><AppStrokeIcon name="scrollText" :size="16" class="shrink-0 opacity-90" />历史记录管理</button>
                    <button type="button" class="admin-menu-item admin-menu-item--sub" :class="isTabActive('favorite') ? 'admin-menu-item--active' : 'admin-menu-item--inactive'" @click="goTab('favorite')"><AppStrokeIcon name="heart" :size="16" class="shrink-0 opacity-90" />收藏管理</button>
                    <button type="button" class="admin-menu-item admin-menu-item--sub" :class="isTabActive('tableData') ? 'admin-menu-item--active' : 'admin-menu-item--inactive'" @click="goTab('tableData')"><AppStrokeIcon name="utensils" :size="16" class="shrink-0 opacity-90" />满汉全席数据</button>
                    <button type="button" class="admin-menu-item admin-menu-item--sub" :class="isTabActive('fortuneData') ? 'admin-menu-item--active' : 'admin-menu-item--inactive'" @click="goTab('fortuneData')"><AppStrokeIcon name="moon" :size="16" class="shrink-0 opacity-90" />玄学厨房数据</button>
                    <button type="button" class="admin-menu-item admin-menu-item--sub" :class="isTabActive('sauceData') ? 'admin-menu-item--active' : 'admin-menu-item--inactive'" @click="goTab('sauceData')"><AppStrokeIcon name="droplet" :size="16" class="shrink-0 opacity-90" />酱料大师数据</button>
                    <button type="button" class="admin-menu-item admin-menu-item--sub" :class="isTabActive('galleryData') ? 'admin-menu-item--active' : 'admin-menu-item--inactive'" @click="goTab('galleryData')"><AppStrokeIcon name="image" :size="16" class="shrink-0 opacity-90" />图鉴数据</button>
                  </div>
                </div>

                <div>
                  <button type="button" class="admin-menu-group-title" @click="onMenuGroupClick('system')">
                    <span>系统配置</span>
                    <span>{{ menuExpanded.system ? '−' : '+' }}</span>
                  </button>
                  <div v-if="menuExpanded.system" class="mt-2 flex flex-col gap-1.5 pl-4">
                    <button v-if="isSuperAdmin" type="button" class="admin-menu-item admin-menu-item--sub" :class="isTabActive('modelConfig') ? 'admin-menu-item--active' : 'admin-menu-item--inactive'" @click="goTab('modelConfig')"><AppStrokeIcon name="cpu" :size="16" class="shrink-0 opacity-90" />配置中心</button>
                    <button v-if="isSuperAdmin" type="button" class="admin-menu-item admin-menu-item--sub" :class="isTabActive('settings') ? 'admin-menu-item--active' : 'admin-menu-item--inactive'" @click="goTab('settings')"><AppStrokeIcon name="sliders" :size="16" class="shrink-0 opacity-90" />系统设置</button>
                    <button v-if="isSuperAdmin" type="button" class="admin-menu-item admin-menu-item--sub" :class="isTabActive('modelConfig') && configCenterSection === 'system_model' ? 'admin-menu-item--active' : 'admin-menu-item--inactive'" @click="goConfigTab('system_model')"><AppStrokeIcon name="cpu" :size="16" class="shrink-0 opacity-90" />模型配置</button>
                  </div>
                </div>

                <router-link to="/" class="admin-menu-item admin-menu-item--inactive">
                  <AppStrokeIcon name="arrowLeft" :size="18" class="shrink-0 opacity-80" />
                  返回前台
                </router-link>
              </div>
            </div>
          </aside>

          <section class="admin-main">
            <div v-if="activeTab === 'dashboard'" class="app-enter-up space-y-4">
              <div class="admin-module-head mb-0">
                <div>
                  <h2 class="admin-module-title">运营看板</h2>
                  <p class="admin-module-desc">基于当前真实数据统计用户、内容与活跃趋势。</p>
                </div>
              </div>

              <div class="app-stagger-grid grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div class="admin-stat-card">
                  <div class="flex items-center justify-between">
                    <div><p class="admin-stat-label">用户总数</p><p class="admin-stat-value">{{ totalUserCount }}</p></div>
                    <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-purple-100 text-purple-700"><AppStrokeIcon name="users" :size="24" /></div>
                  </div>
                </div>
                <div class="admin-stat-card">
                  <div class="flex items-center justify-between">
                    <div><p class="admin-stat-label">历史记录总数</p><p class="admin-stat-value">{{ historyList.length }}</p></div>
                    <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-blue-100 text-blue-700"><AppStrokeIcon name="scrollText" :size="24" /></div>
                  </div>
                </div>
                <div class="admin-stat-card">
                  <div class="flex items-center justify-between">
                    <div><p class="admin-stat-label">收藏总数</p><p class="admin-stat-value">{{ favoriteList.length }}</p></div>
                    <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-red-100 text-red-600"><AppStrokeIcon name="heart" :size="24" /></div>
                  </div>
                </div>
                <div class="admin-stat-card">
                  <div class="flex items-center justify-between">
                    <div><p class="admin-stat-label">管理员总数</p><p class="admin-stat-value">{{ adminUserCount }}</p></div>
                    <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-indigo-100 text-indigo-700"><AppStrokeIcon name="wrench" :size="24" /></div>
                  </div>
                </div>
              </div>

              <div class="grid grid-cols-1 gap-4 xl:grid-cols-3">
                <div class="admin-content-card admin-module-card">
                  <h3 class="text-sm font-semibold text-[#1F2937] mb-2">今日新增</h3>
                  <div class="space-y-2 text-sm">
                    <div class="admin-mini-metric"><span>今日新增用户</span><strong>{{ todayNewUserCount }}</strong></div>
                    <div class="admin-mini-metric"><span>今日新增历史记录</span><strong>{{ todayNewHistoryCount }}</strong></div>
                    <div class="admin-mini-metric"><span>今日新增收藏记录</span><strong>{{ todayNewFavoriteCount }}</strong></div>
                  </div>
                </div>
                <div class="admin-content-card admin-module-card xl:col-span-2">
                  <h3 class="text-sm font-semibold text-[#1F2937] mb-2">最近 7 天趋势</h3>
                  <div class="space-y-3">
                    <div class="admin-trend-row">
                      <span class="admin-trend-label">用户</span>
                      <div class="admin-trend-bars">
                        <div v-for="item in userTrend7d" :key="`u-${item.date}`" class="admin-trend-bar-wrap">
                          <div class="admin-trend-bar bg-purple-500/75" :style="{ height: `${calcTrendBarHeight(item.count, trendMaxValue)}px` }"></div>
                          <span class="admin-trend-day">{{ item.label }}</span>
                        </div>
                      </div>
                    </div>
                    <div class="admin-trend-row">
                      <span class="admin-trend-label">历史</span>
                      <div class="admin-trend-bars">
                        <div v-for="item in historyTrend7d" :key="`h-${item.date}`" class="admin-trend-bar-wrap">
                          <div class="admin-trend-bar bg-blue-500/75" :style="{ height: `${calcTrendBarHeight(item.count, trendMaxValue)}px` }"></div>
                          <span class="admin-trend-day">{{ item.label }}</span>
                        </div>
                      </div>
                    </div>
                    <div class="admin-trend-row">
                      <span class="admin-trend-label">收藏</span>
                      <div class="admin-trend-bars">
                        <div v-for="item in favoriteTrend7d" :key="`f-${item.date}`" class="admin-trend-bar-wrap">
                          <div class="admin-trend-bar bg-rose-500/75" :style="{ height: `${calcTrendBarHeight(item.count, trendMaxValue)}px` }"></div>
                          <span class="admin-trend-day">{{ item.label }}</span>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div class="grid grid-cols-1 gap-4 xl:grid-cols-3">
                <div class="admin-content-card admin-module-card">
                  <h3 class="text-sm font-semibold text-[#1F2937] mb-2">最常被收藏的菜系</h3>
                  <ul v-if="topFavoriteCuisines.length > 0" class="space-y-2 text-sm">
                    <li v-for="row in topFavoriteCuisines" :key="`fav-${row.name}`" class="admin-rank-row"><span>{{ row.name }}</span><strong>{{ row.count }}</strong></li>
                  </ul>
                  <div v-else class="admin-state admin-state--empty !min-h-[96px]">暂无可统计数据</div>
                </div>
                <div class="admin-content-card admin-module-card">
                  <h3 class="text-sm font-semibold text-[#1F2937] mb-2">最常生成的菜系</h3>
                  <ul v-if="topHistoryCuisines.length > 0" class="space-y-2 text-sm">
                    <li v-for="row in topHistoryCuisines" :key="`his-${row.name}`" class="admin-rank-row"><span>{{ row.name }}</span><strong>{{ row.count }}</strong></li>
                  </ul>
                  <div v-else class="admin-state admin-state--empty !min-h-[96px]">暂无可统计数据</div>
                </div>
                <div class="admin-content-card admin-module-card">
                  <h3 class="text-sm font-semibold text-[#1F2937] mb-2">最活跃用户（简版）</h3>
                  <ul v-if="topActiveUsers.length > 0" class="space-y-2 text-sm">
                    <li v-for="row in topActiveUsers" :key="`user-${row.userId}`" class="admin-rank-row"><span class="truncate">{{ row.userId }}</span><strong>{{ row.count }}</strong></li>
                  </ul>
                  <div v-else class="admin-state admin-state--empty !min-h-[96px]">当前缺少 user_id 维度数据，已预留模块</div>
                </div>
              </div>
            </div>

            <div v-if="activeTab === 'history'" class="app-enter-up admin-content-card admin-module-card">
            <div class="admin-module-head">
              <div>
                <h2 class="admin-module-title">历史记录管理</h2>
                <p class="admin-module-desc">统一查看、筛选和维护用户生成记录。</p>
              </div>
            </div>
            <div class="admin-filter-wrap">
              <div class="admin-filter-row">
                <div class="admin-filter-fields">
                  <div class="flex-1 min-w-[200px]">
                    <label class="admin-form-label">标题</label>
                    <AppSearch
                      v-model="historyKeyword"
                      placeholder="输入标题关键词"
                      input-class="admin-input"
                    />
                  </div>
                  <div class="flex-1 min-w-[200px]">
                    <label class="admin-form-label">用户</label>
                    <AppSearch
                      v-model="historyUserKeyword"
                      placeholder="user_id 或 邮箱"
                      input-class="admin-input"
                    />
                  </div>
                  <div class="admin-filter-col">
                    <label class="admin-form-label">菜系</label>
                    <select v-model="historyCuisineFilter" class="admin-select">
                      <option value="">全部菜系</option>
                      <option v-for="c in historyCuisineOptions" :key="c" :value="c">{{ c }}</option>
                    </select>
                  </div>
                  <div class="admin-filter-col">
                    <label class="admin-form-label">时间</label>
                    <select v-model="historyTimeFilter" class="admin-select">
                      <option value="all">全部</option>
                      <option value="today">今日</option>
                      <option value="week">最近7天</option>
                      <option value="month">最近30天</option>
                    </select>
                  </div>
                </div>
                <div class="admin-filter-actions">
                  <button type="button" class="admin-btn admin-btn--secondary" @click="refreshData">
                    刷新
                  </button>
                  <button type="button" class="admin-btn admin-btn--secondary" @click="exportFilteredHistory">
                    导出筛选结果
                  </button>
                  <button v-if="canDeleteData" type="button" class="admin-btn admin-btn--danger" @click="clearHistory">
                    清空历史
                  </button>
                </div>
              </div>
            </div>

            <div v-if="historyList.length === 0" class="admin-state admin-state--empty">
              暂无历史记录
            </div>
            <div v-else-if="filteredHistory.length === 0" class="admin-state admin-state--empty">
              没有符合筛选条件的历史记录
            </div>

            <template v-else>
            <div class="admin-bulk-bar">
              <span>已选 {{ selectedHistoryCount }} 项</span>
              <div class="flex items-center gap-2">
                <button type="button" class="admin-btn admin-btn--secondary admin-btn--xs" @click="selectAllFilteredHistory">全选筛选结果</button>
                <button type="button" class="admin-btn admin-btn--secondary admin-btn--xs" @click="clearSelectedHistory">清空选择</button>
                <button v-if="canDeleteData" type="button" class="admin-btn admin-btn--danger admin-btn--xs" :disabled="selectedHistoryCount === 0" @click="batchDeleteHistory">批量删除</button>
              </div>
            </div>
            <div class="admin-table-wrap">
              <table class="admin-table w-full min-w-[1020px] text-sm text-left">
                <thead>
                  <tr class="admin-table-head-row">
                    <th class="font-bold px-3 py-3 border-r border-[#E5E7EB] text-gray-800 w-12">选择</th>
                    <th class="font-bold px-3 py-3 border-r border-[#E5E7EB] text-gray-800">标题</th>
                    <th class="font-bold px-3 py-3 border-r border-[#E5E7EB] text-gray-800 w-28">菜系</th>
                    <th class="font-bold px-3 py-3 border-r border-[#E5E7EB] text-gray-800 min-w-[200px]">用户</th>
                    <th class="font-bold px-3 py-3 border-r border-[#E5E7EB] text-gray-800 whitespace-nowrap">创建时间</th>
                    <th class="font-bold px-3 py-3 text-gray-800 whitespace-nowrap w-40">操作</th>
                  </tr>
                </thead>
                <tbody class="app-tbody-enter">
                  <tr
                    v-for="item in pagedHistory"
                    :key="item.id"
                    class="admin-table-row"
                  >
                    <td class="px-3 py-2 border-r border-[#E5E7EB] align-top">
                      <input
                        :checked="selectedHistoryIds.includes(String(item.id || ''))"
                        type="checkbox"
                        class="h-4 w-4"
                        @change="toggleHistorySelect(String(item.id || ''), ($event.target as HTMLInputElement).checked)"
                      />
                    </td>
                    <td class="px-3 py-2 border-r border-[#E5E7EB] align-top">
                      <span class="table-cell-ellipsis font-medium text-gray-800" :title="item.title || '未命名记录'">
                        {{ item.title || '未命名记录' }}
                      </span>
                    </td>
                    <td class="px-3 py-2 border-r border-[#E5E7EB] align-top text-gray-700">
                      {{ item.cuisine || '未知菜系' }}
                    </td>
                    <td class="px-3 py-2 border-r border-[#E5E7EB] align-top">
                      <span class="table-cell-ellipsis text-gray-700" :title="formatRecordUserLabel(item)">
                        {{ formatRecordUserLabel(item) }}
                      </span>
                    </td>
                    <td class="px-3 py-2 border-r border-[#E5E7EB] align-top text-gray-600 whitespace-nowrap">
                      {{ formatTime(item.created_at) }}
                    </td>
                    <td class="px-3 py-2 align-top">
                      <div class="flex flex-wrap gap-2">
                        <button
                          @click="viewDetail(item, 'history')"
                          class="admin-btn admin-btn--secondary admin-btn--xs"
                        >
                          查看
                        </button>
                        <button
                          v-if="canDeleteData"
                          @click="deleteHistoryItem(item)"
                          class="admin-btn admin-btn--danger admin-btn--xs"
                        >
                          删除
                        </button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
            <div class="admin-pagination">
              <span>共 {{ filteredHistory.length }} 条，每页 {{ PAGE_SIZE }} 条</span>
              <div class="flex flex-wrap items-center gap-2">
                <button
                  type="button"
                  :disabled="historyPage <= 1"
                  @click="historyPage--"
                  class="admin-btn admin-btn--secondary admin-btn--xs disabled:opacity-40"
                >
                  上一页
                </button>
                <span class="font-medium px-1">第 {{ historyPage }} / {{ historyTotalPages }} 页</span>
                <button
                  type="button"
                  :disabled="historyPage >= historyTotalPages"
                  @click="historyPage++"
                  class="admin-btn admin-btn--secondary admin-btn--xs disabled:opacity-40"
                >
                  下一页
                </button>
              </div>
            </div>
            </template>
          </div>

            <div v-if="activeTab === 'favorite'" class="app-enter-up admin-content-card admin-module-card">
            <div class="admin-module-head">
              <div>
                <h2 class="admin-module-title">收藏管理</h2>
                <p class="admin-module-desc">统一管理用户收藏内容与清理操作。</p>
              </div>
            </div>
            <div class="admin-filter-wrap">
              <div class="admin-filter-row">
                <div class="admin-filter-fields">
                  <div class="flex-1 min-w-[200px]">
                    <label class="admin-form-label">标题</label>
                    <AppSearch
                      v-model="favoriteKeyword"
                      placeholder="输入标题关键词"
                      input-class="admin-input"
                    />
                  </div>
                  <div class="flex-1 min-w-[200px]">
                    <label class="admin-form-label">用户</label>
                    <AppSearch
                      v-model="favoriteUserKeyword"
                      placeholder="user_id 或 邮箱"
                      input-class="admin-input"
                    />
                  </div>
                  <div class="admin-filter-col">
                    <label class="admin-form-label">菜系</label>
                    <select v-model="favoriteCuisineFilter" class="admin-select">
                      <option value="">全部菜系</option>
                      <option v-for="c in favoriteCuisineOptions" :key="c" :value="c">{{ c }}</option>
                    </select>
                  </div>
                  <div class="admin-filter-col">
                    <label class="admin-form-label">时间</label>
                    <select v-model="favoriteTimeFilter" class="admin-select">
                      <option value="all">全部</option>
                      <option value="today">今日</option>
                      <option value="week">最近7天</option>
                      <option value="month">最近30天</option>
                    </select>
                  </div>
                </div>
                <div class="admin-filter-actions">
                  <button type="button" class="admin-btn admin-btn--secondary" @click="refreshData">
                    刷新
                  </button>
                  <button type="button" class="admin-btn admin-btn--secondary" @click="exportFilteredFavorite">
                    导出筛选结果
                  </button>
                  <button v-if="canDeleteData" type="button" class="admin-btn admin-btn--danger" @click="clearFavorite">
                    清空收藏
                  </button>
                </div>
              </div>
            </div>

            <div v-if="favoriteList.length === 0" class="admin-state admin-state--empty">
              暂无收藏记录
            </div>
            <div v-else-if="filteredFavorite.length === 0" class="admin-state admin-state--empty">
              没有符合筛选条件的收藏
            </div>

            <template v-else>
            <div class="admin-bulk-bar">
              <span>已选 {{ selectedFavoriteCount }} 项</span>
              <div class="flex items-center gap-2">
                <button type="button" class="admin-btn admin-btn--secondary admin-btn--xs" @click="selectAllFilteredFavorite">全选筛选结果</button>
                <button type="button" class="admin-btn admin-btn--secondary admin-btn--xs" @click="clearSelectedFavorite">清空选择</button>
                <button v-if="canDeleteData" type="button" class="admin-btn admin-btn--danger admin-btn--xs" :disabled="selectedFavoriteCount === 0" @click="batchDeleteFavorite">批量删除</button>
              </div>
            </div>
            <div class="admin-table-wrap">
              <table class="admin-table w-full min-w-[1020px] text-sm text-left">
                <thead>
                  <tr class="admin-table-head-row">
                    <th class="font-bold px-3 py-3 border-r border-[#E5E7EB] text-gray-800 w-12">选择</th>
                    <th class="font-bold px-3 py-3 border-r border-[#E5E7EB] text-gray-800">标题</th>
                    <th class="font-bold px-3 py-3 border-r border-[#E5E7EB] text-gray-800 w-28">菜系</th>
                    <th class="font-bold px-3 py-3 border-r border-[#E5E7EB] text-gray-800 min-w-[200px]">用户</th>
                    <th class="font-bold px-3 py-3 border-r border-[#E5E7EB] text-gray-800 whitespace-nowrap">创建时间</th>
                    <th class="font-bold px-3 py-3 text-gray-800 whitespace-nowrap w-40">操作</th>
                  </tr>
                </thead>
                <tbody class="app-tbody-enter">
                  <tr
                    v-for="item in pagedFavorite"
                    :key="item.id"
                    class="admin-table-row"
                  >
                    <td class="px-3 py-2 border-r border-[#E5E7EB] align-top">
                      <input
                        :checked="selectedFavoriteIds.includes(String(item.id || ''))"
                        type="checkbox"
                        class="h-4 w-4"
                        @change="toggleFavoriteSelect(String(item.id || ''), ($event.target as HTMLInputElement).checked)"
                      />
                    </td>
                    <td class="px-3 py-2 border-r border-[#E5E7EB] align-top">
                      <span class="table-cell-ellipsis font-medium text-gray-800" :title="item.title || '未命名收藏'">
                        {{ item.title || '未命名收藏' }}
                      </span>
                    </td>
                    <td class="px-3 py-2 border-r border-[#E5E7EB] align-top text-gray-700">
                      {{ item.cuisine || '未知菜系' }}
                    </td>
                    <td class="px-3 py-2 border-r border-[#E5E7EB] align-top">
                      <span class="table-cell-ellipsis text-gray-700" :title="formatRecordUserLabel(item)">
                        {{ formatRecordUserLabel(item) }}
                      </span>
                    </td>
                    <td class="px-3 py-2 border-r border-[#E5E7EB] align-top text-gray-600 whitespace-nowrap">
                      {{ formatTime(item.created_at) }}
                    </td>
                    <td class="px-3 py-2 align-top">
                      <div class="flex flex-wrap gap-2">
                        <button
                          @click="viewDetail(item, 'favorite')"
                          class="admin-btn admin-btn--secondary admin-btn--xs"
                        >
                          查看
                        </button>
                        <button
                          v-if="canDeleteData"
                          @click="deleteFavoriteItem(item)"
                          class="admin-btn admin-btn--danger admin-btn--xs"
                        >
                          删除
                        </button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
            <div class="admin-pagination">
              <span>共 {{ filteredFavorite.length }} 条，每页 {{ PAGE_SIZE }} 条</span>
              <div class="flex flex-wrap items-center gap-2">
                <button
                  type="button"
                  :disabled="favoritePage <= 1"
                  @click="favoritePage--"
                  class="admin-btn admin-btn--secondary admin-btn--xs disabled:opacity-40"
                >
                  上一页
                </button>
                <span class="font-medium px-1">第 {{ favoritePage }} / {{ favoriteTotalPages }} 页</span>
                <button
                  type="button"
                  :disabled="favoritePage >= favoriteTotalPages"
                  @click="favoritePage++"
                  class="admin-btn admin-btn--secondary admin-btn--xs disabled:opacity-40"
                >
                  下一页
                </button>
              </div>
            </div>
            </template>
          </div>

            <div v-if="activeTab === 'user'" class="app-enter-up admin-content-card admin-module-card">
            <div class="admin-module-head">
              <div>
                <h2 class="admin-module-title">用户管理</h2>
                <p class="admin-module-desc">查看账号信息并维护后台角色权限。</p>
              </div>
            </div>
            <div class="admin-filter-wrap">
              <div class="admin-filter-row">
                <div class="admin-filter-fields">
                  <div class="flex-1 min-w-[200px]">
                    <label class="admin-form-label">邮箱</label>
                    <AppSearch
                      v-model="userEmailKeyword"
                      placeholder="输入邮箱关键词"
                      input-class="admin-input"
                    />
                  </div>
                  <div class="flex-1 min-w-[200px]">
                    <label class="admin-form-label">user_id</label>
                    <AppSearch
                      v-model="userIdKeyword"
                      placeholder="输入用户ID关键词"
                      input-class="admin-input"
                    />
                  </div>
                  <div class="admin-filter-col">
                    <label class="admin-form-label">角色</label>
                    <select v-model="userRoleFilter" class="admin-select">
                      <option value="all">全部</option>
                      <option value="viewer">viewer</option>
                      <option value="user">user</option>
                      <option value="operator">operator</option>
                      <option value="super_admin">super_admin</option>
                    </select>
                  </div>
                  <div class="admin-filter-col">
                    <label class="admin-form-label">注册时间</label>
                    <select v-model="userRegisterTimeFilter" class="admin-select">
                      <option value="all">全部</option>
                      <option value="today">今日</option>
                      <option value="week">最近7天</option>
                      <option value="month">最近30天</option>
                    </select>
                  </div>
                </div>
                <div class="shrink-0">
                  <button type="button" class="admin-btn admin-btn--secondary" @click="refreshData">
                    刷新
                  </button>
                </div>
              </div>
            </div>

            <div v-if="userList.length === 0" class="admin-state admin-state--empty">
              暂无用户记录
            </div>
            <div v-else-if="filteredUsers.length === 0" class="admin-state admin-state--empty">
              没有符合筛选条件的用户
            </div>

            <template v-else>
            <div class="admin-bulk-bar"><span>已选 0 项</span><button type="button" class="admin-btn admin-btn--danger admin-btn--xs" disabled>批量删除（预留）</button></div>
            <div class="admin-table-wrap">
              <table class="admin-table w-full min-w-[1080px] text-sm text-left">
                <thead>
                  <tr class="admin-table-head-row">
                    <th class="font-bold px-3 py-3 border-r border-[#E5E7EB] text-gray-800 min-w-[160px]">邮箱</th>
                    <th class="font-bold px-3 py-3 border-r border-[#E5E7EB] text-gray-800 min-w-[120px]">用户ID</th>
                    <th class="font-bold px-3 py-3 border-r border-[#E5E7EB] text-gray-800 w-24">角色</th>
                    <th class="font-bold px-3 py-3 border-r border-[#E5E7EB] text-gray-800 whitespace-nowrap">创建时间</th>
                    <th class="font-bold px-3 py-3 border-r border-[#E5E7EB] text-gray-800 whitespace-nowrap min-w-[140px]">会员等级</th>
                    <th class="font-bold px-3 py-3 border-r border-[#E5E7EB] text-gray-800 whitespace-nowrap min-w-[140px]">会员状态</th>
                    <th class="font-bold px-3 py-3 border-r border-[#E5E7EB] text-gray-800 whitespace-nowrap min-w-[170px]">会员到期</th>
                    <th class="font-bold px-3 py-3 border-r border-[#E5E7EB] text-gray-800 whitespace-nowrap min-w-[170px]">会员开通</th>
                    <th class="font-bold px-3 py-3 border-r border-[#E5E7EB] text-gray-800 whitespace-nowrap">历史记录数</th>
                    <th class="font-bold px-3 py-3 border-r border-[#E5E7EB] text-gray-800 whitespace-nowrap">收藏数</th>
                    <th class="font-bold px-3 py-3 text-gray-800 whitespace-nowrap min-w-[200px]">操作</th>
                  </tr>
                </thead>
                <tbody class="app-tbody-enter">
                  <tr
                    v-for="user in pagedUsers"
                    :key="user.id"
                    class="admin-table-row"
                  >
                    <td class="px-3 py-2 border-r border-[#E5E7EB] align-top">
                      <span class="table-cell-ellipsis text-gray-700" :title="user.email || '未知邮箱'">
                        {{ user.email || '未知邮箱' }}
                      </span>
                    </td>
                    <td class="px-3 py-2 border-r border-[#E5E7EB] align-top">
                      <span class="table-cell-ellipsis font-mono text-xs text-gray-700" :title="String(user.user_id || '')">
                        {{ user.user_id || '未知用户' }}
                      </span>
                    </td>
                    <td class="px-3 py-2 border-r border-[#E5E7EB] align-top font-medium text-gray-800">
                      <span :title="String(user.role || '')">{{ roleLabelCN(normalizeRole(user.role)) }}</span>
                      <span class="block text-xs font-normal text-gray-500 font-mono mt-0.5">
                        {{ normalizeRole(user.role) }}
                      </span>
                    </td>
                    <td class="px-3 py-2 border-r border-[#E5E7EB] align-top text-gray-600 whitespace-nowrap">
                      {{ formatTime(user.created_at) }}
                    </td>
                    <td class="px-3 py-2 border-r border-[#E5E7EB] align-top text-gray-700 whitespace-nowrap">
                      {{ user.membership_tier || '—' }}
                    </td>
                    <td class="px-3 py-2 border-r border-[#E5E7EB] align-top text-gray-700 whitespace-nowrap">
                      {{ user.membership_status || '未开通' }}
                    </td>
                    <td class="px-3 py-2 border-r border-[#E5E7EB] align-top text-gray-700 whitespace-nowrap">
                      {{ user.membership_ended_at ? formatTime(user.membership_ended_at) : '—' }}
                    </td>
                    <td class="px-3 py-2 border-r border-[#E5E7EB] align-top text-gray-700 whitespace-nowrap">
                      {{ user.membership_started_at ? formatTime(user.membership_started_at) : '—' }}
                    </td>
                    <td class="px-3 py-2 border-r border-[#E5E7EB] align-top text-gray-700">
                      {{ userHistoryCountMap.get(String(user.user_id || '')) || 0 }}
                    </td>
                    <td class="px-3 py-2 border-r border-[#E5E7EB] align-top text-gray-700">
                      {{ userFavoriteCountMap.get(String(user.user_id || '')) || 0 }}
                    </td>
                    <td class="px-3 py-2 align-top">
                      <div class="mb-2">
                        <button
                          type="button"
                          class="admin-btn admin-btn--secondary admin-btn--xs"
                          @click="viewUserDetail(user)"
                        >
                          查看详情
                        </button>
                      </div>
                      <template v-if="isSuperAdmin">
                        <select
                          :key="`${user.id}-${user.role}`"
                          :value="normalizeRole(user.role)"
                          :disabled="isLastSuperAdmin(user)"
                          class="admin-select admin-select--compact max-w-[11rem] font-mono disabled:cursor-not-allowed"
                          @change="onUserRoleChange(user, $event)"
                        >
                          <option value="viewer">viewer</option>
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
            <div class="admin-pagination">
              <span>共 {{ filteredUsers.length }} 条，每页 {{ PAGE_SIZE }} 条</span>
              <div class="flex flex-wrap items-center gap-2">
                <button
                  type="button"
                  :disabled="userPage <= 1"
                  @click="userPage--"
                  class="admin-btn admin-btn--secondary admin-btn--xs disabled:opacity-40"
                >
                  上一页
                </button>
                <span class="font-medium px-1">第 {{ userPage }} / {{ userTotalPages }} 页</span>
                <button
                  type="button"
                  :disabled="userPage >= userTotalPages"
                  @click="userPage++"
                  class="admin-btn admin-btn--secondary admin-btn--xs disabled:opacity-40"
                >
                  下一页
                </button>
              </div>
            </div>
            </template>
          </div>

            <div v-if="activeTab === 'logs'" class="app-enter-up admin-content-card admin-module-card">
              <div class="admin-module-head">
                <div>
                  <h2 class="admin-module-title">操作日志</h2>
                  <p class="admin-module-desc">审计后台关键操作，便于追踪与复盘。</p>
                </div>
              </div>
              <div class="admin-filter-wrap">
                <div class="admin-filter-row">
                  <div class="admin-filter-fields">
                    <div class="flex-1 min-w-[220px]">
                      <label class="admin-form-label">操作人</label>
                      <AppSearch
                        v-model="logOperatorKeyword"
                        placeholder="邮箱 / 用户名"
                        input-class="admin-input"
                      />
                    </div>
                    <div class="admin-filter-col">
                      <label class="admin-form-label">操作类型</label>
                      <select v-model="logActionFilter" class="admin-select">
                        <option value="all">全部</option>
                        <option v-for="item in logActionOptions" :key="item" :value="item">
                          {{ formatLogActionType(item) }}
                        </option>
                      </select>
                    </div>
                    <div class="admin-filter-col">
                      <label class="admin-form-label">目标类型</label>
                      <select v-model="logTargetFilter" class="admin-select">
                        <option value="all">全部</option>
                        <option v-for="item in logTargetOptions" :key="item" :value="item">
                          {{ formatLogTargetType(item) }}
                        </option>
                      </select>
                    </div>
                    <div class="admin-filter-col">
                      <label class="admin-form-label">时间范围</label>
                      <select v-model="logTimeFilter" class="admin-select">
                        <option value="all">全部</option>
                        <option value="today">今日</option>
                        <option value="week">最近7天</option>
                        <option value="month">最近30天</option>
                        <option value="custom">自定义</option>
                      </select>
                    </div>
                    <div v-if="logTimeFilter === 'custom'" class="admin-filter-col">
                      <label class="admin-form-label">开始日期</label>
                      <input v-model="logDateStart" type="date" class="admin-input" />
                    </div>
                    <div v-if="logTimeFilter === 'custom'" class="admin-filter-col">
                      <label class="admin-form-label">结束日期</label>
                      <input v-model="logDateEnd" type="date" class="admin-input" />
                    </div>
                  </div>
                  <div class="admin-filter-actions">
                    <button type="button" class="admin-btn admin-btn--secondary" @click="resetLogFilters">
                      重置筛选
                    </button>
                    <button type="button" class="admin-btn admin-btn--secondary" @click="refreshData">
                      刷新
                    </button>
                    <button type="button" class="admin-btn admin-btn--primary" @click="exportFilteredLogs">
                      导出筛选结果
                    </button>
                  </div>
                </div>
              </div>

              <div v-if="logList.length === 0" class="admin-state admin-state--empty">
                暂无操作日志
              </div>
              <div v-else-if="filteredLogs.length === 0" class="admin-state admin-state--empty">
                当前筛选条件下暂无日志
              </div>

              <template v-else>
                <div class="admin-bulk-bar"><span>已选 0 项</span><button type="button" class="admin-btn admin-btn--danger admin-btn--xs" disabled>批量操作（预留）</button></div>
                <div class="admin-table-wrap">
                  <table class="admin-table w-full min-w-[960px] text-sm text-left">
                    <thead>
                      <tr class="admin-table-head-row">
                        <th class="font-bold px-3 py-3 border-r border-[#E5E7EB] text-gray-800 min-w-[140px]">操作人</th>
                        <th class="font-bold px-3 py-3 border-r border-[#E5E7EB] text-gray-800 whitespace-nowrap w-32">操作类型</th>
                        <th class="font-bold px-3 py-3 border-r border-[#E5E7EB] text-gray-800 whitespace-nowrap w-28">目标类型</th>
                        <th class="font-bold px-3 py-3 border-r border-[#E5E7EB] text-gray-800 min-w-[100px]">目标ID</th>
                        <th class="font-bold px-3 py-3 border-r border-[#E5E7EB] text-gray-800 min-w-[180px]">详情</th>
                        <th class="font-bold px-3 py-3 text-gray-800 whitespace-nowrap">操作时间</th>
                      </tr>
                    </thead>
                    <tbody class="app-tbody-enter">
                      <tr
                        v-for="row in pagedLogs"
                        :key="row.id"
                        class="admin-table-row"
                      >
                        <td class="px-3 py-2 border-r border-[#E5E7EB] align-top">
                          <span class="table-cell-ellipsis text-gray-700" :title="row.operator_email || ''">
                            {{ row.operator_email || '—' }}
                          </span>
                        </td>
                        <td class="px-3 py-2 border-r border-[#E5E7EB] align-top text-gray-800 font-medium whitespace-nowrap">
                          {{ formatLogActionType(row.action_type) }}
                        </td>
                        <td class="px-3 py-2 border-r border-[#E5E7EB] align-top text-gray-700 whitespace-nowrap">
                          {{ formatLogTargetType(row.target_type) }}
                        </td>
                        <td class="px-3 py-2 border-r border-[#E5E7EB] align-top">
                          <span class="table-cell-ellipsis font-mono text-xs text-gray-700" :title="row.target_id || ''">
                            {{ row.target_id ?? '—' }}
                          </span>
                        </td>
                        <td class="px-3 py-2 border-r border-[#E5E7EB] align-top">
                          <span class="block max-w-md text-gray-700 break-words" :title="formatLogDetail(row.detail)">
                            {{ formatLogDetailSummary(row.detail) }}
                          </span>
                        </td>
                        <td class="px-3 py-2 align-top text-gray-600 whitespace-nowrap">
                          {{ formatTime(row.created_at) }}
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
                <div class="admin-pagination">
                  <span>共 {{ filteredLogs.length }} 条，每页 {{ PAGE_SIZE }} 条</span>
                  <div class="flex flex-wrap items-center gap-2">
                    <button
                      type="button"
                      :disabled="logPage <= 1"
                      @click="logPage--"
                      class="admin-btn admin-btn--secondary admin-btn--xs disabled:opacity-40"
                    >
                      上一页
                    </button>
                    <span class="font-medium px-1">第 {{ logPage }} / {{ logTotalPages }} 页</span>
                    <button
                      type="button"
                      :disabled="logPage >= logTotalPages"
                      @click="logPage++"
                      class="admin-btn admin-btn--secondary admin-btn--xs disabled:opacity-40"
                    >
                      下一页
                    </button>
                  </div>
                </div>
              </template>
            </div>

            <div
              v-if="activeTab === 'rolePermission'"
              class="app-enter-up admin-content-card admin-module-card"
            >
              <div class="admin-module-head">
                <div>
                  <h2 class="admin-module-title">{{ activeTabLabel }}</h2>
                  <p class="admin-module-desc">该模块正在升级中，当前先提供导航占位，后续按规划逐步接入。</p>
                </div>
              </div>
              <div class="admin-state admin-state--empty">
                <div class="space-y-2">
                  <p>该二级菜单已接入导航结构。</p>
                  <p class="text-xs text-[#6B7280]">当前阶段为占位页，不影响现有后台数据与权限逻辑。</p>
                </div>
              </div>
            </div>

            <div v-if="activeTab === 'galleryData'" class="app-enter-up admin-content-card admin-module-card">
              <div class="admin-module-head">
                <div>
                  <h2 class="admin-module-title">图鉴数据</h2>
                  <p class="admin-module-desc">管理“图鉴”内容记录，支持筛选、详情和删除。</p>
                </div>
              </div>
              <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-4">
                <div class="admin-mini-metric"><span>记录总数</span><strong>{{ galleryTotalCount }}</strong></div>
                <div class="admin-mini-metric"><span>今日新增</span><strong>{{ galleryTodayCount }}</strong></div>
                <div class="admin-mini-metric"><span>最常见分类</span><strong>{{ galleryMostUsedCategory }}</strong></div>
              </div>
              <div class="admin-filter-wrap">
                <div class="admin-filter-row">
                  <div class="admin-filter-fields">
                    <div class="flex-1 min-w-[220px]">
                      <label class="admin-form-label">标题</label>
                      <AppSearch v-model="galleryTitleKeyword" placeholder="按标题搜索" input-class="admin-input" />
                    </div>
                    <div class="admin-filter-col">
                      <label class="admin-form-label">分类</label>
                      <select v-model="galleryCategoryFilter" class="admin-select">
                        <option value="">全部</option>
                        <option v-for="c in galleryCategoryOptions" :key="c" :value="c">{{ c }}</option>
                      </select>
                    </div>
                    <div class="flex-1 min-w-[220px]">
                      <label class="admin-form-label">用户</label>
                      <AppSearch v-model="galleryUserKeyword" placeholder="user_id / 邮箱（旧数据可能为空）" input-class="admin-input" />
                    </div>
                    <div class="admin-filter-col">
                      <label class="admin-form-label">时间</label>
                      <select v-model="galleryTimeFilter" class="admin-select">
                        <option value="all">全部</option>
                        <option value="today">今日</option>
                        <option value="week">最近7天</option>
                        <option value="month">最近30天</option>
                      </select>
                    </div>
                  </div>
                  <div class="admin-filter-actions">
                    <button type="button" class="admin-btn admin-btn--secondary" @click="refreshData">刷新</button>
                    <button
                      type="button"
                      class="admin-btn admin-btn--secondary"
                      @click="galleryTitleKeyword = ''; galleryCategoryFilter = ''; galleryUserKeyword = ''; galleryTimeFilter = 'all'"
                    >
                      重置筛选
                    </button>
                  </div>
                </div>
              </div>
              <div v-if="galleryList.length === 0" class="admin-state admin-state--empty">暂无图鉴记录</div>
              <div v-else-if="filteredGalleries.length === 0" class="admin-state admin-state--empty">没有符合筛选条件的记录</div>
              <template v-else>
                <div class="admin-table-wrap">
                  <table class="admin-table w-full min-w-[1020px] text-sm text-left">
                    <thead>
                      <tr class="admin-table-head-row">
                        <th class="font-bold px-3 py-3 border-r border-[#E5E7EB] text-gray-800">标题</th>
                        <th class="font-bold px-3 py-3 border-r border-[#E5E7EB] text-gray-800 whitespace-nowrap">分类</th>
                        <th class="font-bold px-3 py-3 border-r border-[#E5E7EB] text-gray-800 min-w-[200px]">用户</th>
                        <th class="font-bold px-3 py-3 border-r border-[#E5E7EB] text-gray-800 whitespace-nowrap">创建时间</th>
                        <th class="font-bold px-3 py-3 text-gray-800 whitespace-nowrap">操作</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="item in pagedGalleries" :key="item.id" class="admin-table-row">
                        <td class="px-3 py-2 border-r border-[#E5E7EB] align-top text-gray-800">{{ item.recipeName || '未命名' }}</td>
                        <td class="px-3 py-2 border-r border-[#E5E7EB] align-top text-gray-700">{{ item.cuisine || '未分类' }}</td>
                        <td class="px-3 py-2 border-r border-[#E5E7EB] align-top text-gray-700">
                          <span class="table-cell-ellipsis" :title="`${item.userEmail || '未知邮箱'} (${item.userId || '未知用户'})`">
                            {{ item.userEmail || '未知邮箱' }}（{{ item.userId || '未知用户' }}）
                          </span>
                        </td>
                        <td class="px-3 py-2 border-r border-[#E5E7EB] align-top text-gray-600 whitespace-nowrap">{{ formatTime(item.generatedAt) }}</td>
                        <td class="px-3 py-2 align-top">
                          <div class="flex flex-wrap gap-2">
                            <button type="button" class="admin-btn admin-btn--secondary admin-btn--xs" @click="viewGalleryDetail(item)">查看</button>
                            <button v-if="canDeleteData" type="button" class="admin-btn admin-btn--danger admin-btn--xs" @click="deleteGalleryItem(item)">删除</button>
                          </div>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
                <div class="admin-pagination">
                  <span>共 {{ filteredGalleries.length }} 条，每页 {{ PAGE_SIZE }} 条</span>
                  <div class="flex flex-wrap items-center gap-2">
                    <button type="button" :disabled="galleryPage <= 1" @click="galleryPage--" class="admin-btn admin-btn--secondary admin-btn--xs disabled:opacity-40">上一页</button>
                    <span class="font-medium px-1">第 {{ galleryPage }} / {{ galleryTotalPages }} 页</span>
                    <button type="button" :disabled="galleryPage >= galleryTotalPages" @click="galleryPage++" class="admin-btn admin-btn--secondary admin-btn--xs disabled:opacity-40">下一页</button>
                  </div>
                </div>
              </template>
            </div>

            <div v-if="activeTab === 'sauceData'" class="app-enter-up admin-content-card admin-module-card">
              <div class="admin-module-head">
                <div>
                  <h2 class="admin-module-title">酱料大师数据</h2>
                  <p class="admin-module-desc">管理“酱料大师”结果数据，支持筛选、详情和删除。</p>
                </div>
              </div>
              <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-4">
                <div class="admin-mini-metric"><span>记录总数</span><strong>{{ sauceTotalCount }}</strong></div>
                <div class="admin-mini-metric"><span>今日新增</span><strong>{{ sauceTodayCount }}</strong></div>
                <div class="admin-mini-metric"><span>最常见分类</span><strong>{{ sauceMostUsedCategory }}</strong></div>
              </div>
              <div class="admin-filter-wrap">
                <div class="admin-filter-row">
                  <div class="admin-filter-fields">
                    <div class="flex-1 min-w-[220px]">
                      <label class="admin-form-label">标题</label>
                      <AppSearch v-model="sauceTitleKeyword" placeholder="按酱料名称/标题搜索" input-class="admin-input" />
                    </div>
                    <div class="admin-filter-col">
                      <label class="admin-form-label">分类</label>
                      <select v-model="sauceCategoryFilter" class="admin-select">
                        <option value="all">全部</option>
                        <option v-for="c in sauceCategoryOptions" :key="c" :value="c">{{ sauceCategoryLabel(c) }}</option>
                      </select>
                    </div>
                    <div class="flex-1 min-w-[220px]">
                      <label class="admin-form-label">用户</label>
                      <AppSearch v-model="sauceUserKeyword" placeholder="user_id / 邮箱" input-class="admin-input" />
                    </div>
                    <div class="admin-filter-col">
                      <label class="admin-form-label">时间</label>
                      <select v-model="sauceTimeFilter" class="admin-select">
                        <option value="all">全部</option>
                        <option value="today">今日</option>
                        <option value="week">最近7天</option>
                        <option value="month">最近30天</option>
                      </select>
                    </div>
                  </div>
                  <div class="admin-filter-actions">
                    <button type="button" class="admin-btn admin-btn--secondary" @click="refreshData">刷新</button>
                    <button
                      type="button"
                      class="admin-btn admin-btn--secondary"
                      @click="sauceTitleKeyword = ''; sauceUserKeyword = ''; sauceCategoryFilter = 'all'; sauceTimeFilter = 'all'"
                    >
                      重置筛选
                    </button>
                  </div>
                </div>
              </div>
              <div v-if="sauceList.length === 0" class="admin-state admin-state--empty">暂无酱料大师记录</div>
              <div v-else-if="filteredSauces.length === 0" class="admin-state admin-state--empty">没有符合筛选条件的记录</div>
              <template v-else>
                <div class="admin-table-wrap">
                  <table class="admin-table w-full min-w-[980px] text-sm text-left">
                    <thead>
                      <tr class="admin-table-head-row">
                        <th class="font-bold px-3 py-3 border-r border-[#E5E7EB] text-gray-800">酱料名称/标题</th>
                        <th class="font-bold px-3 py-3 border-r border-[#E5E7EB] text-gray-800 whitespace-nowrap">分类</th>
                        <th class="font-bold px-3 py-3 border-r border-[#E5E7EB] text-gray-800 min-w-[200px]">用户</th>
                        <th class="font-bold px-3 py-3 border-r border-[#E5E7EB] text-gray-800 whitespace-nowrap">创建时间</th>
                        <th class="font-bold px-3 py-3 text-gray-800 whitespace-nowrap">操作</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="item in pagedSauces" :key="item.id" class="admin-table-row">
                        <td class="px-3 py-2 border-r border-[#E5E7EB] align-top text-gray-800">{{ item.title || '未命名酱料' }}</td>
                        <td class="px-3 py-2 border-r border-[#E5E7EB] align-top text-gray-700">{{ sauceCategoryLabel(item.category) }}</td>
                        <td class="px-3 py-2 border-r border-[#E5E7EB] align-top text-gray-700">
                          <span class="table-cell-ellipsis" :title="`${item.user_email || '未知邮箱'} (${item.user_id || '未知用户'})`">
                            {{ item.user_email || '未知邮箱' }}（{{ item.user_id || '未知用户' }}）
                          </span>
                        </td>
                        <td class="px-3 py-2 border-r border-[#E5E7EB] align-top text-gray-600 whitespace-nowrap">{{ formatTime(item.created_at) }}</td>
                        <td class="px-3 py-2 align-top">
                          <div class="flex flex-wrap gap-2">
                            <button type="button" class="admin-btn admin-btn--secondary admin-btn--xs" @click="viewSauceDetail(item)">查看</button>
                            <button v-if="canDeleteData" type="button" class="admin-btn admin-btn--danger admin-btn--xs" @click="deleteSauceItem(item)">删除</button>
                          </div>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
                <div class="admin-pagination">
                  <span>共 {{ filteredSauces.length }} 条，每页 {{ PAGE_SIZE }} 条</span>
                  <div class="flex flex-wrap items-center gap-2">
                    <button type="button" :disabled="saucePage <= 1" @click="saucePage--" class="admin-btn admin-btn--secondary admin-btn--xs disabled:opacity-40">上一页</button>
                    <span class="font-medium px-1">第 {{ saucePage }} / {{ sauceTotalPages }} 页</span>
                    <button type="button" :disabled="saucePage >= sauceTotalPages" @click="saucePage++" class="admin-btn admin-btn--secondary admin-btn--xs disabled:opacity-40">下一页</button>
                  </div>
                </div>
              </template>
            </div>

            <div v-if="activeTab === 'fortuneData'" class="app-enter-up admin-content-card admin-module-card">
              <div class="admin-module-head">
                <div>
                  <h2 class="admin-module-title">玄学厨房数据</h2>
                  <p class="admin-module-desc">管理“玄学厨房”结果数据，支持筛选、详情和删除。</p>
                </div>
              </div>
              <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-4">
                <div class="admin-mini-metric"><span>记录总数</span><strong>{{ fortuneTotalCount }}</strong></div>
                <div class="admin-mini-metric"><span>今日新增</span><strong>{{ fortuneTodayCount }}</strong></div>
                <div class="admin-mini-metric"><span>最常见类型</span><strong>{{ fortuneMostUsedType }}</strong></div>
              </div>
              <div class="admin-filter-wrap">
                <div class="admin-filter-row">
                  <div class="admin-filter-fields">
                    <div class="flex-1 min-w-[220px]">
                      <label class="admin-form-label">标题</label>
                      <AppSearch v-model="fortuneTitleKeyword" placeholder="按标题搜索" input-class="admin-input" />
                    </div>
                    <div class="admin-filter-col">
                      <label class="admin-form-label">类型</label>
                      <select v-model="fortuneTypeFilter" class="admin-select">
                        <option value="all">全部</option>
                        <option v-for="t in fortuneTypeOptions" :key="t" :value="t">{{ fortuneTypeLabel(t) }}</option>
                      </select>
                    </div>
                    <div class="flex-1 min-w-[220px]">
                      <label class="admin-form-label">用户</label>
                      <AppSearch v-model="fortuneUserKeyword" placeholder="user_id / 邮箱" input-class="admin-input" />
                    </div>
                    <div class="admin-filter-col">
                      <label class="admin-form-label">时间</label>
                      <select v-model="fortuneTimeFilter" class="admin-select">
                        <option value="all">全部</option>
                        <option value="today">今日</option>
                        <option value="week">最近7天</option>
                        <option value="month">最近30天</option>
                      </select>
                    </div>
                  </div>
                  <div class="admin-filter-actions">
                    <button type="button" class="admin-btn admin-btn--secondary" @click="refreshData">刷新</button>
                    <button
                      type="button"
                      class="admin-btn admin-btn--secondary"
                      @click="fortuneTitleKeyword = ''; fortuneUserKeyword = ''; fortuneTypeFilter = 'all'; fortuneTimeFilter = 'all'"
                    >
                      重置筛选
                    </button>
                  </div>
                </div>
              </div>
              <div v-if="fortuneList.length === 0" class="admin-state admin-state--empty">暂无玄学厨房记录</div>
              <div v-else-if="filteredFortunes.length === 0" class="admin-state admin-state--empty">没有符合筛选条件的记录</div>
              <template v-else>
                <div class="admin-table-wrap">
                  <table class="admin-table w-full min-w-[980px] text-sm text-left">
                    <thead>
                      <tr class="admin-table-head-row">
                        <th class="font-bold px-3 py-3 border-r border-[#E5E7EB] text-gray-800">标题</th>
                        <th class="font-bold px-3 py-3 border-r border-[#E5E7EB] text-gray-800 whitespace-nowrap">类型</th>
                        <th class="font-bold px-3 py-3 border-r border-[#E5E7EB] text-gray-800 min-w-[200px]">用户</th>
                        <th class="font-bold px-3 py-3 border-r border-[#E5E7EB] text-gray-800 whitespace-nowrap">创建时间</th>
                        <th class="font-bold px-3 py-3 text-gray-800 whitespace-nowrap">操作</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="item in pagedFortunes" :key="item.id" class="admin-table-row">
                        <td class="px-3 py-2 border-r border-[#E5E7EB] align-top text-gray-800">{{ item.title || '未命名结果' }}</td>
                        <td class="px-3 py-2 border-r border-[#E5E7EB] align-top text-gray-700">{{ fortuneTypeLabel(item.fortune_type) }}</td>
                        <td class="px-3 py-2 border-r border-[#E5E7EB] align-top text-gray-700">
                          <span class="table-cell-ellipsis" :title="`${item.user_email || '未知邮箱'} (${item.user_id || '未知用户'})`">
                            {{ item.user_email || '未知邮箱' }}（{{ item.user_id || '未知用户' }}）
                          </span>
                        </td>
                        <td class="px-3 py-2 border-r border-[#E5E7EB] align-top text-gray-600 whitespace-nowrap">{{ formatTime(item.created_at) }}</td>
                        <td class="px-3 py-2 align-top">
                          <div class="flex flex-wrap gap-2">
                            <button type="button" class="admin-btn admin-btn--secondary admin-btn--xs" @click="viewFortuneDetail(item)">查看</button>
                            <button v-if="canDeleteData" type="button" class="admin-btn admin-btn--danger admin-btn--xs" @click="deleteFortuneItem(item)">删除</button>
                          </div>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
                <div class="admin-pagination">
                  <span>共 {{ filteredFortunes.length }} 条，每页 {{ PAGE_SIZE }} 条</span>
                  <div class="flex flex-wrap items-center gap-2">
                    <button type="button" :disabled="fortunePage <= 1" @click="fortunePage--" class="admin-btn admin-btn--secondary admin-btn--xs disabled:opacity-40">上一页</button>
                    <span class="font-medium px-1">第 {{ fortunePage }} / {{ fortuneTotalPages }} 页</span>
                    <button type="button" :disabled="fortunePage >= fortuneTotalPages" @click="fortunePage++" class="admin-btn admin-btn--secondary admin-btn--xs disabled:opacity-40">下一页</button>
                  </div>
                </div>
              </template>
            </div>

            <div v-if="activeTab === 'tableData'" class="app-enter-up admin-content-card admin-module-card">
              <div class="admin-module-head">
                <div>
                  <h2 class="admin-module-title">满汉全席数据</h2>
                  <p class="admin-module-desc">管理前端“满汉全席”生成结果，支持筛选、详情和删除。</p>
                </div>
              </div>
              <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-4">
                <div class="admin-mini-metric"><span>记录总数</span><strong>{{ tableMenuTotalCount }}</strong></div>
                <div class="admin-mini-metric"><span>今日新增</span><strong>{{ tableMenuTodayCount }}</strong></div>
                <div class="admin-mini-metric"><span>最近常见菜系</span><strong>{{ topTableMenuCuisine }}</strong></div>
              </div>
              <div class="admin-filter-wrap">
                <div class="admin-filter-row">
                  <div class="admin-filter-fields">
                    <div class="flex-1 min-w-[220px]">
                      <label class="admin-form-label">标题</label>
                      <AppSearch v-model="tableMenuTitleKeyword" placeholder="按标题搜索" input-class="admin-input" />
                    </div>
                    <div class="flex-1 min-w-[220px]">
                      <label class="admin-form-label">用户</label>
                      <AppSearch v-model="tableMenuUserKeyword" placeholder="user_id / 邮箱" input-class="admin-input" />
                    </div>
                    <div class="admin-filter-col">
                      <label class="admin-form-label">时间</label>
                      <select v-model="tableMenuTimeFilter" class="admin-select">
                        <option value="all">全部</option>
                        <option value="today">今日</option>
                        <option value="week">最近7天</option>
                        <option value="month">最近30天</option>
                      </select>
                    </div>
                  </div>
                  <div class="admin-filter-actions">
                    <button type="button" class="admin-btn admin-btn--secondary" @click="refreshData">刷新</button>
                    <button
                      type="button"
                      class="admin-btn admin-btn--secondary"
                      @click="tableMenuTitleKeyword = ''; tableMenuUserKeyword = ''; tableMenuTimeFilter = 'all'"
                    >
                      重置筛选
                    </button>
                  </div>
                </div>
              </div>
              <div v-if="tableMenuList.length === 0" class="admin-state admin-state--empty">暂无满汉全席记录</div>
              <div v-else-if="filteredTableMenus.length === 0" class="admin-state admin-state--empty">没有符合筛选条件的记录</div>
              <template v-else>
                <div class="admin-table-wrap">
                  <table class="admin-table w-full min-w-[980px] text-sm text-left">
                    <thead>
                      <tr class="admin-table-head-row">
                        <th class="font-bold px-3 py-3 border-r border-[#E5E7EB] text-gray-800">标题</th>
                        <th class="font-bold px-3 py-3 border-r border-[#E5E7EB] text-gray-800 min-w-[200px]">用户</th>
                        <th class="font-bold px-3 py-3 border-r border-[#E5E7EB] text-gray-800 whitespace-nowrap">菜品数量</th>
                        <th class="font-bold px-3 py-3 border-r border-[#E5E7EB] text-gray-800 whitespace-nowrap">创建时间</th>
                        <th class="font-bold px-3 py-3 text-gray-800 whitespace-nowrap">操作</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="item in pagedTableMenus" :key="item.id" class="admin-table-row">
                        <td class="px-3 py-2 border-r border-[#E5E7EB] align-top text-gray-800">{{ item.title || '未命名菜单' }}</td>
                        <td class="px-3 py-2 border-r border-[#E5E7EB] align-top text-gray-700">
                          <span class="table-cell-ellipsis" :title="`${item.user_email || '未知邮箱'} (${item.user_id || '未知用户'})`">
                            {{ item.user_email || '未知邮箱' }}（{{ item.user_id || '未知用户' }}）
                          </span>
                        </td>
                        <td class="px-3 py-2 border-r border-[#E5E7EB] align-top text-gray-700">{{ item.dishes_count || 0 }}</td>
                        <td class="px-3 py-2 border-r border-[#E5E7EB] align-top text-gray-600 whitespace-nowrap">{{ formatTime(item.created_at) }}</td>
                        <td class="px-3 py-2 align-top">
                          <div class="flex flex-wrap gap-2">
                            <button type="button" class="admin-btn admin-btn--secondary admin-btn--xs" @click="viewTableMenuDetail(item)">查看</button>
                            <button v-if="canDeleteData" type="button" class="admin-btn admin-btn--danger admin-btn--xs" @click="deleteTableMenuItem(item)">删除</button>
                          </div>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
                <div class="admin-pagination">
                  <span>共 {{ filteredTableMenus.length }} 条，每页 {{ PAGE_SIZE }} 条</span>
                  <div class="flex flex-wrap items-center gap-2">
                    <button type="button" :disabled="tableMenuPage <= 1" @click="tableMenuPage--" class="admin-btn admin-btn--secondary admin-btn--xs disabled:opacity-40">上一页</button>
                    <span class="font-medium px-1">第 {{ tableMenuPage }} / {{ tableMenuTotalPages }} 页</span>
                    <button type="button" :disabled="tableMenuPage >= tableMenuTotalPages" @click="tableMenuPage++" class="admin-btn admin-btn--secondary admin-btn--xs disabled:opacity-40">下一页</button>
                  </div>
                </div>
              </template>
            </div>

            <div v-if="activeTab === 'settings'" class="app-enter-up admin-content-card admin-module-card">
              <div class="admin-module-head">
                <div>
                  <h2 class="admin-module-title">系统设置</h2>
                  <p class="admin-module-desc">维护站点级功能开关与基础展示配置。</p>
                </div>
              </div>
              <p class="text-sm text-gray-600 mb-2">
                站点与功能开关（第一版）。当前保存在浏览器本地，后续可对接数据库而不改字段结构。
              </p>
              <p v-if="!isSuperAdmin" class="text-sm text-amber-800 font-medium mb-4">
                您为运营人员：可查看配置，仅超级管理员可编辑与保存。
              </p>

              <div class="admin-form-grid">
                <div>
                  <label class="admin-form-label" for="admin-site-name">站点名称</label>
                  <input
                    id="admin-site-name"
                    v-model="systemForm.site_name"
                    type="text"
                    placeholder="例如：今天吃什么"
                    :disabled="!isSuperAdmin"
                    class="admin-input disabled:cursor-not-allowed"
                  />
                </div>
                <div>
                  <label class="admin-form-label" for="admin-site-subtitle">站点副标题</label>
                  <input
                    id="admin-site-subtitle"
                    v-model="systemForm.site_subtitle"
                    type="text"
                    placeholder="一句话介绍"
                    :disabled="!isSuperAdmin"
                    class="admin-input disabled:cursor-not-allowed"
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
                class="admin-btn admin-btn--primary mt-6"
                @click="handleSaveSystemSettings"
              >
                保存系统设置
              </button>
              <p v-else class="mt-6 text-sm text-gray-500">保存按钮仅对超级管理员可见。</p>
            </div>

            <div v-if="activeTab === 'modelConfig'" class="app-enter-up admin-content-card admin-module-card">
              <div class="admin-module-head">
                <div>
                  <h2 class="admin-module-title">配置中心</h2>
                  <p class="admin-module-desc">按分组统一管理前端、运营、开关、文案与系统模型配置。</p>
                </div>
              </div>
              <p class="text-sm text-gray-600 mb-2">
                配置统一从配置中心读取，前端保持“优先读配置，空值走默认兜底”的策略，避免页面异常。
              </p>
              <p v-if="!isSuperAdmin" class="text-sm text-amber-800 font-medium mb-4">
                您为运营人员：可查看配置，仅超级管理员可编辑与保存。
              </p>

              <div class="mb-4 flex flex-wrap gap-2 rounded-[10px] border border-[#E5E7EB] bg-[#F8FAFC] p-2">
                <button type="button" class="admin-btn admin-btn--xs" :class="configCenterSection === 'frontend_basic' ? 'admin-btn--primary' : 'admin-btn--secondary'" @click="configCenterSection = 'frontend_basic'">前端基础配置</button>
                <button type="button" class="admin-btn admin-btn--xs" :class="configCenterSection === 'page_ops' ? 'admin-btn--primary' : 'admin-btn--secondary'" @click="configCenterSection = 'page_ops'">页面运营配置</button>
                <button type="button" class="admin-btn admin-btn--xs" :class="configCenterSection === 'feature_switch' ? 'admin-btn--primary' : 'admin-btn--secondary'" @click="configCenterSection = 'feature_switch'">功能开关配置</button>
                <button type="button" class="admin-btn admin-btn--xs" :class="configCenterSection === 'global_copy' ? 'admin-btn--primary' : 'admin-btn--secondary'" @click="configCenterSection = 'global_copy'">全局文案配置</button>
                <button type="button" class="admin-btn admin-btn--xs" :class="configCenterSection === 'system_model' ? 'admin-btn--primary' : 'admin-btn--secondary'" @click="configCenterSection = 'system_model'">系统 / 模型配置</button>
              </div>

              <div v-if="configCenterSection === 'frontend_basic'">
                <h3 class="text-sm font-semibold text-[#1F2937] mb-2">前端基础配置</h3>
                <p class="text-xs text-[#6B7280] mb-3">基础品牌信息和首页/广场核心标题。</p>
                <div class="admin-form-grid">
                  <div><label class="admin-form-label" for="admin-app-name">应用名称</label><input id="admin-app-name" v-model="frontendForm.app_name" type="text" placeholder="例如：饭否" :disabled="!isSuperAdmin" class="admin-input disabled:cursor-not-allowed" /></div>
                  <div><label class="admin-form-label" for="admin-home-title">首页主标题</label><input id="admin-home-title" v-model="frontendForm.home_title" type="text" placeholder="例如：饭否" :disabled="!isSuperAdmin" class="admin-input disabled:cursor-not-allowed" /></div>
                  <div><label class="admin-form-label" for="admin-home-subtitle">首页副标题</label><input id="admin-home-subtitle" v-model="frontendForm.home_subtitle" type="text" placeholder="一句首页说明文案" :disabled="!isSuperAdmin" class="admin-input disabled:cursor-not-allowed" /></div>
                  <div><label class="admin-form-label" for="admin-plaza-title">功能广场标题</label><input id="admin-plaza-title" v-model="frontendForm.plaza_title" type="text" placeholder="例如：功能广场" :disabled="!isSuperAdmin" class="admin-input disabled:cursor-not-allowed" /></div>
                  <div><label class="admin-form-label" for="admin-plaza-subtitle">功能广场副标题</label><input id="admin-plaza-subtitle" v-model="frontendForm.plaza_subtitle" type="text" placeholder="一句功能广场说明文案" :disabled="!isSuperAdmin" class="admin-input disabled:cursor-not-allowed" /></div>
                  <div><label class="admin-form-label" for="admin-profile-welcome-text">我的页欢迎语</label><input id="admin-profile-welcome-text" v-model="frontendForm.profile_welcome_text" type="text" placeholder="例如：你好呀" :disabled="!isSuperAdmin" class="admin-input disabled:cursor-not-allowed" /></div>
                </div>
                <button v-if="isSuperAdmin" type="button" class="admin-btn admin-btn--primary mt-6" @click="handleSaveFrontendConfig">保存当前分组</button>
              </div>

              <div v-if="configCenterSection === 'page_ops'">
                <h3 class="text-sm font-semibold text-[#1F2937] mb-2">页面运营配置</h3>
                <p class="text-xs text-[#6B7280] mb-3">首页运营位、功能广场入口、我的页面与收藏/历史页面配置。</p>
                <div class="admin-form-grid">
                  <div><label class="admin-form-label" for="admin-home-banner-title">首页 Banner 标题</label><input id="admin-home-banner-title" v-model="frontendForm.home_banner_title" type="text" :disabled="!isSuperAdmin" class="admin-input disabled:cursor-not-allowed" /></div>
                  <div><label class="admin-form-label" for="admin-home-banner-subtitle">首页 Banner 副标题</label><input id="admin-home-banner-subtitle" v-model="frontendForm.home_banner_subtitle" type="text" :disabled="!isSuperAdmin" class="admin-input disabled:cursor-not-allowed" /></div>
                  <div><label class="admin-form-label" for="admin-home-recommend-title">首页推荐区标题</label><input id="admin-home-recommend-title" v-model="frontendForm.home_recommend_title" type="text" :disabled="!isSuperAdmin" class="admin-input disabled:cursor-not-allowed" /></div>
                  <div><label class="admin-form-label" for="admin-home-recommend-subtitle">首页推荐区副标题</label><input id="admin-home-recommend-subtitle" v-model="frontendForm.home_recommend_subtitle" type="text" :disabled="!isSuperAdmin" class="admin-input disabled:cursor-not-allowed" /></div>
                  <div><label class="admin-form-label" for="admin-home-hot-title">首页热门玩法区标题</label><input id="admin-home-hot-title" v-model="frontendForm.home_hot_title" type="text" :disabled="!isSuperAdmin" class="admin-input disabled:cursor-not-allowed" /></div>
                  <div><label class="admin-form-label" for="admin-home-hot-subtitle">首页热门玩法区副标题</label><input id="admin-home-hot-subtitle" v-model="frontendForm.home_hot_subtitle" type="text" :disabled="!isSuperAdmin" class="admin-input disabled:cursor-not-allowed" /></div>
                </div>
                <div class="mt-6">
                  <h4 class="text-sm font-semibold text-[#1F2937] mb-2">功能广场配置</h4>
                  <div class="admin-table-wrap">
                    <table class="admin-table w-full min-w-[980px] text-sm text-left">
                      <thead><tr class="admin-table-head-row"><th class="px-3 py-3 border-r border-[#E5E7EB]">key</th><th class="px-3 py-3 border-r border-[#E5E7EB]">标题</th><th class="px-3 py-3 border-r border-[#E5E7EB]">副标题</th><th class="px-3 py-3 border-r border-[#E5E7EB]">icon</th><th class="px-3 py-3 border-r border-[#E5E7EB]">route</th><th class="px-3 py-3 border-r border-[#E5E7EB]">启用</th><th class="px-3 py-3">排序</th></tr></thead>
                      <tbody>
                        <tr v-for="entry in frontendForm.plaza_entries" :key="entry.key" class="admin-table-row">
                          <td class="px-3 py-2 border-r border-[#E5E7EB] font-mono text-xs text-[#6B7280]">{{ entry.key }}</td>
                          <td class="px-3 py-2 border-r border-[#E5E7EB]"><input v-model="entry.title" type="text" class="admin-input" :disabled="!isSuperAdmin" /></td>
                          <td class="px-3 py-2 border-r border-[#E5E7EB]"><input v-model="entry.subtitle" type="text" class="admin-input" :disabled="!isSuperAdmin" /></td>
                          <td class="px-3 py-2 border-r border-[#E5E7EB] font-mono text-xs text-[#6B7280]">{{ entry.icon }}</td>
                          <td class="px-3 py-2 border-r border-[#E5E7EB] font-mono text-xs text-[#6B7280]">{{ entry.route }}</td>
                          <td class="px-3 py-2 border-r border-[#E5E7EB]"><input v-model="entry.enabled" type="checkbox" class="h-4 w-4 accent-[#4F7BFF]" :disabled="!isSuperAdmin" /></td>
                          <td class="px-3 py-2"><input v-model.number="entry.sort_order" type="number" step="1" class="admin-input max-w-[7rem]" :disabled="!isSuperAdmin" /></td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>
                <div class="mt-6">
                  <h4 class="text-sm font-semibold text-[#1F2937] mb-2">我的页面 / 收藏历史页面</h4>
                  <div class="admin-form-grid">
                    <div><label class="admin-form-label" for="admin-profile-title">我的页顶部标题</label><input id="admin-profile-title" v-model="frontendForm.profile_title" type="text" :disabled="!isSuperAdmin" class="admin-input disabled:cursor-not-allowed" /></div>
                    <div><label class="admin-form-label" for="admin-profile-subtitle">我的页顶部副标题</label><input id="admin-profile-subtitle" v-model="frontendForm.profile_subtitle" type="text" :disabled="!isSuperAdmin" class="admin-input disabled:cursor-not-allowed" /></div>
                    <div><label class="admin-form-label" for="admin-profile-guest-title">访客态标题</label><input id="admin-profile-guest-title" v-model="frontendForm.profile_guest_title" type="text" :disabled="!isSuperAdmin" class="admin-input disabled:cursor-not-allowed" /></div>
                    <div><label class="admin-form-label" for="admin-profile-guest-subtitle">访客态副标题</label><input id="admin-profile-guest-subtitle" v-model="frontendForm.profile_guest_subtitle" type="text" :disabled="!isSuperAdmin" class="admin-input disabled:cursor-not-allowed" /></div>
                    <div><label class="admin-form-label" for="admin-profile-admin-tip">管理员提示文案</label><input id="admin-profile-admin-tip" v-model="frontendForm.profile_admin_tip" type="text" :disabled="!isSuperAdmin" class="admin-input disabled:cursor-not-allowed" /></div>
                    <div><label class="admin-form-label" for="admin-favorites-title">收藏页标题</label><input id="admin-favorites-title" v-model="frontendForm.favorites_title" type="text" :disabled="!isSuperAdmin" class="admin-input disabled:cursor-not-allowed" /></div>
                    <div><label class="admin-form-label" for="admin-favorites-subtitle">收藏页副标题</label><input id="admin-favorites-subtitle" v-model="frontendForm.favorites_subtitle" type="text" :disabled="!isSuperAdmin" class="admin-input disabled:cursor-not-allowed" /></div>
                    <div><label class="admin-form-label" for="admin-favorites-empty-title">收藏空状态标题</label><input id="admin-favorites-empty-title" v-model="frontendForm.favorites_empty_title" type="text" :disabled="!isSuperAdmin" class="admin-input disabled:cursor-not-allowed" /></div>
                    <div><label class="admin-form-label" for="admin-favorites-empty-subtitle">收藏空状态副标题</label><input id="admin-favorites-empty-subtitle" v-model="frontendForm.favorites_empty_subtitle" type="text" :disabled="!isSuperAdmin" class="admin-input disabled:cursor-not-allowed" /></div>
                    <div><label class="admin-form-label" for="admin-favorites-empty-button">收藏空状态按钮文案</label><input id="admin-favorites-empty-button" v-model="frontendForm.favorites_empty_button_text" type="text" :disabled="!isSuperAdmin" class="admin-input disabled:cursor-not-allowed" /></div>
                    <div><label class="admin-form-label" for="admin-histories-title">历史页标题</label><input id="admin-histories-title" v-model="frontendForm.histories_title" type="text" :disabled="!isSuperAdmin" class="admin-input disabled:cursor-not-allowed" /></div>
                    <div><label class="admin-form-label" for="admin-histories-subtitle">历史页副标题</label><input id="admin-histories-subtitle" v-model="frontendForm.histories_subtitle" type="text" :disabled="!isSuperAdmin" class="admin-input disabled:cursor-not-allowed" /></div>
                    <div><label class="admin-form-label" for="admin-histories-empty-title">历史空状态标题</label><input id="admin-histories-empty-title" v-model="frontendForm.histories_empty_title" type="text" :disabled="!isSuperAdmin" class="admin-input disabled:cursor-not-allowed" /></div>
                    <div><label class="admin-form-label" for="admin-histories-empty-subtitle">历史空状态副标题</label><input id="admin-histories-empty-subtitle" v-model="frontendForm.histories_empty_subtitle" type="text" :disabled="!isSuperAdmin" class="admin-input disabled:cursor-not-allowed" /></div>
                    <div><label class="admin-form-label" for="admin-histories-empty-button">历史空状态按钮文案</label><input id="admin-histories-empty-button" v-model="frontendForm.histories_empty_button_text" type="text" :disabled="!isSuperAdmin" class="admin-input disabled:cursor-not-allowed" /></div>
                    <AppSwitch id="admin-show-recent-favorites" v-model="frontendForm.show_recent_favorites" label="显示我的页最近收藏区块" :disabled="!isSuperAdmin" />
                    <AppSwitch id="admin-show-recent-histories" v-model="frontendForm.show_recent_histories" label="显示我的页最近历史区块" :disabled="!isSuperAdmin" />
                  </div>
                </div>
                <button v-if="isSuperAdmin" type="button" class="admin-btn admin-btn--primary mt-6" @click="handleSaveFrontendConfig">保存当前分组</button>
              </div>

              <div v-if="configCenterSection === 'feature_switch'">
                <h3 class="text-sm font-semibold text-[#1F2937] mb-2">功能开关配置</h3>
                <p class="text-xs text-[#6B7280] mb-3">统一维护首页运营位和功能模块开关。</p>
                <div class="admin-form-grid">
                  <AppSwitch id="admin-show-home-banner" v-model="frontendForm.show_home_banner" label="显示首页 Banner" :disabled="!isSuperAdmin" />
                  <AppSwitch id="admin-show-home-recommend" v-model="frontendForm.show_home_recommend" label="显示首页推荐区" :disabled="!isSuperAdmin" />
                  <AppSwitch id="admin-show-home-hot" v-model="frontendForm.show_home_hot" label="显示首页热门玩法区" :disabled="!isSuperAdmin" />
                  <AppSwitch id="admin-front-enable-fortune" v-model="frontendForm.enable_fortune" label="启用玄学厨房" :disabled="!isSuperAdmin" />
                  <AppSwitch id="admin-front-enable-gallery" v-model="frontendForm.enable_gallery" label="启用图鉴" :disabled="!isSuperAdmin" />
                  <AppSwitch id="admin-front-enable-sauce" v-model="frontendForm.enable_sauce" label="启用酱料设计" :disabled="!isSuperAdmin" />
                  <AppSwitch id="admin-front-enable-table-design" v-model="frontendForm.enable_table_design" label="启用一桌好菜" :disabled="!isSuperAdmin" />
                  <AppSwitch id="admin-show-profile-favorites" v-model="frontendForm.show_profile_favorites" label="显示我的收藏入口" :disabled="!isSuperAdmin" />
                  <AppSwitch id="admin-show-profile-histories" v-model="frontendForm.show_profile_histories" label="显示我的历史入口" :disabled="!isSuperAdmin" />
                  <AppSwitch id="admin-show-profile-settings" v-model="frontendForm.show_profile_settings" label="显示设置入口" :disabled="!isSuperAdmin" />
                  <AppSwitch id="admin-show-profile-admin-entry" v-model="frontendForm.show_profile_admin_entry" label="显示后台入口（仍受权限控制）" :disabled="!isSuperAdmin" />
                </div>
                <button v-if="isSuperAdmin" type="button" class="admin-btn admin-btn--primary mt-6" @click="handleSaveFrontendConfig">保存当前分组</button>
              </div>

              <div v-if="configCenterSection === 'global_copy'">
                <h3 class="text-sm font-semibold text-[#1F2937] mb-2">全局文案配置</h3>
                <p class="text-xs text-[#6B7280] mb-3">统一维护登录引导、Toast 和空状态文案。</p>
                <div class="admin-form-grid">
                  <div><label class="admin-form-label" for="admin-login-prompt-title">登录引导标题</label><input id="admin-login-prompt-title" v-model="frontendForm.login_prompt_title" type="text" :disabled="!isSuperAdmin" class="admin-input disabled:cursor-not-allowed" /></div>
                  <div><label class="admin-form-label" for="admin-login-prompt-subtitle">登录引导副标题</label><input id="admin-login-prompt-subtitle" v-model="frontendForm.login_prompt_subtitle" type="text" :disabled="!isSuperAdmin" class="admin-input disabled:cursor-not-allowed" /></div>
                  <div><label class="admin-form-label" for="admin-login-button-text">登录按钮文案</label><input id="admin-login-button-text" v-model="frontendForm.login_button_text" type="text" :disabled="!isSuperAdmin" class="admin-input disabled:cursor-not-allowed" /></div>
                  <div><label class="admin-form-label" for="admin-toast-favorite-success">收藏成功 Toast</label><input id="admin-toast-favorite-success" v-model="frontendForm.toast_favorite_success" type="text" :disabled="!isSuperAdmin" class="admin-input disabled:cursor-not-allowed" /></div>
                  <div><label class="admin-form-label" for="admin-toast-favorite-cancel">取消收藏 Toast</label><input id="admin-toast-favorite-cancel" v-model="frontendForm.toast_favorite_cancel" type="text" :disabled="!isSuperAdmin" class="admin-input disabled:cursor-not-allowed" /></div>
                  <div><label class="admin-form-label" for="admin-toast-history-deleted">删除历史 Toast</label><input id="admin-toast-history-deleted" v-model="frontendForm.toast_history_deleted" type="text" :disabled="!isSuperAdmin" class="admin-input disabled:cursor-not-allowed" /></div>
                  <div><label class="admin-form-label" for="admin-toast-favorite-deleted">删除收藏 Toast</label><input id="admin-toast-favorite-deleted" v-model="frontendForm.toast_favorite_deleted" type="text" :disabled="!isSuperAdmin" class="admin-input disabled:cursor-not-allowed" /></div>
                  <div><label class="admin-form-label" for="admin-toast-save-success">通用保存成功 Toast</label><input id="admin-toast-save-success" v-model="frontendForm.toast_save_success" type="text" :disabled="!isSuperAdmin" class="admin-input disabled:cursor-not-allowed" /></div>
                  <div><label class="admin-form-label" for="admin-toast-save-failed">通用保存失败 Toast</label><input id="admin-toast-save-failed" v-model="frontendForm.toast_save_failed" type="text" :disabled="!isSuperAdmin" class="admin-input disabled:cursor-not-allowed" /></div>
                  <div><label class="admin-form-label" for="admin-toast-load-failed">通用加载失败文案</label><input id="admin-toast-load-failed" v-model="frontendForm.toast_load_failed" type="text" :disabled="!isSuperAdmin" class="admin-input disabled:cursor-not-allowed" /></div>
                  <div><label class="admin-form-label" for="admin-common-empty-title">通用空状态标题</label><input id="admin-common-empty-title" v-model="frontendForm.common_empty_title" type="text" :disabled="!isSuperAdmin" class="admin-input disabled:cursor-not-allowed" /></div>
                  <div><label class="admin-form-label" for="admin-common-empty-subtitle">通用空状态副标题</label><input id="admin-common-empty-subtitle" v-model="frontendForm.common_empty_subtitle" type="text" :disabled="!isSuperAdmin" class="admin-input disabled:cursor-not-allowed" /></div>
                  <div><label class="admin-form-label" for="admin-common-empty-button-text">通用空状态按钮文案</label><input id="admin-common-empty-button-text" v-model="frontendForm.common_empty_button_text" type="text" :disabled="!isSuperAdmin" class="admin-input disabled:cursor-not-allowed" /></div>
                </div>
                <button v-if="isSuperAdmin" type="button" class="admin-btn admin-btn--primary mt-6" @click="handleSaveFrontendConfig">保存当前分组</button>
              </div>

              <div v-if="configCenterSection === 'system_model'">
                <h3 class="text-sm font-semibold text-[#1F2937] mb-2">系统 / 模型配置</h3>
                <p class="text-xs text-[#6B7280] mb-3">系统设置与模型参数统一收口，便于排查和维护。</p>
                <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
                  <section class="rounded-[10px] border border-[#E5E7EB] bg-white p-4">
                    <h4 class="text-sm font-semibold text-[#1F2937] mb-2">系统设置</h4>
                    <p class="text-xs text-[#6B7280] mb-3">基础站点信息与核心功能开关，影响全局展示行为。</p>
                    <div class="admin-form-grid">
                      <div>
                        <label class="admin-form-label" for="admin-site-name">站点名称</label>
                        <input id="admin-site-name" v-model="systemForm.site_name" type="text" :disabled="!isSuperAdmin" class="admin-input disabled:cursor-not-allowed" />
                        <p class="mt-1 text-xs text-[#6B7280]">建议 2-20 字，用于后台与前台品牌展示。</p>
                      </div>
                      <div>
                        <label class="admin-form-label" for="admin-site-subtitle">站点副标题</label>
                        <input id="admin-site-subtitle" v-model="systemForm.site_subtitle" type="text" :disabled="!isSuperAdmin" class="admin-input disabled:cursor-not-allowed" />
                        <p class="mt-1 text-xs text-[#6B7280]">一句话描述产品定位，建议不超过 60 字。</p>
                      </div>
                      <AppSwitch id="admin-enable-gallery" v-model="systemForm.enable_gallery" label="启用图鉴" :disabled="!isSuperAdmin" />
                      <AppSwitch id="admin-enable-fortune" v-model="systemForm.enable_fortune" label="启用玄学厨房" :disabled="!isSuperAdmin" />
                    </div>
                    <button v-if="isSuperAdmin" type="button" class="admin-btn admin-btn--primary mt-4" @click="handleSaveSystemSettings">保存系统设置</button>
                  </section>
                  <section class="rounded-[10px] border border-[#E5E7EB] bg-white p-4">
                    <h4 class="text-sm font-semibold text-[#1F2937] mb-2">模型配置</h4>
                    <p class="text-xs text-[#6B7280] mb-3">用于文本生成能力，保存前会进行基础校验，可执行连接测试。</p>
                    <div class="admin-form-grid">
                      <div>
                        <label class="admin-form-label" for="admin-model-name">model_name</label>
                        <input id="admin-model-name" v-model="modelForm.model_name" type="text" placeholder="如 gpt-4o-mini" :disabled="!isSuperAdmin" class="admin-input disabled:cursor-not-allowed" />
                        <p class="mt-1 text-xs text-[#6B7280]">必填。对应 AI 服务支持的模型标识。</p>
                      </div>
                      <div>
                        <label class="admin-form-label" for="admin-model-base-url">base_url</label>
                        <input id="admin-model-base-url" v-model="modelForm.base_url" type="text" placeholder="https://api.openai.com/v1" :disabled="!isSuperAdmin" class="admin-input disabled:cursor-not-allowed" />
                        <p class="mt-1 text-xs text-[#6B7280]">必填。必须是合法 http/https 地址。</p>
                      </div>
                      <div>
                        <label class="admin-form-label">api_key</label>
                        <div class="rounded-[8px] border border-[#E5E7EB] bg-[#F8FAFC] p-3">
                          <p class="text-xs text-[#6B7280] mb-2">已配置密钥：{{ maskedApiKey }}</p>
                          <button
                            v-if="!modelApiKeyEditMode && isSuperAdmin"
                            type="button"
                            class="admin-btn admin-btn--secondary admin-btn--xs"
                            @click="modelApiKeyEditMode = true"
                          >
                            更换 API Key
                          </button>
                          <div v-else-if="isSuperAdmin" class="space-y-2">
                            <input
                              id="admin-model-api-key"
                              v-model="modelApiKeyDraft"
                              type="password"
                              placeholder="输入新的 API Key（留空则保持原值）"
                              class="admin-input"
                            />
                            <div class="flex items-center gap-2">
                              <button type="button" class="admin-btn admin-btn--secondary admin-btn--xs" @click="modelApiKeyEditMode = false; modelApiKeyDraft = ''">取消更换</button>
                            </div>
                          </div>
                        </div>
                      </div>
                      <div>
                        <label class="admin-form-label" for="admin-model-temperature">temperature</label>
                        <input id="admin-model-temperature" v-model.number="modelForm.temperature" type="number" step="0.1" min="0" max="2" :disabled="!isSuperAdmin" class="admin-input disabled:cursor-not-allowed" />
                        <p class="mt-1 text-xs text-[#6B7280]">范围 0-2，越大结果越发散。</p>
                      </div>
                      <div>
                        <label class="admin-form-label" for="admin-model-timeout">timeout</label>
                        <input id="admin-model-timeout" v-model.number="modelForm.timeout" type="number" step="1000" min="1000" :disabled="!isSuperAdmin" class="admin-input disabled:cursor-not-allowed" />
                        <p class="mt-1 text-xs text-[#6B7280]">单位 ms，建议 10000-120000。</p>
                      </div>
                    </div>
                    <div v-if="isSuperAdmin" class="mt-4 flex items-center gap-2">
                      <button type="button" class="admin-btn admin-btn--secondary" :disabled="modelConnectionTesting" @click="handleTestModelConnection">
                        {{ modelConnectionTesting ? '连接测试中...' : '连接测试' }}
                      </button>
                      <button type="button" class="admin-btn admin-btn--primary" @click="handleSaveModelSettings">保存模型配置</button>
                    </div>
                  </section>
                </div>
              </div>

              <p v-if="!isSuperAdmin" class="mt-6 text-sm text-gray-500">保存按钮仅对超级管理员可见。</p>
            </div>
          </section>
        </div>
      </div>
    </div>

    <div
      v-if="detailVisible"
      class="admin-modal-mask admin-modal-mask--drawer"
      @click.self="detailVisible = false"
    >
      <div
        class="admin-modal-panel admin-modal-panel--drawer"
        @click.stop
      >
        <div
          class="admin-modal-header"
        >
          <h3 class="admin-modal-title">详情查看</h3>
          <button
            type="button"
            class="admin-modal-close"
            aria-label="关闭"
            @click="detailVisible = false"
          >
            ×
          </button>
        </div>

        <div class="admin-modal-body">
          <div v-if="detailData">
            <div class="mb-4">
              <div class="mb-2 text-xl font-bold text-app-fg">{{ detailData.title || '未命名' }}</div>
              <div class="mb-3 flex flex-wrap gap-2">
                <span class="admin-tag admin-tag--primary">{{ detailData.cuisine || '未知菜系' }}</span>
                <span class="admin-tag admin-tag--muted">{{ formatTime(detailData.created_at) }}</span>
              </div>
              <div class="space-y-2 text-sm text-[#1F2937]">
                <div><span class="font-medium text-[#6B7280]">用户：</span>{{ formatRecordUserLabel(detailData) }}</div>
                <div><span class="font-medium text-[#6B7280]">食材：</span>{{ formatIngredients(detailData.ingredients) }}</div>
                <div><span class="font-medium text-[#6B7280]">创建时间：</span>{{ formatTime(detailData.created_at) }}</div>
              </div>
            </div>

            <div class="space-y-3">
              <div
                v-if="detailType === 'history'"
                class="whitespace-pre-wrap rounded-[10px] bg-[#F8FAFC] p-4 text-sm leading-relaxed text-[#1F2937] ring-1 ring-[#E5E7EB]"
              >
                <div class="mb-1 text-xs font-semibold text-[#6B7280]">request_payload</div>
                {{ formatPayload(detailData.request_payload) }}
              </div>
              <div
                class="whitespace-pre-wrap rounded-[10px] bg-[#F8FAFC] p-4 text-sm leading-relaxed text-[#1F2937] ring-1 ring-[#E5E7EB]"
              >
                <div class="mb-1 text-xs font-semibold text-[#6B7280]">
                  {{ detailType === 'history' ? 'response_content' : 'recipe_content' }}
                </div>
                {{ detailData.response_content || detailData.recipe_content || '暂无内容' }}
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div
      v-if="userDetailVisible"
      class="admin-modal-mask admin-modal-mask--drawer"
      @click.self="userDetailVisible = false"
    >
      <div class="admin-modal-panel admin-modal-panel--drawer" @click.stop>
        <div class="admin-modal-header">
          <h3 class="admin-modal-title">用户详情</h3>
          <button type="button" class="admin-modal-close" aria-label="关闭" @click="userDetailVisible = false">
            ×
          </button>
        </div>
        <div class="admin-modal-body">
          <template v-if="userDetailData">
            <div class="space-y-3 text-sm">
              <div class="admin-mini-metric"><span>邮箱</span><strong class="text-xs font-normal">{{ userDetailData.email || '未知邮箱' }}</strong></div>
              <div class="admin-mini-metric"><span>user_id</span><strong class="text-xs font-normal">{{ userDetailData.user_id || '未知用户' }}</strong></div>
              <div class="admin-mini-metric"><span>角色</span><strong>{{ roleLabelCN(normalizeRole(userDetailData.role)) }}</strong></div>
              <div class="admin-mini-metric"><span>注册时间</span><strong class="text-xs font-normal">{{ formatTime(userDetailData.created_at) }}</strong></div>
                <div class="admin-mini-metric"><span>会员等级</span><strong class="text-xs font-normal">{{ userDetailData.membership_tier || '—' }}</strong></div>
                <div class="admin-mini-metric"><span>会员状态</span><strong class="text-xs font-normal">{{ userDetailData.membership_status || '未开通' }}</strong></div>
                <div class="admin-mini-metric"><span>会员到期</span><strong class="text-xs font-normal">{{ userDetailData.membership_ended_at ? formatTime(userDetailData.membership_ended_at) : '—' }}</strong></div>
                <div class="admin-mini-metric"><span>会员开通</span><strong class="text-xs font-normal">{{ userDetailData.membership_started_at ? formatTime(userDetailData.membership_started_at) : '—' }}</strong></div>
                <div class="admin-mini-metric"><span>付费渠道</span><strong class="text-xs font-normal">{{ userDetailData.membership_provider || '—' }}</strong></div>
              <div class="admin-mini-metric"><span>历史记录数量</span><strong>{{ userHistoryCountMap.get(String(userDetailData.user_id || '')) || 0 }}</strong></div>
              <div class="admin-mini-metric"><span>收藏数量</span><strong>{{ userFavoriteCountMap.get(String(userDetailData.user_id || '')) || 0 }}</strong></div>

              <div class="admin-mini-metric"><span>命理出生信息</span><strong class="text-xs font-normal">{{ [userDetailData.birth_date, userDetailData.birth_time].filter(Boolean).join(' ') || '—' }}</strong></div>
              <div class="admin-mini-metric"><span>命理出生地/时区</span><strong class="text-xs font-normal">{{ [userDetailData.birth_place, userDetailData.birth_timezone].filter(Boolean).join(' · ') || '—' }}</strong></div>
              <div class="admin-mini-metric"><span>性别</span><strong class="text-xs font-normal">{{ userDetailData.gender || '—' }}</strong></div>
              <div class="admin-mini-metric"><span>命理来源/校准</span><strong class="text-xs font-normal">{{ userDetailData.zodiac_source || '—' }}</strong></div>

              <div class="admin-mini-metric"><span>默认占卜类型</span><strong class="text-xs font-normal">{{ userDetailData.default_fortune_type || '—' }}</strong></div>
              <div class="admin-mini-metric"><span>默认语言</span><strong class="text-xs font-normal">{{ userDetailData.default_locale || '—' }}</strong></div>
              <div class="admin-mini-metric"><span>情绪偏好</span><strong class="text-xs font-normal">{{ formatCompactValue(userDetailData.mood_preferred_moods) }}</strong></div>
              <div class="admin-mini-metric"><span>情绪强度偏好</span><strong class="text-xs font-normal">{{ formatCompactValue(userDetailData.mood_preferred_intensity) }}</strong></div>
              <div class="admin-mini-metric"><span>幸运数字范围</span><strong class="text-xs font-normal">{{ formatCompactValue(userDetailData.number_preferred_range) }}</strong></div>
              <div class="admin-mini-metric"><span>缘分偏好</span><strong class="text-xs font-normal">{{ formatCompactValue(userDetailData.couple_preferred_sets) }}</strong></div>

              <div class="admin-mini-metric"><span>最近一次占卜</span><strong class="text-xs font-normal">
                {{ userFortuneLatestMap.get(String(userDetailData.user_id || ''))?.fortuneTypeLabel || '—' }}
                ({{ userFortuneLatestMap.get(String(userDetailData.user_id || ''))?.created_at ? formatTime(userFortuneLatestMap.get(String(userDetailData.user_id || ''))?.created_at) : '时间未知' }})
              </strong></div>
              <div class="admin-mini-metric"><span>最近一次结果/输入</span><strong class="text-xs font-normal">
                {{ userFortuneLatestMap.get(String(userDetailData.user_id || ''))?.title || '—' }} · {{ userFortuneLatestMap.get(String(userDetailData.user_id || ''))?.inputSummary || '—' }}
              </strong></div>
              <div class="admin-mini-metric"><span>总占卜次数</span><strong class="text-xs font-normal">{{ userFortuneCountMap.get(String(userDetailData.user_id || ''))?.total || 0 }}</strong></div>
              <div class="admin-mini-metric"><span>类型分布</span><strong class="text-xs font-normal">
                今日{{ userFortuneCountMap.get(String(userDetailData.user_id || ''))?.daily || 0 }}
                · 心情{{ userFortuneCountMap.get(String(userDetailData.user_id || ''))?.mood || 0 }}
                · 缘分{{ userFortuneCountMap.get(String(userDetailData.user_id || ''))?.couple || 0 }}
                · 数字{{ userFortuneCountMap.get(String(userDetailData.user_id || ''))?.number || 0 }}
              </strong></div>

              <div class="admin-mini-metric"><span>最近登录时间</span><strong class="text-xs font-normal">当前数据结构暂未提供</strong></div>
            </div>
          </template>
        </div>
      </div>
    </div>

    <div
      v-if="tableMenuDetailVisible"
      class="admin-modal-mask admin-modal-mask--drawer"
      @click.self="tableMenuDetailVisible = false"
    >
      <div class="admin-modal-panel admin-modal-panel--drawer" @click.stop>
        <div class="admin-modal-header">
          <h3 class="admin-modal-title">满汉全席详情</h3>
          <button type="button" class="admin-modal-close" aria-label="关闭" @click="tableMenuDetailVisible = false">×</button>
        </div>
        <div class="admin-modal-body">
          <div v-if="tableMenuDetailData" class="space-y-4">
            <div>
              <div class="mb-2 text-xl font-bold text-app-fg">{{ tableMenuDetailData.title || '未命名菜单' }}</div>
              <div class="space-y-2 text-sm text-[#1F2937]">
                <div><span class="font-medium text-[#6B7280]">用户：</span>{{ tableMenuDetailData.user_email || '未知邮箱' }}（{{ tableMenuDetailData.user_id || '未知用户' }}）</div>
                <div><span class="font-medium text-[#6B7280]">创建时间：</span>{{ formatTime(tableMenuDetailData.created_at) }}</div>
                <div><span class="font-medium text-[#6B7280]">菜品数量：</span>{{ tableMenuDetailData.dishes_count || 0 }}</div>
              </div>
            </div>
            <div class="rounded-[10px] bg-[#F8FAFC] p-4 ring-1 ring-[#E5E7EB]">
              <div class="mb-2 text-xs font-semibold text-[#6B7280]">菜单内容</div>
              <div v-if="!tableMenuDetailData.menu_content || tableMenuDetailData.menu_content.length === 0" class="text-sm text-[#6B7280]">暂无菜单内容</div>
              <ul v-else class="space-y-2">
                <li v-for="(dish, idx) in tableMenuDetailData.menu_content" :key="`${dish.name}-${idx}`" class="text-sm text-[#1F2937]">
                  <p class="font-semibold">{{ idx + 1 }}. {{ dish.name || '未命名菜品' }} <span class="text-xs text-[#6B7280]">（{{ dish.category || '未分类' }}）</span></p>
                  <p class="text-xs text-[#6B7280]">{{ dish.description || '无描述' }}</p>
                </li>
              </ul>
            </div>
            <div class="rounded-[10px] bg-[#F8FAFC] p-4 ring-1 ring-[#E5E7EB]">
              <div class="mb-2 text-xs font-semibold text-[#6B7280]">请求/响应数据</div>
              <div class="space-y-3">
                <div>
                  <div class="mb-1 text-xs font-semibold text-[#6B7280]">request（配置快照）</div>
                  <pre class="whitespace-pre-wrap text-xs text-[#1F2937]">{{ formatPayload(tableMenuDetailData.config_snapshot || null) }}</pre>
                </div>
                <div>
                  <div class="mb-1 text-xs font-semibold text-[#6B7280]">response（菜单结果）</div>
                  <pre class="whitespace-pre-wrap text-xs text-[#1F2937]">{{ formatPayload(tableMenuDetailData.menu_content || null) }}</pre>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div
      v-if="fortuneDetailVisible"
      class="admin-modal-mask admin-modal-mask--drawer"
      @click.self="fortuneDetailVisible = false"
    >
      <div class="admin-modal-panel admin-modal-panel--drawer" @click.stop>
        <div class="admin-modal-header">
          <h3 class="admin-modal-title">玄学厨房详情</h3>
          <button type="button" class="admin-modal-close" aria-label="关闭" @click="fortuneDetailVisible = false">×</button>
        </div>
        <div class="admin-modal-body">
          <div v-if="fortuneDetailData" class="space-y-4">
            <div>
              <div class="mb-2 text-xl font-bold text-app-fg">{{ fortuneDetailData.title || '未命名结果' }}</div>
              <div class="space-y-2 text-sm text-[#1F2937]">
                <div><span class="font-medium text-[#6B7280]">类型：</span>{{ fortuneTypeLabel(fortuneDetailData.fortune_type) }}</div>
                <div><span class="font-medium text-[#6B7280]">用户：</span>{{ fortuneDetailData.user_email || '未知邮箱' }}（{{ fortuneDetailData.user_id || '未知用户' }}）</div>
                <div><span class="font-medium text-[#6B7280]">创建时间：</span>{{ formatTime(fortuneDetailData.created_at) }}</div>
              </div>
            </div>
            <div class="rounded-[10px] bg-[#F8FAFC] p-4 ring-1 ring-[#E5E7EB]">
              <div class="mb-2 text-xs font-semibold text-[#6B7280]">推荐结果内容</div>
              <pre class="whitespace-pre-wrap text-sm text-[#1F2937]">{{ fortuneDetailData.result_content || '暂无内容' }}</pre>
            </div>
            <div class="rounded-[10px] bg-[#F8FAFC] p-4 ring-1 ring-[#E5E7EB]">
              <div class="mb-2 text-xs font-semibold text-[#6B7280]">幸运指数 / 描述</div>
              <div class="space-y-2 text-sm text-[#1F2937]">
                <div>
                  <span class="font-medium text-[#6B7280]">幸运指数：</span>
                  {{ (fortuneDetailData.raw_result as any)?.luckyIndex ?? '暂无' }}
                </div>
                <div>
                  <span class="font-medium text-[#6B7280]">描述：</span>
                  {{ (fortuneDetailData.raw_result as any)?.description || (fortuneDetailData.raw_result as any)?.reason || '暂无' }}
                </div>
              </div>
            </div>
            <div class="rounded-[10px] bg-[#F8FAFC] p-4 ring-1 ring-[#E5E7EB]">
              <div class="mb-2 text-xs font-semibold text-[#6B7280]">原始字段</div>
              <pre class="whitespace-pre-wrap text-xs text-[#1F2937]">{{ formatPayload(fortuneDetailData.raw_result || null) }}</pre>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div
      v-if="sauceDetailVisible"
      class="admin-modal-mask admin-modal-mask--drawer"
      @click.self="sauceDetailVisible = false"
    >
      <div class="admin-modal-panel admin-modal-panel--drawer" @click.stop>
        <div class="admin-modal-header">
          <h3 class="admin-modal-title">酱料大师详情</h3>
          <button type="button" class="admin-modal-close" aria-label="关闭" @click="sauceDetailVisible = false">×</button>
        </div>
        <div class="admin-modal-body">
          <div v-if="sauceDetailData" class="space-y-4">
            <div>
              <div class="mb-2 text-xl font-bold text-app-fg">{{ sauceDetailData.title || '未命名酱料' }}</div>
              <div class="space-y-2 text-sm text-[#1F2937]">
                <div><span class="font-medium text-[#6B7280]">分类：</span>{{ sauceCategoryLabel(sauceDetailData.category) }}</div>
                <div><span class="font-medium text-[#6B7280]">用户：</span>{{ sauceDetailData.user_email || '未知邮箱' }}（{{ sauceDetailData.user_id || '未知用户' }}）</div>
                <div><span class="font-medium text-[#6B7280]">创建时间：</span>{{ formatTime(sauceDetailData.created_at) }}</div>
              </div>
            </div>
            <div class="rounded-[10px] bg-[#F8FAFC] p-4 ring-1 ring-[#E5E7EB]">
              <div class="mb-2 text-xs font-semibold text-[#6B7280]">配方内容 / 步骤</div>
              <pre class="whitespace-pre-wrap text-sm text-[#1F2937]">{{ sauceDetailData.content || '暂无内容' }}</pre>
            </div>
            <div class="rounded-[10px] bg-[#F8FAFC] p-4 ring-1 ring-[#E5E7EB]">
              <div class="mb-2 text-xs font-semibold text-[#6B7280]">原料</div>
              <div class="text-sm text-[#1F2937]">
                <template v-if="sauceDetailData.recipe?.ingredients && sauceDetailData.recipe.ingredients.length > 0">
                  {{ sauceDetailData.recipe.ingredients.join('、') }}
                </template>
                <template v-else>暂无原料信息</template>
              </div>
            </div>
            <div class="rounded-[10px] bg-[#F8FAFC] p-4 ring-1 ring-[#E5E7EB]">
              <div class="mb-2 text-xs font-semibold text-[#6B7280]">步骤</div>
              <ol v-if="sauceDetailData.recipe?.steps && sauceDetailData.recipe.steps.length > 0" class="list-decimal pl-5 space-y-1 text-sm text-[#1F2937]">
                <li v-for="(step, idx) in sauceDetailData.recipe.steps" :key="`sauce-step-${idx}`">{{ step }}</li>
              </ol>
              <div v-else class="text-sm text-[#6B7280]">暂无步骤信息</div>
            </div>
            <div class="rounded-[10px] bg-[#F8FAFC] p-4 ring-1 ring-[#E5E7EB]">
              <div class="mb-2 text-xs font-semibold text-[#6B7280]">其他字段</div>
              <pre class="whitespace-pre-wrap text-xs text-[#1F2937]">{{ formatPayload(sauceDetailData.recipe || null) }}</pre>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div
      v-if="galleryDetailVisible"
      class="admin-modal-mask admin-modal-mask--drawer"
      @click.self="galleryDetailVisible = false"
    >
      <div class="admin-modal-panel admin-modal-panel--drawer" @click.stop>
        <div class="admin-modal-header">
          <h3 class="admin-modal-title">图鉴详情</h3>
          <button type="button" class="admin-modal-close" aria-label="关闭" @click="galleryDetailVisible = false">×</button>
        </div>
        <div class="admin-modal-body">
          <div v-if="galleryDetailData" class="space-y-4">
            <div>
              <div class="mb-2 text-xl font-bold text-app-fg">{{ galleryDetailData.recipeName || '未命名' }}</div>
              <div class="space-y-2 text-sm text-[#1F2937]">
                <div><span class="font-medium text-[#6B7280]">分类：</span>{{ galleryDetailData.cuisine || '未分类' }}</div>
                <div><span class="font-medium text-[#6B7280]">用户：</span>{{ galleryDetailData.userEmail || '未知邮箱' }}（{{ galleryDetailData.userId || '未知用户' }}）</div>
                <div><span class="font-medium text-[#6B7280]">创建时间：</span>{{ formatTime(galleryDetailData.generatedAt) }}</div>
              </div>
            </div>
            <div v-if="galleryDetailData.url" class="rounded-[10px] bg-[#F8FAFC] p-4 ring-1 ring-[#E5E7EB]">
              <div class="mb-2 text-xs font-semibold text-[#6B7280]">图片</div>
              <img :src="galleryDetailData.url" :alt="galleryDetailData.recipeName" class="max-h-72 w-full rounded-lg object-cover ring-1 ring-[#E5E7EB]" />
            </div>
            <div class="rounded-[10px] bg-[#F8FAFC] p-4 ring-1 ring-[#E5E7EB]">
              <div class="mb-2 text-xs font-semibold text-[#6B7280]">内容/说明</div>
              <pre class="whitespace-pre-wrap text-sm text-[#1F2937]">{{ galleryDetailData.prompt || `食材：${(galleryDetailData.ingredients || []).join('、') || '无'}\nrecipeId：${galleryDetailData.recipeId || '无'}` }}</pre>
            </div>
            <div class="rounded-[10px] bg-[#F8FAFC] p-4 ring-1 ring-[#E5E7EB]">
              <div class="mb-2 text-xs font-semibold text-[#6B7280]">关键信息</div>
              <div class="space-y-2 text-sm text-[#1F2937]">
                <div><span class="font-medium text-[#6B7280]">recipeId：</span>{{ galleryDetailData.recipeId || '无' }}</div>
                <div><span class="font-medium text-[#6B7280]">食材：</span>{{ (galleryDetailData.ingredients || []).join('、') || '无' }}</div>
                <div><span class="font-medium text-[#6B7280]">图片链接：</span>{{ galleryDetailData.url || '无' }}</div>
              </div>
            </div>
            <div class="rounded-[10px] bg-[#F8FAFC] p-4 ring-1 ring-[#E5E7EB]">
              <div class="mb-2 text-xs font-semibold text-[#6B7280]">其他字段</div>
              <pre class="whitespace-pre-wrap text-xs text-[#1F2937]">{{ formatPayload(galleryDetailData) }}</pre>
            </div>
          </div>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
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
import { testAIConnection } from '@/services/aiService'
import {
  getAllAdminLogs,
  addAdminLog,
  type AdminLog,
  type AdminLogInput
} from '@/services/adminLogService'
import {
  loadSystemSettings,
  saveSystemSettings,
  loadFrontendConfig,
  saveFrontendConfig,
  loadModelConfig,
  saveModelConfig,
  type SystemSettings,
  type FrontendConfig,
  type ModelConfig
} from '@/services/adminConfigService'
import {
  getAllTableMenuRecords,
  deleteTableMenuRecord,
  type TableMenuRecord
} from '@/services/tableMenuRecordService'
import {
  getAllFortuneRecords,
  deleteFortuneRecord,
  type FortuneRecord
} from '@/services/fortuneRecordService'
import {
  getAllSauceRecords,
  deleteSauceRecord,
  type SauceRecord
} from '@/services/sauceRecordService'
import {
  getAllGalleryRecords,
  deleteGalleryRecord,
  type GalleryRecord
} from '@/services/galleryRecordService'

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
  // 会员信息（可选：未创建 user_memberships 表时为 null）
  membership_tier?: string | null
  membership_status?: string | null
  membership_started_at?: string | null
  membership_ended_at?: string | null
  membership_autorenew?: boolean | null
  membership_provider?: string | null
  membership_txn_id?: string | null
  membership_id?: string | number | null

  // 命理档案（可选：未创建 user_astrology_profiles 时为 null）
  birth_date?: string | null
  birth_time?: string | null
  birth_timezone?: string | null
  birth_place?: string | null
  gender?: string | null
  zodiac_source?: string | null
  zodiac_overrides?: unknown | null

  // 命理默认/偏好（可选：未创建 user_fortune_defaults 时为 null）
  default_fortune_type?: string | null
  default_locale?: string | null
  mood_preferred_moods?: unknown | null
  mood_preferred_intensity?: unknown | null
  number_preferred_range?: unknown | null
  couple_preferred_sets?: unknown | null
}

const PAGE_SIZE = 10

type AdminTab =
  | 'dashboard'
  | 'history'
  | 'favorite'
  | 'user'
  | 'logs'
  | 'settings'
  | 'modelConfig'
  | 'rolePermission'
  | 'tableData'
  | 'fortuneData'
  | 'sauceData'
  | 'galleryData'
type MenuGroupKey = 'dashboard' | 'user_permission' | 'content_ops' | 'data' | 'system'

const activeTab = ref<
  AdminTab
>('dashboard')
const activeTabLabel = computed(() => {
  const tabLabelMap: Record<string, string> = {
    dashboard: '仪表盘',
    history: '历史记录管理',
    favorite: '收藏管理',
    user: '用户管理',
    logs: '操作日志',
    settings: '系统设置',
    modelConfig: '配置中心',
    rolePermission: '角色权限',
    tableData: '满汉全席数据',
    fortuneData: '玄学厨房数据',
    sauceData: '酱料大师数据',
    galleryData: '图鉴数据'
  }
  return tabLabelMap[activeTab.value] || '仪表盘'
})
const menuExpanded = ref<Record<MenuGroupKey, boolean>>({
  dashboard: false,
  user_permission: false,
  content_ops: false,
  data: false,
  system: false
})
const isTabActive = (tab: AdminTab) => activeTab.value === tab
const goTab = (tab: AdminTab) => {
  activeTab.value = tab
}
const goConfigTab = (section: 'frontend_basic' | 'page_ops' | 'feature_switch' | 'global_copy' | 'system_model') => {
  activeTab.value = 'modelConfig'
  configCenterSection.value = section
}
const collapseAllMenuGroups = () => {
  ;(Object.keys(menuExpanded.value) as MenuGroupKey[]).forEach(key => {
    menuExpanded.value[key] = false
  })
}
const expandOnlyGroup = (group: MenuGroupKey) => {
  collapseAllMenuGroups()
  menuExpanded.value[group] = true
}
const groupIncludesCurrentTab = (group: MenuGroupKey) => {
  if (group === 'dashboard') return activeTab.value === 'dashboard'
  if (group === 'user_permission') return ['user', 'rolePermission', 'logs'].includes(activeTab.value)
  if (group === 'content_ops') return activeTab.value === 'modelConfig' && configCenterSection.value !== 'system_model'
  if (group === 'data') return ['history', 'favorite', 'tableData', 'fortuneData', 'sauceData', 'galleryData'].includes(activeTab.value)
  if (group === 'system') return activeTab.value === 'settings' || (activeTab.value === 'modelConfig' && configCenterSection.value === 'system_model')
  return false
}
const goFirstSubmenuOfGroup = (group: MenuGroupKey) => {
  if (group === 'dashboard') return goTab('dashboard')
  if (group === 'user_permission') return goTab('user')
  if (group === 'content_ops') return goConfigTab('frontend_basic')
  if (group === 'data') return goTab('history')
  if (group === 'system') {
    if (isSuperAdmin.value) return goConfigTab('system_model')
    return
  }
}
const onMenuGroupClick = (group: MenuGroupKey) => {
  expandOnlyGroup(group)
  if (!groupIncludesCurrentTab(group)) {
    goFirstSubmenuOfGroup(group)
  }
}
const syncExpandedGroupByCurrentTab = () => {
  const order: MenuGroupKey[] = ['dashboard', 'user_permission', 'content_ops', 'data', 'system']
  const matched = order.find(group => groupIncludesCurrentTab(group))
  if (!matched) {
    collapseAllMenuGroups()
    return
  }
  expandOnlyGroup(matched)
}
const historyKeyword = ref('')
const historyUserKeyword = ref('')
const historyCuisineFilter = ref('')
const historyTimeFilter = ref<'all' | 'today' | 'week' | 'month'>('all')
const historyPage = ref(1)

const favoriteKeyword = ref('')
const favoriteUserKeyword = ref('')
const favoriteCuisineFilter = ref('')
const favoriteTimeFilter = ref<'all' | 'today' | 'week' | 'month'>('all')
const favoritePage = ref(1)
const tableMenuPage = ref(1)
const tableMenuTitleKeyword = ref('')
const tableMenuUserKeyword = ref('')
const tableMenuTimeFilter = ref<'all' | 'today' | 'week' | 'month'>('all')
const fortunePage = ref(1)
const fortuneTitleKeyword = ref('')
const fortuneUserKeyword = ref('')
const fortuneTypeFilter = ref<'all' | string>('all')
const fortuneTimeFilter = ref<'all' | 'today' | 'week' | 'month'>('all')
const saucePage = ref(1)
const sauceTitleKeyword = ref('')
const sauceUserKeyword = ref('')
const sauceCategoryFilter = ref<'all' | string>('all')
const sauceTimeFilter = ref<'all' | 'today' | 'week' | 'month'>('all')
const galleryPage = ref(1)
const galleryTitleKeyword = ref('')
const galleryCategoryFilter = ref('')
const galleryUserKeyword = ref('')
const galleryTimeFilter = ref<'all' | 'today' | 'week' | 'month'>('all')

const userEmailKeyword = ref('')
const userIdKeyword = ref('')
const userRoleFilter = ref<'all' | 'viewer' | 'user' | 'operator' | 'super_admin'>('all')
const userRegisterTimeFilter = ref<'all' | 'today' | 'week' | 'month'>('all')
const userPage = ref(1)

const logPage = ref(1)
const logOperatorKeyword = ref('')
const logActionFilter = ref<'all' | string>('all')
const logTargetFilter = ref<'all' | string>('all')
const logTimeFilter = ref<'all' | 'today' | 'week' | 'month' | 'custom'>('all')
const logDateStart = ref('')
const logDateEnd = ref('')

const historyList = ref<CommonRecord[]>([])
const favoriteList = ref<CommonRecord[]>([])
const userList = ref<UserProfile[]>([])
const logList = ref<AdminLog[]>([])
const tableMenuList = ref<TableMenuRecord[]>([])
const fortuneList = ref<FortuneRecord[]>([])
const sauceList = ref<SauceRecord[]>([])
const galleryList = ref<GalleryRecord[]>([])
const loading = ref(false)

const systemForm = ref<SystemSettings>(loadSystemSettings())
const frontendForm = ref<FrontendConfig>(loadFrontendConfig())
const modelForm = ref<ModelConfig>(loadModelConfig())
const configCenterSection = ref<'frontend_basic' | 'page_ops' | 'feature_switch' | 'global_copy' | 'system_model'>('frontend_basic')
const modelApiKeyEditMode = ref(false)
const modelApiKeyDraft = ref('')
const modelConnectionTesting = ref(false)

const myRawRole = ref<string | null>(null)
const myRole = computed(() => normalizeRole(myRawRole.value))
const isSuperAdmin = computed(() => myRole.value === 'super_admin')
const canDeleteData = computed(() => myRole.value === 'super_admin' || myRole.value === 'operator')

const detailVisible = ref(false)
const detailData = ref<CommonRecord | null>(null)
const detailType = ref<'history' | 'favorite'>('history')
const userDetailVisible = ref(false)
const userDetailData = ref<UserProfile | null>(null)
const tableMenuDetailVisible = ref(false)
const tableMenuDetailData = ref<TableMenuRecord | null>(null)
const fortuneDetailVisible = ref(false)
const fortuneDetailData = ref<FortuneRecord | null>(null)
const sauceDetailVisible = ref(false)
const sauceDetailData = ref<SauceRecord | null>(null)
const galleryDetailVisible = ref(false)
const galleryDetailData = ref<GalleryRecord | null>(null)

const selectedHistoryIds = ref<string[]>([])
const selectedFavoriteIds = ref<string[]>([])

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

const userEmailMap = computed(() => {
  const map = new Map<string, string>()
  userList.value.forEach(user => {
    const uid = String(user.user_id || '').trim()
    if (!uid) return
    map.set(uid, user.email || '')
  })
  return map
})

const getUserEmailById = (userId: unknown) => {
  const uid = String(userId || '').trim()
  if (!uid) return ''
  return userEmailMap.value.get(uid) || ''
}

const formatRecordUserLabel = (item: CommonRecord) => {
  const uid = String(item.user_id || '').trim()
  if (!uid) return '未知用户'
  const email = getUserEmailById(uid)
  return email ? `${email} (${uid})` : uid
}

const formatPayload = (payload: unknown) => {
  if (payload == null) return '—'
  if (typeof payload === 'string') {
    const text = payload.trim()
    if (!text) return '—'
    try {
      return JSON.stringify(JSON.parse(text), null, 2)
    } catch {
      return text
    }
  }
  try {
    return JSON.stringify(payload, null, 2)
  } catch {
    return String(payload)
  }
}

function formatCompactValue(payload: unknown, maxLen = 30): string {
  if (payload == null) return '—'
  const v = typeof payload === 'string' ? payload.trim() : JSON.stringify(payload)
  if (!v || v === 'null' || v === 'undefined') return '—'
  const oneLine = String(v).replace(/\s+/g, ' ').trim()
  if (oneLine.length <= maxLen) return oneLine
  return `${oneLine.slice(0, maxLen)}…`
}

const maskedApiKey = computed(() => {
  const key = (modelForm.value.api_key || '').trim()
  if (!key) return '未配置'
  if (key.length <= 8) return '*'.repeat(key.length)
  return `${key.slice(0, 3)}***${key.slice(-4)}`
})

const validateSystemSettings = (settings: SystemSettings) => {
  if (!settings.site_name.trim()) return '站点名称不能为空'
  if (settings.site_name.trim().length > 40) return '站点名称长度不能超过 40'
  if (settings.site_subtitle.trim().length > 120) return '站点副标题长度不能超过 120'
  return ''
}

const buildModelConfigForAction = (): ModelConfig => {
  const mergedKey = modelApiKeyEditMode.value
    ? (modelApiKeyDraft.value.trim() || modelForm.value.api_key || '')
    : (modelForm.value.api_key || '')
  return {
    ...modelForm.value,
    model_name: modelForm.value.model_name.trim(),
    base_url: modelForm.value.base_url.trim(),
    api_key: mergedKey
  }
}

const validateModelConfig = (config: ModelConfig) => {
  if (!config.model_name.trim()) return '模型名称不能为空'
  if (!config.base_url.trim()) return 'Base URL 不能为空'
  try {
    const url = new URL(config.base_url)
    if (!url.protocol.startsWith('http')) return 'Base URL 必须是 http/https 地址'
  } catch {
    return 'Base URL 格式不正确'
  }
  if (!config.api_key.trim()) return 'API Key 不能为空'
  if (Number.isNaN(config.temperature) || config.temperature < 0 || config.temperature > 2) {
    return 'Temperature 必须在 0 ~ 2 之间'
  }
  if (Number.isNaN(config.timeout) || config.timeout < 1000 || config.timeout > 300000) {
    return '超时必须在 1000 ~ 300000 ms 之间'
  }
  return ''
}

const LOG_ACTION_LABELS: Record<string, string> = {
  set_admin: '设为管理员',
  unset_admin: '取消管理员',
  update_user_role: '修改用户角色',
  delete_history: '删除历史记录',
  delete_favorite: '删除收藏',
  clear_history: '清空历史记录',
  clear_favorites: '清空收藏',
  delete_table_menu: '删除满汉全席记录',
  delete_fortune_record: '删除玄学厨房记录',
  delete_sauce_record: '删除酱料大师记录',
  delete_gallery_item: '删除图鉴记录',
  update_system_settings: '更新系统设置',
  update_model_config: '更新模型配置'
}

const LOG_TARGET_LABELS: Record<string, string> = {
  user: '用户',
  recipe_history: '历史记录',
  favorite: '收藏',
  recipe_histories: '历史记录（批量）',
  favorites: '收藏（批量）',
  table_menu: '满汉全席',
  fortune_record: '玄学厨房',
  sauce_record: '酱料大师',
  gallery_item: '图鉴',
  system_settings: '系统配置',
  model_config: '模型配置'
}

const formatLogActionType = (t: string) => LOG_ACTION_LABELS[t] || t
const formatLogTargetType = (t: string) => LOG_TARGET_LABELS[t] || t

const formatLogDetail = (detail: string | null) => {
  if (!detail) return '—'
  const text = detail.trim()
  if (!text) return '—'
  try {
    const parsed = JSON.parse(text)
    return JSON.stringify(parsed, null, 2)
  } catch {
    return text
  }
}

const formatLogDetailSummary = (detail: string | null) => {
  const text = formatLogDetail(detail)
  if (text === '—') return text
  const singleLine = text.replace(/\s+/g, ' ').trim()
  return singleLine.length > 88 ? `${singleLine.slice(0, 88)}…` : singleLine
}

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

const adminUserCount = computed(() =>
  userList.value.filter(user => {
    const role = normalizeRole(user.role)
    return role === 'viewer' || role === 'operator' || role === 'super_admin'
  }).length
)

const todayNewUserCount = computed(() => userList.value.filter(user => isToday(user.created_at)).length)
const todayNewHistoryCount = computed(() => historyList.value.filter(item => isToday(item.created_at)).length)
const todayNewFavoriteCount = computed(() => favoriteList.value.filter(item => isToday(item.created_at)).length)

type DashboardTrendPoint = { date: string; label: string; count: number }
type DashboardRankRow = { name: string; count: number }
type DashboardUserRankRow = { userId: string; count: number }

const toDayKey = (value: string | Date) => {
  const d = new Date(value)
  if (Number.isNaN(d.getTime())) return ''
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`
}

const buildLast7DayRange = () => {
  const list: { date: string; label: string }[] = []
  const now = new Date()
  for (let i = 6; i >= 0; i -= 1) {
    const d = new Date(now)
    d.setDate(now.getDate() - i)
    list.push({
      date: toDayKey(d),
      label: `${d.getMonth() + 1}/${d.getDate()}`
    })
  }
  return list
}

const buildTrendByCreatedAt = (rows: { created_at?: string }[]): DashboardTrendPoint[] => {
  const range = buildLast7DayRange()
  const countMap = new Map<string, number>()
  rows.forEach(row => {
    const key = toDayKey(row.created_at || '')
    if (!key) return
    countMap.set(key, (countMap.get(key) || 0) + 1)
  })
  return range.map(day => ({
    date: day.date,
    label: day.label,
    count: countMap.get(day.date) || 0
  }))
}

const userTrend7d = computed(() => buildTrendByCreatedAt(userList.value))
const historyTrend7d = computed(() => buildTrendByCreatedAt(historyList.value))
const favoriteTrend7d = computed(() => buildTrendByCreatedAt(favoriteList.value))

const trendMaxValue = computed(() => {
  const maxValue = Math.max(
    ...userTrend7d.value.map(item => item.count),
    ...historyTrend7d.value.map(item => item.count),
    ...favoriteTrend7d.value.map(item => item.count),
    1
  )
  return maxValue
})

const calcTrendBarHeight = (count: number, maxValue: number) => {
  if (maxValue <= 0) return 8
  const ratio = count / maxValue
  return Math.max(8, Math.round(ratio * 42))
}

const topFavoriteCuisines = computed<DashboardRankRow[]>(() => {
  const map = new Map<string, number>()
  favoriteList.value.forEach(item => {
    const cuisine = (item.cuisine || '').trim() || '未知菜系'
    map.set(cuisine, (map.get(cuisine) || 0) + 1)
  })
  return Array.from(map.entries())
    .map(([name, count]) => ({ name, count }))
    .sort((a, b) => b.count - a.count)
    .slice(0, 5)
})

const topHistoryCuisines = computed<DashboardRankRow[]>(() => {
  const map = new Map<string, number>()
  historyList.value.forEach(item => {
    const cuisine = (item.cuisine || '').trim() || '未知菜系'
    map.set(cuisine, (map.get(cuisine) || 0) + 1)
  })
  return Array.from(map.entries())
    .map(([name, count]) => ({ name, count }))
    .sort((a, b) => b.count - a.count)
    .slice(0, 5)
})

const topActiveUsers = computed<DashboardUserRankRow[]>(() => {
  const map = new Map<string, number>()
  ;[...historyList.value, ...favoriteList.value].forEach(item => {
    const userId = String(item.user_id || '').trim()
    if (!userId) return
    map.set(userId, (map.get(userId) || 0) + 1)
  })
  return Array.from(map.entries())
    .map(([userId, count]) => ({ userId, count }))
    .sort((a, b) => b.count - a.count)
    .slice(0, 5)
})

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
    if (historyTimeFilter.value === 'month') {
      const d = new Date(item.created_at || '')
      if (Number.isNaN(d.getTime())) return false
      if (d.getTime() < Date.now() - 30 * 24 * 60 * 60 * 1000) return false
    }

    if (historyCuisineFilter.value) {
      const c = (item.cuisine || '').trim()
      if (c !== historyCuisineFilter.value) return false
    }

    const keyword = historyKeyword.value.trim().toLowerCase()
    const userKeyword = historyUserKeyword.value.trim().toLowerCase()

    const title = (item.title || '').toLowerCase()
    const uid = String(item.user_id || '').toLowerCase()
    const email = getUserEmailById(item.user_id).toLowerCase()
    if (keyword && !title.includes(keyword)) return false
    if (userKeyword && !uid.includes(userKeyword) && !email.includes(userKeyword)) return false
    return true
  })
})

const filteredFavorite = computed(() => {
  return favoriteList.value.filter(item => {
    if (favoriteTimeFilter.value === 'today' && !isToday(item.created_at)) return false
    if (favoriteTimeFilter.value === 'week' && !isWithinLast7Days(item.created_at)) return false
    if (favoriteTimeFilter.value === 'month') {
      const d = new Date(item.created_at || '')
      if (Number.isNaN(d.getTime())) return false
      if (d.getTime() < Date.now() - 30 * 24 * 60 * 60 * 1000) return false
    }

    if (favoriteCuisineFilter.value) {
      const c = (item.cuisine || '').trim()
      if (c !== favoriteCuisineFilter.value) return false
    }

    const keyword = favoriteKeyword.value.trim().toLowerCase()
    const userKeyword = favoriteUserKeyword.value.trim().toLowerCase()

    const title = (item.title || '').toLowerCase()
    const uid = String(item.user_id || '').toLowerCase()
    const email = getUserEmailById(item.user_id).toLowerCase()
    if (keyword && !title.includes(keyword)) return false
    if (userKeyword && !uid.includes(userKeyword) && !email.includes(userKeyword)) return false
    return true
  })
})

const filteredTableMenus = computed(() => {
  return tableMenuList.value.filter(item => {
    if (tableMenuTimeFilter.value === 'today' && !isToday(item.created_at)) return false
    if (tableMenuTimeFilter.value === 'week' && !isWithinLast7Days(item.created_at)) return false
    if (tableMenuTimeFilter.value === 'month') {
      const d = new Date(item.created_at || '')
      if (Number.isNaN(d.getTime())) return false
      if (d.getTime() < Date.now() - 30 * 24 * 60 * 60 * 1000) return false
    }
    const titleKeyword = tableMenuTitleKeyword.value.trim().toLowerCase()
    const userKeyword = tableMenuUserKeyword.value.trim().toLowerCase()
    const title = (item.title || '').toLowerCase()
    const user = `${item.user_email || ''} ${item.user_id || ''}`.toLowerCase()
    if (titleKeyword && !title.includes(titleKeyword)) return false
    if (userKeyword && !user.includes(userKeyword)) return false
    return true
  })
})

const tableMenuTotalCount = computed(() => tableMenuList.value.length)
const tableMenuTodayCount = computed(() =>
  tableMenuList.value.filter(item => isToday(item.created_at)).length
)
const topTableMenuCuisine = computed(() => {
  const map = new Map<string, number>()
  tableMenuList.value.forEach(item => {
    ;(item.menu_content || []).forEach(dish => {
      const key = (dish.category || '未分类').trim() || '未分类'
      map.set(key, (map.get(key) || 0) + 1)
    })
  })
  const sorted = Array.from(map.entries()).sort((a, b) => b[1] - a[1])
  if (sorted.length === 0) return '暂无'
  return `${sorted[0][0]}（${sorted[0][1]}）`
})

const fortuneTypeLabel = (type: string) => {
  if (type === 'daily') return '每日运势'
  if (type === 'mood') return '心情料理'
  if (type === 'number') return '幸运数字菜'
  if (type === 'couple') return '缘分配菜'
  return type || '未知类型'
}

const fortuneTypeOptions = computed(() => {
  const set = new Set<string>()
  fortuneList.value.forEach(item => {
    if (item.fortune_type) set.add(item.fortune_type)
  })
  return Array.from(set)
})

const filteredFortunes = computed(() => {
  return fortuneList.value.filter(item => {
    if (fortuneTimeFilter.value === 'today' && !isToday(item.created_at)) return false
    if (fortuneTimeFilter.value === 'week' && !isWithinLast7Days(item.created_at)) return false
    if (fortuneTimeFilter.value === 'month') {
      const d = new Date(item.created_at || '')
      if (Number.isNaN(d.getTime())) return false
      if (d.getTime() < Date.now() - 30 * 24 * 60 * 60 * 1000) return false
    }
    if (fortuneTypeFilter.value !== 'all' && item.fortune_type !== fortuneTypeFilter.value) return false
    const titleKeyword = fortuneTitleKeyword.value.trim().toLowerCase()
    const userKeyword = fortuneUserKeyword.value.trim().toLowerCase()
    const title = (item.title || '').toLowerCase()
    const user = `${item.user_email || ''} ${item.user_id || ''}`.toLowerCase()
    if (titleKeyword && !title.includes(titleKeyword)) return false
    if (userKeyword && !user.includes(userKeyword)) return false
    return true
  })
})

const fortuneTotalCount = computed(() => fortuneList.value.length)
const fortuneTodayCount = computed(() =>
  fortuneList.value.filter(item => isToday(item.created_at)).length
)
const fortuneMostUsedType = computed(() => {
  const map = new Map<string, number>()
  fortuneList.value.forEach(item => {
    const key = item.fortune_type || 'unknown'
    map.set(key, (map.get(key) || 0) + 1)
  })
  const sorted = Array.from(map.entries()).sort((a, b) => b[1] - a[1])
  if (sorted.length === 0) return '暂无'
  return `${fortuneTypeLabel(sorted[0][0])}（${sorted[0][1]}）`
})

const sauceCategoryLabel = (category: string) => {
  const m: Record<string, string> = {
    simple: '基础酱',
    complex: '复合酱',
    oil: '油性酱料',
    water: '水性酱料',
    paste: '膏状酱料',
    granular: '颗粒酱料'
  }
  return m[category] || category || '未知分类'
}

const sauceCategoryOptions = computed(() => {
  const set = new Set<string>()
  sauceList.value.forEach(item => {
    if (item.category) set.add(item.category)
  })
  return Array.from(set)
})

const filteredSauces = computed(() => {
  return sauceList.value.filter(item => {
    if (sauceTimeFilter.value === 'today' && !isToday(item.created_at)) return false
    if (sauceTimeFilter.value === 'week' && !isWithinLast7Days(item.created_at)) return false
    if (sauceTimeFilter.value === 'month') {
      const d = new Date(item.created_at || '')
      if (Number.isNaN(d.getTime())) return false
      if (d.getTime() < Date.now() - 30 * 24 * 60 * 60 * 1000) return false
    }
    if (sauceCategoryFilter.value !== 'all' && item.category !== sauceCategoryFilter.value) return false
    const titleKeyword = sauceTitleKeyword.value.trim().toLowerCase()
    const userKeyword = sauceUserKeyword.value.trim().toLowerCase()
    const title = (item.title || '').toLowerCase()
    const user = `${item.user_email || ''} ${item.user_id || ''}`.toLowerCase()
    if (titleKeyword && !title.includes(titleKeyword)) return false
    if (userKeyword && !user.includes(userKeyword)) return false
    return true
  })
})

const sauceTotalCount = computed(() => sauceList.value.length)
const sauceTodayCount = computed(() =>
  sauceList.value.filter(item => isToday(item.created_at)).length
)
const sauceMostUsedCategory = computed(() => {
  const map = new Map<string, number>()
  sauceList.value.forEach(item => {
    const key = item.category || 'unknown'
    map.set(key, (map.get(key) || 0) + 1)
  })
  const sorted = Array.from(map.entries()).sort((a, b) => b[1] - a[1])
  if (sorted.length === 0) return '暂无'
  return `${sauceCategoryLabel(sorted[0][0])}（${sorted[0][1]}）`
})

const galleryCategoryOptions = computed(() => {
  const set = new Set<string>()
  galleryList.value.forEach(item => {
    if (item.cuisine) set.add(item.cuisine)
  })
  return Array.from(set).sort()
})

const filteredGalleries = computed(() => {
  return galleryList.value.filter(item => {
    if (galleryTimeFilter.value === 'today' && !isToday(item.generatedAt)) return false
    if (galleryTimeFilter.value === 'week' && !isWithinLast7Days(item.generatedAt)) return false
    if (galleryTimeFilter.value === 'month') {
      const d = new Date(item.generatedAt || '')
      if (Number.isNaN(d.getTime())) return false
      if (d.getTime() < Date.now() - 30 * 24 * 60 * 60 * 1000) return false
    }
    if (galleryCategoryFilter.value && item.cuisine !== galleryCategoryFilter.value) return false
    const titleKeyword = galleryTitleKeyword.value.trim().toLowerCase()
    const userKeyword = galleryUserKeyword.value.trim().toLowerCase()
    const title = (item.recipeName || '').toLowerCase()
    const user = `${item.userEmail || ''} ${item.userId || ''}`.toLowerCase()
    if (titleKeyword && !title.includes(titleKeyword)) return false
    if (userKeyword && !user.includes(userKeyword)) return false
    return true
  })
})

const galleryTotalCount = computed(() => galleryList.value.length)
const galleryTodayCount = computed(() =>
  galleryList.value.filter(item => isToday(item.generatedAt)).length
)
const galleryMostUsedCategory = computed(() => {
  const map = new Map<string, number>()
  galleryList.value.forEach(item => {
    const key = (item.cuisine || '未分类').trim() || '未分类'
    map.set(key, (map.get(key) || 0) + 1)
  })
  const sorted = Array.from(map.entries()).sort((a, b) => b[1] - a[1])
  if (sorted.length === 0) return '暂无'
  return `${sorted[0][0]}（${sorted[0][1]}）`
})

const filteredUsers = computed(() => {
  return userList.value.filter(user => {
    if (userRoleFilter.value !== 'all') {
      const r = normalizeRole(user.role)
      if (r !== userRoleFilter.value) return false
    }

    if (userRegisterTimeFilter.value === 'today' && !isToday(user.created_at)) return false
    if (userRegisterTimeFilter.value === 'week' && !isWithinLast7Days(user.created_at)) return false
    if (userRegisterTimeFilter.value === 'month') {
      const d = new Date(user.created_at)
      if (Number.isNaN(d.getTime())) return false
      if (d.getTime() < Date.now() - 30 * 24 * 60 * 60 * 1000) return false
    }

    const emailKeyword = userEmailKeyword.value.trim().toLowerCase()
    const uidKeyword = userIdKeyword.value.trim().toLowerCase()

    const email = (user.email || '').toLowerCase()
    const uid = String(user.user_id || '').toLowerCase()
    if (emailKeyword && !email.includes(emailKeyword)) return false
    if (uidKeyword && !uid.includes(uidKeyword)) return false
    return true
  })
})

const userHistoryCountMap = computed(() => {
  const map = new Map<string, number>()
  historyList.value.forEach(item => {
    const userId = String(item.user_id || '').trim()
    if (!userId) return
    map.set(userId, (map.get(userId) || 0) + 1)
  })
  return map
})

const userFavoriteCountMap = computed(() => {
  const map = new Map<string, number>()
  favoriteList.value.forEach(item => {
    const userId = String(item.user_id || '').trim()
    if (!userId) return
    map.set(userId, (map.get(userId) || 0) + 1)
  })
  return map
})

type FortuneTypeId = 'daily' | 'mood' | 'number' | 'couple'
const FORTUNE_TYPE_LABEL_CN: Record<FortuneTypeId, string> = {
  daily: '今日运势',
  mood: '心情料理',
  couple: '缘分配菜',
  number: '幸运数字'
}
const FORTUNE_TYPE_SET = new Set<FortuneTypeId>(['daily', 'mood', 'couple', 'number'])

function tryParseJsonMaybe(v: unknown): unknown | null {
  if (v == null) return null
  if (typeof v === 'string') {
    const s = v.trim()
    if (!s) return null
    try {
      return JSON.parse(s)
    } catch {
      return null
    }
  }
  return v
}

function extractFortuneTypeFromRequestPayload(payload: unknown): FortuneTypeId | null {
  const obj = tryParseJsonMaybe(payload)
  if (!obj || typeof obj !== 'object') return null
  const ft = (obj as any).fortune_type
  if (!ft || typeof ft !== 'string') return null
  return FORTUNE_TYPE_SET.has(ft as FortuneTypeId) ? (ft as FortuneTypeId) : null
}

function buildFortuneInputSummary(fortuneType: FortuneTypeId, payload: unknown): string | null {
  const obj = tryParseJsonMaybe(payload)
  if (!obj || typeof obj !== 'object') return null

  if (fortuneType === 'daily') {
    const daily = (obj as any).daily ?? {}
    const zodiac = daily.zodiac ?? daily.zodiac_name ?? ''
    const animal = daily.animal ?? daily.animal_name ?? ''
    const date = daily.date ?? daily.day ?? ''
    const parts = [zodiac && zodiac !== '' ? zodiac : '', animal && animal !== '' ? animal : '', date && date !== '' ? date : ''].filter(Boolean)
    return parts.length ? parts.join(' · ') : null
  }

  if (fortuneType === 'mood') {
    const mood = (obj as any).mood ?? {}
    const moods = mood.moods ?? mood.mood_list ?? null
    const intensity = mood.intensity ?? mood.level ?? null
    const moodText = Array.isArray(moods) ? moods.filter(Boolean).join('、') : (moods ? String(moods) : '')
    if (!moodText && intensity == null) return null
    return [moodText || '—', intensity != null ? String(intensity) : ''].filter(Boolean).join(' · ')
  }

  if (fortuneType === 'number') {
    const num = (obj as any).number ?? {}
    const number = num.number ?? num.value ?? null
    const isRandom = num.is_random ?? num.isRandom ?? null
    if (number == null) return null
    return isRandom === true ? `${number}（随机）` : String(number)
  }

  // couple
  const couple = (obj as any).couple ?? {}
  const u1 = couple.user1 ?? {}
  const u2 = couple.user2 ?? {}
  const u1Z = u1.zodiac ?? ''
  const u1A = u1.animal ?? ''
  const u2Z = u2.zodiac ?? ''
  const u2A = u2.animal ?? ''
  const p1 = [u1Z, u1A].filter(Boolean).join('')
  const p2 = [u2Z, u2A].filter(Boolean).join('')
  if (!p1 && !p2) return null
  return [p1 || '未知', p2 || '未知'].join(' + ')
}

const userFortuneCountMap = computed(() => {
  const map = new Map<string, { total: number; daily: number; mood: number; couple: number; number: number }>()
  historyList.value.forEach(item => {
    const userId = String(item.user_id || '').trim()
    if (!userId) return
    const ft = extractFortuneTypeFromRequestPayload(item.request_payload)
    if (!ft) return

    const cur = map.get(userId) || { total: 0, daily: 0, mood: 0, couple: 0, number: 0 }
    cur.total += 1
    cur[ft] += 1
    map.set(userId, cur)
  })
  return map
})

const userFortuneLatestMap = computed(() => {
  const map = new Map<string, { fortuneType: FortuneTypeId; fortuneTypeLabel: string; created_at?: string; title?: string; inputSummary?: string }>()

  historyList.value.forEach(item => {
    const userId = String(item.user_id || '').trim()
    if (!userId) return

    const ft = extractFortuneTypeFromRequestPayload(item.request_payload)
    if (!ft) return

    const createdAt = item.created_at
    const createdMs = createdAt ? new Date(createdAt).getTime() : 0
    if (!createdMs) return

    const cur = map.get(userId)
    if (!cur) {
      map.set(userId, {
        fortuneType: ft,
        fortuneTypeLabel: FORTUNE_TYPE_LABEL_CN[ft],
        created_at: createdAt,
        title: item.title ?? '未命名占卜',
        inputSummary: buildFortuneInputSummary(ft, item.request_payload) ?? undefined
      })
      return
    }

    const curMs = cur.created_at ? new Date(cur.created_at).getTime() : 0
    if (createdMs > curMs) {
      map.set(userId, {
        fortuneType: ft,
        fortuneTypeLabel: FORTUNE_TYPE_LABEL_CN[ft],
        created_at: createdAt,
        title: item.title ?? '未命名占卜',
        inputSummary: buildFortuneInputSummary(ft, item.request_payload) ?? undefined
      })
    }
  })

  return map
})

const historyTotalPages = computed(() =>
  Math.max(1, Math.ceil(filteredHistory.value.length / PAGE_SIZE))
)

const favoriteTotalPages = computed(() =>
  Math.max(1, Math.ceil(filteredFavorite.value.length / PAGE_SIZE))
)

const tableMenuTotalPages = computed(() =>
  Math.max(1, Math.ceil(filteredTableMenus.value.length / PAGE_SIZE))
)

const fortuneTotalPages = computed(() =>
  Math.max(1, Math.ceil(filteredFortunes.value.length / PAGE_SIZE))
)

const sauceTotalPages = computed(() =>
  Math.max(1, Math.ceil(filteredSauces.value.length / PAGE_SIZE))
)

const galleryTotalPages = computed(() =>
  Math.max(1, Math.ceil(filteredGalleries.value.length / PAGE_SIZE))
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

const pagedTableMenus = computed(() => {
  const start = (tableMenuPage.value - 1) * PAGE_SIZE
  return filteredTableMenus.value.slice(start, start + PAGE_SIZE)
})

const pagedFortunes = computed(() => {
  const start = (fortunePage.value - 1) * PAGE_SIZE
  return filteredFortunes.value.slice(start, start + PAGE_SIZE)
})

const pagedSauces = computed(() => {
  const start = (saucePage.value - 1) * PAGE_SIZE
  return filteredSauces.value.slice(start, start + PAGE_SIZE)
})

const pagedGalleries = computed(() => {
  const start = (galleryPage.value - 1) * PAGE_SIZE
  return filteredGalleries.value.slice(start, start + PAGE_SIZE)
})

const selectedHistoryCount = computed(() =>
  selectedHistoryIds.value.filter(id => filteredHistory.value.some(item => String(item.id || '') === id)).length
)

const selectedFavoriteCount = computed(() =>
  selectedFavoriteIds.value.filter(id => filteredFavorite.value.some(item => String(item.id || '') === id)).length
)

const pagedUsers = computed(() => {
  const start = (userPage.value - 1) * PAGE_SIZE
  return filteredUsers.value.slice(start, start + PAGE_SIZE)
})

const logActionOptions = computed(() => {
  const set = new Set<string>()
  logList.value.forEach(item => {
    if (item.action_type) set.add(item.action_type)
  })
  return Array.from(set).sort()
})

const logTargetOptions = computed(() => {
  const set = new Set<string>()
  logList.value.forEach(item => {
    if (item.target_type) set.add(item.target_type)
  })
  return Array.from(set).sort()
})

const filteredLogs = computed(() => {
  const keyword = logOperatorKeyword.value.trim().toLowerCase()
  const startDate = logDateStart.value ? new Date(`${logDateStart.value}T00:00:00`) : null
  const endDate = logDateEnd.value ? new Date(`${logDateEnd.value}T23:59:59`) : null
  return logList.value.filter(item => {
    if (keyword) {
      const operator = (item.operator_email || '').toLowerCase()
      if (!operator.includes(keyword)) return false
    }
    if (logActionFilter.value !== 'all' && item.action_type !== logActionFilter.value) return false
    if (logTargetFilter.value !== 'all' && item.target_type !== logTargetFilter.value) return false
    if (logTimeFilter.value === 'today' && !isToday(item.created_at)) return false
    if (logTimeFilter.value === 'week' && !isWithinLast7Days(item.created_at)) return false
    if (logTimeFilter.value === 'month') {
      const d = new Date(item.created_at)
      if (Number.isNaN(d.getTime())) return false
      if (d.getTime() < Date.now() - 30 * 24 * 60 * 60 * 1000) return false
    }
    if (logTimeFilter.value === 'custom') {
      const d = new Date(item.created_at)
      if (Number.isNaN(d.getTime())) return false
      if (startDate && d < startDate) return false
      if (endDate && d > endDate) return false
    }
    return true
  })
})

const logTotalPages = computed(() =>
  Math.max(1, Math.ceil(filteredLogs.value.length / PAGE_SIZE))
)

const pagedLogs = computed(() => {
  const start = (logPage.value - 1) * PAGE_SIZE
  return filteredLogs.value.slice(start, start + PAGE_SIZE)
})

const resetLogFilters = () => {
  logOperatorKeyword.value = ''
  logActionFilter.value = 'all'
  logTargetFilter.value = 'all'
  logTimeFilter.value = 'all'
  logDateStart.value = ''
  logDateEnd.value = ''
}

const exportFilteredLogs = () => {
  const exportObj = {
    exportTime: new Date().toISOString(),
    total: filteredLogs.value.length,
    filters: {
      operator: logOperatorKeyword.value,
      actionType: logActionFilter.value,
      targetType: logTargetFilter.value,
      timeRange: logTimeFilter.value,
      startDate: logDateStart.value,
      endDate: logDateEnd.value
    },
    logs: filteredLogs.value
  }
  const blob = new Blob([JSON.stringify(exportObj, null, 2)], {
    type: 'application/json;charset=utf-8'
  })
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = `admin-logs-${Date.now()}.json`
  a.click()
  URL.revokeObjectURL(url)
  showAppToast('日志导出成功', 'success')
}

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

watch([historyKeyword, historyUserKeyword, historyCuisineFilter, historyTimeFilter], () => {
  historyPage.value = 1
  const validIds = new Set(filteredHistory.value.map(item => String(item.id || '')).filter(Boolean))
  selectedHistoryIds.value = selectedHistoryIds.value.filter(id => validIds.has(id))
})

watch([favoriteKeyword, favoriteUserKeyword, favoriteCuisineFilter, favoriteTimeFilter], () => {
  favoritePage.value = 1
  const validIds = new Set(filteredFavorite.value.map(item => String(item.id || '')).filter(Boolean))
  selectedFavoriteIds.value = selectedFavoriteIds.value.filter(id => validIds.has(id))
})

watch([tableMenuTitleKeyword, tableMenuUserKeyword, tableMenuTimeFilter], () => {
  tableMenuPage.value = 1
})

watch([fortuneTitleKeyword, fortuneUserKeyword, fortuneTypeFilter, fortuneTimeFilter], () => {
  fortunePage.value = 1
})

watch([sauceTitleKeyword, sauceUserKeyword, sauceCategoryFilter, sauceTimeFilter], () => {
  saucePage.value = 1
})

watch([galleryTitleKeyword, galleryCategoryFilter, galleryUserKeyword, galleryTimeFilter], () => {
  galleryPage.value = 1
})

watch([userEmailKeyword, userIdKeyword, userRoleFilter, userRegisterTimeFilter], () => {
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

watch([tableMenuTotalPages, tableMenuPage], () => {
  if (tableMenuPage.value > tableMenuTotalPages.value) {
    tableMenuPage.value = tableMenuTotalPages.value
  }
})

watch([fortuneTotalPages, fortunePage], () => {
  if (fortunePage.value > fortuneTotalPages.value) {
    fortunePage.value = fortuneTotalPages.value
  }
})

watch([sauceTotalPages, saucePage], () => {
  if (saucePage.value > sauceTotalPages.value) {
    saucePage.value = sauceTotalPages.value
  }
})

watch([galleryTotalPages, galleryPage], () => {
  if (galleryPage.value > galleryTotalPages.value) {
    galleryPage.value = galleryTotalPages.value
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

watch([logOperatorKeyword, logActionFilter, logTargetFilter, logTimeFilter, logDateStart, logDateEnd], () => {
  logPage.value = 1
})

watch([myRole, activeTab], () => {
  if (!isSuperAdmin.value && (activeTab.value === 'settings' || activeTab.value === 'modelConfig')) {
    activeTab.value = 'dashboard'
  }
})
watch([activeTab, configCenterSection], () => {
  syncExpandedGroupByCurrentTab()
})

const refreshData = async () => {
  loading.value = true
  try {
    historyList.value = await getAllHistories()
    favoriteList.value = await getAllFavorites()
    userList.value = await getAllUsers()
    tableMenuList.value = getAllTableMenuRecords()
    fortuneList.value = getAllFortuneRecords()
    sauceList.value = getAllSauceRecords()
    galleryList.value = getAllGalleryRecords()
    selectedHistoryIds.value = []
    selectedFavoriteIds.value = []
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
  if (!canDeleteData.value) {
    showAppToast('当前角色无删除权限', 'warning')
    return
  }
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
  if (!canDeleteData.value) {
    showAppToast('当前角色无删除权限', 'warning')
    return
  }
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
  if (!canDeleteData.value) {
    showAppToast('当前角色无删除权限', 'warning')
    return
  }
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
  if (!canDeleteData.value) {
    showAppToast('当前角色无删除权限', 'warning')
    return
  }
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

const toggleHistorySelect = (id: string, checked: boolean) => {
  const set = new Set(selectedHistoryIds.value)
  if (checked) set.add(id)
  else set.delete(id)
  selectedHistoryIds.value = Array.from(set)
}

const toggleFavoriteSelect = (id: string, checked: boolean) => {
  const set = new Set(selectedFavoriteIds.value)
  if (checked) set.add(id)
  else set.delete(id)
  selectedFavoriteIds.value = Array.from(set)
}

const selectAllFilteredHistory = () => {
  selectedHistoryIds.value = filteredHistory.value.map(item => String(item.id || '')).filter(Boolean)
}

const clearSelectedHistory = () => {
  selectedHistoryIds.value = []
}

const selectAllFilteredFavorite = () => {
  selectedFavoriteIds.value = filteredFavorite.value.map(item => String(item.id || '')).filter(Boolean)
}

const clearSelectedFavorite = () => {
  selectedFavoriteIds.value = []
}

const exportFilteredHistory = () => {
  const exportObj = {
    exportTime: new Date().toISOString(),
    total: filteredHistory.value.length,
    filters: {
      title: historyKeyword.value,
      cuisine: historyCuisineFilter.value,
      user: historyUserKeyword.value,
      timeRange: historyTimeFilter.value
    },
    records: filteredHistory.value
  }
  const blob = new Blob([JSON.stringify(exportObj, null, 2)], {
    type: 'application/json;charset=utf-8'
  })
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = `admin-history-${Date.now()}.json`
  a.click()
  URL.revokeObjectURL(url)
  showAppToast('历史记录导出成功', 'success')
}

const exportFilteredFavorite = () => {
  const exportObj = {
    exportTime: new Date().toISOString(),
    total: filteredFavorite.value.length,
    filters: {
      title: favoriteKeyword.value,
      cuisine: favoriteCuisineFilter.value,
      user: favoriteUserKeyword.value,
      timeRange: favoriteTimeFilter.value
    },
    records: filteredFavorite.value
  }
  const blob = new Blob([JSON.stringify(exportObj, null, 2)], {
    type: 'application/json;charset=utf-8'
  })
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = `admin-favorite-${Date.now()}.json`
  a.click()
  URL.revokeObjectURL(url)
  showAppToast('收藏记录导出成功', 'success')
}

const batchDeleteHistory = async () => {
  if (!canDeleteData.value) {
    showAppToast('当前角色无删除权限', 'warning')
    return
  }
  const ids = selectedHistoryIds.value.filter(Boolean)
  if (ids.length === 0) {
    showAppToast('请先勾选要删除的历史记录', 'info')
    return
  }
  const ok = await showAppConfirm({
    title: '批量删除历史记录',
    message: `将删除 ${ids.length} 条历史记录，此操作不可恢复。`,
    confirmText: '批量删除',
    cancelText: '取消',
    danger: true
  })
  if (!ok) return
  try {
    for (const id of ids) {
      await deleteAnyHistory(Number(id))
    }
    await safeRecordAdminLog({
      action_type: 'clear_history',
      target_type: 'recipe_histories',
      target_id: null,
      detail: `批量删除历史记录，共 ${ids.length} 条`
    })
    selectedHistoryIds.value = []
    await refreshData()
    showAppToast(`已删除 ${ids.length} 条历史记录`, 'success')
  } catch (error) {
    console.error('批量删除历史记录失败:', error)
    showAppToast('批量删除历史记录失败', 'error')
  }
}

const batchDeleteFavorite = async () => {
  if (!canDeleteData.value) {
    showAppToast('当前角色无删除权限', 'warning')
    return
  }
  const ids = selectedFavoriteIds.value.filter(Boolean)
  if (ids.length === 0) {
    showAppToast('请先勾选要删除的收藏记录', 'info')
    return
  }
  const ok = await showAppConfirm({
    title: '批量删除收藏',
    message: `将删除 ${ids.length} 条收藏记录，此操作不可恢复。`,
    confirmText: '批量删除',
    cancelText: '取消',
    danger: true
  })
  if (!ok) return
  try {
    for (const id of ids) {
      await deleteAnyFavorite(Number(id))
    }
    await safeRecordAdminLog({
      action_type: 'clear_favorites',
      target_type: 'favorites',
      target_id: null,
      detail: `批量删除收藏，共 ${ids.length} 条`
    })
    selectedFavoriteIds.value = []
    await refreshData()
    showAppToast(`已删除 ${ids.length} 条收藏记录`, 'success')
  } catch (error) {
    console.error('批量删除收藏失败:', error)
    showAppToast('批量删除收藏失败', 'error')
  }
}

const viewDetail = (item: CommonRecord, type: 'history' | 'favorite') => {
  detailData.value = item
  detailType.value = type
  detailVisible.value = true
}

const viewUserDetail = (user: UserProfile) => {
  userDetailData.value = user
  userDetailVisible.value = true
}

const viewTableMenuDetail = (item: TableMenuRecord) => {
  tableMenuDetailData.value = item
  tableMenuDetailVisible.value = true
}

const deleteTableMenuItem = async (item: TableMenuRecord) => {
  if (!canDeleteData.value) {
    showAppToast('当前角色无删除权限', 'warning')
    return
  }
  const ok = await showAppConfirm({
    title: '删除满汉全席记录',
    message: '确定删除这条满汉全席记录吗？此操作不可恢复。',
    confirmText: '删除',
    cancelText: '取消',
    danger: true
  })
  if (!ok) return
  try {
    deleteTableMenuRecord(item.id)
    await safeRecordAdminLog({
      action_type: 'delete_table_menu',
      target_type: 'table_menu',
      target_id: item.id,
      detail: `标题: ${item.title || '未命名'}`
    })
    await refreshData()
    showAppToast('满汉全席记录已删除', 'success')
  } catch (e) {
    console.error('删除满汉全席记录失败:', e)
    showAppToast('删除满汉全席记录失败', 'error')
  }
}

const viewFortuneDetail = (item: FortuneRecord) => {
  fortuneDetailData.value = item
  fortuneDetailVisible.value = true
}

const deleteFortuneItem = async (item: FortuneRecord) => {
  if (!canDeleteData.value) {
    showAppToast('当前角色无删除权限', 'warning')
    return
  }
  const ok = await showAppConfirm({
    title: '删除玄学厨房记录',
    message: '确定删除这条玄学厨房记录吗？此操作不可恢复。',
    confirmText: '删除',
    cancelText: '取消',
    danger: true
  })
  if (!ok) return
  try {
    deleteFortuneRecord(item.id)
    await safeRecordAdminLog({
      action_type: 'delete_fortune_record',
      target_type: 'fortune_record',
      target_id: item.id,
      detail: `标题: ${item.title || '未命名'}; 类型: ${item.fortune_type || 'unknown'}`
    })
    await refreshData()
    showAppToast('玄学厨房记录已删除', 'success')
  } catch (e) {
    console.error('删除玄学厨房记录失败:', e)
    showAppToast('删除玄学厨房记录失败', 'error')
  }
}

const viewSauceDetail = (item: SauceRecord) => {
  sauceDetailData.value = item
  sauceDetailVisible.value = true
}

const deleteSauceItem = async (item: SauceRecord) => {
  if (!canDeleteData.value) {
    showAppToast('当前角色无删除权限', 'warning')
    return
  }
  const ok = await showAppConfirm({
    title: '删除酱料大师记录',
    message: '确定删除这条酱料大师记录吗？此操作不可恢复。',
    confirmText: '删除',
    cancelText: '取消',
    danger: true
  })
  if (!ok) return
  try {
    deleteSauceRecord(item.id)
    await safeRecordAdminLog({
      action_type: 'delete_sauce_record',
      target_type: 'sauce_record',
      target_id: item.id,
      detail: `标题: ${item.title || '未命名'}; 分类: ${item.category || 'unknown'}`
    })
    await refreshData()
    showAppToast('酱料大师记录已删除', 'success')
  } catch (e) {
    console.error('删除酱料大师记录失败:', e)
    showAppToast('删除酱料大师记录失败', 'error')
  }
}

const viewGalleryDetail = (item: GalleryRecord) => {
  galleryDetailData.value = item
  galleryDetailVisible.value = true
}

const deleteGalleryItem = async (item: GalleryRecord) => {
  if (!canDeleteData.value) {
    showAppToast('当前角色无删除权限', 'warning')
    return
  }
  const ok = await showAppConfirm({
    title: '删除图鉴记录',
    message: '确定删除这条图鉴记录吗？此操作不可恢复。',
    confirmText: '删除',
    cancelText: '取消',
    danger: true
  })
  if (!ok) return
  try {
    deleteGalleryRecord(item.id)
    await safeRecordAdminLog({
      action_type: 'delete_gallery_item',
      target_type: 'gallery_item',
      target_id: item.id,
      detail: `标题: ${item.recipeName || '未命名'}; 分类: ${item.cuisine || '未知'}`
    })
    await refreshData()
    showAppToast('图鉴记录已删除', 'success')
  } catch (e) {
    console.error('删除图鉴记录失败:', e)
    showAppToast('删除图鉴记录失败', 'error')
  }
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
      detail: `目标用户: ${user.email || user.user_id}; 角色变更: ${current} -> ${nextRole}`
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
    favorite: favoriteList.value,
    tableMenus: tableMenuList.value,
    fortunes: fortuneList.value,
    sauces: sauceList.value,
    galleries: galleryList.value
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
  const err = validateSystemSettings(systemForm.value)
  if (err) {
    showAppToast(err, 'warning')
    return
  }
  const before = loadSystemSettings()
  saveSystemSettings(systemForm.value)
  systemForm.value = loadSystemSettings()
  const changed: string[] = []
  if (before.site_name !== systemForm.value.site_name) changed.push(`site_name: "${before.site_name}" -> "${systemForm.value.site_name}"`)
  if (before.site_subtitle !== systemForm.value.site_subtitle) changed.push('site_subtitle: 已更新')
  if (before.enable_gallery !== systemForm.value.enable_gallery) changed.push(`enable_gallery: ${before.enable_gallery} -> ${systemForm.value.enable_gallery}`)
  if (before.enable_fortune !== systemForm.value.enable_fortune) changed.push(`enable_fortune: ${before.enable_fortune} -> ${systemForm.value.enable_fortune}`)
  void safeRecordAdminLog({
    action_type: 'update_system_settings',
    target_type: 'system_settings',
    target_id: null,
    detail: changed.length > 0 ? changed.join('; ') : '系统设置已保存（无字段变化）'
  })
  showAppToast('系统设置已保存（本地暂存）', 'success')
}

const handleSaveFrontendConfig = () => {
  if (!isSuperAdmin.value) {
    showAppToast('仅超级管理员可保存前端配置', 'warning')
    return
  }
  try {
    saveFrontendConfig(frontendForm.value)
    frontendForm.value = loadFrontendConfig()
    showAppToast(frontendForm.value.toast_save_success || '前端配置已保存（本地暂存）', 'success')
  } catch {
    showAppToast(frontendForm.value.toast_save_failed || '前端配置保存失败，请稍后再试', 'error')
  }
}

const handleSaveModelSettings = () => {
  if (!isSuperAdmin.value) {
    showAppToast('仅超级管理员可保存模型配置', 'warning')
    return
  }
  const nextConfig = buildModelConfigForAction()
  const err = validateModelConfig(nextConfig)
  if (err) {
    showAppToast(err, 'warning')
    return
  }
  try {
    const before = loadModelConfig()
    saveModelConfig(nextConfig)
    modelForm.value = loadModelConfig()
    modelApiKeyEditMode.value = false
    modelApiKeyDraft.value = ''
    const changed: string[] = []
    if (before.model_name !== modelForm.value.model_name) changed.push(`model_name: ${before.model_name || '空'} -> ${modelForm.value.model_name}`)
    if (before.base_url !== modelForm.value.base_url) changed.push(`base_url: ${before.base_url || '空'} -> ${modelForm.value.base_url}`)
    if (before.temperature !== modelForm.value.temperature) changed.push(`temperature: ${before.temperature} -> ${modelForm.value.temperature}`)
    if (before.timeout !== modelForm.value.timeout) changed.push(`timeout: ${before.timeout} -> ${modelForm.value.timeout}`)
    if (before.api_key !== modelForm.value.api_key) changed.push('api_key: 已更新')
    void safeRecordAdminLog({
      action_type: 'update_model_config',
      target_type: 'model_config',
      target_id: null,
      detail: changed.length > 0 ? changed.join('; ') : '模型配置已保存（无字段变化）'
    })
    showAppToast(frontendForm.value.toast_save_success || '模型配置已保存（本地暂存）', 'success')
  } catch {
    showAppToast(frontendForm.value.toast_save_failed || '模型配置保存失败，请稍后再试', 'error')
  }
}

const handleTestModelConnection = async () => {
  if (!isSuperAdmin.value || modelConnectionTesting.value) return
  const nextConfig = buildModelConfigForAction()
  const err = validateModelConfig(nextConfig)
  if (err) {
    showAppToast(err, 'warning')
    return
  }
  modelConnectionTesting.value = true
  const original = loadModelConfig()
  try {
    // 复用现有 testAIConnection：临时写入配置后测试，再回滚。
    saveModelConfig(nextConfig)
    const res = await testAIConnection({
      baseUrl: nextConfig.base_url,
      apiKey: nextConfig.api_key,
      model: nextConfig.model_name,
      temperature: nextConfig.temperature,
      timeout: nextConfig.timeout,
    })
    saveModelConfig(original)
    if (res.ok) {
      showAppToast('模型连接测试成功', 'success')
    } else {
      const msg = res.error?.message || '模型连接测试失败（未知错误）'
      const status = res.error?.statusCode ? `（HTTP ${res.error.statusCode}）` : ''
      showAppToast(`模型连接测试失败：${msg}${status}`, 'error')
    }
  } catch (e) {
    saveModelConfig(original)
    console.error('模型连接测试异常:', e)
    const errMsg = e instanceof Error ? e.message : String(e)
    showAppToast(`模型连接测试异常：${errMsg}`, 'error')
  } finally {
    modelConnectionTesting.value = false
  }
}

const syncMyRole = async () => {
  myRawRole.value = await getCurrentUserRole()
}

onMounted(async () => {
  systemForm.value = loadSystemSettings()
  frontendForm.value = loadFrontendConfig()
  modelForm.value = loadModelConfig()
  modelApiKeyEditMode.value = false
  modelApiKeyDraft.value = ''
  syncExpandedGroupByCurrentTab()
  await syncMyRole()
  await refreshData()
})
</script>

<style scoped>
.admin-page {
  background: #f5f7fa;
  color: #1f2937;
}

.admin-shell {
  margin: 0 auto;
  max-width: 1440px;
  padding: 16px;
}

.admin-topbar {
  position: sticky;
  top: 0;
  z-index: 20;
  margin-bottom: 16px;
  border: 1px solid #e5e7eb;
  border-radius: 10px;
  background: #ffffff;
  padding: 14px 18px;
  box-shadow: 0 2px 10px rgba(15, 23, 42, 0.04);
}

.admin-layout {
  display: flex;
  gap: 16px;
  align-items: flex-start;
}

.admin-sidebar {
  position: sticky;
  top: 92px;
  width: 240px;
  flex-shrink: 0;
}

.admin-sidebar-inner {
  border: 1px solid #dbe5f1;
  border-radius: 10px;
  background: #eef3f8;
  padding: 10px;
}

.admin-menu-item {
  display: flex;
  width: 100%;
  align-items: center;
  gap: 8px;
  border: 1px solid #dbe5f1;
  border-radius: 8px;
  padding: 10px 12px;
  font-size: 14px;
  font-weight: 500;
  line-height: 1.2;
  transition: all 0.2s ease;
}

.admin-menu-item--active {
  border-color: #4f7bff;
  background: #4f7bff;
  color: #ffffff;
  box-shadow: 0 6px 16px rgba(79, 123, 255, 0.2);
}

.admin-menu-item--inactive {
  background: #f7fbff;
  color: #374151;
}

.admin-menu-item--inactive:hover {
  background: #eff6ff;
  color: #1f2937;
}

.admin-menu-group-title {
  display: flex;
  width: 100%;
  align-items: center;
  justify-content: space-between;
  border-radius: 10px;
  border: 1px solid #dbe5f1;
  background: #f4f8ff;
  padding: 10px 12px;
  font-size: 13px;
  font-weight: 800;
  color: #334155;
  letter-spacing: 0.01em;
  transition: background-color 0.2s ease, border-color 0.2s ease;
}

.admin-menu-group-title:hover {
  background: #edf4ff;
  border-color: #cfdced;
}

.admin-menu-item--sub {
  border-radius: 7px;
  font-size: 12px;
  font-weight: 500;
  padding: 7px 9px;
  gap: 6px;
}

.admin-menu-item--sub.admin-menu-item--inactive {
  background: #f8fbff;
  border-color: #e2e8f0;
  color: #475569;
}

.admin-menu-item--sub.admin-menu-item--active {
  border-color: #9eb8ff;
  background: #eaf1ff;
  color: #2f5fdb;
  box-shadow: none;
}

.admin-main {
  min-width: 0;
  flex: 1;
}

.admin-content-card {
  border-width: 1px !important;
  border-color: #e5e7eb !important;
  border-radius: 10px !important;
  box-shadow: 0 6px 18px rgba(15, 23, 42, 0.06) !important;
}

.admin-module-card {
  padding: 18px;
}

.admin-module-head {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 14px;
}

.admin-module-title {
  font-size: 18px;
  line-height: 1.2;
  font-weight: 700;
  color: #1f2937;
}

.admin-module-desc {
  margin-top: 4px;
  font-size: 13px;
  color: #6b7280;
}

.admin-bulk-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
  margin-bottom: 10px;
  border: 1px dashed #cfe0ff;
  border-radius: 8px;
  background: #f5f9ff;
  color: #4b5563;
  font-size: 12px;
  padding: 8px 10px;
}

.admin-filter-wrap {
  margin-bottom: 14px;
  border: 1px solid #e5e7eb;
  border-radius: 10px;
  background: #f9fafb;
  padding: 12px;
}

.admin-filter-row {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.admin-filter-fields {
  display: flex;
  flex: 1;
  min-width: 0;
  flex-wrap: wrap;
  gap: 12px;
}

.admin-filter-col {
  width: 100%;
}

.admin-filter-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.admin-form-label {
  display: block;
  margin-bottom: 6px;
  font-size: 12px;
  font-weight: 600;
  color: #6b7280;
}

.admin-input,
.admin-select {
  width: 100%;
  min-height: 38px;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  background: #fff;
  color: #1f2937;
  font-size: 14px;
}

.admin-input {
  padding: 8px 12px;
}

.admin-input--mono {
  font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
}

.admin-select {
  padding: 8px 34px 8px 12px;
}

.admin-select--compact {
  min-height: 32px;
  padding-top: 5px;
  padding-bottom: 5px;
}

.admin-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  height: 34px;
  padding: 0 12px;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  font-size: 13px;
  font-weight: 500;
  transition: all 0.2s ease;
}

.admin-btn--xs {
  height: 28px;
  padding: 0 10px;
  font-size: 12px;
}

.admin-btn--primary {
  background: #4f7bff;
  color: #fff;
  border-color: #4f7bff;
}

.admin-btn--secondary {
  background: #fff;
  color: #374151;
}

.admin-btn--secondary:hover {
  background: #f9fafb;
}

.admin-btn--danger {
  background: #fff5f5;
  border-color: #fecaca;
  color: #dc2626;
}

.admin-table-wrap {
  overflow-x: auto;
  border: 1px solid #e5e7eb;
  border-radius: 10px;
  background: #fff;
}

.admin-stat-card {
  display: flex;
  align-items: center;
  justify-content: space-between;
  min-height: 118px;
  border: 1px solid #e5e7eb;
  border-radius: 10px;
  background: #fff;
  padding: 16px;
  box-shadow: 0 5px 14px rgba(15, 23, 42, 0.06);
}

.admin-stat-label {
  font-size: 12px;
  color: #6b7280;
}

.admin-stat-value {
  margin-top: 6px;
  font-size: 30px;
  line-height: 1;
  font-weight: 700;
  color: #1f2937;
}

.admin-mini-metric {
  display: flex;
  align-items: center;
  justify-content: space-between;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  background: #f9fafb;
  padding: 8px 10px;
  color: #374151;
}

.admin-mini-metric strong {
  font-size: 16px;
  color: #1f2937;
}

.admin-trend-row {
  display: flex;
  align-items: flex-end;
  gap: 10px;
}

.admin-trend-label {
  width: 42px;
  flex-shrink: 0;
  color: #6b7280;
  font-size: 12px;
}

.admin-trend-bars {
  display: grid;
  grid-template-columns: repeat(7, minmax(0, 1fr));
  width: 100%;
  gap: 8px;
}

.admin-trend-bar-wrap {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: flex-end;
  min-height: 58px;
  gap: 4px;
}

.admin-trend-bar {
  width: 100%;
  max-width: 14px;
  border-radius: 6px 6px 2px 2px;
  min-height: 8px;
}

.admin-trend-day {
  color: #9ca3af;
  font-size: 10px;
}

.admin-rank-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  background: #f9fafb;
  padding: 8px 10px;
  color: #374151;
}

.admin-rank-row strong {
  color: #1f2937;
}

.admin-table :deep(th) {
  background: #f9fafb;
  color: #1f2937;
  font-size: 13px;
  font-weight: 600;
}

.admin-table :deep(td) {
  color: #374151;
  font-size: 13px;
}

.admin-table-row:hover {
  background: #f8fbff;
}

.admin-pagination {
  margin-top: 12px;
  padding-top: 12px;
  border-top: 1px solid #e5e7eb;
  display: flex;
  flex-direction: column;
  gap: 8px;
  align-items: flex-start;
  color: #6b7280;
  font-size: 13px;
}

.admin-form-grid {
  display: grid;
  gap: 14px;
  max-width: 760px;
  grid-template-columns: repeat(2, minmax(0, 1fr));
}

.admin-form-grid :deep(.app-switch) {
  min-height: 38px;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  background: #f9fafb;
  padding: 8px 12px;
}

.admin-modal-mask {
  position: fixed;
  inset: 0;
  z-index: 90;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(15, 23, 42, 0.35);
  padding: 16px;
}

.admin-modal-mask--drawer {
  justify-content: flex-end;
}

.admin-modal-panel {
  max-height: 85vh;
  width: 100%;
  max-width: 920px;
  overflow: hidden;
  border-radius: 10px;
  border: 1px solid #e5e7eb;
  background: #fff;
  box-shadow: 0 18px 48px rgba(15, 23, 42, 0.18);
}

.admin-modal-panel--drawer {
  max-width: min(720px, 92vw);
  height: 100%;
  max-height: 100%;
  border-radius: 0;
}

.admin-modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  border-bottom: 1px solid #e5e7eb;
  background: #f9fafb;
  padding: 12px 16px;
}

.admin-modal-title {
  font-size: 16px;
  font-weight: 600;
  color: #1f2937;
}

.admin-modal-close {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  background: #fff;
  color: #6b7280;
}

.admin-modal-body {
  max-height: calc(85vh - 60px);
  overflow-y: auto;
  padding: 16px;
}

.admin-state {
  display: flex;
  min-height: 130px;
  align-items: center;
  justify-content: center;
  border: 1px dashed #d1d5db;
  border-radius: 10px;
  background: #f9fafb;
  color: #6b7280;
  font-size: 14px;
}

.admin-state--loading::before {
  content: '';
  width: 14px;
  height: 14px;
  margin-right: 8px;
  border: 2px solid #bfdbfe;
  border-top-color: #4f7bff;
  border-radius: 999px;
  animation: admin-spin 0.8s linear infinite;
}

.admin-state--empty {
  min-height: 170px;
}

.admin-tag {
  display: inline-flex;
  align-items: center;
  border-radius: 999px;
  padding: 3px 10px;
  font-size: 12px;
  border: 1px solid #e5e7eb;
}

.admin-tag--primary {
  color: #315ecc;
  background: #e9f1ff;
  border-color: #cfe0ff;
}

.admin-tag--muted {
  color: #6b7280;
  background: #f3f4f6;
}

.admin-breadcrumb {
  margin-bottom: 6px;
  font-size: 12px;
  color: #6b7280;
}

.admin-toolbar-icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 34px;
  height: 34px;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  background: #f8fafc;
  color: #6b7280;
}

.admin-user-chip {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  height: 34px;
  padding: 0 10px;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  background: #ffffff;
}

.admin-user-avatar {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 22px;
  height: 22px;
  border-radius: 9999px;
  background: #4f7bff;
  color: #ffffff;
  font-size: 12px;
  font-weight: 700;
}

.admin-user-name {
  color: #1f2937;
  font-size: 13px;
  font-weight: 500;
}

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

@media (max-width: 1023px) {
  .admin-layout {
    flex-direction: column;
  }

  .admin-sidebar {
    position: static;
    top: auto;
    width: 100%;
  }

  .admin-form-grid {
    grid-template-columns: 1fr;
  }
}

@media (min-width: 640px) {
  .admin-filter-col {
    width: 10rem;
  }

  .admin-filter-fields > .flex-1 {
    min-width: 220px;
  }

  .admin-pagination {
    flex-direction: row;
    align-items: center;
    justify-content: space-between;
  }
}

@media (min-width: 1280px) {
  .admin-filter-row {
    flex-direction: row;
    align-items: flex-end;
    justify-content: space-between;
  }
}

@keyframes admin-spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}

</style>