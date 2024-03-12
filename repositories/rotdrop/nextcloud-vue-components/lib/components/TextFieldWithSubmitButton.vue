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
  <div class="component-wrapper">
    <NcTextField ref="ncTextField"
                 v-bind="$attrs"
                 :value="value || ''"
                 :show-trailing-button="true"
                 trailing-button-icon="arrowRight"
                 v-on="$listeners"
                 @trailing-button-click="$emit('submit', $refs.ncTextField.value)"
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
    <p v-if="hint !== '' || !!$slots.hint" class="hint">
      {{ hint }}
      <slot name="hint" />
    </p>
  </div>
</template>
<script>
import { NcTextField } from '@nextcloud/vue'

export default {
  name: 'TextFieldWithSubmitButton',
  components: {
    NcTextField,
  },
  props: {
    hint: {
      type: String,
      default: '',
    },
    value: {
      type: [String, Number],
      default: null,
      validator: (p) => p === null || (p !== undefined && ['string', 'number'].includes(typeof p))
    },
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
    .input-field__input.input-field__input--trailing-icon[dir="rtl"] {
      // still it is a trailing icon, so for rtl the margins have to be interchanged
      padding-inline-end: 12px;
      padding-inline-start: var(--default-clickable-area);
      + .input-field__label {
        margin-inline-end: 9px;
        margin-inline-start: var(--default-clickable-area);
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
          border: 2px solid var(--color-primary-element);
          border-radius: var(--border-radius-large);
          outline: 2px solid var(--color-main-background);
        }
      }
    }
  }
}
</style>
