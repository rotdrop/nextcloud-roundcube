/**
 * @copyright Copyright (c) 2025 Claus-Justus Heine <himself@claus-justus-heine.de>
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
import {
  getDialogBuilder,
  DialogSeverity,
} from '@nextcloud/dialogs';
import { translate as t } from '@nextcloud/l10n';

export interface DialogAlertArgs {
  title: string,
  text: string,
  allowHtml: boolean,
}

const dialogAlert = async ({ title, text, allowHtml }: DialogAlertArgs) => {
  console.info('START');
  const dialog = getDialogBuilder(title)
    .setText(allowHtml === true ? '' : text)
    .setSeverity(DialogSeverity.Info)
    .addButton({
      label: t(appName, 'close'),
      type: 'primary',
      callback() {},
    })
    .build();
  console.info('AFTER BUILD');
  if (allowHtml === true) {
    dialog.setHTML(text);
  }
  console.info('BEFORE SHOW()');
  const result = dialog.show();
  console.info('AFTER SHOW()');
  return result;
};

export default dialogAlert;
