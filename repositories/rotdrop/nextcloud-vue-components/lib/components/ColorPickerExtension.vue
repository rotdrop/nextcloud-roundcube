<!--
 - @author Claus-Justus Heine <himself@claus-justus-heine.de>
 - @copyright 2022, 2024, 2025 Claus-Justus Heine
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
           @click="emit('update', rgbColor)"
    >
  </div>
</template>
<script setup lang="ts">
import type { Color as RGBColorType } from '@nextcloud/vue'
import { appName } from '../config.ts'
import {
  NcActions,
  NcActionButton,
  NcButton,
  NcColorPicker,
} from '@nextcloud/vue'
import {
  nextTick,
  computed,
  ref,
  watch,
  onMounted,
  reactive,
} from 'vue'
import { translate as t } from '@nextcloud/l10n'

type NcColorPickerType = typeof NcColorPicker

// eslint-disable-next-line @typescript-eslint/no-explicit-any
const isRGBColor = (arg: any): arg is RGBColorType =>
  !!arg && !Array.isArray(arg) && typeof arg === 'object' && (arg.r || arg.g || arg.b || arg.color) !== undefined

const props = withDefaults(
  defineProps<{
    value?: RGBColorType|string|number[],
    label?: string,
    componentLabels?: {
      openColorPicker: string,
      submitColorChoice: string,
      revertColor: string,
      revertColorPalette: string,
      resetColorPalette: string,
    },
    colorPalette: RGBColorType[],
  }>(), {
    value: undefined,
    label: () => t(appName, 'pick a color'),
    componentLabels: () => {
      return {
        openColorPicker: t(appName, 'open'),
        submitColorChoice: t(appName, 'submit'),
        revertColor: t(appName, 'revert color'),
        revertColorPalette: t(appName, 'restore palette'),
        resetColorPalette: t(appName, 'factory reset palette'),
      }
    },
    colorPalette: () => [],
  },
)

const rgbColor = ref<undefined|RGBColorType>(undefined)
const pickerVisible = ref(false)
const factoryColorPalette = ref<undefined|RGBColorType[]>(undefined)
const colorPickerPalette = ref<undefined|RGBColorType[]>(undefined)
const savedState = reactive({
  rgbColor: undefined as undefined|RGBColorType,
  colorPickerPalette: undefined as undefined|RGBColorType[],
})
const loading = ref(true)

const handleOpen = () => {}

const submitCustomColor = (color: RGBColorType) => {
  prependColorToPalette(color, colorPickerPalette.value!)
}

const submitColorChoice = () => {
  pickerVisible.value = false
  savedState.rgbColor = rgbColor.value
}

const revertColorPalette = () => {
  colorPickerPalette.value!.splice(0, Infinity, ...savedState.colorPickerPalette!)
}

const resetColorPalette = () => {
  colorPickerPalette.value!.splice(0, Infinity, ...factoryColorPalette.value!)
}

const prependColorToPalette = (rgbColor: RGBColorType, palette: RGBColorType []) => {
  const rgb = rgbColor.color
  if (palette.findIndex(rgbColor => rgbColor.color === rgb) < 0) {
    palette.pop()
    palette.splice(0, 0, rgbColor)
  }
}

/**
 * Convert an RGH color to a grey-scale value. This is used to
 * switch the trigger-button color between black and white,
 * depending on the grey-value of the color.
 *
 * @param color RGB color
 *
 * @return {number} Grey-value corresponding to rgb.
 */
const rgbToGrayScale = (color: RGBColorType):number => {
  // const r = Number('0x' + rgb.substring(1, 3))
  // const g = Number('0x' + rgb.substring(3, 5))
  // const b = Number('0x' + rgb.substring(5, 7))
  return (0.3 * color.r + 0.59 * color.g + 0.11 * color.b) / 255.0
}

const saveState = () => {
  savedState.rgbColor = rgbColor.value
  savedState.colorPickerPalette = [...colorPickerPalette.value!]
  prependColorToPalette(rgbColor.value!, savedState.colorPickerPalette)
}

defineExpose({
  saveState,
})

const cssVariables = computed(() => {
  return {
    '--button-background-color': rgbColor.value!.color,
    '--button-foreground-color': rgbToGrayScale(rgbColor.value!) > 0.5 ? 'black' : 'white',
  }
})
const colorPaletteIsDefault = computed(() =>
  loading.value || ('' + colorPickerPalette.value) === ('' + factoryColorPalette.value))

const colorPaletteHasChanged = computed(() =>
  !loading.value && ('' + colorPickerPalette.value) !== ('' + savedState.colorPickerPalette))

const emit = defineEmits([
  'error',
  'input',
  'update',
  'update:value',
  'update:color-palette',
])

watch(() => props.value, (newValue) => {
  if (loading.value) {
    return
  }
  if (newValue === undefined || isRGBColor(newValue)) {
    rgbColor.value = newValue
  } else {
    let r: number, g: number, b: number
    const name = t(appName, 'Custom Color')
    if (Array.isArray(newValue)) {
      r = newValue[0]
      g = newValue[1]
      b = newValue[2]
    } else {
      const colorString = (newValue.startsWith('#') ? newValue.substring(1) : newValue) + '000000'
      r = parseInt(colorString.substring(0, 2), 16)
      g = parseInt(colorString.substring(2, 4), 16)
      b = parseInt(colorString.substring(4, 6), 16)
    }
    const Ctor = factoryColorPalette.value![0].constructor
    rgbColor.value = new Ctor(r, g, b, name)
  }
  emit('update:value', rgbColor.value)
  emit('input', rgbColor.value)
})

const colorPicker = ref<null | NcColorPickerType>(null)

watch(
  colorPickerPalette,
  (newValue) => {
    if (loading.value) {
      return
    }
    // colorPaletteHasChanged.value = true ??? computed property
    emit('update:color-palette', newValue)
  },
  { deep: true },
)

watch(() => props.colorPalette, (newValue, oldValue) => {
  if (loading.value) {
    return
  }
  if (!!newValue && !!oldValue && newValue.toString() === oldValue.toString()) {
    return
  }
  if (newValue && Array.isArray(newValue) && colorPickerPalette.value) {
    colorPickerPalette.value.splice(0, Infinity, ...newValue)
  }
})

onMounted(() => {
  // This seemingly stupid construct of having
  // this.colorPickerPalette === undefined at start enables us to peek
  // the default palette from the NC color picker widget.
  factoryColorPalette.value = [...colorPicker.value!.palette]
  console.info('FACTORY PALETTE', factoryColorPalette.value)
  colorPickerPalette.value = (props.colorPalette && Array.isArray(props.colorPalette) && props.colorPalette.length > 0)
    ? [...props.colorPalette]
    : [...factoryColorPalette.value]
  console.info('PALETTE IS NOW', colorPickerPalette.value, props.colorPalette, factoryColorPalette.value)
  if (rgbColor.value) {
    prependColorToPalette(rgbColor.value, colorPickerPalette.value)
  }
  saveState()
  nextTick(() => {
    loading.value = false
  })
})

</script>
<script lang="ts">
export default {
  name: 'ColorPickerExtension',
  inheritAttrs: false,
}
</script>
<style scoped lang="scss">
.color-picker-container {
  .trigger-button {
    background-color: var(--button-background-color);
    color: var(--button-foreground-color);
    margin-right:0;
    border-top-right-radius:0;
    border-bottom-right-radius:0;
    &:not(:focus,:hover) {
      border-right:0;
    }
  }
  .confirm-button {
    min-height: 44px; // in order to match NcButton
    border-top-left-radius:0;
    border-bottom-left-radius:0;
    border: 2px solid var(--color-border-dark);
    &:hover:not(:disabled) {
      border: 2px solid var(--color-primary-element);
    }
    &:not(:focus,:hover) {
      border-left:2px solid var(--color-background-dark);
    }
  }
}
</style>
