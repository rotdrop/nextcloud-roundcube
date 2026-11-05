/**
 * Wrapper config for `make l10n-ignore`: the analysis build never writes, but
 * Vite warns if the output directory is the project root.
 */
import { defineConfig, mergeConfig, type ConfigEnv, type UserConfig } from 'vite';
import appConfig from './vite.config.ts';

export default defineConfig(async (env: ConfigEnv) => {
  const base: UserConfig = typeof appConfig === 'function' ? await appConfig(env) : await appConfig;
  return mergeConfig(base, { build: { outDir: 'build/l10n-analysis' } });
});
