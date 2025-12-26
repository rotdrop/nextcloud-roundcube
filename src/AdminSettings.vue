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
  <div :class="['templateroot', appName, ...cloudVersionClasses]">
    <h1 class="title">
      {{ t(appName, 'Embedded RoundCube, Admin Settings') }}
    </h1>
    <NcSettingsSection :name="t(appName, 'Roundcube Installation')"
                       class="flex-container flex-column"
    >
      <TextField :value.sync="settings.externalLocation"
                 type="text"
                 :label="t(appName, 'RoundCube Installation Path')"
                 :helper-text="t(appName, 'RoundCube path can be entered relative to the Nextcloud server')"
                 :disabled="loading"
                 @submit="saveTextInput('externalLocation')"
      />
    </NcSettingsSection>
    <NcSettingsSection :name="t(appName, 'Email Address Selection')"
                       class="flex-container flex-column"
    >
      <div class="flex-container flex-row flex-center email-address-choice">
        <input id="user-id-email"
               v-model="settings.emailAddressChoice"
               class="radio"
               type="radio"
               name="emailAddressChoice"
               value="userIdEmail"
               :disabled="loading"
               @change="saveSetting('emailAddressChoice')"
        >
        <label for="user-id-email">
          {{ t(appName, 'Cloud Login-Id') }}
        </label>
        <span :class="['user-id-email-placeholder', { disabled: loading || (settings.emailAddressChoice !== 'userIdEmail')}]">{{ t(appName, 'User ID') }}@</span>
        <TextField :value.sync="settings.emailDefaultDomain"
                   class="email-default-domain"
                   :disabled="loading || (settings.emailAddressChoice !== 'userIdEmail')"
                   :placeholder="t(appName, 'Email Domain')"
                   @submit="saveTextInput('emailDefaultDomain')"
        />
      </div>
      <div class="flex-container flex-row flex-center email-address-choice">
        <input id="user-preferences-email"
               v-model="settings.emailAddressChoice"
               class="radio"
               type="radio"
               name="emailAddressChoice"
               value="userPreferencesEmail"
               :disabled="loading"
               @change="saveSetting('emailAddressChoice')"
        >
        <label for="user-preferences-email">
          {{ t(appName, 'User\'s Preferences') }}
        </label>
      </div>
      <div class="flex-container flex-row flex-center email-address-choice">
        <input id="user-chosen-email"
               v-model="settings.emailAddressChoice"
               class="radio"
               type="radio"
               name="emailAddressChoice"
               value="userChosenEmail"
               :disabled="loading"
               @change="saveSetting('emailAddressChoice')"
        >
        <label for="user-chosen-email">
          {{ t(appName, 'User\'s Choice') }}
        </label>
      </div>
      <div class="email-address-choice">
        <input id="fixed-single-address"
               v-model="settings.emailAddressChoice"
               class="radio"
               type="radio"
               name="emailAddressChoice"
               value="fixedSingleAddress"
               :disabled="loading"
               @change="saveSetting('emailAddressChoice')"
        >
        <label for="fixed-single-address">
          {{ t(appName, 'Fixed Single Address') }}
        </label>
        <div v-if="settings.emailAddressChoice === 'fixedSingleAddress'">
          <TextField :value.sync="settings.fixedSingleEmailAddress"
                     type="text"
                     :label="t(appName, 'Global Email Login')"
                     :helper-text="t(appName, 'Global email user-name for Roundcube for all users')"
                     :disabled="loading || (settings.emailAddressChoice !== 'fixedSingleAddress')"
                     :placeholder="t(appName, 'Email Address')"
                     @submit="saveTextInput('fixedSingleEmailAddress'); saveTextInput('fixedSingleEmailPassword')"
          />
          <TextField :value.sync="protectedFixedSingleEmailPassword"
                     :type="isPasswordHidden ? 'password' : 'text'"
                     :helper-text="t(appName, 'Global email password for Roundcube for all users')"
                     :label="t(appName, 'Global Email Password')"
                     :disabled="loading || (settings.emailAddressChoice !== 'fixedSingleAddress')"
                     :placeholder="t(appName, 'Email Password')"
                     class="password"
          >
            <template #alignedAfter>
              <NcButton :aria-label="passwordToggleLabel"
                        :disabled="loading || (settings.emailAddressChoice !== 'fixedSingleAddress')"
                        @click.stop.prevent="isPasswordHidden = !isPasswordHidden"
              >
                <template #icon>
                  <Eye v-if="isPasswordHidden" :size="18" />
                  <EyeOff v-else :size="18" />
                </template>
              </NcButton>
            </template>
          </TextField>
        </div>
      </div>
    </NcSettingsSection>
    <NcSettingsSection :name="t(appName, 'Advanced Settings')"
                       class="flex-container flex-column"
    >
      <input id="force-sso"
             v-model="settings.forceSSO"
             class="checkbox"
             type="checkbox"
             name="forceSSO"
             value="1"
             :disabled="loading || settings.emailAddressChoice === 'fixedSingleAddress'"
             @change="saveSetting('forceSSO')"
      >
      <label for="force-sso">
        {{ t(appName, 'Force single sign on (disables custom password).') }}
      </label>
      <input id="show-top-line"
             v-model="settings.showTopLine"
             class="checkbox"
             type="checkbox"
             name="showTopLine"
             value="1"
             :disabled="loading"
             @change="saveSetting('showTopLine')"
      >
      <label for="show-top-line">
        {{ t(appName, 'Show RoundCube top information bar (shows logout button).') }}
      </label>
      <input id="enable-ssl-verify"
             v-model="settings.enableSSLVerify"
             class="checkbox"
             type="checkbox"
             name="enableSSLVerify"
             value="1"
             :disabled="loading"
             @change="saveSetting('enableSSLVerify')"
      >
      <label for="enable-ssl-verify"
             :title="t(appName, 'Disable when debugging with self-signed certificates.')"
      >
        {{ t(appName, 'Enable SSL verification.') }}
      </label>
      <input id="enable-tls-client-certificates"
             v-model="settings.enableTLSClientCertificates"
             class="checkbox"
             type="checkbox"
             name="enableTLSClientCertificates"
             value="1"
             :disabled="loading"
             @change="saveSetting('enableTLSClientCertificates')"
      >
      <label for="enable-tls-client-certificates"
             :title="t(appName, 'Enable when mutual TLS is enforced on the webserver.')"
      >
        {{ t(appName, 'Enable TLS Client Certificates.') }}
      </label>
      <input id="client-tls-key-file"
             v-model="settings.clientTLSKeyFile"
             class="checkbox"
             type="checkbox"
             name="clientTLSKeyFile"
             value="1"
             :disabled="loading"
             @change="saveSetting('clientTLSKeyFile')"
      >
      <label for="client-tls-key-file"
             :title="t(appName, 'Filename for the private key used for mutual TLS.')"
      >
        {{ t(appName, 'Filename for the client private key.') }}
      </label>
      <input id="client-tls-certificate-file"
             v-model="settings.clientTLSCertificateFile"
             class="checkbox"
             type="checkbox"
             name="clientTLSCertificateFile"
             value="1"
             :disabled="loading"
             @change="saveSetting('clientTLSCertificateFile')"
      >
      <label for="client-tls-certificate-file"
             :title="t(appName, 'Filename for the certificate used for mutual TLS.')"
      >
        {{ t(appName, 'FIlename for the client certificate.') }}
      </label>
      <input id="client-tls-key-password`"
             v-model="settings.clientTLSKeyPassword"
             class="checkbox"
             type="checkbox"
             name="clientTLSKeyPassword"
             value="1"
             :disabled="loading"
             @change="saveSetting('clientTLSKeyPassword')"
      >
      <label for="enable-tls-key-password"
             :title="t(appName, 'Password for the private key file.')"
      >
        {{ t(appName, 'Private key password.') }}
      </label>
      <input id="personal-encryption"
             v-model="settings.personalEncryption"
             class="checkbox"
             type="checkbox"
             name="personalEncryption"
             value="1"
             :disabled="loading"
             @change="saveSetting('personalEncryption')"
      >
      <label for="personal-encryption"
             :title="t(appName, 'Encrypt per-user data -- in particular their email passwords -- with their personal cloud password. This implies that these settings will be lost when users forget their passwords. If unchecked the email login credentials are still protected by the server secret. The latter implies that an administrator is able to decrypt the login credentials, but the configuration data survives user password-loss.')"
      >
        {{ t(appName, 'Per-user encryption of config values.') }}
      </label>
      <TextField :value.sync="settings.cardDavProvisioningTag"
                 :label="t(appName, 'RoundCube CardDAV Tag')"
                 :helper-text="t(appName, 'Tag of a preconfigured CardDAV account pointing to the cloud addressbook. See the documentation of the RCMCardDAV plugin.')"
                 :disabled="loading"
                 :placeholder="t(appName, 'Email Password')"
                 @submit="saveTextInput('cardDavProvisioningTag')"
      />
      <div v-if="settings.cardDavProvisioningTag">
        <p class="hint">
          {{ t(appName, `Below is a configuration snippet which may or may not work with the
current version of the RoundCube CardDAV plugin. The configuration
shown below is just a suggestion and will not automatically be
registered with the RoundCube app. It is your responsibility to
configure the RoundCube CardDAV plugin correctly. Please have a look
at the explanations in the README.md file.`) }}
        </p>
        <ul class="card-dav-template">
          <NcListItem :name="t(appName, 'RCMCardDAV Plugin Configuration')">
            <template #subname>
              <pre>
                {{ cardDavTemplate }}
              </pre>
            </template>
            <template #actions>
              <NcActionButton @click="copyCardDavConfig">
                <template #icon>
                  <ClipBoard :size="20" />
                </template>
                {{ t(appName, 'ClipBoard') }}
              </NcActionButton>
            </template>
          </NcListItem>
        </ul>
      </div>
    </NcSettingsSection>
  </div>
</template>
<script setup lang="ts">
import { appName } from './config.ts'
import { generateRemoteUrl } from '@nextcloud/router'
import {
  computed,
  ref,
  reactive,
} from 'vue'
import { translate as t } from '@nextcloud/l10n'
import {
  NcActionButton,
  NcButton,
  NcListItem,
  NcSettingsSection,
} from '@nextcloud/vue'
import TextField from '@rotdrop/nextcloud-vue-components/lib/components/TextFieldWithSubmitButton.vue'
import { showSuccess, showError } from '@nextcloud/dialogs'
import ClipBoard from 'vue-material-design-icons/Clipboard.vue'
import cloudVersionClassesImport from './toolkit/util/cloud-version-classes.ts'
import type { EmailAddressChoice } from './types/settings.d.ts'
import {
  fetchSettings,
  saveConfirmedSetting,
  saveSimpleSetting,
} from './toolkit/util/settings-sync.ts'
import Eye from 'vue-material-design-icons/Eye.vue'
import EyeOff from 'vue-material-design-icons/EyeOff.vue'
import logger from './logger.ts'

const loading = ref(true)

const isPasswordHidden = ref(true)
const passwordToggleLabel = computed(() => isPasswordHidden.value ? t(appName, 'Show password') : t(appName, 'Hide password'))

const cloudVersionClasses = computed(() => cloudVersionClassesImport)

const settings = reactive({
  externalLocation: '',
  emailAddressChoice: 'userChosenEmail' as EmailAddressChoice,
  emailDefaultDomain: '',
  fixedSingleEmailAddress: '',
  fixedSingleEmailPassword: '',
  forceSSO: false,
  showTopLine: false,
  enableSSLVerify: true,
  enableTLSClientCertificates: false,
  clientTLSKeyFile: '',
  clientTLSCertificateFile: '',
  clientTLSKeyPassword: '',
  personalEncryption: false,
  cardDavProvisioningTag: '',
})

const addressBookUrl = computed(() => generateRemoteUrl('dav') + '/addressbooks/users/%l')

const cardDavTemplate = computed(() =>
  `
$prefs['${settings.cardDavProvisioningTag}'] = [
  'accountname'    => '${settings.cardDavProvisioningTag}',
  'discovery_url'  => '${addressBookUrl.value}',
  'username'       => '%l',
  'password'       => '%p',
  'name'           => '%N (%a)',
  'active'         =>  true,
  'readonly'       =>  false,
  'refresh_time'   => '00:15:00',
  'fixed'          => ['discovery_url',],
  'hide'           =>  false,
  'use_categories' => true,
];
      `)

const protectedFixedSingleEmailPassword = computed({
  get() { return settings.fixedSingleEmailPassword || '' },
  set(newValue) { settings.fixedSingleEmailPassword = newValue },
})

const getData = async () => {
  // slurp in all personal settings
  fetchSettings({ section: 'admin', settings }).finally(() => {
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
  return saveConfirmedSetting({ value, section: 'admin', settingsKey, force, settings })
}

const saveSetting = async (settingsKey: string) => {
  if (loading.value) {
    // avoid ping-pong by reactivity
    logger.info('SKIPPING SETTINGS-SAVE DURING LOAD', settingsKey)
    return
  }
  saveSimpleSetting({ settingsKey, section: 'admin', settings })
}

const copyCardDavConfig = () => {
  navigator.clipboard.writeText(cardDavTemplate.value).then(function() {
    showSuccess(t(appName, 'Config template has been copied to the clipboard.'))
  }, function(reason) {
    showError(t(appName, 'Failed copying the config template to the clipboard: {reason}.', { reason }))
  })
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
.templateroot {
  display:flex;
  flex-direction:column;
  .flex-container {
    display:flex !important;
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
  h1.title {
    margin: 30px 30px 0px;
    font-size:revert;
    font-weight:revert;
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
  .settings-section {
    :deep(.list-item-content__wrapper) {
      height:fit-content;
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
    :deep(.component-wrapper.email-default-domain) .alignment-wrapper {
      margin: 0;
      margin-block-start: 0;
    }
    .hint {
      color: var(--color-text-lighter);
      font-style: italic;
    }
    .card-dav-template {
      :deep(.list-item__anchor) {
        height:fit-content;
      }
      pre {
        font-size: 80%;
        line-height: 16px;
        font-family: monospace;
      }
    }
  }
}
</style>
