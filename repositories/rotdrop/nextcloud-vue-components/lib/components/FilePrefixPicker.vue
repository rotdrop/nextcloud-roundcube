<script>
/**
 * @author Claus-Justus Heine <himself@claus-justus-heine.de>
 * @copyright 2022 Claus-Justus Heine
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */
</script>
<template>
  <div class="input-wrapper">
    <div v-if="hint" class="hint">
      {{ hint }}
    </div>
    <div class="flex flex-center flex-wrap">
      <div class="dirname">
        <a href="#"
           class="file-picker button"
           @click="openFilePicker(...arguments)"
        >
          {{ pathInfo.dirName + (pathInfo.dirName !== '/' ? '/' : '') }}
        </a>
      </div>
      <SettingsInputText v-model="pathInfo.baseName"
                         label=""
                         class="flex-grow"
                         :placeholder="placeholder"
                         :readonly="readonly === 'basename'"
                         @update="$emit('update', pathInfo)"
      />
    </div>
  </div>
</template>
<script>

// import { appName } from '../config.js'
import Vue from 'vue'
import { getFilePickerBuilder, showError, showInfo, TOAST_PERMANENT_TIMEOUT } from '@nextcloud/dialogs'
import SettingsInputText from '../components/SettingsInputText'

export default {
  name: 'FilePrefixPicker',
  components: {
    SettingsInputText,
  },
  emits: [
    'input',
    // 'update:modelValue', Vue 3
  ],
  props: {
    value: {
      type: Object,
      default: () => {
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
    filePickerTitle: {
      type: String,
      // default: t(appName, 'Choose a prefix-folder'),
      default: 'Choose a prefix-folder',
    },
  },
  data() {
    return {
      pathInfo: {
        dirName: null,
        baseName: null,
      },
    }
  },
  created() {
    this.pathInfo = this.value
    if (!this.pathInfo.baseName && this.baseName) {
      Vue.set(this.pathInfo, 'baseName', this.baseName)
    }
    if (!this.pathInfo.dirName && this.dirName) {
      Vue.set(this.pathInfo, 'dirName', this.dirName)
    }
  },
  computed: {
    pathName() {
      return (this.pathInfo.dirName ? this.pathInfo.dirName + '/' : '') + this.pathInfo.baseName
    }
  },
  watch: {
    pathName(newValue, oldValue) {
      this.$emit('input', this.pathInfo) // Vue 2
    },
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
        // showError(t(appName, 'Invalid path selected: "{dir}".', { dir }), { timeout: TOAST_PERMANENT_TIMEOUT })
        $this.$emit('error:invalidDirName', dir)
      } else  {
        if (dir === '/') {
          dir = ''
        }
        // showInfo(t(appName, 'Selected path: "{dir}/{base}/".', { dir, base: this.pathInfo.baseName }))
        this.$emit('update:dirName', dir, this.pathInfo.baseName)
        Vue.set(this.pathInfo, 'dirName', dir)
      }
    },
  },
}
</script>
<style lang="scss" scoped>
.input-wrapper {
  .dirname {
    font-weight:bold;
    font-family:monospace;
    .button {
      display:block;
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
