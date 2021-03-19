/**
 * nextCloud - RoundCube mail plugin
 *
 * @author Claus-Justus Heine
 * @copyright 2020, 2021 Claus-Justus Heine <himself@claus-justus-heine.de>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

import { webPrefix } from './config.js';
import { loadHandler, resizeHandler, } from './roundcube.js';
import '../style/base.css';

const jQuery = require('jquery');
const $ = jQuery;

$(function() {
  const $frame = $('#' + webPrefix + 'Frame');

  if ($frame.length > 0) {
    $frame.on('load', function() {
      loadHandler($frame);
    });

    $(window).resize(function() {
      resizeHandler($frame);
    });
  }

});

// Local Variables: ***
// js-indent-level: 2 ***
// indent-tabs-mode: nil ***
// End: ***
