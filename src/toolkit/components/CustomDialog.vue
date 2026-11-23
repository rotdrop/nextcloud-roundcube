<!--
  - SPDX-FileCopyrightText: 2026 Claus-Justus Heine
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
  <NcDialog :class="dialogClasses"
            :dialogClasses="['nc-generic-dialog', ...dialogClasses]"
            :buttons="dialogButtons"
            :name="name"
            :message="text"
            @update:open="emit('close', false)"
  >
    <NcNoteCard v-if="severity" :type="severity">
      <div ref="content">
        <!-- HTML is deliberately opt-in and must be trusted by the caller. -->
        <!-- eslint-disable-next-line vue/no-v-html -->
        <div v-if="html !== undefined" v-html="html" />
        <p v-else v-text="text" />
      </div>
    </NcNoteCard>
    <div v-else ref="content">
      <!-- eslint-disable-next-line vue/no-v-html -->
      <div v-if="html !== undefined" v-html="html" />
      <p v-else v-text="text" />
    </div>
  </NcDialog>
</template>

<script setup lang="ts">
import type { IDialogButton, IDialogSeverity } from '@nextcloud/dialogs'
import type { CustomDialogResult } from '../util/custom-dialog.ts'

import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue'
import NcDialog from '@nextcloud/vue/components/NcDialog'
import NcNoteCard from '@nextcloud/vue/components/NcNoteCard'

const props = withDefaults(defineProps<{
  name: string
  text: string
  html?: string
  buttons: IDialogButton[]
  severity?: IDialogSeverity
  dialogClasses?: string[]
  onReady?: (content: HTMLElement) => void
}>(), {
  html: undefined,
  severity: undefined,
  dialogClasses: () => [],
  onReady: undefined,
})

const emit = defineEmits<{ close: [result: CustomDialogResult] }>()
const content = ref<HTMLElement>()
let unmounted = false
const dialogButtons = computed(() => props.buttons.map((button) => ({
  ...button,
  callback() {
    button.callback()
    emit('close', true)
  },
})))

onBeforeUnmount(() => {
  unmounted = true
})

onMounted(async () => {
  if (!props.onReady) {
    return
  }
  // Wait until spawnDialog has completed mounting before emitting a failure.
  await nextTick()
  if (unmounted) {
    return
  }
  try {
    if (!content.value) {
      throw new Error('Custom dialog content not found')
    }
    props.onReady(content.value)
  } catch (error) {
    emit('close', { error })
  }
})
</script>

<style>
.nc-generic-dialog .dialog__actions {
  justify-content: space-between;
  min-width: calc(100% - 12px);
}
</style>
