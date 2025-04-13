/**
 * @copyright Copyright (c) 2022, 2023, 2025 Claus-Justus Heine <himself@claus-justus-heine.de>
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

import { appName } from '../../config.ts';
import { set as vueSet } from 'vue';
import {
  showError,
  showSuccess,
  showInfo,
  TOAST_PERMANENT_TIMEOUT,
} from '@nextcloud/dialogs';
import axios from '@nextcloud/axios';
import { generateUrl } from '@nextcloud/router';
import { translate as t } from '@nextcloud/l10n';
import { isAxiosErrorResponse } from '../types/axios-type-guards.ts';
import dialogConfirm from './dialog-confirm.ts';
import deepEqual from 'deep-equal';

interface FetchSettingsArgs {
  section: 'admin'|'personal',
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  settings: Record<string, any>,
}

const equals = (a: any, b: any) => deepEqual(a, b, { strict: true });

/**
 * @param data The destructuring object
 *
 * @param data.section AJAX call goes to `apps/${appName}/settings/${section}`.
 *
 * @param data.settings The object which receives the
 * settings keys as properties.
 *
 * @return Success status, false on error, true on success.
 */
async function fetchSettings({ section, settings }: FetchSettingsArgs) {
  try {
    const response = await axios.get(generateUrl('apps/' + appName + '/settings/' + section), {});
    // Object.assign(settings, response.data);
    for (const [key, value] of Object.entries(response.data)) {
      if (!equals(settings[key], value)) {
        vueSet(settings, key, value);
      }
    }
    return true;
  } catch (e) {
    console.info('ERROR', e);
    let message = t(appName, 'reason unknown');
    if (isAxiosErrorResponse(e) && e.response.data) {
      const responseData = e.response.data as { messages?: string[] };
      if (Array.isArray(responseData.messages)) {
        message = responseData.messages.join(' ');
      }
    }
    showError(t(appName, 'Unable to query the initial value of all settings: {message}', {
      message,
    }));
    return false;
  }
}

interface FetchSettingArgs {
  settingsKey: string,
  section: 'admin'|'personal',
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  settings: Record<string, any>,
}

/**
 * @param data The destructuring object.
 *
 * @param data.settingsKey TDB.
 *
 * @param data.section TDB.
 *
 * @param data.settings TBD.
 *
 * @return Result.
 */
async function fetchSetting({ settingsKey, section, settings }: FetchSettingArgs) {
  try {
    const response = await axios.get(generateUrl('apps/' + appName + '/settings/' + section + '/' + settingsKey), {});
    if (!equal(settings[settingsKey], response.data.value)) {
      vueSet(settings, settingsKey, response.data.value);
    }
    return true;
  } catch (e) {
    console.info('ERROR', e);
    let message = t(appName, 'reason unknown');
    if (isAxiosErrorResponse(e) && e.response.data) {
      const responseData = e.response.data as { messages?: string[] };
      if (Array.isArray(responseData.messages)) {
        message = responseData.messages.join(' ');
      }
    }
    showError(t(appName, 'Unable to query the initial value of {settingsKey}: {message}', {
      settingsKey,
      message,
    }), {
      timeout: TOAST_PERMANENT_TIMEOUT,
    });
    return false;
  }
}

interface SaveSimpleSettingArgs {
  settingsKey: string,
  section: 'admin'|'personal',
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  onSuccess?: (responseData: any, value: any, section: 'admin'|'personal', settingsKey: string) => any,
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  settings: Record<string, any>,
}

/**
 * @param data The destructuring object.
 *
 * @param data.settingsKey TDB.
 *
 * @param data.section TDB.
 *
 * @param data.onSuccess Success callback, invoked with the
 * response data and the arguments of this function.
 *
 * @param data.settings TBD.
 *
 * @return Result.
 */
async function saveSimpleSetting({ settingsKey, section, onSuccess, settings }: SaveSimpleSettingArgs) {
  const value = settings[settingsKey];
  try {
    const response = await axios.post(generateUrl('apps/' + appName + '/settings/' + section + '/' + settingsKey), { value });
    const responseData = response.data;
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    let displayValue: undefined|string|boolean|any[]|Record<string, any>;
    if (responseData) {
      if (responseData.newValue !== undefined) {
        if (!equals(settings[settingsKey], responseData.newValue)) {
          vueSet(settings, settingsKey, responseData.newValue);
        }
        displayValue = settings[settingsKey];
      }
      if (responseData.humanValue !== undefined) {
        const humanKey = 'human' + settingsKey[0].toUpperCase() + settingsKey.substring(1);
        if (!equals(settings[humanKey], responseData.humanValue)) {
          vueSet(settings, humanKey, responseData.humanValue);
        }
        displayValue = settings[humanKey];
      }
      if (Array.isArray(displayValue)) {
        displayValue = displayValue.toString();
      }
    }
    if (displayValue === true) {
      displayValue = t(appName, 'true');
    }
    if (displayValue && displayValue !== '') {
      showInfo(t(appName, 'Successfully set "{settingsKey}" to {value}.', { settingsKey, value: displayValue as string }));
    } else {
      showInfo(t(appName, 'Setting "{settingsKey}" has been unset successfully.', { settingsKey }));
    }
    if (typeof onSuccess === 'function') {
      onSuccess(responseData, value, section, settingsKey);
    }
    return true;
  } catch (e) {
    console.info('ERROR', e);
    let message = t(appName, 'reason unknown');
    if (isAxiosErrorResponse(e) && e.response.data) {
      const responseData = e.response.data as { messages?: string[] };
      if (Array.isArray(responseData.messages)) {
        message = responseData.messages.join(' ');
      }
    }
    if (value) {
      showError(t(appName, 'Unable to set "{settingsKey}" to {value}: {message}.', {
        settingsKey,
        value: value === true ? t(appName, 'true') : '' + value,
        message,
      }), {
        timeout: TOAST_PERMANENT_TIMEOUT,
      });
    } else {
      showError(t(appName, 'Unable to unset "{settingsKey}": {message}', {
        settingsKey,
        message,
      }), {
        timeout: TOAST_PERMANENT_TIMEOUT,
      });
    }
    return false;
  }
}

interface SaveConfirmedSettingArgs {
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  value: any,
  section: 'admin'|'personal',
  settingsKey: string,
  force?: boolean,
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  onSuccess?: (responseData: any, value: any, section: 'admin'|'personal', settingsKey: string, force?: boolean) => any,
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  settings: Record<string, any>
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  resetData?: () => any,
}

/**
 * @param data The destructuring object.
 *
 * @param data.value TBD.
 *
 * @param data.section TDB.
 *
 * @param data.settingsKey TDB.
 *
 * @param data.force TDB.
 *
 * @param data.onSuccess Success callback, invoked with the
 * response data and the arguments of this function.
 *
 * @param data.settings TBD.
 *
 * @param data.resetData TBD.
 *
 * @return TBD.
 */
const saveConfirmedSetting = async ({
  value,
  section,
  settingsKey,
  force,
  onSuccess,
  settings,
  resetData,
}: SaveConfirmedSettingArgs): Promise<boolean> => {
  try {
    const response = await axios.post(generateUrl('apps/' + appName + '/settings/' + section + '/' + settingsKey), { value, force });
    const responseData = response.data;
    if (responseData.status === 'unconfirmed') {
      const confirmed = await dialogConfirm({
        title: t(appName, 'Confirmation Required'),
        text: responseData.feedback,
      });
      if (confirmed) {
        return saveConfirmedSetting({ value, section, settingsKey, force: true, settings, resetData });
      } else {
        showInfo(t(appName, 'Unconfirmed, reverting to old value.'));
        resetData && await resetData();
        return false;
      }
    } else {
      // eslint-disable-next-line @typescript-eslint/no-explicit-any
      let displayValue: any;
      if (responseData) {
        if (responseData.newValue !== undefined) {
          if (!equals(settings[settingsKey], responseData.newValue)) {
            vueSet(settings, settingsKey, responseData.newValue);
          }
          displayValue = settings[settingsKey];
        }
        if (responseData.humanValue !== undefined) {
          const humanKey = 'human' + settingsKey[0].toUpperCase() + settingsKey.substring(1);
          if (!equals(settings[humanKey], responseData.humanValue)) {
            vueSet(settings, humanKey, responseData.humanValue);
          }
          displayValue = settings[humanKey];
          if (Array.isArray(displayValue)) {
            displayValue = displayValue.toString();
          }
        }
      }
      if (displayValue && displayValue !== '') {
        showSuccess(t(appName, 'Successfully set value for "{settingsKey}" to "{displayValue}"', { settingsKey, displayValue }));
      } else {
        showInfo(t(appName, 'Setting "{setting}" has been unset successfully.', { setting: settingsKey }));
      }
      if (typeof onSuccess === 'function') {
        onSuccess(responseData, value, section, settingsKey, force);
      }
    }
    return true;
  } catch (e) {
    console.info('ERROR', e);
    let message = t(appName, 'reason unknown');
    if (isAxiosErrorResponse(e) && e.response.data) {
      const responseData = e.response.data as { messages?: string[] };
      if (Array.isArray(responseData.messages)) {
        message = responseData.messages.join(' ');
      }
    }
    showError(t(appName, 'Could not set value for "{settingsKey}" to "{value}": {message}', {
      settingsKey, value, message,
    }), {
      timeout: TOAST_PERMANENT_TIMEOUT,
    });
    resetData && resetData();
    return false;
  }
};

export {
  fetchSetting,
  fetchSettings,
  saveConfirmedSetting,
  saveSimpleSetting,
};
