<template>
  <section class="app-page-hero">
    <div class="app-page-hero__bg" />
    <div class="app-page-hero__glow-a" />
    <div class="app-page-hero__glow-b" />
    <div class="app-page-hero__noise" />
    <div class="app-page-hero__body">
      <p v-if="eyebrow || $slots.eyebrow" class="app-page-hero__eyebrow">
        <slot name="eyebrow">{{ eyebrow }}</slot>
      </p>
      <h1 class="app-page-hero__title">
        <slot name="title">{{ title }}</slot>
      </h1>
      <p v-if="subtitle || $slots.subtitle" class="app-page-hero__subtitle">
        <slot name="subtitle">{{ subtitle }}</slot>
      </p>
    </div>
  </section>
</template>

<script setup lang="ts">
withDefaults(
  defineProps<{
    eyebrow?: string
    title?: string
    subtitle?: string
  }>(),
  {
    eyebrow: '',
    title: '',
    subtitle: ''
  }
)
</script>
