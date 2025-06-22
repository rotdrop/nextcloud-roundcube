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
        {{ componentLabels.undoColorChoice }}
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
                   v-model="rgbColorString"
                   :palette="colorPickerPalette"
                   :shown.sync="pickerVisible"
                   v-bind="$attrs"
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
           @click.prevent="submitColorChoice"
    >
  </div>
</template>
<script setup lang="ts">
import type { Color as NCColorType } from '@nextcloud/vue'
import { Color as RGBColor } from '../util/color.ts'
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
// import type { PropType } from 'vue'

type NcColorPickerType = typeof NcColorPicker

// eslint-disable-next-line @typescript-eslint/no-explicit-any
const isNCColorType = (arg: any): arg is NCColorType =>
  !!arg && !(arg instanceof RGBColor) && (typeof arg === 'object') && !isNaN(arg.r + arg.g + arg.b)

const props = withDefaults(
  defineProps<{
    modelValue?: string,
    label?: string,
    componentLabels?: {
      openColorPicker: string,
      submitColorChoice: string,
      undoColorChoice: string,
      revertColorPalette: string,
      resetColorPalette: string,
    },
    colorPalette?: RGBColor[],
  }>(), {
    modelValue: '#000000',
    label: () => t(appName, 'pick a color'),
    componentLabels: () => {
      return {
        openColorPicker: t(appName, 'open'),
        submitColorChoice: t(appName, 'submit'),
        undoColorChoice: t(appName, 'undo color choice'),
        revertColorPalette: t(appName, 'restore palette'),
        resetColorPalette: t(appName, 'factory reset palette'),
      }
    },
    colorPalette: () => [],
  },
)

const anyToRgb = (value: NCColorType|RGBColor|string|number[]) => {
  if (value instanceof RGBColor) {
    return value
  }
  let r: number, g: number, b: number
  let name = t(appName, 'Custom Color')
  if (isNCColorType(value)) {
    r = value.r
    g = value.g
    b = value.b
    name = value?.name || name
  } else if (Array.isArray(value)) {
    r = value[0]
    g = value[1]
    b = value[2]
  } else { // if (typeof value === 'string') {
    const colorString = (value.startsWith('#') ? value.substring(1) : value) + '000000'
    r = parseInt(colorString.substring(0, 2), 16)
    g = parseInt(colorString.substring(2, 4), 16)
    b = parseInt(colorString.substring(4, 6), 16)
  }
  // const Ctor = factoryColorPalette.value![0].constructor
  // rgbColor.value = new Ctor(r, g, b, name)
  return new RGBColor(r, g, b, name)
}
const rgbColor = ref(anyToRgb(props.modelValue))
const rgbColorString = computed({
  set: (value: string) => { rgbColor.value = anyToRgb(value) },
  get: () => rgbColor.value?.color || '',
})
const pickerVisible = ref(false)
const factoryColorPalette = ref<undefined|RGBColor[]>(undefined)
const colorPickerPalette = ref<undefined|RGBColor[]>(undefined)
const savedState = reactive({
  rgbColor: undefined as undefined|RGBColor,
  colorPickerPalette: undefined as undefined|RGBColor[],
})
const loading = ref(true)

const handleOpen = () => {}

const submitCustomColor = (color: RGBColor) => {
  prependColorToPalette(color, colorPickerPalette.value!)
}

const submitColorChoice = () => {
  pickerVisible.value = false
  savedState.rgbColor = rgbColor.value
  emit('submit', rgbColorString)
}

const revertColorPalette = () => {
  colorPickerPalette.value!.splice(0, Infinity, ...savedState.colorPickerPalette!)
}

const resetColorPalette = () => {
  colorPickerPalette.value!.splice(0, Infinity, ...factoryColorPalette.value!)
}

const prependColorToPalette = (rgbColor: RGBColor, palette: RGBColor[]) => {
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
const rgbToGrayScale = (color: RGBColor):number => {
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
  'submit',
  'update:value',
  'update:modelValue',
  'update:model-value',
  'update:color-palette',
])

watch(() => props.modelValue, (newValue) => {
  if (loading.value) {
    return
  }
  rgbColor.value = anyToRgb(newValue)
  if (newValue !== rgbColor.value.color) {
    emit('update:value', rgbColorString.value)
    emit('input', rgbColorString.value)
  }
})

const colorPicker = ref<null|NcColorPickerType>(null)

let paletteIsUpdating = false

watch(
  colorPickerPalette,
  (newValue) => {
    if (loading.value || paletteIsUpdating) {
      return
    }
    // colorPaletteHasChanged.value = true ??? computed property
    for (const [index, color] of (colorPickerPalette.value || []).entries()) {
      if (typeof color === 'string') {
        colorPickerPalette.value![index] = anyToRgb(color)
      }
    }
    console.debug('EMITTING UPDATE COLOR PALETTE', {
      palette: colorPickerPalette.value,
      asString: colorPickerPalette.value!.toString(),
    })
    emit('update:color-palette', newValue)
  },
  { deep: true },
)

watch(() => props.colorPalette, (newValue, oldValue) => {
  if (loading.value) {
    return
  }
  console.debug('PROPS PALETTE WATCHER', {
    newValue: newValue ? JSON.stringify(newValue, undefined, 2) : newValue,
    oldValue: oldValue ? JSON.stringify(oldValue, undefined, 2) : oldValue,
    equal: !!newValue && !!oldValue && JSON.stringify(newValue) === JSON.stringify(oldValue),
  })
  if (!!newValue && !!oldValue && JSON.stringify(newValue) === JSON.stringify(oldValue)) {
    console.debug('PALETTES ARE EQUAL, SKIPPING UPDATE')
    return
  }
  if (newValue && Array.isArray(newValue) && colorPickerPalette.value) {
    paletteIsUpdating = true
    const newPalette = newValue.map(color => anyToRgb(color))
    colorPickerPalette.value.splice(0, Infinity, ...newPalette)
    nextTick(() => { paletteIsUpdating = false })
  }
})

watch(rgbColorString, () => {
  console.debug('RGB COLOR CHANGE', {
    rgbColor: rgbColor.value,
    rgbColorString: rgbColorString.value,
  })
  emit('update:value', rgbColorString.value)
  emit('update:model-value', rgbColorString.value)
  emit('update:modelValue', rgbColorString.value)
})

onMounted(() => {
  // This seemingly stupid construct of having
  // this.colorPickerPalette === undefined at start enables us to peek
  // the default palette from the NC color picker widget.
  factoryColorPalette.value = colorPicker.value!.palette.map(color => anyToRgb(color))
  if (props.colorPalette && Array.isArray(props.colorPalette) && props.colorPalette.length > 0) {
    colorPickerPalette.value = props.colorPalette.map(color => anyToRgb(color))
  } else {
    colorPickerPalette.value = [...factoryColorPalette.value]
  }
  console.debug('PALETTE IS NOW', {
    active: colorPickerPalette.value,
    activeString: colorPickerPalette.value.toString(),
    activeJSON: JSON.stringify(colorPickerPalette.value),
    props: props.colorPalette,
    factory: factoryColorPalette.value,
    factoryString: colorPickerPalette.value.toString(),
  })
  if (rgbColor.value) {
    prependColorToPalette(rgbColor.value, colorPickerPalette.value)
  }
  saveState()
  nextTick(() => { loading.value = false })
})

</script>
<script lang="ts">
export default {
  name: 'ColorPickerExtension',
  inheritAttrs: false,
  model: {
    prop: 'modelValue',
    event: 'update:modelValue',
  },
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
    min-height: var(--default-clickable-area);
    max-height: var(--default-clickable-area);
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
