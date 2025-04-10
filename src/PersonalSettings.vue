<!--
 - @copyright Copyright (c) 2022-2025 Claus-Justus Heine <himself@claus-justus-heine.de>
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
 - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 - GNU Affero General Public License for more details.
 -
 - You should have received a copy of the GNU Affero General Public License
 - along with this program. If not, see <http://www.gnu.org/licenses/>.
 -->
<template>
  <NcSettingsSection :name="t(appName, 'Embedded RoundCube, Personal Settings')"
                     :class="[...cloudVersionClasses, appName]"
  >
    <TextField :value.sync="settings.emailAddress"
               :label="t(appName, 'Email Login Name')"
               :helper-text="emailAddressHint"
               :placeholder="t(appName, 'Email Address')"
               :disabled="emailAddressDisabled"
               @submit="saveTextInput('emailAddress')"
    />
    <TextField :value.sync="protectedEmailPassword"
               :type="isPasswordHidden ? 'password' : 'text'"
               :label="t(appName, 'Email Password')"
               :disabled="emailPasswordDisabled"
               :placeholder="t(appName, 'Email Password')"
               :helper-text="emailPasswordHint"
               class="password"
               @submit="saveTextInput('emailPassword')"
    >
      <template #alignedAfter>
        <NcButton :aria-label="passwordToggleLabel"
                  :disabled="emailPasswordDisabled"
                  @click.stop.prevent="isPasswordHidden = !isPasswordHidden"
        >
          <template #icon>
            <Eye v-if="isPasswordHidden" :size="18" />
            <EyeOff v-else :size="18" />
          </template>
        </NcButton>
      </template>
    </TextField>
  </NcSettingsSection>
</template>
<script setup lang="ts">
import { appName } from './config.ts'
import {
  NcButton,
  NcSettingsSection,
} from '@nextcloud/vue'
import { translate as t } from '@nextcloud/l10n'
import {
  computed,
  ref,
  reactive,
} from 'vue'
import TextField from '@rotdrop/nextcloud-vue-components/lib/components/TextFieldWithSubmitButton.vue'
import cloudVersionClassesImport from './toolkit/util/cloud-version-classes.js'
import {
  fetchSettings,
  saveConfirmedSetting,
} from './toolkit/util/settings-sync.ts'
import Eye from 'vue-material-design-icons/Eye.vue'
import EyeOff from 'vue-material-design-icons/EyeOff.vue'
import logger from './logger.ts'
import type { EmailAddressChoice } from './types/settings.d.ts'

const loading = ref(true)

const isPasswordHidden = ref(true)
const passwordToggleLabel = computed(() => isPasswordHidden.value ? t(appName, 'Show password') : t(appName, 'Hide password'))

const cloudVersionClasses = computed(() => cloudVersionClassesImport)

const settings = reactive({
  emailAddress: '',
  emailPassword: '',
  emailAddressChoiceAdmin: 'userChosenEmail' as EmailAddressChoice,
  emailDefaultDomainAdmin: null as null|string,
  fixedSingleEmailAddressAdmin: null as null|string,
  forceSSOAdmin: false,
})

const protectedEmailPassword = computed({
  get() { return settings.emailPassword || '' },
  set(newValue) { settings.emailPassword = newValue },
})

const emailAddressDisabled = computed(() => {
  if (loading.value) {
    return true
  }
  switch (settings.emailAddressChoiceAdmin) {
  case 'userIdEmail':
    return true
  case 'userPreferencesEmail':
    return true
  case 'userChosenEmail':
    return false
  case 'fixedSingleAddress':
    return true
  }
  return false
})

const emailAddressHint = computed(() => {
  switch (settings.emailAddressChoiceAdmin) {
  case 'userIdEmail':
    // @ts-expect-error settings does serve as data provider for the substitution
    return t(appName, 'Globally configured as USERID@{emailDefaultDomainAdmin}', settings)
  case 'userPreferencesEmail':
    return t(appName, 'Globally configured as user\'s email address, see user\'s personal settings.')
  case 'fixedSingleAddress':
    // @ts-expect-error settings does serve as data provider for the substitution
    return t(appName, 'Globally configured as {fixedSingleEmailAddressAdmin}', settings)
  case 'userChosenEmail':
  default:
    return t(appName, 'Please specify an email address to use with RoundCube.')
  }
})

const emailPasswordDisabled = computed(() => {
  if (settings.forceSSOAdmin) {
    return true
  }
  switch (settings.emailAddressChoiceAdmin) {
  case 'userIdEmail':
    return false
  case 'userPreferencesEmail':
    return false
  case 'userChosenEmail':
    return false
  case 'fixedSingleAddress':
    return true
  default:
    return false
  }
})

const emailPasswordHint = computed(() => {
  if (settings.emailAddressChoiceAdmin === 'fixedSingleAddress') {
    return t(appName, 'Globally configured by the administrator')
  }
  return settings.forceSSOAdmin
    ? t(appName, 'Single sign-on is globally forced "on".')
    : t(appName, 'Email password for RoundCube, if needed.')
})

const getData = async () => {
  // slurp in all personal settings
  fetchSettings({ section: 'personal', settings }).finally(() => {
    loading.value = false
  })
}
getData()

const saveTextInput = async (settingsKey: string, value?: string, force?: boolean) => {
  if (value === undefined) {
    value = settings[settingsKey] || ''
  }
  if (loading.value) {
    // avoid ping-pong by reactivity
    logger.info('SKIPPING SETTINGS-SAVE DURING LOAD', settingsKey, value)
    return
  }
  saveConfirmedSetting({ value, section: 'personal', settingsKey, force, settings })
}
</script>
<style lang="scss" scoped>
.cloud-version {
  --cloud-icon-info: var(--icon-info-dark);
  --cloud-icon-checkmark: var(--icon-checkmark-dark);
  --cloud-icon-alert: var(--icon-alert-outline-dark);
  --cloud-theme-filter: var(--background-invert-if-dark);
  &.cloud-version-major-24 {
    --cloud-icon-info: var(--icon-info-000);
    --cloud-icon-checkmark: var(--icon-checkmark-000);
    --cloud-icon-alert: var(--icon-alert-outline-000);
    --cloud-theme-filter: none;
  }
}
.settings-section {
  :deep(.settings-section__name) {
    position: relative;
    padding-left:48px;
    height:32px;
    &::before {
      content: "";
      position: absolute;
      left: 0;
      top: 0;
      width: 32px;
      height: 32px;
      background-size:32px;
      background-image:url('../img/app.svg');
      background-repeat:no-repeat;
      background-origin:border-box;
      background-position:left center;
      filter: var(--cloud-theme-filter);
    }
  }
  // Tweak the submit button of the NcTextField
  .input-field::v-deep {
    &.email-default-domain {
      width:unset !important;
    }
    &:not(.password) {
      input.input-field__input--trailing-icon:not([type="password"], .password) {
        // the following is just the button ...
        ~ .input-field__trailing-button.button-vue--vue-tertiary-no-background {
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
  }
}
</style>
