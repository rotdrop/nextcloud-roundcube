<!--
 - @author Claus-Justus Heine <himself@claus-justus-heine.de>
 - @copyright 2022, 2024 Claus-Justus Heine
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
  <div class="color-picker-container flex-container flex-center">
    <NcActions>
      <NcActionButton icon="icon-play"
                      @click="pickerVisible = true"
      >
        {{ componentLabels.openColorPicker }}
      </NcActionButton>
      <NcActionButton icon="icon-confirm"
                      @click="submitColorChoice"
      >
        {{ componentLabels.submitColorChoice }}
      </NcActionButton>
      <NcActionButton icon="icon-history"
                      :disabled="savedState.rgbColor === rgbColor"
                      @click="rgbColor = savedState.rgbColor"
      >
        {{ componentLabels.revertColor }}
      </NcActionButton>
      <NcActionButton icon="icon-toggle-background"
                      :disabled="!colorPaletteHasChanged"
                      @click="revertColorPalette"
      >
        {{ componentLabels.revertColorPalette }}
      </NcActionButton>
      <NcActionButton icon="icon-toggle-background"
                      :disabled="colorPaletteIsDefault"
                      @click="resetColorPalette"
      >
        {{ componentLabels.resetColorPalette }}
      </NcActionButton>
    </NcActions>
    <NcColorPicker ref="colorPicker"
                   v-model="rgbColor"
                   :palette="colorPickerPalette"
                   :shown.sync="pickerVisible"
                   @submit="submitCustomColor"
                   @update:open="handleOpen"
                   @close="() => false"
    >
      <NcButton :style="cssVariables"
                type="primary"
                class="trigger-button"
      >
        {{ label }}
      </NcButton>
    </NcColorPicker>
    <input type="submit"
           class="icon-confirm confirm-button"
           value=""
           @click="$emit('update', rgbColor)"
    >
  </div>
</template>
<script>
import {
  NcActions,
  NcActionButton,
  NcButton,
  NcColorPicker,
} from '@nextcloud/vue'
import { nextTick, set as vueSet } from 'vue'

const appName = APP_NAME // e.g. by webpack DefinePlugin

export default {
  name: 'ColorPickerExtension',
  components: {
    NcActionButton,
    NcActions,
    NcButton,
    NcColorPicker,
  },
  inheritAttrs: false,
  props: {
    value: {
      type: String,
      default: '',
    },
    label: {
      type: String,
      default: t(appName, 'pick a color'),
    },
    componentLabels: {
      type: Object,
      default: () => {
        return {
          openColorPicker: t(appName, 'open'),
          submitColorChoice: t(appName, 'submit'),
          revertColor: t(appName, 'revert color'),
          revertColorPalette: t(appName, 'restore palette'),
          resetColorPalette: t(appName, 'factory reset palette'),
        }
      },
    },
    colorPalette: {
      type: Array,
      default: () => [],
    },
  },
  data() {
    return {
      pickerVisible: false,
      factoryColorPalette: undefined,
      colorPickerPalette: undefined,
      savedState: {
        rgbColor: undefined,
        colorPickerPalette: undefined,
      },
      loading: true,
      id: this._uid,
    }
  },
  computed: {
    cssVariables() {
      return {
        '--button-background-color': this.rgbColor,
        '--button-foreground-color': this.rgbToGrayScale(this.rgbColor) > 0.5 ? 'black' : 'white',
      }
    },
    colorPaletteIsDefault() {
      return this.loading || this.colorPickerPalette.toString() === this.factoryColorPalette.toString()
    },
    colorPaletteHasChanged() {
      return !this.loading && this.colorPickerPalette.toString() !== this.savedState.colorPickerPalette.toString()
    },
    /**
     * Writable computable property which updates this.value through
     * sending an event to the parent.
     */
    rgbColor: {
      set(newValue) {
        if (this.loading) {
          return
        }
        newValue = newValue.toLowerCase()
        this.$emit('update:value', newValue)
        this.$emit('input', newValue)
      },
      get() {
        return this.value
      },
    },
  },
  watch: {
    colorPickerPalette: {
      handler(newValue, oldValue) {
        this.info('PALETTE', newValue, oldValue)
        if (this.loading) {
          return
        }
        this.colorPaletteHasChanged = true
        this.$emit('update:color-palette', newValue)
      },
      deep: true,
    },
    colorPalette(newValue, oldValue) {
      if (this.loading) {
        return
      }
      if (!!newValue && !!oldValue && newValue.toString() === oldValue.toString()) {
        return
      }
      if (newValue && Array.isArray(newValue) && this.colorPickerPalette) {
        this.colorPickerPalette.splice(0, Infinity, ...newValue)
      }
    },
  },
  created() {
    // console.info('VALUE', this.value, this.rgbColor, this.oldRgbColor)
    // console.info('LOADING IN CREATED', this.loading)
  },
  mounted() {
    // This seemingly stupid construct of having
    // this.colorPickerPalette === undefined at start enables us to peek
    // the default palette from the NC color picker widget.
    this.factoryColorPalette = [...this.$refs.colorPicker.palette]
    this.info('FACTORY PALETTE', this.factoryColorPalette)
    vueSet(
      this,
      'colorPickerPalette',
      (this.colorPalette && Array.isArray(this.colorPalette) && this.colorPalette.length > 0)
        ? [...this.colorPalette]
        : [...this.factoryColorPalette],
    )
    this.info('PALETTE IS NOW', this.colorPickerPalette, this.colorPalette, this.factoryColorPalette)
    if (this.rgbColor) {
      this.prependColorToPalette(this.rgbColor)
    }
    this.saveState()
    nextTick(() => {
      this.loading = false
    })
  },
  methods: {
    info(...args) {
      console.info(this.$options.name, ...args)
    },
    submitCustomColor(color) {
      this.prependColorToPalette(color)
    },
    submitColorChoice(color) {
      this.pickerVisible = false
      this.oldRgbColor = this.rgbColor
    },
    handleOpen() {
    },
    revertColorPalette() {
      this.colorPickerPalette.splice(0, Infinity, ...this.oldColorPalette)
    },
    resetColorPalette() {
      this.colorPickerPalette.splice(0, Infinity, ...this.factoryColorPalette)
    },
    prependColorToPalette(color, destinationStorage) {
      if (destinationStorage === undefined) {
        destinationStorage = this
      }
      color = color.toLowerCase()
      if (!destinationStorage.colorPickerPalette.includes(color)) {
        const palette = [...destinationStorage.colorPickerPalette]
        palette.pop()
        palette.splice(0, 0, color)
        vueSet(destinationStorage, 'colorPickerPalette', palette)
      }
    },
    /**
     * Convert an RGH color to a grey-scale value. This is used to
     * switch the trigger-button color between black and white,
     * depending on the grey-value of the color.
     *
     * @param {Array} rgb RGB color array.
     *
     * @return {number} Grey-value corresponding to rgb.
     */
    rgbToGrayScale(rgb) {
      const r = Number('0x' + rgb.substring(1, 3))
      const g = Number('0x' + rgb.substring(3, 5))
      const b = Number('0x' + rgb.substring(5, 7))
      return (0.3 * r + 0.59 * g + 0.11 * b) / 255.0
    },
    saveState() {
      this.savedState.rgbColor = this.rgbColor
      this.savedState.colorPickerPalette = [...this.colorPickerPalette]
      this.prependColorToPalette(this.rgbColor, this.savedState)
    },
  },
}
</script>
<style scoped lang="scss">
.color-picker-container {
  .trigger-button {
    margin-right:0;
    border-top-right-radius:0;
    border-bottom-right-radius:0;
    &:not(:focus,:hover) {
      border-right:0;
    }
    background-color: var(--button-background-color);
    color: var(--button-foreground-color);
  }
  .confirm-button {
    border-top-left-radius:0;
    border-bottom-left-radius:0;
    &:not(:focus,:hover) {
      border-left:2px solid var(--color-background-dark);
    }
    min-height: 44px; // in order to match NcButton
    border: 2px solid var(--color-border-dark);
    &:hover:not(:disabled) {
      border: 2px solid var(--color-primary-element);
    }
  }
}
</style>
