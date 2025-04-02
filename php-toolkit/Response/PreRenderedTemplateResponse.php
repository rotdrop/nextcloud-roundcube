<?php
/**
 * A collection of reusable traits classes for Nextcloud apps.
 *
 * @author Claus-Justus Heine <himself@claus-justus-heine.de>
 * @copyright 2022, 2023, 2024, 2025 Claus-Justus Heine <himself@claus-justus-heine.de>
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

namespace OCA\RotDrop\Toolkit\Response;

use OCP\AppFramework\Http\TemplateResponse;

/**
 * A template response which optionally pre-renders its content.
 *
 * This can be used to catch ecxeptions thrown during render. The stock NC
 * TemplateResponse::render() method is called outside the middle-ware loop
 * and directly triggers the exceptions handler which then renders the core
 * exception template.
 */
class PreRenderedTemplateResponse extends TemplateResponse
{
  protected ?string $content = null;

  // phpcs:disable Squiz.Commenting.FunctionComment.Missing
  public function __construct(
    string $appName,
    string $templateName,
    array $params = [],
    string $renderAs = self::RENDER_AS_USER
  ) {
    parent::__construct($appName, $templateName, $params, $renderAs);
  }
  // phpcs:enable

  /**
   * Call parent::render() and cache its output.
   *
   * @return string
   */
  public function preRender()
  {
    $this->content = parent::render();
    return $this->content;
  }

  /** {@inheritdoc} */
  public function render()
  {
    if (!$this->content) {
      $this->preRender();
    }
    return $this->content;
  }
}
