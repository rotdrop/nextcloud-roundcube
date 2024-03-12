<!--
 - @author Claus-Justus Heine
 - @copyright 2024 Claus-Justus Heine <himself@claus-justus-heine.de>
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
  <div :class="['input-wrapper', { empty, required }, ...actionClasses]">
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
                  v-tooltip="tooltipToShow"
                  :value="value"
                  :multiple="multiple"
                  :label-outside="true"
                  :clearable="clearable"
                  :disabled="disabled"
                  :required="required"
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
<script>

import { NcSelect, NcActions, NcActionButton } from '@nextcloud/vue'

const appName = APP_NAME // e.g. by webpack DefinePlugin

export default {
  name: 'SelectWithSubmitButton',
  components: {
    NcSelect,
    NcActions,
    NcActionButton,
  },
  inheritAttrs: false,
  props: {
    // show an loading indicator on the wrapper select
    loading: {
      type: Boolean,
      default: false,
    },
    disabled: {
      type: Boolean,
      default: false,
    },
    // clearable allows deselection of the last item
    clearable: {
      type: Boolean,
      default: true,
    },
    /**
     * Allow selection of multiple options
     *
     * @see https://vue-select.org/api/props.html#multiple
     */
    multiple: {
      type: Boolean,
      default: false,
    },
    // required blocks the final submit if no value is selected
    required: {
      type: Boolean,
      default: false,
    },
    labelOutside: {
      type: Boolean,
      default: false,
    },
    inputLabel: {
      type: String,
      required: false,
      default: null,
    },
    inputId: {
      type: String,
      required: false,
      default: null,
    },
    hint: {
      type: String,
      required: false,
      default: null,
    },
    tooltip: {
      type: [Object, String, Boolean],
      required: false,
      default: undefined,
    },
    value: {
      type: [String, Number, Object, Array],
      default: null,
    },
    flexContainerClasses: {
      type: Array,
      default: () => ['flex-justify-left', 'flex-align-center'],
    },
    flexItemClasses: {
      type: Array,
      default: () => ['flex-justify-left', 'flex-align-center'],
    },
    // configure default button and action additions
    submitButton: {
      type: Boolean,
      default: true,
    },
    clearAction: {
      type: Boolean,
      default: false,
    },
    resetAction: {
      type: Boolean,
      default: false,
    },
    resetState: {
      type: [String, Number, Object, Array],
      default: null,
    },
  },
  data() {
    return {
      active: false,
      id: this._uid,
      ncSelect: undefined,
    }
  },
  computed: {
    actionClasses() {
      const classes = []
      this.submitButton && classes.push('submit-button')
      this.clearAction && classes.push('clear-action')
      this.resetAction && classes.push('reset-action')

      return classes
    },
    submitAction() {
      return this.action.indexOf('submit') !== -1
    },
    empty() {
      return !this.value || (Array.isArray(this.value) && this.value.length === 0)
    },
    tooltipToShow() {
      if (this.active) {
        return false
      }
      if (this.tooltip) {
        return this.tooltip
      }
      if (this.empty && this.required) {
        return t(appName, 'Please select an item!')
      }
      return false
    },
    selectId() {
      return this.inputId || this._uid + '-select-input-id'
    },
    listenersToForward() {
      const listeners = { ...this.$listeners }
      // delete listeners.input
      return listeners
    },
    attributesToForward() {
      const attributes = { ...this.$attrs }
      // delete attributes.value
      return attributes
    },
  },
  created() {
    this.id = this._uid
  },
  mounted() {
    this.ncSelect = this.$refs?.ncSelect
  },
  methods: {
    info(...args) {
      console.info(this.$options.name, ...args)
    },
    emitInput(value) {
      this.$emit('input', value)
      this.$emit('update:modelValue', value)
    },
    emitUpdate() {
      if (this.required && this.empty) {
        this.$emit('error', t(appName, 'An empty value is not allowed, please make your choice!'))
      } else {
        this.emitInput(this.value)
        this.$emit('update', this.value)
      }
    },
    resetChanges() {
      this.emitInput(this.resetState)
    },
    clearSelection() {
      this.emitInput(this.multiple ? [] : null)
    },
  },
}
</script>
<style lang="scss" scoped>
.input-wrapper {
  position:relative;
  display: flex;
  flex-wrap: wrap;
  flex-direction: column;
  width: 100%;
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
