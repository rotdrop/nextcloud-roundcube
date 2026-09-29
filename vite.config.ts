/**
 * @copyright Copyright (c) 2026 Claus-Justus Heine <himself@claus-justus-heine.de>
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
 */

import type { AppOptions } from '@nextcloud/vite-config';
import type { OutputAsset, OutputChunk } from 'rolldown';
import type { Config as SVGOConfig } from 'svgo';
import type { Plugin, PluginOption } from 'vite';

import { createAppConfig } from '@nextcloud/vite-config';
import { readFileSync, writeFileSync } from 'node:fs';
import path from 'node:path';
import { defineConfig } from 'vite';
import svg from 'vite-plugin-svgo';
import { CSS, JS, WEB_ASSET_META } from './build/ts-types/php-modules/Constants.ts';

const appInfoFile = path.join(import.meta.dirname, 'appinfo/info.xml');
const appInfoContent = String(readFileSync(appInfoFile));
// const appVersion = appInfoContent.match(/<version>([^<]+)<\/version>/i)[1];
const appName = appInfoContent?.match(/<id>([^<]+)<\/id>/i)?.[1];
const assetsPrefix = (`${appName}-`).replaceAll(/[/\\]/g, '-');
const hashLength = 8;
const cssFolder = 'css' as const;
const jsFolder = 'js' as const;

if (path.dirname(WEB_ASSET_META) !== jsFolder) {
  throw new Error(`Inconsitency: "${WEB_ASSET_META}" does not start with "${jsFolder}/".`);
}

const svgoConfig: SVGOConfig = {
  multipass: true,
  js2svg: {
    indent: 2,
    pretty: true,
  },
  plugins: [
    {
      name: 'preset-default',
      params: {
        overrides: {
          // viewBox is required to resize SVGs with CSS.
          // @see https://github.com/svg/svgo/issues/1128
          // removeViewBox: false,
        },
      },
    },
  ],
};

const isEntryOutputChunk = (arg: OutputAsset|OutputChunk): arg is OutputChunk =>
  arg.type === 'chunk' && arg.isEntry === true;

/**
 * Record the assets in asset-metadata.json in order for the PHP code to find it.
 */
function postBuildHook(): Plugin {
  return {
    name: 'Post Build Hook',
    enforce: 'post',
    // apply: 'build',
    // generateBundle(_, bundle) {
    //   for (const output of Object.values(bundle)) {
    //     if (!isEntryOutputChunk(output)) {
    //       continue;
    //     }
    //     console.info('GENERATE BUNDLE', { ...output });
    //   }
    // },
    writeBundle(_, bundle) {
      const entryPoints: Record<string, { [JS]: string; [CSS]?: string }> = {};
      for (const output of Object.values(bundle)) {
        if (!isEntryOutputChunk(output)) {
          continue;
        }
        const hashedName = path.basename(output.fileName, '.mjs').replace(assetsPrefix, '');
        const base = hashedName.substring(0, hashedName.length - hashLength - 1);
        const cssAsset = path.join('css', `${assetsPrefix}${hashedName}.css`);
        entryPoints[base] = { [JS]: output.fileName, [CSS]: bundle[cssAsset]?.fileName };
      }
      const assetMetaPath = path.resolve(import.meta.dirname, WEB_ASSET_META);
      writeFileSync(assetMetaPath, JSON.stringify(entryPoints, undefined, 2));
    },
  };
}

const overrides = defineConfig(({ mode }) => ({
  css: {
    preprocessorOptions: {
      scss: {
        additionalData: `$roundCubeAppName: ${appName};`,
      },
    },
  },
  define: {
    APP_NAME: JSON.stringify(appName),
    __VUE_PROD_DEVTOOLS__: mode === 'development',
  },
  optimizeDeps: {
    rolldownOptions: {
      transform: {
        // target: 'esnext',
      },
    },
  },
  oxc: {
    // target:'esnext',
  },
  build: {
    // target: 'esnext',
    cssCodeSplit: true,
    cssMinify: mode === 'development' ? false : 'esbuild',
    manifest: true,
    modulePreload: false,
    rolldownOptions: {
      transform: {
        // target: 'esnext',
      },
      output: {
        intro: '',
        entryFileNames: () => {
          return `${jsFolder}/${assetsPrefix}[name]-[hash].mjs`;
        },
        chunkFileNames: () => {
          return `${jsFolder}/chunks/[name]-[hash].chunk.mjs`;
        },
      },
      logLevel: 'silent',
    },
  },
  plugins: [
    svg(svgoConfig),
    postBuildHook(),
  ] as PluginOption[],
}));

const appOptions: AppOptions = {
  config: overrides,
  assetsPrefix,
  assetFileNames: (assetInfo) => {
    const [name] = assetInfo.names;
    const extType = name.split('.').pop()!;
    if (/png|jpe?g|svg|gif|tiff|bmp|ico/i.test(extType)) {
      return `${cssFolder}/img/[name][extname]`;
    } else if (/css/i.test(extType)) {
      // we need hashed css name for css chunks as a cache buster
      return `${cssFolder}/[name]-[hash].css`;
    } else if (/woff2?|ttf|otf/i.test(extType)) {
      return `${cssFolder}/fonts/[name][extname]`;
    }
    return 'dist/[name]-[hash][extname]';
  },
  extractLicenseInformation: {},
  codeSplitting: {
    groups: [
      { name: 'shared', minShareCount: 2, minSize: 70_000 },
      { name: 'common', entriesAware: true, entriesAwareMergeThreshold: 90_000, minSize: 70_000 },
      { name: 'vendor', test: /node_modules/ },
      { name: 'toast-position', test: /toast-position.css/ },
      { name: 'remain' },
    ],
  },
};

if (process.env.VITEST) {
  appOptions.nodePolyfills = false;
}

const appConfig = createAppConfig(
  {
    // entry points: {name: script}
    'admin-settings': path.resolve(path.join('src', 'admin-settings.ts')),
    'personal-settings': path.resolve(path.join('src', 'personal-settings.ts')),
    app: path.resolve(path.join('src', 'app.ts')),
  },
  appOptions,
);

export default appConfig;
