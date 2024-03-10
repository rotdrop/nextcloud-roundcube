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
  <div :class="['templateroot', appName, ...cloudVersionClasses]">
    <NcSettingsSection :name="t(appName, 'Roundcube Installation')"
                       class="flex-container flex-column"
    >
      <NcTextField :value.sync="externalLocation"
                   type="text"
                   :label="t(appName, 'RoundCube Installation Path')"
                   :disabled="loading > 0"
                   :show-trailing-button="true"
                   trailing-button-icon="arrowRight"
                   @trailing-button-click="saveTextInput('externalLocation')"
                   @update="info(externalLocation, ...arguments)"
                   @update:value="info(externalLocation, ...arguments)"
      />
      <p class="hint">
        {{ t(appName, 'RoundCube path can be entered relative to the Nextcloud server') }}
      </p>
    </NcSettingsSection>
    <NcSettingsSection :name="t(appName, 'Email Address Selection')"
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
        <NcTextField :value.sync="emailDefaultDomain"
                     class="email-default-domain"
                     :disabled="loading > 0 || (emailAddressChoice !== 'userIdEmail')"
                     :placeholder="t(appName, 'Email Domain')"
                     :show-trailing-button="true"
                     trailing-button-icon="arrowRight"
                     @trailing-button-click="saveTextInput('emailDefaultDomain')"
                     @update="info(emailDefaultDomain, ...arguments)"
                     @update:value="info(emailDefaultDomain, ...arguments)"
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
          <NcTextField :value.sync="fixedSingleEmailAddress"
                       type="text"
                       :label="t(appName, 'Global Email Login')"
                       :disabled="loading > 0 || (emailAddressChoice !== 'fixedSingleAddress')"
                       :placeholder="t(appName, 'Email Address')"
                       :show-trailing-button="true"
                       trailing-button-icon="arrowRight"
                       @trailing-button-click="saveTextInput('fixedSingleEmailAddress'); saveTextInput('fixedSingleEmailPassword')"
                       @update="info(fixedSingleEmailAddress, ...arguments)"
                       @update:value="info(fixedSingleEmailAddress, ...arguments)"
          />
          <p class="hint">
            {{ t(appName, 'Global email user-name for Roundcube for all users') }}
          </p>
          <NcPasswordField :value.sync="fixedSingleEmailPassword"
                           :label="t(appName, 'Global Email Password')"
                           :disabled="loading > 0 || (emailAddressChoice !== 'fixedSingleAddress')"
                           :placeholder="t(appName, 'Email Password')"
                           @update="info(fixedSingleEmailPassword, ...arguments)"
                           @update:value="info(fixedSingleEmailPassword, ...arguments)"
          />
          <!-- :show-trailing-button="true"
               trailing-button-icon="arrowRight"
               @trailing-button-click="saveTextInput('fixedSingleEmailPassword')" -->
          <p class="hint">
            {{ t(appName, 'Global email password for Roundcube for all users') }}
          </p>
        </div>
      </div>
    </NcSettingsSection>
    <NcSettingsSection :name="t(appName, 'Advanced Settings')"
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
      <NcTextField :value.sync="cardDavProvisioningTag"
                   :label="t(appName, 'RoundCube CardDAV Tag')"
                   :disabled="loading > 0"
                   :placeholder="t(appName, 'Email Password')"
                   :show-trailing-button="true"
                   trailing-button-icon="arrowRight"
                   @trailing-button-click="saveTextInput('cardDavProvisioningTag')"
                   @update="info(cardDavProvisioningTag, ...arguments)"
                   @update:value="info(cardDavProvisioningTag, ...arguments)"
      />
      <p class="hint">
        {{ t(appName, 'Tag of a preconfigured CardDAV account pointing to the cloud addressbook. See the documentation of the RCMCardDAV plugin.') }}
      </p>
      <div v-if="cardDavProvisioningTag">
        <p class="hint">
          {{ t(appName, `Below is a configuration snippet which may or may not work with the
current version of the RoundCube CardDAV plugin. The configuration
shown below is just a suggestion and will not automatically be
registered with the RoundCube app. It is your responsibility to
configure the RoundCube CardDAV plugin correctly.`) }}
        </p>
        <p class="hint">
          {{ t(appName, `Please note that the password-setting "%p" will not work if 2FA is
enabled. If this app detects that this is the case, it will try to
generate a suitable app-token automatically and register it with the
RoundCube CardDAV plugin -- which may work or not.`) }}
        </p>
        <p class="hint">
          {{ t(appName, `In order to have auto-configuration working it is vital to NOT include
"username" and "password" into the "fixed" array. The simple choice of
"%l" for the username and "%p" for the password will only work without
2Fa and if the local part of the email address is the same as the
cloud user-id.`) }}
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
<script>
import { appName } from './config.js'
import { generateRemoteUrl } from '@nextcloud/router'
import {
  NcActionButton,
  NcListItem,
  NcPasswordField,
  NcSettingsSection,
  NcTextField,
} from '@nextcloud/vue'
import { showSuccess, showError } from '@nextcloud/dialogs'
import ClipBoard from 'vue-material-design-icons/Clipboard.vue'
import settingsSync from './toolkit/mixins/settings-sync'
import cloudVersionClasses from './toolkit/util/cloud-version-classes.js'

export default {
  name: 'AdminSettings',
  components: {
    ClipBoard,
    NcActionButton,
    NcListItem,
    NcPasswordField,
    NcSettingsSection,
    NcTextField,
  },
  data() {
    return {
      loading: 1,
      cloudVersionClasses,
      externalLocation: '',
      emailAddressChoice: '',
      emailDefaultDomain: '',
      fixedSingleEmailAddress: '',
      fixedSingleEmailPassword: '',
      forceSSO: false,
      showTopLine: false,
      enableSSLVerify: true,
      personalEncryption: false,
      cardDavProvisioningTag: '',
    }
  },
  mixins: [
    settingsSync,
  ],
  computed: {
    cardDavTemplate() {
      return `
$prefs['${this.cardDavProvisioningTag}'] = [
  'accountname'    => '${this.cardDavProvisioningTag}',
  'discovery_url'  => '${this.addressBookUrl}',
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
      `
    },
    addressBookUrl() {
      return generateRemoteUrl('dav') + '/addressbooks/users/%l'
    },
  },
  watch: {},
  created() {
    this.getData()
  },
  mounted() {
  },
  methods: {
    info() {
      console.info(this.$options.name, ...arguments)
    },
    async getData() {
      // slurp in all personal settings
      // ++this.loading
      this.fetchSettings('admin').finally(() => {
        console.info('THIS', this)
        this.externalLocation = this.externalLocation || ''
        this.emailAddressChoice = this.emailAddressChoice || ''
        this.emailDefaultDomain = this.emailDefaultDomain || ''
        this.fixedSingleEmailAddress = this.fixedSingleEmailAddress || ''
        this.fixedSingleEmailPassword = this.fixedSingleEmailPassword || ''
        --this.loading
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
    copyCardDavConfig() {
      navigator.clipboard.writeText(this.cardDavTemplate).then(function() {
        showSuccess(t(appName, 'Config template has been copied to the clipboard.'));
      }, function(reason) {
        showError(t(appName, 'Failed copying the config template to the clipboard: {reason}.', { reason }));
      });
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
  // Tweak the submit button of the NcTextField
  .input-field::v-deep {
    &.email-default-domain {
      width:unset !important;
    }
    input.input-field__input--trailing-icon:not([type="password"]) {
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
</style>
