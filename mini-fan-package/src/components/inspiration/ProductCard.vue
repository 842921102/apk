<template>
  <view v-if="product" class="ip-card">
    <image class="ip-card__img" :src="product.image" mode="aspectFill" />
    <view class="ip-card__body">
      <text class="ip-card__name">{{ product.name }}</text>
      <text class="ip-card__price">{{ product.priceText }}</text>
      <view class="ip-card__actions">
        <button class="mp-btn-primary ip-card__btn" @click.stop="$emit('view')">查看商品</button>
      </view>
    </view>
  </view>
</template>

<script setup lang="ts">
defineProps<{
  product: { id: string; name: string; priceText: string; image: string } | null | undefined
}>()
defineEmits<{ view: [] }>()
</script>

<style lang="scss" scoped>
@import '@/uni.scss';
.ip-card{display:flex;gap:14rpx;padding:16rpx;border:1rpx solid $mp-border;border-radius:16rpx;background:#fff}
.ip-card__img{width:140rpx;height:140rpx;border-radius:12rpx;flex-shrink:0;background:#f3f4f6}
.ip-card__body{flex:1;min-width:0}
.ip-card__name{display:block;font-size:26rpx;font-weight:700;color:$mp-text-primary}
.ip-card__price{display:block;margin-top:8rpx;font-size:24rpx;color:#d15454}
.ip-card__actions{margin-top:12rpx;display:flex;gap:10rpx}
.ip-card__btn{flex:1}
</style>
