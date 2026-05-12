/**
 * SPDX-FileCopyrightText: 2026 Claus-Justus Heine <himself@claus-justus-heine.de>
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { options, vTooltip } from 'floating-vue'

import './index.scss'

options.themes.tooltip.html = false
options.themes.tooltip.delay = { show: 500, hide: 200 }
options.themes.tooltip.distance = 10
options.themes.tooltip['arrow-padding'] = 3

export {
	vTooltip as default,
	options,
}
