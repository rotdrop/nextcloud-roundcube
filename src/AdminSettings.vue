<script>
/**
 * @copyright Copyright (c) 2022, 2023 Claus-Justus Heine <himself@claus-justus-heine.de>
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
  <SettingsSection :class="[...cloudVersionClasses, appName]" :title="t(appName, 'Embedded RoundCube, Admin Settings')">
    <AppSettingsSection :title="t(appName, 'Roundcube Installation')">
      <SettingsInputText v-model="externalLocation"
                         :label="t(appName, 'RoundCube Installation Path')"
                         :hint="t(appName, 'RoundCube path can be entered relative to the Nextcloud server')"
                         :disabled="loading > 0"
                         @update="saveTextInput(...arguments, 'externalLocation')"
      />
    </AppSettingsSection>
    <AppSettingsSection :title="t(appName, 'Email Address Selection')"
                        class="flex-container flex-column"
    >
      <div class="flex-container flex-row flex-center email-address-choice">
        <input id="user-id-email"
               v-model="emailAddressChoice"
               class="radio"
               type="radio"
               name="emailAddressChoice"
               value="userIdEmail"
               :disabled="loading > 0"
               @change="saveSetting('emailAddressChoice')"
        >
        <label for="user-id-email">
          {{ t(appName, 'Cloud Login-Id') }}
        </label>
        <span :class="['user-id-email-placeholder', { disabled: loading > 0 || (emailAddressChoice !== 'userIdEmail')}]">{{ t(appName, 'User ID') }}@</span>
        <SettingsInputText v-model="emailDefaultDomain"
                           label=""
                           :disabled="loading > 0 || (emailAddressChoice !== 'userIdEmail')"
                           :placeholder="t(appName, 'Email Domain')"
                           @update="saveTextInput(...arguments, 'emailDefaultDomain')"
        />
      </div>
      <div class="flex-container flex-row flex-center email-address-choice">
        <input id="user-preferences-email"
               v-model="emailAddressChoice"
               class="radio"
               type="radio"
               name="emailAddressChoice"
               value="userPreferencesEmail"
               :disabled="loading > 0"
               @change="saveSetting('emailAddressChoice')"
        >
        <label for="user-preferences-email">
          {{ t(appName, 'User\'s Preferences') }}
        </label>
      </div>
      <div class="flex-container flex-row flex-center email-address-choice">
        <input id="user-chosen-email"
               v-model="emailAddressChoice"
               class="radio"
               type="radio"
               name="emailAddressChoice"
               value="userChosenEmail"
               :disabled="loading > 0"
               @change="saveSetting('emailAddressChoice')"
        >
        <label for="user-chosen-email">
          {{ t(appName, 'User\'s Choice') }}
        </label>
      </div>
      <div class="email-address-choice">
        <input id="fixed-single-address"
               v-model="emailAddressChoice"
               class="radio"
               type="radio"
               name="emailAddressChoice"
               value="fixedSingleAddress"
               :disabled="loading > 0"
               @change="saveSetting('emailAddressChoice')"
        >
        <label for="fixed-single-address">
          {{ t(appName, 'Fixed Single Address') }}
        </label>
        <div v-if="emailAddressChoice === 'fixedSingleAddress'">
          <SettingsInputText v-model="fixedSingleEmailAddress"
                             :label="t(appName, 'Global Email Login')"
                             :title="t(appName, 'Global email user-name for Roundcube for all users')"
                             :disabled="loading > 0 || (emailAddressChoice !== 'fixedSingleAddress')"
                             :placeholder="t(appName, 'Email Address')"
                             @update="saveTextInput(...arguments, 'fixedSingleEmailAddress')"
          />
          <SettingsInputText v-model="fixedSingleEmailPassword"
                             type="password"
                             :label="t(appName, 'Global Email Password')"
                             :title="t(appName, 'Global email password for Roundcube for all users')"
                             :disabled="loading > 0 || (emailAddressChoice !== 'fixedSingleAddress')"
                             :placeholder="t(appName, 'Email Password')"
                             @update="saveTextInput(...arguments, 'fixedSingleEmailPassword')"
          />
        </div>
      </div>
    </AppSettingsSection>
    <AppSettingsSection :title="t(appName, 'Advanced Settings')"
                        class="flex-container flex-column"
    >
      <input id="force-sso"
             v-model="forceSSO"
             class="checkbox"
             type="checkbox"
             name="forceSSO"
             value="1"
             :disabled="loading > 0 || emailAddressChoice === 'fixedSingleAddress'"
             @change="saveSetting('forceSSO')"
      >
      <label for="force-sso">
        {{ t(appName, 'Force single sign on (disables custom password).') }}
      </label>
      <input id="show-top-line"
             v-model="showTopLine"
             class="checkbox"
             type="checkbox"
             name="showTopLine"
             value="1"
             :disabled="loading > 0"
             @change="saveSetting('showTopLine')"
      >
      <label for="show-top-line">
        {{ t(appName, 'Show RoundCube top information bar (shows logout button).') }}
      </label>
      <input id="enable-ssl-verify"
             v-model="enableSSLVerify"
             class="checkbox"
             type="checkbox"
             name="enableSSLVerify"
             value="1"
             :disabled="loading > 0"
             @change="saveSetting('enableSSLVerify')"
      >
      <label for="enable-ssl-verify"
             :title="t(appName, 'Disable when debugging with self-signed certificates.')"
      >
        {{ t(appName, 'Enable SSL verification.') }}
      </label>
      <input id="personal-encryption"
             v-model="personalEncryption"
             class="checkbox"
             type="checkbox"
             name="personalEncryption"
             value="1"
             :disabled="loading > 0"
             @change="saveSetting('personalEncryption')"
      >
      <label for="personal-encryption"
             :title="t(appName, 'Encrypt per-user data -- in particular their email passwords -- with their personal cloud password. This implies that these settings will be lost when users forget their passwords. If unchecked the email login credentials are still protected by the server secret. The latter implies that an administrator is able to decrypt the login credentials, but the configuration data survives user password-loss.')"
      >
        {{ t(appName, 'Per-user encryption of config values.') }}
      </label>
    </AppSettingsSection>
  </SettingsSection>
</template>
<script>
import { appName } from './config.js'
import AppSettingsSection from '@nextcloud/vue/dist/Components/NcAppSettingsSection'
import SettingsSection from '@nextcloud/vue/dist/Components/NcSettingsSection'
import SettingsInputText from '@rotdrop/nextcloud-vue-components/lib/components/SettingsInputText'
import settingsSync from './toolkit/mixins/settings-sync'
import cloudVersionClasses from './toolkit/util/cloud-version-classes.js'

export default {
  name: 'AdminSettings',
  components: {
    AppSettingsSection,
    SettingsSection,
    SettingsInputText,
  },
  data() {
    return {
      loading: 0,
      cloudVersionClasses,
      externalLocation: null,
      emailAddressChoice: null,
      emailDefaultDomain: null,
      fixedSingleEmailAddress: null,
      fixedSingleEmailPassword: null,
      forceSSO: false,
      showTopLine: false,
      enableSSLVerify: true,
      personalEncryption: false,
    }
  },
  mixins: [
    settingsSync,
  ],
  computed: {
  },
  watch: {},
  created() {
    this.getData()
  },
  mounted() {
  },
  methods: {
    info() {
      console.info(...arguments)
    },
    async getData() {
      // slurp in all personal settings
      ++this.loading
      this.fetchSettings('admin').finally(() => {
        console.info('THIS', this)
        --this.loading
      })
    },
    async saveTextInput(value, settingsKey, force) {
      if (this.loading > 0) {
        // avoid ping-pong by reactivity
        console.info('SKIPPING SETTINGS-SAVE DURING LOAD', settingsKey, value)
        return
      }
      this.saveConfirmedSetting(value, 'admin', settingsKey, force);
    },
    async saveSetting(setting) {
      if (this.loading > 0) {
        // avoid ping-pong by reactivity
        console.info('SKIPPING SETTINGS-SAVE DURING LOAD', setting)
        return
      }
      this.saveSimpleSetting(setting, 'admin')
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
.flex-container {
  display:flex;
  &.flex-column {
    flex-direction:column;
  }
  &.flex-row {
    flex-direction:row;
  }
  &.flex-center {
    align-items:center;
  }
}
.settings-section {
  :deep(.app-settings-section) {
    margin-bottom: 40px;
  }
  :deep(.settings-section__title) {
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
  .user-id-email-placeholder {
    font-family:mono-space;
    font-weight:bold;
    margin-left:0.5em;
    margin-right:0.5em;
    &::before {
      content: '—';
      margin-right:0.5em;
    }
  }
}
</style>
