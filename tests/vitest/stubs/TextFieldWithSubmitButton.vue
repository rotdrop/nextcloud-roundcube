<template>
  <input v-bind="$attrs"
         v-model="model"
         class="text-field-stub"
         @keydown.enter="$emit('submit')"
  >
  <slot name="alignedAfter" />
</template>

<script setup lang="ts">
defineOptions({ inheritAttrs: false })
const model = defineModel<string>({ default: '' })
defineEmits(['submit'])
</script>
