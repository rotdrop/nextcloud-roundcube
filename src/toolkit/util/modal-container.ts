/**
 * @file
 *
 * @license AGPL-3.0-or-later
 */

/**
 * CSS selector of the element to mount modals into. Nextcloud's user pages
 * use `#body-user`, the settings pages `#body-settings`; fall back to `body`.
 */
export const modalContainer = (): string => {
  for (const selector of ['#body-user', '#body-settings']) {
    if (document.querySelector(selector)) {
      return selector;
    }
  }
  return 'body';
};

export default modalContainer;
