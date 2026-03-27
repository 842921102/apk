<template>
  <view class="mp-page cr-pub">
    <view class="mp-card cr-pub__block">
      <text class="mp-kicker mp-kicker--accent">标题</text>
      <text class="cr-pub__optional">（选填，最多 80 字）</text>
      <input v-model="title" class="cr-pub__input" maxlength="80" placeholder="短文标题，可不填" />
    </view>

    <view class="mp-card cr-pub__block">
      <text class="mp-kicker mp-kicker--accent">正文</text>
      <textarea
        v-model="content"
        class="cr-pub__textarea"
        maxlength="2000"
        placeholder="分享你的餐桌灵感…"
      />
      <text class="cr-pub__count">{{ content.length }} / 2000</text>
    </view>

    <view class="mp-card cr-pub__block">
      <text class="mp-kicker mp-kicker--accent">话题</text>
      <picker mode="selector" :range="topicLabels" :value="topicIndex" @change="onTopicPick">
        <view class="cr-pub__pick">
          <text class="cr-pub__pick-val">{{ topicLabels[topicIndex] }}</text>
          <text class="cr-pub__pick-chev">▼</text>
        </view>
      </picker>
    </view>

    <view class="mp-card cr-pub__block">
      <view class="cr-pub__img-head">
        <text class="mp-kicker mp-kicker--accent">图片</text>
        <text class="cr-pub__optional">最多 9 张</text>
      </view>
      <view class="cr-pub__imgs">
        <view v-for="(src, i) in images" :key="i" class="cr-pub__thumb-wrap">
          <image class="cr-pub__thumb" :src="src" mode="aspectFill" @click="preview(i)" />
          <text class="cr-pub__thumb-x" @click.stop="removeImg(i)">×</text>
        </view>
        <view v-if="images.length < 9" class="cr-pub__add" @click="pickImages">
          <text class="cr-pub__add-txt">+</text>
        </view>
      </view>
    </view>

    <button class="mp-btn-primary cr-pub__submit" :disabled="submitting" @click="submit">
      {{ submitting ? '发布中…' : '发布' }}
    </button>
  </view>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import { CIRCLE_TOPICS, createCirclePost } from '@/api/circle'

const title = ref('')
const content = ref('')
const topicIndex = ref(0)
const images = ref<string[]>([])
const submitting = ref(false)

const topicLabels = computed(() => CIRCLE_TOPICS.map((t) => t.label))

onLoad((query) => {
  const images = typeof query?.images === 'string' ? query.images : ''
  const from = typeof query?.from === 'string' ? query.from : ''
  const q = [
    from ? `from=${encodeURIComponent(from)}` : '',
    images ? `images=${encodeURIComponent(images)}` : '',
  ]
    .filter(Boolean)
    .join('&')
  const url = q ? `/pages/inspiration/publish?${q}` : '/pages/inspiration/publish'
  uni.redirectTo({ url })
})

function onTopicPick(e: { detail: { value: string } }) {
  topicIndex.value = Number(e.detail.value) || 0
}

async function pickImages() {
  const remain = 9 - images.value.length
  try {
    const res = await uni.chooseImage({
      count: remain,
      sizeType: ['compressed'],
      sourceType: ['album', 'camera'],
    })
    const paths = res.tempFilePaths || []
    if (paths.length) images.value = images.value.concat(paths)
  } catch {
    // user cancel
  }
}

function removeImg(i: number) {
  images.value = images.value.filter((_, idx) => idx !== i)
}

function preview(i: number) {
  uni.previewImage({ current: images.value[i], urls: images.value })
}

function validate(): string | null {
  if (!content.value.trim()) return '请填写正文'
  if (content.value.trim().length > 2000) return '正文过长'
  if (title.value.length > 80) return '标题过长'
  return null
}

async function submit() {
  const err = validate()
  if (err) {
    uni.showToast({ title: err, icon: 'none' })
    return
  }
  submitting.value = true
  try {
    const ti = topicIndex.value
    await createCirclePost({
      title: title.value.trim(),
      content: content.value.trim(),
      topic: CIRCLE_TOPICS[ti]?.label || '吃喝推荐',
      images: [...images.value],
    })
    uni.showToast({ title: '发布成功', icon: 'success' })
    setTimeout(() => {
      uni.navigateBack({
        fail: () => {
          uni.switchTab({ url: '/pages/circle/index' })
        },
      })
    }, 400)
  } catch (e: unknown) {
    const ex = e as Error
    uni.showToast({ title: ex.message || '发布失败', icon: 'none' })
  } finally {
    submitting.value = false
  }
}
</script>

<style lang="scss" scoped>
@import '@/uni.scss';

.cr-pub__block {
  margin-bottom: 24rpx;
}

.cr-pub__optional {
  font-size: 22rpx;
  color: $mp-text-muted;
  margin-left: 8rpx;
}

.cr-pub__input {
  display: block;
  margin-top: 16rpx;
  padding: 16rpx 0;
  font-size: 28rpx;
  border-bottom: 1rpx solid #f3f4f6;
}

.cr-pub__textarea {
  margin-top: 16rpx;
  width: 100%;
  min-height: 220rpx;
  font-size: 28rpx;
  line-height: 1.55;
  color: $mp-text-primary;
}

.cr-pub__count {
  display: block;
  margin-top: 8rpx;
  text-align: right;
  font-size: 22rpx;
  color: $mp-text-muted;
}

.cr-pub__pick {
  margin-top: 16rpx;
  padding: 20rpx 20rpx;
  border-radius: 16rpx;
  border: 1rpx solid $mp-border;
  background: #fafbfc;
  display: flex;
  flex-direction: row;
  justify-content: space-between;
  align-items: center;
}

.cr-pub__pick-val {
  font-size: 28rpx;
  font-weight: 600;
  color: $mp-text-primary;
}

.cr-pub__pick-chev {
  font-size: 22rpx;
  color: $mp-text-muted;
}

.cr-pub__img-head {
  display: flex;
  flex-direction: row;
  align-items: baseline;
  gap: 8rpx;
}

.cr-pub__imgs {
  margin-top: 16rpx;
  display: flex;
  flex-direction: row;
  flex-wrap: wrap;
  gap: 12rpx;
}

.cr-pub__thumb-wrap {
  position: relative;
  width: 200rpx;
  height: 200rpx;
  border-radius: 12rpx;
  overflow: hidden;
  border: 1rpx solid $mp-border;
}

.cr-pub__thumb {
  width: 100%;
  height: 100%;
}

.cr-pub__thumb-x {
  position: absolute;
  top: 4rpx;
  right: 4rpx;
  width: 40rpx;
  height: 40rpx;
  line-height: 40rpx;
  text-align: center;
  border-radius: 50%;
  background: rgba(0, 0, 0, 0.45);
  color: #fff;
  font-size: 28rpx;
  font-weight: 700;
}

.cr-pub__add {
  width: 200rpx;
  height: 200rpx;
  border-radius: 12rpx;
  border: 1rpx dashed $mp-border;
  background: #fafbfc;
  display: flex;
  align-items: center;
  justify-content: center;
}

.cr-pub__add-txt {
  font-size: 64rpx;
  font-weight: 300;
  color: $mp-text-muted;
}

.cr-pub__submit {
  margin-top: 16rpx;
}
</style>
