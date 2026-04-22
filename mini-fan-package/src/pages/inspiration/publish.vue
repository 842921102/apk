<template>
  <view class="mp-page ipb">
    <view class="mp-card">
      <text class="mp-kicker mp-kicker--accent">内容类型</text>
      <view class="ipb__types">
        <view class="ipb__type" :class="{ 'ipb__type--on': sourceType === 'ai_generated' }" @click="sourceType = 'ai_generated'">AI生成</view>
        <view class="ipb__type" :class="{ 'ipb__type--on': sourceType === 'user_uploaded' }" @click="sourceType = 'user_uploaded'">用户实拍</view>
      </view>
    </view>

    <view class="mp-card">
      <text class="mp-kicker mp-kicker--accent">图片（最多9张）</text>
      <view class="ipb__imgs">
        <view v-for="(src, i) in images" :key="i" class="ipb__img-wrap">
          <image class="ipb__img" :src="src" mode="aspectFill" />
          <text class="ipb__x" @click="removeImage(i)">×</text>
        </view>
        <view v-if="images.length < 9" class="ipb__add" @click="pickImage">+</view>
      </view>
    </view>

    <view class="mp-card">
      <text class="mp-kicker mp-kicker--accent">标题</text>
      <input v-model="title" class="ipb__input" maxlength="80" placeholder="一句话标题（选填）" />
      <text class="mp-kicker mp-kicker--accent">描述</text>
      <textarea v-model="description" class="ipb__textarea" maxlength="1000" placeholder="补充一点灵感说明..." />
      <text class="ipb__muted">预留字段：related_product_id（商品挂载）</text>
    </view>

    <button class="mp-btn-primary" :disabled="submitting" @click="submit">{{ submitting ? '发布中...' : '发布到灵感' }}</button>
  </view>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import { createInspiration } from '@/api/inspiration'
import type { InspirationPublishSource, InspirationSourceType } from '@/types/inspiration'

const title = ref('')
const description = ref('')
const images = ref<string[]>([])
const sourceType = ref<InspirationSourceType>('user_uploaded')
const publishSource = ref<InspirationPublishSource>('manual_upload')
const submitting = ref(false)

onLoad((query) => {
  const preset = typeof query?.images === 'string' ? decodeURIComponent(query.images) : ''
  const fromAi = typeof query?.from === 'string' ? query.from === 'ai_result' : false
  const presetTitle = typeof query?.title === 'string' ? decodeURIComponent(query.title) : ''
  const presetDesc = typeof query?.description === 'string' ? decodeURIComponent(query.description) : ''

  if (preset) images.value = preset.split(',').filter(Boolean).slice(0, 9)
  if (fromAi) {
    sourceType.value = 'ai_generated'
    publishSource.value = 'ai_result'
  }
  if (presetTitle) title.value = presetTitle
  if (presetDesc) description.value = presetDesc
})

async function pickImage() {
  try {
    const choose = await uni.chooseImage({ count: 9 - images.value.length, sizeType: ['compressed'], sourceType: ['album', 'camera'] })
    images.value = images.value.concat(choose.tempFilePaths || []).slice(0, 9)
  } catch {
    // ignore
  }
}
function removeImage(i: number) { images.value = images.value.filter((_, idx) => idx !== i) }

async function submit() {
  if (!images.value.length) return uni.showToast({ title: '请至少上传1张图片', icon: 'none' })
  if (!description.value.trim() && !title.value.trim()) return uni.showToast({ title: '请填写标题或描述', icon: 'none' })
  submitting.value = true
  try {
    await createInspiration({
      title: title.value.trim(),
      description: description.value.trim(),
      images: [...images.value],
      sourceType: sourceType.value,
      publishSource: publishSource.value,
      relatedProductId: null,
    })
    uni.showToast({ title: '发布成功', icon: 'success' })
    setTimeout(() => {
      uni.navigateBack({ fail: () => uni.switchTab({ url: '/pages/inspiration/index' }) })
    }, 300)
  } finally {
    submitting.value = false
  }
}
</script>

<style lang="scss" scoped>
@import '@/uni.scss';
.ipb__types{display:flex;gap:12rpx;margin-top:10rpx}
.ipb__type{padding:10rpx 18rpx;border-radius:999rpx;background:#f5f5f7;font-size:24rpx;color:$mp-text-secondary}
.ipb__type--on{background:$mp-accent-soft;color:$mp-accent;border:1rpx solid $mp-ring-accent}
.ipb__imgs{margin-top:10rpx;display:flex;flex-wrap:wrap;gap:12rpx}
.ipb__img-wrap{position:relative;width:200rpx;height:200rpx;border-radius:12rpx;overflow:hidden;border:1rpx solid $mp-border}
.ipb__img{width:100%;height:100%}
.ipb__x{position:absolute;top:2rpx;right:2rpx;width:36rpx;height:36rpx;line-height:36rpx;text-align:center;border-radius:50%;background:rgba(0,0,0,.4);color:#fff}
.ipb__add{width:200rpx;height:200rpx;border-radius:12rpx;border:1rpx dashed $mp-border;background:#fafbfc;display:flex;align-items:center;justify-content:center;font-size:56rpx;color:$mp-text-muted}
.ipb__input{margin-top:10rpx;padding:12rpx 0;font-size:26rpx;border-bottom:1rpx solid #f3f4f6}
.ipb__textarea{margin-top:10rpx;min-height:220rpx;font-size:26rpx;line-height:1.55}
.ipb__muted{display:block;margin-top:8rpx;font-size:22rpx;color:$mp-text-muted}
</style>
