/**
 * SPDX-FileCopyrightText: 2019, 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { translate } from '@nextcloud/l10n';
import { Color } from './color.ts';

const t = (s: string) => translate('core', s);

// This are our default base colors we use for mixing palettes
const COLOR_RED = new Color(182, 70, 157, t('Purple'));
const COLOR_YELLOW = new Color(221, 203, 85, t('Gold'));
const COLOR_BLUE = new Color(0, 130, 201, t('Nextcloud blue'));
// Special "none"-colors
export const COLOR_BLACK = new Color(0, 0, 0, t('Black'));
export const COLOR_WHITE = new Color(255, 255, 255, t('White'));

/**
 * Like GenColor(4) but with labels
 */
const defaultPalette = [
  COLOR_RED,
  new Color(
    ...[191, 103, 139],
    t('Rosy brown'), // TRANSLATORS: A color name for RGB(191, 103, 139)
  ),
  new Color(
    ...[201, 136, 121],
    t('Feldspar'), // TRANSLATORS: A color name for RGB(201, 136, 121)
  ),
  new Color(
    ...[211, 169, 103],
    t('Whiskey'), // TRANSLATORS: A color name for RGB(211, 169, 103)
  ),
  COLOR_YELLOW,
  new Color(
    ...[165, 184, 114],
    t('Olivine'), // TRANSLATORS: A color name for RGB(165, 184, 114)
  ),
  new Color(
    ...[110, 166, 143],
    t('Acapulco'), // TRANSLATORS: A color name for RGB(110, 166, 143)
  ),
  new Color(
    ...[55, 148, 172],
    t('Boston Blue'), // TRANSLATORS: A color name for RGB(55, 148, 172)
  ),
  COLOR_BLUE,
  new Color(
    ...[45, 115, 190],
    t('Mariner'), // TRANSLATORS: A color name for RGB(45, 115, 190)
  ),
  new Color(
    ...[91, 100, 179],
    t('Blue Violet'), // TRANSLATORS: A color name for RGB(91, 100, 179)
  ),
  new Color(
    ...[136, 85, 168],
    t('Deluge'), // TRANSLATORS: A color name for RGB(136, 85, 168)
  ),
];

export default defaultPalette;
