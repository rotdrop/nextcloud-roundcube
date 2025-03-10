<!--
 - @author Claus-Justus Heine
 - @copyright 2024, 2025 Claus-Justus Heine <himself@claus-justus-heine.de>
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
  <div v-tooltip="tooltipToShow"
       :class="['input-wrapper', { empty, required }, ...actionClasses]"
  >
    <label v-if="!labelOutside && inputLabel" :for="selectId" class="select-with-submit-button-label">
      {{ inputLabel }}
    </label>
    <div :class="['alignment-wrapper', ...flexContainerClasses]">
      <div v-if="$slots.alignedBefore" :class="['aligned-before', ...flexItemClasses]">
        <slot name="alignedBefore" />
      </div>
      <div :class="['select-combo-wrapper', { loading }, ...flexItemClasses, ...actionClasses]">
        <NcSelect ref="ncSelect"
                  v-bind="$attrs"
                  v-model="value"
                  :multiple="props.multiple"
                  :label-outside="true"
                  :clearable="props.clearable"
                  :disabled="props.disabled"
                  :required="props.required"
                  :input-id="selectId"
                  v-on="$listeners"
                  @open="active = true"
                  @close="active = false"
        >
          <!-- pass through scoped slots -->
          <template v-for="(_, scopedSlotName) in $scopedSlots" #[scopedSlotName]="slotData">
            <slot :name="scopedSlotName" v-bind="slotData" />
          </template>

          <!-- pass through normal slots -->
          <template v-for="(_, slotName) in $slots" #[slotName]>
            <slot :name="slotName" />
          </template>

          <!-- after iterating over slots and scopedSlots, you can customize them like this -->
          <!-- <template v-slot:overrideExample>
               <slot name="overrideExample" />
               <span>This text content goes to overrideExample slot</span>
               </template> -->
        </NcSelect>
        <input v-if="submitButton"
               v-tooltip="active ? false : t(appName, 'Click to submit your changes.')"
               type="submit"
               class="select-with-submit-button icon-confirm"
               value=""
               :disabled="disabled"
               @click="emitUpdate"
        >
      </div>
      <NcActions v-if="$slots.actions || clearAction || resetAction"
                 :disabled="disabled"
                 :class="['aligned-after', ...flexItemClasses]"
      >
        <slot name="actions" />
        <NcActionButton v-if="resetAction"
                        icon="icon-history"
                        @click="resetChanges"
        >
          {{ t(appName, 'Reset Changes') }}
        </NcActionButton>
        <NcActionButton v-if="clearAction"
                        icon="icon-delete"
                        @click="clearSelection"
        >
          {{ t(appName, 'Clear Selection') }}
        </NcActionButton>
      </NcActions>
      <div v-if="$slots.alignedAfter" :class="['aligned-after', ...flexItemClasses]">
        <slot name="alignedAfter" />
      </div>
    </div>
    <p v-if="hint !== ''" class="hint">
      {{ hint }}
    </p>
  </div>
</template>
<script setup lang="ts">
import {
  NcActions,
  NcActionButton,
  NcSelect,
} from '@nextcloud/vue'
import { appName } from '../config.ts'
import { translate as t } from '@nextcloud/l10n'
import {
  computed,
  ref,
  watch,
} from 'vue'
import { v4 as uuidv4 } from 'uuid'

type ItemType = string|number|Record<string, unknown>
type ValueType = ItemType|ItemType[]

const props = withDefaults(
  defineProps<{
    // show an loading indicator on the wrapper select
    loading?: boolean,
    disabled?: boolean,
    // clearable allows deselection of the last item
    clearable?: boolean,
    /**
     * Allow selection of multiple options
     *
     * @see https://vue-select.org/api/props.html#multiple
     */
    multiple?: boolean,
    // required blocks the final submit if no value is selected
    required?: boolean,
    labelOutside?: boolean,
    inputLabel?: string,
    inputId?: string,
    hint?: string,
    tooltip?: Record<string, string>|string|boolean,
    value?: ValueType,
    flexContainerClasses?: string[],
    flexItemClasses?: string[],
    // configure default button and action additions
    submitButton?: boolean,
    clearAction?: boolean,
    resetAction?: boolean,
    resetState?: ValueType,
  }>(), {
    loading: false,
    disabled: false,
    // clearable allows deselection of the last item
    clearable: true,
    /**
     * Allow selection of multiple options
     *
     * @see https://vue-select.org/api/props.html#multiple
     */
    multiple: false,
    // required blocks the final submit if no value is selected
    required: false,
    labelOutside: false,
    inputLabel: undefined,
    inputId: undefined,
    hint: undefined,
    tooltip: undefined,
    value: undefined,
    flexContainerClasses: () => ['flex-justify-left', 'flex-align-center'],
    flexItemClasses: () => ['flex-justify-left', 'flex-align-center'],
    // configure default button and action additions
    submitButton: true,
    clearAction: false,
    resetAction: false,
    resetState: undefined,
  },
)

const value = ref<undefined|ValueType>(props.value)
const active = ref(false)
const ncSelect = ref<null|typeof NcSelect>(null)

const actionClasses = computed(() => {
  const classes: string[] = []
  props.submitButton && classes.push('submit-button')
  props.clearAction && classes.push('clear-action')
  props.resetAction && classes.push('reset-action')
  return classes
})

// const submitAction = computed(() => this.actionClasses.indexOf('submit-button') !== -1)
const empty = computed(() => !props.value || (Array.isArray(props.value) && props.value.length === 0))
const tooltipToShow = computed(() => {
  if (active.value) {
    return false
  }
  if (props.tooltip) {
    return props.tooltip
  }
  if (empty.value && props.required) {
    return t(appName, 'Please select an item!')
  }
  return false
})
const selectId = computed(() => props.inputId || uuidv4())

defineExpose({
  ncSelect,
})

// receive updates from the parent ...
watch(() => props.value, (newValue) => { value.value = newValue })

const emit = defineEmits([
  'error',
  'input',
  'update',
  'update:modelValue',
])

const emitInput = (value: ValueType|undefined) => {
  emit('input', value)
  emit('update:modelValue', value)
}

const emitUpdate = () => {
  if (props.required && empty.value) {
    emit('error', t(appName, 'An empty value is not allowed, please make your choice!'))
  } else {
    emitInput(value.value)
    emit('update', value.value)
  }
}

const resetChanges = () => {
  emitInput(props.resetState)
}

const clearSelection = () => {
  emitInput(props.multiple ? [] : undefined)
}
</script>
<script lang="ts">
export default {
  name: 'SelectWithSubmitButton',
  inheritAttrs: false,
}
</script>
<style lang="scss" scoped>
.input-wrapper {
  position:relative;
  display: flex;
  flex-wrap: wrap;
  flex-direction: column;
  width: 100%;
  margin: 0 0 var(--default-grid-baseline);
  &::v-deep .alignment-wrapper {
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
  .loading-indicator.loading {
    position:absolute;
    width:0;
    height:0;
    top:50%;
    left:50%;
  }
  label.select-with-submit-button-label {
    width: 100%;
  }
  .select-combo-wrapper {
    display: flex;
    max-width: 100%;
    align-items: stretch;
    flex-grow: 1;
    flex-wrap: nowrap;
    .v-select.select {
      flex-grow:1;
      max-width:100%;
      margin: 0 0 0;
    }
    &.submit-button::v-deep .v-select.select {
      .vs__dropdown-toggle {
        // substract the round borders for the overlay
        padding-right: calc(var(--default-clickable-area) - var(--vs-border-radius));
      }
      + .select-with-submit-button.icon-confirm {
        flex-shrink: 0;
        width:var(--default-clickable-area);
        align-self: stretch;
        // align-self: stretch should do what we want here :)
        // height:var(--default-clickable-area);
        margin: 0 0 0 calc(0px - var(--default-clickable-area));
        z-index: 2;
        border-radius: var(--vs-border-radius) var(--vs-border-radius);
        border-style: none;
        background-color: rgba(0, 0, 0, 0);
        background-clip: padding-box;
        opacity: 1;
        &:hover, &:focus {
          /* &:not(:readonly), */ &:not(:disabled) {
            border: var(--vs-border-width) var(--vs-border-style) var(--color-primary-element);
            border-radius: var(--vs-border-radius);
            outline: 2px solid var(--color-main-background);
            background-color: var(--vs-search-input-bg);
            padding: calc((var(--default-clickable-area) - 1lh)/2 - var(--vs-border-width)) calc(3*var(--default-grid-baseline));
          }
        }
      }
      &.vs--disabled + .select-with-submit-button.icon-confirm {
        cursor: var(--vs-disabled-cursor);
      }
    }
  }
  &.empty.required:not(.loading) {
    .v-select.select::v-deep .vs__dropdown-toggle {
      border-color: red;
    }
  }
  .hint {
    color: var(--color-text-lighter);
    font-size:80%;
  }
}
</style>
<style lang="scss">
[csstag="vue-tooltip-data-popup"].v-popper--theme-tooltip {
  .v-popper__inner div div {
    text-align: left !important;
    *:not(h4) {
      color: var(--color-text-lighter);
      font-size:80%;
    }
    h4 {
      font-weight: bold;
      font-size: 100%;
    }
  }
}
.v-popper--theme-tooltip.v-popper__popper {
  z-index: 100010 !important;
}
</style>
