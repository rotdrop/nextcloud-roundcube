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

import {
  getDialogBuilder,
  DialogSeverity,
} from '@nextcloud/dialogs';
import { translate as t } from '@nextcloud/l10n';

export interface DialogConfirmArgs {
  title: string,
  text: string,
  allowHtml?: boolean,
  defaultNo?: boolean,
}

const dialogConfirm = async ({ title, text, allowHtml, defaultNo }: DialogConfirmArgs): Promise<boolean|undefined> => {
  let answer: boolean|undefined;
  const dialog = getDialogBuilder(title)
    .setSeverity(DialogSeverity.Info)
    .setText(allowHtml === true ? '' : text)
    .addButton({
      label: t('core', 'No'),
      type: defaultNo ? 'primary' : 'secondary',
      callback() { answer = false; },
    })
    .addButton({
      label: t('core', 'Yes'),
      type: defaultNo ? 'secondary' : 'primary',
      callback() { answer = true; },
    })
    .build();
  if (allowHtml === true) {
    dialog.setHTML(text);
  }
  await dialog.show();
  if (answer === undefined) {
    return undefined;
  } else {
    return answer;
  }
};

export default dialogConfirm;
