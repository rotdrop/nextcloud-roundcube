<script>
/**
 * @copyright Copyright (c) 2022-2024 Claus-Justus Heine <himself@claus-justus-heine.de>
 * @author Claus-Justus Heine <himself@claus-justus-heine.de>
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
  <NcSettingsSection :name="t(appName, 'Embedded RoundCube, Personal Settings')"
                     :class="[...cloudVersionClasses, appName]"
  >
    <NcTextField :value.sync="protectedEmailAddress"
                 :label="t(appName, 'Email Login Name')"
                 :placeholder="t(appName, 'Email Address')"
                 :disabled="emailAddressDisabled"
                 :show-trailing-button="true"
                 trailing-button-icon="arrowRight"
                 @trailing-button-click="saveTextInput('emailAddress'); saveTextInput('emailPassword')"
                 @update="info(emailAddress, ...arguments)"
                 @update:value="info(emailAddress, ...arguments)"
    />
    <p class="hint">
      {{ emailAddressHint }}
    </p>
    <NcPasswordField :value.sync="protectedEmailPassword"
                     :label="t(appName, 'Email Password')"
                     :disabled="emailPasswordDisabled"
                     :placeholder="t(appName, 'Email Password')"
                     class="password"
                     @update="info(emailPassword, ...arguments)"
                     @update:value="info(emailPassword, ...arguments)"
    />
    <p class="hint">
      {{ emailPasswordHint }}
    </p>
  </NcSettingsSection>
</template>
<script>
import { appName } from './config.js'
import Vue from 'vue'
import {
  NcPasswordField,
  NcSettingsSection,
  NcTextField,
} from '@nextcloud/vue'
import settingsSync from './toolkit/mixins/settings-sync'
import cloudVersionClasses from './toolkit/util/cloud-version-classes.js'

export default {
  name: 'PersonalSettings',
  components: {
    NcPasswordField,
    NcSettingsSection,
    NcTextField,
  },
  data() {
    return {
      loading: true,
      cloudVersionClasses,
      emailAddress: '',
      emailPassword: '',
      emailAddressChoiceAdmin: null,
      emailDefaultDomainAdmin: null,
      fixedSingleEmailAddressAdmin: null,
      forceSSOAdmin: null
    }
  },
  mixins: [
    settingsSync,
  ],
  computed: {
    protectedEmailAddress: {
      get() { return this.emailAddress || '' },
      set(newValue) { this.emailAddress = newValue },
    },
    protectedEmailPassword: {
      get() { return this.emailPassword || '' },
      set(newValue) { this.emailPassword = newValue },
    },
    emailAddressDisabled() {
      if (this.loading > 0) {
        return true
      }
      switch (this.emailAddressChoiceAdmin) {
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
    },
    emailAddressHint() {
      switch (this.emailAddressChoiceAdmin) {
        case 'userIdEmail':
          return t(appName, 'Globally configured as USERID@{emailDefaultDomainAdmin}', this)
        case 'userPreferencesEmail':
          return t(appName, 'Globally configured as user\'s email address, see user\'s personal settings.')
        case 'fixedSingleAddress':
          return t(appName, 'Globally configured as {fixedSingleEmailAddressAdmin}', this)
        case 'userChosenEmail':
        default:
          return t(appName, 'Please specify an email address to use with RoundCube.')
      }
    },
    emailPasswordDisabled() {
      if (this.forceSSO) {
        return true
      }
      switch (this.emailAddressChoiceAdmin) {
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
    },
    emailPasswordHint() {
      if (this.emailAddressChoiceAdmin === 'fixedSingleAddress') {
        return t(appName, 'Globally configured by the administrator')
      }
      return this.forceSSOAdmin
           ? t(appName, 'Single sign-on is globally forced "on".')
           : t(appName, 'Email password for RoundCube, if needed.')
    },
  },
  watch: {},
  created() {
    this.getData()
  },
  mounted() {
  },
  methods: {
    info(...args) {
      console.info(this.$options.name, ...args)
    },
    async getData() {
      // slurp in all personal settings
      this.fetchSettings('personal').finally(() => {
        this.loading = false
      })
    },
    async saveTextInput(settingsKey, value, force) {
      if (value === undefined) {
        value = this[settingsKey] || ''
      }
      if (this.loading > 0) {
        // avoid ping-pong by reactivity
        console.info('SKIPPING SETTINGS-SAVE DURING LOAD', settingsKey, value)
        return
      }
      this.saveConfirmedSetting(value, 'personal', settingsKey, force, this.updatePatternTestResult);
    },
    async saveSetting(setting) {
      if (this.loading > 0) {
        // avoid ping-pong by reactivity
        console.info('SKIPPING SETTINGS-SAVE DURING LOAD', setting)
        return
      }
      this.saveSimpleSetting(setting, 'personal', this.updatePatternTestResult)
    },
  },
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
