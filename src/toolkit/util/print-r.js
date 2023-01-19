/**
 * @copyright Copyright (c) 2022 Claus-Justus Heine <himself@claus-justus-heine.de>
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
 *
 */

// eslint-disable-next-line camelcase
const print_r = function(array, returnVal, maxDepth) {
  if (maxDepth === undefined) {
    maxDepth = 5;
  }
  // discuss at: http://phpjs.org/functions/print_r/
  // original by: Michael White (http://getsprink.com)
  // improved by: Ben Bryan
  // improved by: Brett Zamir (http://brett-zamir.me)
  // improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
  // input by: Brett Zamir (http://brett-zamir.me)
  // depends on: echo
  // example 1: print_r(1, true);
  // returns 1: 1
  let output = '';
  const padChar = ' ';
  const padVal = 4;
  let d = window.document;
  const getFuncName = function(fn) {
    const name = (/\W*function\s+([\w$]+)\s*\(/).exec(fn);
    if (!name) {
      return '(Anonymous)';
    }
    return name[1];
  };
  const repeatChar = function(len, padChar) {
    let str = '';
    for (let i = 0; i < len; i++) {
      str += padChar;
    }
    return str;
  };
  const formatArray = function(obj, curDepth, padVal, padChar, maxDepth) {
    if (curDepth > 0) {
      curDepth++;
    }
    const basePad = repeatChar(padVal * curDepth, padChar);
    const thickPad = repeatChar(padVal * (curDepth + 1), padChar);
    let str = '';
    if (typeof obj === 'object' && obj !== null && obj.constructor && getFuncName(obj.constructor)
        !== 'PHPJS_Resource') {
      const type = Object.prototype.toString.call(obj);
      if (type === '[object Array]') {
        str += 'Array\n';
      } else /* if (type == '[object Object]') */ {
        str += 'Object\n';
      }
      str += basePad + '(\n';
      for (const key in obj) {
        const fieldType = Object.prototype.toString.call(obj[key]);
        if (curDepth > maxDepth) {
          str += thickPad + '[' + key + '] => ' + obj[key] + '\n';
        } else if (fieldType === '[object Array]') {
          str += thickPad + '[' + key + '] => ' + formatArray(obj[key], curDepth + 1, padVal, padChar, maxDepth);
        } else if (fieldType === '[object Object]') {
          str += thickPad + '[' + key + '] => ' + formatArray(obj[key], curDepth + 1, padVal, padChar, maxDepth);
        } else {
          str += thickPad + '[' + key + '] => ' + obj[key] + '\n';
        }
      }
      str += basePad + ')\n';
    } else if (obj === null || obj === undefined) {
      str = '';
    } else {
      // for our "resource" class
      str = obj.toString();
    }
    return str;
  };
  output = formatArray(array, 0, padVal, padChar);
  if (returnVal !== true) {
    if (d.body) {
      window.echo(output);
    } else {
      try {
        // We're in XUL, so appending as plain text won't work; trigger an error out of XUL
        // eslint-disable-next-line no-undef
        d = XULDocument;
        window.echo('<pre xmlns="http://www.w3.org/1999/xhtml" style="white-space:pre;">' + output + '</pre>');
      } catch (e) {
        // Outputting as plain text may work in some plain XML
        window.echo(output);
      }
    }
    return true;
  }
  return output;
};

// eslint-disable-next-line camelcase
export default print_r;

// Local Variables: ***
// js-indent-level: 2 ***
// indent-tabs-mode: nil ***
// End: ***
