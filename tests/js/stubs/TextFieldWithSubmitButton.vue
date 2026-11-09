<template>
  <input class="text-field-stub" :value="modelValue">
</template>

<script setup lang="ts">
defineProps<{ modelValue?: string }>()
</script>
