<template>
  <view class="pi">
    <view class="pi__hero">
      <view class="pi__avatar-wrap" @click="onAvatarTap">
        <view class="pi__avatar-ring">
          <image v-if="avatarSrc" class="pi__avatar-img" :src="avatarSrc" mode="aspectFill" />
          <text v-else class="pi__avatar-letter">{{ avatarLetter }}</text>
        </view>
        <view class="pi__avatar-cam" aria-hidden="true">
          <text class="pi__avatar-cam-ico">📷</text>
        </view>
      </view>
      <text class="pi__avatar-hint">点击更换头像</text>
    </view>

    <view class="pi__card">
      <view class="pi__row pi__row--tap" @click="openNicknameSheet">
        <text class="pi__k">昵称</text>
        <text class="pi__v pi__v--ellipsis">{{ nicknameDisplay }}</text>
        <text class="pi__chev">›</text>
      </view>
      <view class="pi__rule" />
      <picker mode="selector" :range="genderLabels" :value="genderPickerIndex" @change="onGenderPick">
        <view class="pi__row pi__row--tap">
          <text class="pi__k">性别</text>
          <text class="pi__v">{{ genderDisplay }}</text>
          <text class="pi__chev">›</text>
        </view>
      </picker>
      <view class="pi__rule" />
      <picker mode="date" :value="birthdayValue" @change="onBirthPick">
        <view class="pi__row pi__row--tap">
          <text class="pi__k">生日</text>
          <text class="pi__v">{{ birthdayDisplay }}</text>
          <text class="pi__chev">›</text>
        </view>
      </picker>
      <view class="pi__rule" />
      <picker mode="region" :value="regionCodesForPicker" @change="onRegionPick">
        <view class="pi__row pi__row--tap">
          <text class="pi__k">所在地区</text>
          <text class="pi__v pi__v--ellipsis">{{ regionDisplay }}</text>
          <text class="pi__chev">›</text>
        </view>
      </picker>
    </view>

    <text class="pi__sec-title">联系信息</text>
    <view class="pi__card">
      <view class="pi__row">
        <text class="pi__k">手机号</text>
        <text class="pi__v pi__v--phone">{{ phoneMaskedDisplay }}</text>
        <view class="pi__chip" hover-class="pi__chip--hover" :hover-stay-time="80" @click.stop="onChangePhoneTap">
          <text class="pi__chip-txt">更换</text>
        </view>
      </view>
      <view class="pi__rule" />
      <view class="pi__row pi__row--tap" @click="onWechatRowTap">
        <text class="pi__k">微信</text>
        <text class="pi__v">{{ wechatBindText }}</text>
        <text class="pi__chev">›</text>
      </view>
    </view>

    <!-- 微信头像 -->
    <view
      v-if="wxAvatarSheetVisible"
      class="pi__mask"
      @touchmove.stop.prevent
      @click="wxAvatarSheetVisible = false"
    >
      <view class="pi__sheet" @click.stop>
        <text class="pi__sheet-title">使用微信头像</text>
        <button class="pi__sheet-primary" open-type="chooseAvatar" @chooseavatar="onWxChooseAvatar">
          选择微信头像
        </button>
        <button class="pi__sheet-cancel" @click="wxAvatarSheetVisible = false">取消</button>
      </view>
    </view>

    <!-- 昵称 -->
    <view
      v-if="nickSheetVisible"
      class="pi__mask"
      @touchmove.stop.prevent
      @click="closeNicknameSheet"
    >
      <view class="pi__sheet pi__sheet--nick" @click.stop>
        <text class="pi__sheet-title">修改昵称</text>
        <input
          v-model="nicknameDraft"
          class="pi__nick-input"
          type="nickname"
          maxlength="24"
          placeholder="输入昵称或使用微信昵称"
        />
        <view class="pi__sheet-actions">
          <button class="pi__sheet-btn pi__sheet-btn--ghost" @click="closeNicknameSheet">取消</button>
          <button class="pi__sheet-btn pi__sheet-btn--primary" @click="saveNickname">保存</button>
        </view>
      </view>
    </view>
  </view>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { onShow } from '@dcloudio/uni-app'
import { fetchMeProfile, putMeProfile } from '@/api/me'
import { HttpError } from '@/api/http'
import type { UserProfileDto } from '@/types/profile'
import { useAuth } from '@/composables/useAuth'
import { API_BASE_URL } from '@/constants'

const STORAGE_REGION = 'me_personal_region_v1'
const STORAGE_PHONE = 'me_personal_phone_digits_v1'

const { currentUser, isLoggedIn, syncAuthFromSupabase, setCurrentUser } = useAuth()

const wxAvatarSheetVisible = ref(false)
const nickSheetVisible = ref(false)
const nicknameDraft = ref('')

const birthday = ref('')
const gender = ref<UserProfileDto['gender']>('unknown')
const regionCodes = ref<string[]>(['', '', ''])

const genderOptions: { value: UserProfileDto['gender']; label: string }[] = [
  { value: 'unknown', label: '未设置' },
  { value: 'male', label: '男' },
  { value: 'female', label: '女' },
  { value: 'undisclosed', label: '保密' },
]

const genderLabels = genderOptions.map((g) => g.label)

const genderPickerIndex = computed(() => {
  const i = genderOptions.findIndex((g) => g.value === gender.value)
  return i >= 0 ? i : 0
})

const genderDisplay = computed(() => genderOptions[genderPickerIndex.value]?.label ?? '未设置')

const birthdayValue = computed(() =>
  birthday.value.trim() ? birthday.value : '1990-01-01',
)

const birthdayDisplay = computed(() => (birthday.value.trim() ? birthday.value : '未设置'))

/** 未选择地区时给 picker 合法默认，避免部分端空 value 异常；仅在选择后写入 storage */
const regionCodesForPicker = computed((): string[] => {
  const r = regionCodes.value
  if (r.length >= 3 && r[0] && r[1] && r[2]) return [r[0], r[1], r[2]]
  return ['北京市', '北京市', '东城区']
})

const regionDisplay = computed(() => {
  const r = regionCodes.value
  if (r.length >= 3 && r[0] && r[1] && r[2]) {
    return `${r[0]} ${r[1]} ${r[2]}`
  }
  return '未设置'
})

const nicknameDisplay = computed(() => {
  const n = currentUser.value?.nickname?.trim()
  if (n) return n
  return '微信用户'
})

const avatarSrc = computed(() => {
  const u = currentUser.value?.avatarUrl?.trim()
  return u || ''
})

const avatarLetter = computed(() => {
  const n = nicknameDisplay.value
  const ch = n.trim().charAt(0)
  return ch || '用'
})

const phoneDigits = ref('')

const phoneMaskedDisplay = computed(() => {
  const d = phoneDigits.value.replace(/\D/g, '')
  if (d.length < 7) return '未绑定'
  return `${d.slice(0, 3)}****${d.slice(-4)}`
})

const wechatBindText = computed(() => (isLoggedIn.value ? '已绑定' : '未绑定'))

const apiReady = computed(() => Boolean(API_BASE_URL.trim()))

async function persistProfilePatch(body: Partial<Pick<UserProfileDto, 'birthday' | 'gender'>>) {
  if (!apiReady.value) {
    uni.showToast({ title: '未配置服务端地址', icon: 'none' })
    return
  }
  try {
    await putMeProfile(body)
    uni.showToast({ title: '已保存', icon: 'success' })
  } catch (e) {
    const msg = e instanceof HttpError ? e.message : '保存失败'
    uni.showToast({ title: msg.slice(0, 200), icon: 'none' })
  }
}

function loadLocalExtras() {
  try {
    const raw = uni.getStorageSync(STORAGE_REGION) as string | undefined
    if (typeof raw === 'string' && raw) {
      const j = JSON.parse(raw) as { codes?: string[] }
      if (Array.isArray(j.codes) && j.codes.length >= 3) {
        regionCodes.value = j.codes.map((x) => String(x || ''))
      }
    }
  } catch {
    /* ignore */
  }
  try {
    const p = uni.getStorageSync(STORAGE_PHONE) as string | undefined
    phoneDigits.value = typeof p === 'string' ? p : ''
  } catch {
    phoneDigits.value = ''
  }
}

function saveRegion(codes: string[]) {
  regionCodes.value = codes
  try {
    uni.setStorageSync(STORAGE_REGION, JSON.stringify({ codes }))
  } catch {
    /* ignore */
  }
}

async function loadRemoteProfile() {
  if (!isLoggedIn.value || !apiReady.value) return
  try {
    const res = await fetchMeProfile()
    birthday.value = res.profile.birthday || ''
    gender.value = res.profile.gender || 'unknown'
    const u = currentUser.value
    if (u && typeof res.nickname === 'string') {
      const nick = res.nickname.trim()
      setCurrentUser({ ...u, nickname: nick || undefined })
    }
  } catch {
    uni.showToast({ title: '资料加载失败', icon: 'none' })
  }
}

onShow(async () => {
  await syncAuthFromSupabase()
  if (!isLoggedIn.value) {
    uni.navigateTo({ url: '/pages/login/index?redirect=/pages/me/personal-info' })
    return
  }
  loadLocalExtras()
  await loadRemoteProfile()
})

function onAvatarTap() {
  if (!currentUser.value) return
  uni.showActionSheet({
    itemList: ['使用微信头像', '从相册选择', '拍照'],
    success: (res) => {
      if (res.tapIndex === 0) wxAvatarSheetVisible.value = true
      else if (res.tapIndex === 1) pickLocalAvatar(['album'])
      else if (res.tapIndex === 2) pickLocalAvatar(['camera'])
    },
  })
}

function pickLocalAvatar(sourceType: Array<'album' | 'camera'>) {
  const u = currentUser.value
  if (!u) return
  uni.chooseImage({
    count: 1,
    sizeType: ['compressed'],
    sourceType,
    success: (res) => {
      const p = res.tempFilePaths?.[0]
      if (!p) return
      setCurrentUser({ ...u, avatarUrl: p })
      uni.showToast({ title: '头像已更新', icon: 'success' })
    },
  })
}

function onWxChooseAvatar(e: { detail?: { avatarUrl?: string } }) {
  const u = currentUser.value
  const url = e?.detail?.avatarUrl?.trim()
  wxAvatarSheetVisible.value = false
  if (!url || !u) return
  setCurrentUser({ ...u, avatarUrl: url })
  uni.showToast({ title: '头像已更新', icon: 'success' })
}

function openNicknameSheet() {
  if (!currentUser.value) return
  nicknameDraft.value =
    nicknameDisplay.value === '微信用户' ? '' : nicknameDisplay.value
  nickSheetVisible.value = true
}

function closeNicknameSheet() {
  nickSheetVisible.value = false
}

async function saveNickname() {
  const u = currentUser.value
  if (!u) return
  const v = nicknameDraft.value.trim()
  if (apiReady.value) {
    try {
      await putMeProfile({ nickname: v || null })
    } catch (e) {
      const msg = e instanceof HttpError ? e.message.slice(0, 240) : '保存失败'
      uni.showToast({ title: msg, icon: 'none' })
      return
    }
  }
  setCurrentUser({
    ...u,
    nickname: v || undefined,
  })
  closeNicknameSheet()
  uni.showToast({ title: '昵称已保存', icon: 'success' })
}

function onGenderPick(e: { detail?: { value?: string | number } }) {
  const i = Number(e.detail?.value)
  const opt = genderOptions[i]
  if (!opt) return
  gender.value = opt.value
  void persistProfilePatch({ gender: opt.value })
}

function onBirthPick(e: { detail?: { value?: string } }) {
  const v = String(e.detail?.value || '').trim()
  birthday.value = v
  void persistProfilePatch({ birthday: v || null })
}

function onRegionPick(e: { detail?: { value?: string[] } }) {
  const v = e.detail?.value
  if (!v || v.length < 3) return
  saveRegion([String(v[0] || ''), String(v[1] || ''), String(v[2] || '')])
  uni.showToast({ title: '地区已保存', icon: 'success' })
}

function onChangePhoneTap() {
  uni.showModal({
    title: '更换手机号',
    editable: true,
    placeholderText: '请输入11位手机号',
    success: (res) => {
      if (!res.confirm) return
      const raw = String((res as unknown as { content?: string }).content || '').replace(/\D/g, '')
      if (raw.length !== 11) {
        uni.showToast({ title: '请输入11位数字', icon: 'none' })
        return
      }
      phoneDigits.value = raw
      try {
        uni.setStorageSync(STORAGE_PHONE, raw)
      } catch {
        /* ignore */
      }
      uni.showToast({ title: '已保存（本地）', icon: 'success' })
    },
  })
}

function onWechatRowTap() {
  if (!isLoggedIn.value) {
    uni.showToast({ title: '请先登录', icon: 'none' })
    return
  }
  uni.showToast({
    title: '微信账号已与当前登录态绑定',
    icon: 'none',
    duration: 2200,
  })
}
</script>

<style lang="scss" scoped>
$pi-bg: #f2f3f5;
$pi-card: #ffffff;
$pi-text: #1f2329;
$pi-sub: #6b7280;
$pi-line: rgba(31, 35, 41, 0.06);
$pi-purple: #8b5cf6;

.pi {
  min-height: 100vh;
  padding: 24rpx 24rpx 48rpx;
  box-sizing: border-box;
  background: $pi-bg;
}

.pi__hero {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 32rpx 0 40rpx;
}

.pi__avatar-wrap {
  position: relative;
  width: 200rpx;
  height: 200rpx;
}

.pi__avatar-ring {
  width: 200rpx;
  height: 200rpx;
  border-radius: 999rpx;
  overflow: hidden;
  background: linear-gradient(145deg, #f3eeff, #fff);
  border: 4rpx solid #fff;
  box-shadow: 0 8rpx 28rpx rgba(139, 92, 246, 0.12);
  display: flex;
  align-items: center;
  justify-content: center;
}

.pi__avatar-img {
  width: 100%;
  height: 100%;
}

.pi__avatar-letter {
  font-size: 72rpx;
  font-weight: 800;
  color: $pi-purple;
}

.pi__avatar-cam {
  position: absolute;
  right: 4rpx;
  bottom: 4rpx;
  width: 56rpx;
  height: 56rpx;
  border-radius: 999rpx;
  background: #3b82f6;
  border: 3rpx solid #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 2rpx 10rpx rgba(59, 130, 246, 0.35);
}

.pi__avatar-cam-ico {
  font-size: 26rpx;
  line-height: 1;
}

.pi__avatar-hint {
  margin-top: 20rpx;
  font-size: 26rpx;
  color: $pi-sub;
}

.pi__card {
  background: $pi-card;
  border-radius: 20rpx;
  overflow: hidden;
  margin-bottom: 28rpx;
  box-shadow: 0 2rpx 16rpx rgba(31, 35, 41, 0.04);
}

.pi__sec-title {
  display: block;
  font-size: 26rpx;
  font-weight: 700;
  color: $pi-text;
  margin: 8rpx 8rpx 16rpx;
}

.pi__row {
  display: flex;
  flex-direction: row;
  align-items: center;
  padding: 28rpx 24rpx;
  box-sizing: border-box;
  gap: 16rpx;
}

.pi__row--tap:active {
  background: rgba(139, 92, 246, 0.04);
}

.pi__k {
  flex-shrink: 0;
  font-size: 28rpx;
  color: $pi-text;
  font-weight: 500;
}

.pi__v {
  flex: 1;
  min-width: 0;
  font-size: 28rpx;
  color: $pi-sub;
  text-align: right;
}

.pi__v--ellipsis {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.pi__v--phone {
  font-weight: 600;
  color: #374151;
}

.pi__chev {
  flex-shrink: 0;
  font-size: 32rpx;
  color: #c4c4c4;
  line-height: 1;
}

.pi__rule {
  height: 1rpx;
  margin-left: 24rpx;
  background: $pi-line;
}

.pi__chip {
  flex-shrink: 0;
  padding: 8rpx 22rpx;
  border-radius: 999rpx;
  background: rgba(59, 130, 246, 0.12);
}

.pi__chip--hover {
  opacity: 0.88;
}

.pi__chip-txt {
  font-size: 24rpx;
  font-weight: 600;
  color: #2563eb;
}

.pi__mask {
  position: fixed;
  z-index: 900;
  left: 0;
  right: 0;
  top: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.45);
  display: flex;
  align-items: flex-end;
  justify-content: center;
}

.pi__sheet {
  width: 100%;
  padding: 32rpx 32rpx calc(24rpx + env(safe-area-inset-bottom));
  background: #fff;
  border-radius: 24rpx 24rpx 0 0;
  box-sizing: border-box;
}

.pi__sheet--nick {
  max-height: 70vh;
}

.pi__sheet-title {
  display: block;
  font-size: 32rpx;
  font-weight: 800;
  color: $pi-text;
  text-align: center;
}

.pi__sheet-primary {
  margin-top: 28rpx;
  width: 100%;
  padding: 26rpx;
  font-size: 30rpx;
  font-weight: 800;
  color: #fff !important;
  background: #07c160;
  border-radius: 16rpx;
  border: none;
}

.pi__sheet-primary::after {
  border: none;
}

.pi__sheet-cancel {
  margin-top: 16rpx;
  width: 100%;
  padding: 22rpx;
  font-size: 28rpx;
  font-weight: 600;
  color: $pi-sub;
  background: #f3f4f6;
  border-radius: 16rpx;
  border: none;
}

.pi__sheet-cancel::after {
  border: none;
}

.pi__nick-input {
  margin-top: 28rpx;
  width: 100%;
  height: 88rpx;
  padding: 0 20rpx;
  border-radius: 16rpx;
  border: 1rpx solid rgba(139, 92, 246, 0.2);
  background: #fafbfc;
  font-size: 28rpx;
  color: $pi-text;
  box-sizing: border-box;
}

.pi__sheet-actions {
  margin-top: 28rpx;
  display: flex;
  flex-direction: row;
  gap: 16rpx;
}

.pi__sheet-btn {
  flex: 1;
  padding: 22rpx;
  font-size: 28rpx;
  font-weight: 700;
  border-radius: 16rpx;
  border: none;
}

.pi__sheet-btn::after {
  border: none;
}

.pi__sheet-btn--ghost {
  color: $pi-sub;
  background: #f3f4f6;
}

.pi__sheet-btn--primary {
  color: #fff !important;
  background: $pi-purple;
}
</style>
