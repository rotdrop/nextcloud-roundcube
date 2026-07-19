<!--
 - @copyright Copyright (c) 2022-2026 Claus-Justus Heine <himself@claus-justus-heine.de>
 - @author Claus-Justus Heine <himself@claus-justus-heine.de>
 - @license AGPL-3.0-or-later
 -
 - This program is free software: you can redistribute it and/or modify
 - it under the terms of the GNU Affero General Public License as
 - published by the Free Software Foundation, either version 3 of the
 - License, or (at your option) any later version.
 -
 - This program is distributed in the hope that it will be useful,
 - but WITHOUT ANY WARRANTY; without even the implied warranty of
 - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 - GNU Affero General Public License for more details.
 -
 - You should have received a copy of the GNU Affero General Public License
 - along with this program. If not, see <http://www.gnu.org/licenses/>.
 -->
<template>
  <NcContent :appName="appName" class="app-container" :class="[state]">
    <NcAppContent :class="[appName + '-content-container', { 'icon-loading': loading }]">
      <RouterView v-show="!loading && state !== 'error'"
                  v-model:loading="loading"
                  @iframeLoaded="onIFrameLoaded($event)"
                  @error="onError"
      />
      <NcEmptyContent v-if="state === 'error'">
        <template #name>
          <h2>{{ t(appName, 'RoundCube Wrapper for Nextcloud') }}</h2>
        </template>
        <template #icon>
          <DynamicSvgIcon :data="appIcon" :size="64" />
        </template>
        <template #description>
          <div class="error-message">
            {{ errorMessage }}
          </div>
        </template>
      </NcEmptyContent>
    </NcAppContent>
  </NcContent>
</template>

<script setup lang="ts">
import type { RouteLocationRaw as RouterLocation } from 'vue-router'
import type { InitialState } from './types/initial-state.d.ts'

import { translate as t } from '@nextcloud/l10n'
import {
  NcAppContent,
  NcContent,
  NcEmptyContent,
} from '@nextcloud/vue'
import {
  computed,
  ref,
} from 'vue'
import {
  useRoute,
  useRouter,
} from 'vue-router'
import DynamicSvgIcon from '@rotdrop/nextcloud-vue-components/lib/components/DynamicSvgIcon.vue'
import appIcon from '../img/app.svg?raw'
import { appName } from './config.ts'
import logger from './logger.ts'
import getInitialState from './toolkit/util/initial-state.ts'

type TranslationVariables = Parameters<typeof t>[2]

const loading = ref(true)
const errorHint = ref<string | undefined>(undefined)

const router = useRouter()
const currentRoute = useRoute()

const initialState = getInitialState<InitialState>()

const state = computed(() => errorHint.value ? 'error' : initialState?.state)
const reason = computed(() => initialState?.reason)

const errorMessage = computed(() => {
  if (state.value !== 'error') {
    return null
  }
  switch (reason.value) {
    case 'norcurl':
      return t(appName, `You did not tell me where to find your configured Roundcube
instance. Please head over to the admin-settings and configure this
app, thank you! It might also be a good idea to have a look at the
README.md file which is distributed together with this app.`)
    case 'login':
      return t(appName, `Unable to login into Roundcube, there are login errors. Please check
your personal Roundcube settings. Maybe a re-login to Nextcloud
helps. Otherwise contact your system administrator.`)
    case 'carddav':
      return t(appName, 'Unable to configure the CardDAV integration for "{emailUserId}".', initialState as TranslationVariables)
    case 'noemail':
      return t(appName, 'Unable to obtain email credentials for "{emailUserId}". Please check your personal Roundcube settings.', initialState as TranslationVariables)
    default:
      return errorHint.value || null
  }
})

const onError = (event: { error: Error, hint: string }) => {
  logger.error('Caught an error event', { event })
  errorHint.value = event.hint
  loading.value = false
}

const onIFrameLoaded = async (event: { query: Record<string, string> }) => {
  loading.value = false
  logger.debug('GOT EVENT', { event })
  const routerLocation: RouterLocation = {
    name: currentRoute.name!,
    params: {},
    query: { ...event.query },
  }
  if (JSON.stringify(currentRoute.query) !== JSON.stringify(routerLocation.query)) {
    logger.debug('PUSHING ROUTE', {
      currentRoute,
      routerLocation,
      currentQuery: JSON.stringify(currentRoute.query),
      targetQuery: JSON.stringify(routerLocation.query),
    })
    try {
      await router.push(routerLocation)
    } catch (error) {
      logger.debug('NAVIGATION ABORTED', { error })
    }
  }
}

// The initial route is not named and consequently does not load the
// wrapper component, so just replace it by the one and only named
// route.
router.isReady().then(async () => {
  if (!currentRoute.name) {
    logger.debug('FORCING NAMED ROUTE', { currentRoute })
    const routerLocation: RouterLocation = {
      name: 'home',
      params: {},
      query: { ...currentRoute.query },
    }
    try {
      await router.replace(routerLocation)
    } catch (error) {
      logger.debug('NAVIGATION ABORTED', { error })
    }
  }
})
</script>

<style lang="scss" scoped>
.app-container {
  display: flex;
  flex-direction: column;
  flex-wrap: wrap;
  justify-content: center;
  align-items: stretch;
  align-content: stretch;
  main {
      // strange: all divs have the same height, there is no horizontal
      // scrollbar, but still FF likes to emit a vertical scrollbar.
      //
      // DO NOT ALLOW THIS!
      overflow: hidden !important;
  }
  :deep(.empty-content) {
    h2 ~ p {
      text-align: center;
      width: 72ex;
    }
    .hint {
      color: var(--color-text-lighter);
    }
    .empty-content__icon {
      margin-top: 16px;
    }
    .error-message {
      font-weight: bold;
    }
  }
}
</style>
