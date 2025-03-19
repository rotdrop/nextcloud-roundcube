/**
 * @copyright Copyright (c) 2022, 2023, 2024, 2025 Claus-Justus Heine <himself@claus-justus-heine.de>
 *
 * @author Claus-Justus Heine <himself@claus-justus-heine.de>
 *
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

import axios from '@nextcloud/axios';
import fileDownload from 'js-file-download';
import { generateUrl as generateAppUrl } from './generate-url.js';
import { generateUrl, generateRemoteUrl } from '@nextcloud/router';
import { parse as parseContentDisposition } from 'content-disposition';
import type { ResponseType } from 'axios';
import { isAxiosErrorResponse } from '../types/axios-type-guards.js';

/**
 * Place a download request by posting to the given Ajax URL.
 *
 * @param url Relative download url, will be first fed in to
 * generateUrl().
 *
 * @param post Optional. Additional post-data. If present we send a
   POST request to the URL with this data.
 */
const axiosFileDownload = async (url: string, post?: Record<string, any>) => {

  const downloadUrl = (url.startsWith(generateUrl(''))
                       || url.startsWith(generateRemoteUrl('')))
    ? url
    : generateAppUrl(url);

  const axiosOptions = { responseType: 'blob' as ResponseType }
  try {
    const response = post
      ? await axios.post(downloadUrl, post, axiosOptions)
      : await axios.get(downloadUrl, axiosOptions);

    let fileName = 'download';

    const contentDisposition = response.headers?.['content-disposition'];
    if (typeof contentDisposition === 'string') {
      const contentMeta = parseContentDisposition(contentDisposition);
      fileName = contentMeta.parameters.filename || fileName;
    }
    let contentType = response.headers?.['content-type'];
    if (typeof contentType === 'string') {
      contentType = contentType.split(';')[0];
    } else {
      contentType = 'application/octetstream';
    }
    fileDownload(response.data, fileName, contentType);
  } catch (error) {
    if (isAxiosErrorResponse(error)
        && error.response.data
        && error.response.data instanceof Blob
        && error.response.data.type === 'application/json') {
      try {
        const text = await error.response.data.text();
        const data = JSON.parse(text);
        error.response.data = data;
      } catch (error2) {
        console.error('Unable to convert JSON Blob to objecct.', { error: error2 });
      }
      throw error;
    }
  }
};

export default axiosFileDownload;
