<!--
 - @author Claus-Justus Heine <himself@claus-justus-heine.de>
 - @copyright 2022, 2023, 2024, 2025 Claus-Justus Heine
 - @license AGPL-3.0-or-later
 -
 - This program is free software: you can redistribute it and/or modify
 - it under the terms of the GNU Affero General Public License as
 - published by the Free Software Foundation, either version 3 of the
 - License, or (at your option) any later version.
 -
 - This program is distributed in the hope that it will be useful,
 - but WITHOUT ANY WARRANTY; without even the implied warranty of
 - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 - GNU Affero General Public License for more details.
 -
 - You should have received a copy of the GNU Affero General Public License
 - along with this program. If not, see <http://www.gnu.org/licenses/>.
 -->
<template>
  <div class="input-wrapper">
    <div v-if="hint" class="hint">
      {{ hint }}
    </div>
    <div class="flex flex-center flex-wrap">
      <div v-tooltip="unclippedPopup(pathInfo.dirName)"
           class="dirname"
      >
        <a href="#"
           class="file-picker button icon-folder"
           @click.prevent.stop="() => !disabled && openFilePicker()"
        >
          {{ displayDirName }}
        </a>
      </div>
      <TextFieldWithSubmitButton v-if="!onlyDirName"
                                 v-model="pathInfo.baseName"
                                 v-tooltip="unclippedPopup(pathInfo.baseName)"
                                 :label="placeholder"
                                 class="flex-grow"
                                 :placeholder="placeholder"
                                 :readonly="readonly === 'basename'"
                                 :disabled="disabled"
                                 @submit="emit('submit', pathInfo)"
      />
    </div>
  </div>
</template>
<script setup lang="ts">
import { appName } from '../config.ts'
import {
  computed,
  onBeforeMount,
  reactive,
  set as vueSet,
  watch,
} from 'vue'
import {
  getFilePickerBuilder,
  showError,
  showInfo,
  TOAST_PERMANENT_TIMEOUT,
  FilePickerType,
} from '@nextcloud/dialogs'
import TextFieldWithSubmitButton from './TextFieldWithSubmitButton.vue'
import { translate as t } from '@nextcloud/l10n'
import '@nextcloud/dialogs/style.css' // still needed?

const props = withDefaults(
  defineProps<{
    modelValue?: { baseName: string, dirName: string },
    baseName?: string,
    dirName?: string,
    onlyDirName?: boolean
    hint: string,
    placeholder?: string,
    readonly?: boolean|string,
    disabled?: boolean,
    filePickerTitle?: string,
  }>(), {
    modelValue: () => { return { baseName: '', dirName: '' } },
    baseName: undefined,
    dirName: undefined,
    onlyDirName: false,
    hint: undefined,
    placeholder: undefined,
    readonly: undefined,
    disabled: undefined,
    filePickerTitle: undefined,
  }
)

const emit = defineEmits([
  'input',
  'submit',
  'error:invalid-dir-name',
  'update:dirName',
  'update:modelValue',
  'update:model-value',
  'update:value',
])

const pathInfo = reactive(props.modelValue)

const pathName = computed(() =>
  (pathInfo.dirName ? pathInfo.dirName + '/' : '') + (props.onlyDirName ? '' : pathInfo.baseName)
)

const displayDirName = computed(() =>
  !pathInfo.dirName
  ? './'
  : pathInfo.dirName + (pathInfo.dirName !== '/' ? '/' : '')
)

const filePickerTitle = computed(() =>
  props.filePickerTitle
  || props.onlyDirName ? t(appName, 'Choose a folder') : t(appName, 'Choose a prefix-folder')
)

watch(pathName, () => {
  emit('update:modelValue', pathInfo)
  emit('update:model-value', pathInfo)
  emit('update:value', pathInfo)
  emit('input', pathInfo)
})

onBeforeMount(() => {
  pathInfo.dirName = props.modelValue?.dirName || ''
  pathInfo.baseName = props.value?.baseName || ''
  if (!pathInfo.baseName && props.baseName) {
    vueSet(pathInfo, 'baseName', props.baseName)
  }
  if (!pathInfo.dirName && props.dirName) {
    vueSet(pathInfo, 'dirName', props.dirName)
  }
  if (!pathInfo.dirName) {
    vueSet(pathInfo, 'dirName', '/')
  }
})

const openFilePicker = async () => {
  const picker = getFilePickerBuilder(filePickerTitle.value)
    .startAt(pathInfo.dirName)
    .setMultiSelect(false)
    .setType(FilePickerType.Choose)
    .setMimeTypeFilter(['httpd/unix-directory'])
    .allowDirectories()
    .build()

  let dir = await picker.pick() || '/'
  if (Array.isArray(dir)) {
    // work around bug in @nextcloud/dialogs@6.2.0
    dir = dir[0]
  }
  if (dir.startsWith('//')) { // new in Nextcloud 25?
    dir = dir.slice(1)
  }
  if (!dir.startsWith('/')) {
    showError(t(appName, 'Invalid path selected: "{dir}".', { dir }), { timeout: TOAST_PERMANENT_TIMEOUT })
    emit('error:invalid-dir-name', dir)
  } else {
    if (dir === '/') {
      dir = ''
    }
    showInfo(t(appName, 'Selected path: "{dir}/{base}/".', { dir, base: pathInfo.baseName }))
    emit('update:dirName', dir, pathInfo.baseName)
    vueSet(pathInfo, 'dirName', dir)
    if (props.onlyDirName) {
      emit('submit', pathInfo)
    }
  }
}

const unclippedPopup = (content: string, html = true) => {
  return {
    content,
    preventOverflow: true,
    html,
    // shown: true,
    // triggers: [],
    csstag: ['vue-tooltip-unclipped-popup'],
  }
}
</script>
<script lang="ts">
export default {
  name: 'FilePrefixPicker',
  model: {
    prop: 'modelValue',
    event: 'update:modelValue',
  },
}
</script>
<style lang="scss">
[csstag="vue-tooltip-unclipped-popup"].v-popper--theme-tooltip {
  .v-popper__inner {
    max-width:unset!important;
  }
}
</style>
<style lang="scss" scoped>
.input-wrapper {
  .dirname {
    margin-block-start: 10px;
    font-weight:bold;
    font-family:monospace;
    .button {
      display:block;
      background-position: 8px center;
      padding-left: 30px;
    }
  }
  .flex {
    display:flex;
    &.flex-center {
      align-items:center;
    }
    &.flex-wrap {
      flex-wrap:wrap;
    }
    .flex-grow {
      flex-grow:1;
    }
  }
}
</style>
