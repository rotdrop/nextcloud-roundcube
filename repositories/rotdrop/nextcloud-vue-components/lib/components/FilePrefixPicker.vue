<!--
 - @author Claus-Justus Heine <himself@claus-justus-heine.de>
 - @copyright 2022, 2023, 2024 Claus-Justus Heine
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
           @click.prevent.stop="!disabled && openFilePicker(...arguments)"
        >
          {{ pathInfo.dirName + (pathInfo.dirName !== '/' ? '/' : '') }}
        </a>
      </div>
      <SettingsInputText v-if="!onlyDirName"
                         v-model="pathInfo.baseName"
                         v-tooltip="unclippedPopup(pathInfo.baseName)"
                         label=""
                         class="flex-grow"
                         :placeholder="placeholder"
                         :readonly="readonly === 'basename'"
                         :disabled="disabled"
                         @update="$emit('update', pathInfo)"
      />
    </div>
  </div>
</template>
<script>
// import { appName } from '../config.js'
import { set as vueSet } from 'vue'
import {
  getFilePickerBuilder,
  showError,
  showInfo,
  TOAST_PERMANENT_TIMEOUT,
} from '@nextcloud/dialogs'
import SettingsInputText from '../components/SettingsInputText.vue'
import '@nextcloud/dialogs/style.css'

const appName = APP_NAME // e.g. by webpack DefinePlugin

export default {
  name: 'FilePrefixPicker',
  components: {
    SettingsInputText,
  },
  props: {
    value: {
      type: Object,
      default() {
        return {
          baseName: this.pathInfo.baseName,
          dirName: this.pathInfo.dirName,
        }
      },
    },
    baseName: {
      type: String,
      default: undefined,
    },
    dirName: {
      type: String,
      default: undefined,
    },
    onlyDirName: {
      type: Boolean,
      default: false,
    },
    hint: {
      type: String,
      default: undefined,
    },
    placeholder: {
      type: String,
      default: undefined,
    },
    readonly: {
      type: [Boolean, String],
      default: undefined,
    },
    disabled: {
      type: Boolean,
      default: false,
    },
    filePickerTitle: {
      type: String,
      default() {
        return this.onlyDirName ? t(appName, 'Choose a folder') : t(appName, 'Choose a prefix-folder')
      },
    },
  },
  emits: [
    'input',
    // 'update:modelValue', Vue 3
  ],
  data() {
    return {
      pathInfo: {
        dirName: null,
        baseName: null,
      },
    }
  },
  computed: {
    pathName() {
      return (this.pathInfo.dirName ? this.pathInfo.dirName + '/' : '') + (this.onlyDirName ? '' : this.pathInfo.baseName)
    },
  },
  watch: {
    pathName(newValue, oldValue) {
      this.$emit('input', this.pathInfo) // Vue 2
    },
  },
  created() {
    this.pathInfo = this.value
    if (!this.pathInfo.baseName && this.baseName) {
      vueSet(this.pathInfo, 'baseName', this.baseName)
    }
    if (!this.pathInfo.dirName && this.dirName) {
      vueSet(this.pathInfo, 'dirName', this.dirName)
    }
    if (!this.pathInfo.dirName) {
      vueSet(this.pathInfo, 'dirName', '/')
    }
  },
  methods: {
    async openFilePicker() {
      const picker = getFilePickerBuilder(this.filePickerTitle)
        .startAt(this.pathInfo.dirName)
        .setMultiSelect(false)
        .setModal(true)
        .setType(1)
        .setMimeTypeFilter(['httpd/unix-directory'])
        .allowDirectories()
        .build()

      let dir = await picker.pick() || '/'
      if (dir.startsWith('//')) { // new in Nextcloud 25?
        dir = dir.slice(1)
      }
      if (!dir.startsWith('/')) {
        showError(t(appName, 'Invalid path selected: "{dir}".', { dir }), { timeout: TOAST_PERMANENT_TIMEOUT })
        this.$emit('error:invalid-dir-name', dir)
      } else {
        if (dir === '/') {
          dir = ''
        }
        showInfo(t(appName, 'Selected path: "{dir}/{base}/".', { dir, base: this.pathInfo.baseName }))
        this.$emit('update:dirName', dir, this.pathInfo.baseName)
        vueSet(this.pathInfo, 'dirName', dir)
        if (this.onlyDirName) {
          this.$emit('update', this.pathInfo)
        }
      }
    },
    unclippedPopup(content, html) {
      return {
        content,
        preventOverflow: true,
        html: true,
        // shown: true,
        // triggers: [],
        csstag: ['vue-tooltip-unclipped-popup'],
      }
    },
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
