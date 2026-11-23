/**
 * @copyright Copyright (c) 2026 Claus-Justus Heine
 * @license AGPL-3.0-or-later
 */

import type { IDialogButton, IDialogSeverity } from '@nextcloud/dialogs';

import { spawnDialog } from '@nextcloud/vue/functions/dialog';
import CustomDialog from '../components/CustomDialog.vue';

export type CustomDialogResult = boolean | { error: unknown };

export interface CustomDialogOptions {
  name: string;
  text: string;
  /** HTML must be trusted or escaped by the caller. */
  html?: string;
  buttons: IDialogButton[];
  severity?: IDialogSeverity;
  dialogClasses?: string[];
  /** Called after mounting with this dialog's content element. */
  onReady?: (content: HTMLElement) => void;
}

export class Dialog {
  constructor(private readonly options: CustomDialogOptions) {}

  async show(): Promise<void> {
    // The repository's Vue shim exposes the close event but not component props.
    const props: CustomDialogOptions & { onClose?: (result: CustomDialogResult) => void } = this.options;
    const result = await spawnDialog(CustomDialog, props);
    if (typeof result === 'object') {
      throw new Error('Custom dialog initialization failed', { cause: result.error });
    }
    if (!result) {
      throw new Error('Dialog closed');
    }
  }
}
