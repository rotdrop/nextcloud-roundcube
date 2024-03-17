<!--
 - @copyright Copyright (c) 2019, 2022, 2023, 2024 Julius Härtl <jus@bitgrid.net>
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
  <form :class="['component-wrapper', ...cloudVersionClasses]"
        @submit.prevent=""
  >
    <div class="input-wrapper">
      <label :for="id" :class="{ empty: !label || label === '' }">{{ label }}</label>
      <input v-bind="$attrs"
             :id="id"
             :type="inputType"
             :value="inputVal"
             :disabled="disabled"
             :placeholder="placeholder"
             @input="$emit('input', $event.target.value); inputVal = $event.target.value;"
      >
      <input type="submit"
             class="icon-confirm"
             value=""
             :disabled="disabled"
             @click="$emit('update', inputVal)"
      >
      <input v-if="type === 'password'"
             :id="id + '-visibility-toggle'"
             v-model="inputIsVisible"
             class="visibility-toggle"
             type="checkbox"
             :disabled="disabled"
      >
      <label v-if="type === 'password'"
             :for="id + '-visibility-toggle'"
             class="visibility-toggle"
      />
    </div>
    <p v-if="hint !== '' || !!$slots.hint" class="hint">
      {{ hint }}
      <slot name="hint" />
    </p>
  </form>
</template>

<script>

const cloudVersion = OC.config.versionstring.split('.')
const cloudVersionClasses = [
  'cloud-version',
  'cloud-version-major-' + cloudVersion[0],
  'cloud-version-minor-' + cloudVersion[1],
  'cloud-version-patch-' + cloudVersion[2],
]

export default {
  name: 'SettingsInputText',
  props: {
    type: {
      type: String,
      default: 'text',
    },
    label: {
      type: String,
      required: true,
    },
    hint: {
      type: String,
      default: '',
    },
    value: {
      type: [String, Number],
      default: '',
    },
    disabled: {
      type: Boolean,
      default: false,
    },
    placeholder: {
      type: String,
      default: '',
    },
  },
  data() {
    return {
      inputIsVisible: this.type !== 'password',
      inputVal: this.value,
      cloudVersionClasses,
    }
  },
  computed: {
    id() {
      return 'settings-input-text-' + this._uid
    },
    inputType() {
      return this.type !== 'password' || !this.inputIsVisible ? this.type : 'text'
    },
  },
  watch: {
    value(newVal) {
      this.inputVal = this.value
    },
  },
}
</script>
<style lang="scss" scoped>
.cloud-version {
  --cloud-border-radius: var(--border-radius-large);
  --cloud-confirm-size: 36px;
  --cloud-confirm-left-margin: -13px;
  --cloud-input-border-width: 2px;
  --cloud-input-border-color: var(--color-border-maxcontrast);
  --cloud-icon-confirm: var(--icon-confirm-dark);
  --cloud-icon-confirm-fade: var(--icon-confirm-fade-dark);
  &.cloud-version-major-24 {
    --cloud-border-radius: var(--border-radius);
    --cloud-confirm-size: 34px;
    --cloud-confirm-left-margin: -8px;
    --cloud-input-border-width: 1px;
    --cloud-input-border-color: var(--color-border-dark);
    --cloud-icon-confirm: var(--icon-confirm-000);
    --cloud-icon-confirm-fade: var(--icon-confirm-fade-000);
  }
}
.component-wrapper {
  .hint {
    color: var(--color-text-lighter);
    font-style:italic;
  }
  :deep(.input-wrapper) {
    display: flex;
    position:relative;
    flex-wrap: wrap;
    width: 100%;
    max-width: 400px;

    label:not(.empty) {
      width: 100%;
    }

    // let the main input grow
    label + input {
      flex-grow: 1;
      &[type='number'] {
        direction:rtl;
      }
      &:read-only, &:disabled {
        background-color: var(--color-background-dark);
        color: var(--color-text-maxcontrast);
        cursor: default;
        opacity: 1;
      }
    }

    input + .icon-confirm + input.visibility-toggle {
      position:absolute;
      width:0;
      height:0;
      left:-10000px;
      &:checked + label.visibility-toggle {
        filter: alpha(opacity=80);
        opacity: .8;
      }
      + label.visibility-toggle {
        position:absolute;
        width:24px;
        height:100%;
        right:32px;
        background-image: var(--icon-toggle-dark);
        background-repeat: no-repeat;
        background-position: center;
        filter: alpha(opacity=30);
        opacity: .3;
        text-indent: -10000em;
        z-index: 10;
      }
    }

    // Fixup for Nextcloud v25 not setting confirm button border
    input[type='text'], input[type='password'], input[type='number'], input[type='email'] {
      + .icon-confirm {
        border-width: var(--cloud-input-border-width);
        border-color: var(--cloud-input-border-color);
      }
      &:not(:active):not(:hover):not(:focus):read-only, &:read-only, & {
        &:disabled + {
          .icon-confirm, .icon-confirm:hover {
            background-color: var(--color-background-dark) !important;
            border-color: var(--cloud-input-border-color) !important;
            border-left-color: transparent !important;
            border-radius: 0 var(--cloud-border-radius) var(--cloud-border-radius) 0 !important;
          }
        }
      }
    }

    // Fixup for Nextcloud not styling confirm after number input
    input[type='password'], input[type='number'], input[type='email'] {
      + .icon-confirm {
        margin-left: var(--cloud-confirm-left-margin) !important;
        border-left-color: transparent !important;
        border-radius: 0 var(--cloud-border-radius) var(--cloud-border-radius) 0 !important;
        border-width: var(--cloud-input-border-width);
        border-color: var(--cloud-input-border-color);
        background-clip: padding-box;
        /* Avoid background under border */
        background-color: var(--color-main-background) !important;
        opacity: 1;
        height: var(--cloud-confirm-size);
        width: var(--cloud-confirm-size);
        padding: 7px 6px;
        cursor: pointer;
        margin-right: 0;
        &:disabled {
          cursor: default;
          opacity: 1;
          background-image: var(--cloud-icon-confirm-fade);
          background-color: var(--color-background-dark);
        }
      }
      &:not(:active):not(:hover):not(:focus):invalid + .icon-confirm {
        border-color: var(--color-error);
      }
      &:not(:active):not(:hover):not(:focus):not(:read-only:disabled) + .icon-confirm {
        &:active, &:hover, &:focus {
          border-color: var(--color-primary-element) !important;
          border-radius: var(--border-radius) !important;
          &:disabled {
            border-color: var(--cloud-input-border-color) !important;
          }
        }
      }
      &:active, &:hover, &:focus {
        &:not(:read-only:disabled) {
          + .icon-confirm {
            border-color: var(--color-primary-element) !important;
            border-left-color: transparent !important;
            /* above previous input */
            z-index: 2;
          }
        }
      }
    }
  }
}
</style>
