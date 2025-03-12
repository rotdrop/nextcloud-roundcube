/**
 * Orchestra member, musicion and project management application.
 *
 * CAFEVDB -- Camerata Academica Freiburg e.V. DataBase.
 *
 * @author Claus-Justus Heine
 * @copyright 2024, 2025 Claus-Justus Heine <himself@claus-justus-heine.de>
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

declare module '@nextcloud/vue' {
  import Vue from 'vue';
  import { VTooltip } from 'floating-vue';

  const NcActionButton: Vue;
  const NcActionCaption: Vue;
  const NcActionLink: Vue;
  const NcActionRadio: Vue;
  const NcActionRouter: Vue;
  const NcActionSeparator: Vue;
  const NcActions: Vue & {
    opened: boolean,
    closeMenu(returnFocus?: boolean):void,
    openMenu(event?: Event):void,
    $refs: Record<string, Vue> & {
      menuButton: Vue,
    },
  };
  const NcActionCheckbox: Vue & {
    'onUpdate:checked': (value: boolean) => any,
  };
  const NcButton: Vue;

  export declare class Color {
    constructor(r: number, g: number, b: number, name?: string);
    declare ['constructor']: typeof Color;
    r: number;
    g: number;
    b: number;
    name?: string;
    readonly color: string;
  }
  const NcColorPicker: Vue & {
    palette: Color[],
  }

  const NcCounterBubble: Vue;

  const NcListItem: Vue & {
    forceDisplayActions: true,
  };
  const NcListItemIcon: Vue;
  const NcModal: Vue;
  const NcProgressBar: Vue;
  const NcRichText: Vue;
  const NcSelect : Vue & {
    localLabel: string;
    search: string;
  };

  const NcSettingsSection: Vue;

  const NcTextField: Vue & {
    value: string|number;
  };

  const NcActionCheckbox: Vue;
  const Tooltip: typeof VTooltip;

  const NcContent: Vue;
  const NcAppContent: Vue;
  const NcAppNavigation: Vue;
  const NcAppNavigationItem: Vue;
  const NcAppNavigationSettings: Vue;
  const NcAppSidebar: Vue;
  const NcAppSidebarTab: Vue;
  const NcCheckboxRadioSwitch: Vue;
  const NcEllipsisedOption: Vue;
  const NcEmptyContent: Vue;
  const NcPasswordField: Vue;

  export {
    NcActionButton,
    NcActionCaption,
    NcActionCheckbox,
    NcActionLink,
    NcActionRadio,
    NcActionRouter,
    NcActionSeparator,
    NcActions,
    NcAppContent,
    NcAppNavigation,
    NcAppNavigationItem,
    NcAppNavigationSettings,
    NcAppSidebar,
    NcAppSidebarTab,
    NcButton,
    NcCheckboxRadioSwitch,
    NcColorPicker,
    NcContent,
    NcCounterBubble,
    NcEllipsisedOption,
    NcEmptyContent,
    NcListItem,
    NcListItemIcon,
    NcModal,
    NcPasswordField,
    NcProgressBar,
    NcRichText,
    NcSelect,
    NcSettingsSection,
    NcTextField,
    Tooltip,
  }
}

declare module '@nextcloud/vue';

declare module '@nextcloud/vue/dist/Directives/*.js' {
  import type { DirectiveOptions } from 'vue';

  const DirectiveVue: DirectiveOptions<>;

  export default DirectiveVue;
}

declare module '@nextcloud/vue/dist/Components/*.js' {
  import Vue from 'vue';
  export default Vue;
}
