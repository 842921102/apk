<template>
  <view class="feedback-page">
    <view class="feedback-card">
      <view class="feedback-field">
        <text class="feedback-field__label">需求标题</text>
        <input
          v-model="form.title"
          class="feedback-input"
          maxlength="80"
          placeholder=""
          placeholder-class="feedback-input__placeholder"
        />
      </view>

      <view class="feedback-field">
        <text class="feedback-field__label">需求详情</text>
        <textarea
          v-model="form.content"
          class="feedback-textarea"
          maxlength="3000"
          :show-confirm-bar="false"
          placeholder=""
          placeholder-class="feedback-input__placeholder"
        />
        <text class="feedback-field__counter">{{ contentCount }}/3000</text>
      </view>

      <view class="feedback-field">
        <text class="feedback-field__label">联系方式（可选）</text>
        <input
          v-model="form.contact"
          class="feedback-input"
          maxlength="120"
          placeholder=""
          placeholder-class="feedback-input__placeholder"
        />
      </view>

      <button
        class="feedback-submit"
        :loading="submitting"
        :disabled="submitting"
        @click="submitFeedback"
      >
        提交需求反馈
      </button>
    </view>

    <view class="feedback-card feedback-card--history">
      <view class="feedback-section-head">
        <text class="feedback-section-head__title">我的反馈记录</text>
      </view>
      <view v-if="loadingList" class="feedback-empty">
        <text class="feedback-empty__text">加载中...</text>
      </view>
      <view v-else-if="feedbackList.length === 0" class="feedback-empty">
        <text class="feedback-empty__text">还没有反馈记录，欢迎提出你的想法</text>
      </view>
      <view v-else>
        <view
          v-for="item in feedbackList"
          :key="item.id"
          class="feedback-history-item"
        >
          <view class="feedback-history-item__head">
            <text class="feedback-history-item__title">{{ item.title }}</text>
            <text class="feedback-status" :class="`feedback-status--${item.status}`">{{ statusText(item.status) }}</text>
          </view>
          <text class="feedback-history-item__time">{{ formatTime(item.created_at) }}</text>
          <text v-if="item.admin_remark" class="feedback-history-item__remark">回复：{{ item.admin_remark }}</text>
        </view>
      </view>
    </view>
  </view>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import {
  apiCreateUserFeedback,
  apiListMyUserFeedbacks,
  type UserFeedbackItem,
  type UserFeedbackStatus,
} from '@/api/userFeedback'
import { HttpError } from '@/api/http'

const form = ref({
  title: '',
  content: '',
  contact: '',
})

const submitting = ref(false)
const loadingList = ref(false)
const feedbackList = ref<UserFeedbackItem[]>([])

const contentCount = computed(() => form.value.content.trim().length)

function statusText(status: UserFeedbackStatus): string {
  if (status === 'processing') return '处理中'
  if (status === 'resolved') return '已解决'
  if (status === 'rejected') return '暂不采纳'
  return '待处理'
}

function formatTime(v?: string | null): string {
  if (!v) return ''
  const date = new Date(v)
  if (Number.isNaN(date.getTime())) return v
  const y = date.getFullYear()
  const m = String(date.getMonth() + 1).padStart(2, '0')
  const d = String(date.getDate()).padStart(2, '0')
  const hh = String(date.getHours()).padStart(2, '0')
  const mm = String(date.getMinutes()).padStart(2, '0')
  return `${y}-${m}-${d} ${hh}:${mm}`
}

function errorText(error: unknown): string {
  if (error instanceof HttpError) return error.message
  if (error instanceof Error) return error.message
  return '提交失败，请稍后重试'
}

async function loadFeedbackList() {
  loadingList.value = true
  try {
    const result = await apiListMyUserFeedbacks({ page: 1, per_page: 20 })
    feedbackList.value = result.data
  } catch {
    feedbackList.value = []
  } finally {
    loadingList.value = false
  }
}

async function submitFeedback() {
  const title = form.value.title.trim()
  const content = form.value.content.trim()
  const contact = form.value.contact.trim()

  if (!title) {
    uni.showToast({ title: '请填写需求标题', icon: 'none' })
    return
  }
  if (content.length < 10) {
    uni.showToast({ title: '需求详情至少 10 个字', icon: 'none' })
    return
  }

  submitting.value = true
  try {
    await apiCreateUserFeedback({
      title,
      content,
      contact: contact || null,
    })
    uni.showToast({ title: '已提交，感谢你的建议', icon: 'success' })
    form.value = {
      title: '',
      content: '',
      contact: '',
    }
    await loadFeedbackList()
  } catch (error) {
    uni.showToast({ title: errorText(error), icon: 'none', duration: 2600 })
  } finally {
    submitting.value = false
  }
}

onMounted(() => {
  void loadFeedbackList()
})
</script>

<style lang="scss" scoped>
.feedback-page {
  min-height: 100vh;
  padding: 24rpx;
  box-sizing: border-box;
  background: #f6f7fb;
}

.feedback-hero__desc {
  margin-top: 14rpx;
  display: block;
  font-size: 24rpx;
  line-height: 1.6;
  color: #6b7280;
}

.feedback-card {
  margin-top: 22rpx;
  padding: 26rpx;
  border-radius: 28rpx;
  background: #fff;
  border: 1rpx solid rgba(139, 92, 246, 0.1);
  box-shadow: 0 4rpx 20rpx rgba(31, 35, 41, 0.03);
}

.feedback-card--history {
  margin-bottom: 30rpx;
}

.feedback-field {
  margin-bottom: 22rpx;
}

.feedback-field__label {
  display: block;
  margin-bottom: 12rpx;
  font-size: 26rpx;
  font-weight: 700;
  color: #1f2329;
}

.feedback-input,
.feedback-textarea {
  width: 100%;
  box-sizing: border-box;
  border-radius: 18rpx;
  border: 1rpx solid rgba(139, 92, 246, 0.22);
  background: #fcfbff;
  font-size: 28rpx;
  color: #1f2329;
}

.feedback-input {
  height: 88rpx;
  padding: 0 20rpx;
}

.feedback-textarea {
  min-height: 220rpx;
  padding: 18rpx 20rpx;
}

.feedback-input__placeholder {
  color: #a1a1aa;
}

.feedback-field__counter {
  margin-top: 8rpx;
  display: block;
  text-align: right;
  font-size: 22rpx;
  color: #9ca3af;
}

.feedback-submit {
  margin-top: 8rpx;
  width: 100%;
  border: none !important;
  border-radius: 999rpx;
  background: #8b5cf6 !important;
  color: #fff !important;
  font-size: 30rpx;
  font-weight: 700;
  line-height: 1;
  padding: 28rpx 0;
  box-shadow: 0 12rpx 32rpx rgba(139, 92, 246, 0.24);
}

.feedback-submit::after {
  border: none !important;
}

.feedback-section-head__title {
  display: block;
  margin-bottom: 12rpx;
  font-size: 28rpx;
  font-weight: 800;
  color: #1f2329;
}

.feedback-empty {
  padding: 18rpx 0 8rpx;
}

.feedback-empty__text {
  font-size: 24rpx;
  color: #9ca3af;
}

.feedback-history-item {
  padding: 20rpx 0;
  border-bottom: 1rpx solid rgba(31, 35, 41, 0.06);
}

.feedback-history-item:last-child {
  border-bottom: none;
  padding-bottom: 0;
}

.feedback-history-item__head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16rpx;
}

.feedback-history-item__title {
  flex: 1;
  min-width: 0;
  font-size: 27rpx;
  font-weight: 700;
  color: #1f2329;
}

.feedback-history-item__time {
  margin-top: 8rpx;
  display: block;
  font-size: 22rpx;
  color: #9ca3af;
}

.feedback-history-item__remark {
  margin-top: 10rpx;
  display: block;
  font-size: 24rpx;
  line-height: 1.6;
  color: #6b7280;
}

.feedback-status {
  flex-shrink: 0;
  font-size: 22rpx;
  font-weight: 700;
  padding: 8rpx 16rpx;
  border-radius: 999rpx;
}

.feedback-status--pending {
  background: #fee2e2;
  color: #b91c1c;
}

.feedback-status--processing {
  background: #fef3c7;
  color: #b45309;
}

.feedback-status--resolved {
  background: #dcfce7;
  color: #166534;
}

.feedback-status--rejected {
  background: #e5e7eb;
  color: #4b5563;
}
</style>
