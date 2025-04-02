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

export interface DialogAlertArgs {
  title: string,
  text: string,
}

const dialogAlert = async ({ title, text }: DialogAlertArgs) => {
  await getDialogBuilder(title)
    .setText(text)
    .setSeverity(DialogSeverity.Info)
    .addButton({
      label: t('core', 'Yes'),
      type: 'primary',
      callback() {},
    })
    .build()
    .show();
};

export default dialogAlert;
