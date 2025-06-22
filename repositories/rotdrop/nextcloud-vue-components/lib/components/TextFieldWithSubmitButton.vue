<!--
 - @copyright Copyright (c) 2019, 2022, 2023, 2024, 2025 Julius Härtl <jus@bitgrid.net>
 - @copyright Copyright (c) 2022 Claus-Justus Heine <himself@claus-justus-heine.de>
 -
 - @author Julius Härtl <jus@bitgrid.net>
 - @author Claus-Justus Heine <himself@claus-justus-heine.de>
 - @license AGPL-3.0-or-later
 -
 - This program is free software: you can redistribute it and/or modify
 - it under the terms of the GNU Affero General Public License as
 - published by the Free Software Foundation, either version 3 of the
 - License, or (at your option) any later version.
 -
 - This program is distributed in the hope that it will be useful,
 - but WITHOUT ANY WARRANTY; without even the implied warranty of
 - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 - GNU Affero General Public License for more details.
 -
 - You should have received a copy of the GNU Affero General Public License
 - along with this program. If not, see <http://www.gnu.org/licenses/>.
 -->
<template>
  <div class="component-wrapper">
    <div :class="['alignment-wrapper', ...props.flexContainerClasses]">
      <div v-if="$slots.alignedBefore" :class="['aligned-before', ...props.flexItemClasses]">
        <slot name="alignedBefore" />
      </div>
      <NcTextField ref="ncTextField"
                   v-model="model"
                   v-bind="$attrs"
                   :show-trailing-button="true"
                   trailing-button-icon="arrowRight"
                   v-on="$listeners"
                   @trailing-button-click="$emit('submit', model)"
      >
        <!-- pass through scoped slots -->
        <template v-for="(_, scopedSlotName) in $scopedSlots" #[scopedSlotName]="slotData">
          <slot :name="scopedSlotName" v-bind="slotData" />
        </template>
        <!-- pass through normal slots -->
        <template v-for="(_, slotName) in $slots" #[slotName]>
          <slot :name="slotName" />
        </template>
      </NcTextField>
      <div v-if="$slots.alignedAfter" :class="['aligned-after', ...flexItemClasses]">
        <slot name="alignedAfter" />
      </div>
    </div>
  </div>
</template>
<script setup lang="ts">
import { ref, watch } from 'vue';
import { NcTextField } from '@nextcloud/vue'

const props = withDefaults(defineProps<{
  modelValue?: string|number,
  value?: string|number,
  flexContainerClasses?: string[],
  flexItemClasses?: string[],
  readonly?: boolean,
}>(), {
  modelValue: undefined,
  value: undefined,
  flexContainerClasses: () => ['flex-justify-left', 'flex-align-start'],
  flexItemClasses: () => ['flex-justify-left', 'flex-align-start'],
  readonly: false,
})

const emit = defineEmits([
  'submit',
  'input',
  'update:modelValue',
  'update:model-value',
  'update:value',
])

// Keep a private data of the copy in order to support even missing
// value or modelValue props. Still hitting the submit button should
// present the current input value as event data.
const model = ref<string|number|undefined>(props.modelValue || props.value || '')

watch(() => props.value, (value) => { model.value = value })
watch(() => props.modelValue, (value) => { model.value = value })

watch(model, (value) => {
  emit('update:modelValue', value)
  emit('update:model-value', value)
  emit('update:value', value)
  emit('input', value)
})
</script>
<script lang="ts">
export default {
  name: 'TextFieldWithSubmitButton',
  inheritAttrs: false,
  model: {
    prop: 'modelValue',
    event: 'update:modelValue',
  },
}
</script>
<style lang="scss" scoped>
.component-wrapper {
  .hint {
    color: var(--color-text-lighter);
    font-style:italic;
  }
  // Tweak the submit button of the NcTextField
  .input-field::v-deep { // wrapper
    margin-block-start: 0;
    &.input-field--trailing-icon .input-field__input,
    .input-field__input.input-field__input--trailing-icon {
      &[dir="rtl"] {
        // still it is a trailing icon, so for rtl the margins have to be interchanged
        padding-inline-end: 12px;
        padding-inline-start: var(--default-clickable-area);
        // + .input-field__label {
        //   margin-inline-end: 9px;
        //   margin-inline-start: var(--default-clickable-area);
        // }
      }
    }
    .input-field__trailing-button.button-vue--vue-tertiary-no-background {
      max-height: var(--default-clickable-area);
      max-width: var(--default-clickable-area);
      // FIXME: instead we probably should switch to material design icons for everything else ...
      background-image: var(--icon-confirm-dark);
      background-position: center;
      background-repeat: no-repeat;
      .button-vue__icon {
        opacity: 0;
      }
      &:hover, &:focus {
        &:not(:disabled) {
          top: 0;
          right: 0;
          height: var(--default-clickable-area);
          width: var(--default-clickable-area) !important;
          border: 2px solid var(--color-primary-element);
          border-radius: var(--border-radius-large);
          outline: 2px solid var(--color-main-background);
        }
      }
    }
  }
  &::v-deep .alignment-wrapper {
    margin-block-start: 10px;
    display: flex;
    flex-grow: 1;
    max-width: 100%;
    &.flex- {
      &align- {
        &center {
          align-items: center;
        }
        &baseline {
          align-items: baseline;
        }
        &stretch {
          align-items: stretch;
        }
        &start {
          align-items: start;
        }
        &end {
          align-items: end;
        }
      }
      &justify- {
        &center {
          justify-content: center;
        }
        &start {
          justify-content: flex-start;
        }
        &left {
          justify-content: left;
        }
      }
    }
  }
}
</style>
