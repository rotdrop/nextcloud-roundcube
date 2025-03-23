<!--
 - @copyright Copyright (c) 2022-2025 Claus-Justus Heine <himself@claus-justus-heine.de>
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
  <NcContent :app-name="appName" :class="['app-container', state]">
    <NcAppContent :class="[appName + '-content-container', { 'icon-loading': loading }]">
      <RouterView v-show="!loading && state !== 'error'"
                  :loading.sync="loading"
                  @iframe-loaded="onIFrameLoaded($event)"
      />
    </NcAppContent>
    <div v-if="state === 'error'"
         id="errorMsg"
    >
      <p>{{ errorMessage }}</p>
    </div>
  </NcContent>
</template>
<script setup lang="ts">
import { appName } from './config.ts'
import {
  NcAppContent,
  // NcAppNavigation,
  NcContent,
} from '@nextcloud/vue'
import {
  computed,
  ref,
} from 'vue'
import { translate as t } from '@nextcloud/l10n'
import getInitialState from './toolkit/util/initial-state.ts'
import type { InitialState } from './types/initial-state.d.ts'
import {
  useRoute,
  useRouter,
} from 'vue-router/composables'
import type { Location as RouterLocation } from 'vue-router'

const loading = ref(true)

const router = useRouter()
const currentRoute = useRoute()

const initialState = getInitialState<InitialState>()

const state = computed(() => initialState?.state)
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
    return t(appName, 'Unable to configure the CardDAV integration for "{emailUserId}".', this)
  case 'noemail':
    return t(appName, 'Unable to obtain email credentials for "{emailUserId}". Please check your personal Roundcube settings.', this)
  default:
    return null
  }
})

const onIFrameLoaded = async (event: { query: Record<string, string> }) => {
  loading.value = false
  console.debug('GOT EVENT', { event })
  if (event.query.id) {
    delete event.query.id
  }
  const routerLocation: RouterLocation = {
    name: currentRoute.name!,
    params: {},
    query: { ...event.query },
  }
  try {
    await router.push(routerLocation)
  } catch (error) {
    console.debug('NAVIGATION ABORTED', { error })
  }
}

// The initial route is not named and consequently does not load the
// wrapper component, so just replace it by the one and only named
// route.
router.onReady(async () => {
  if (!currentRoute.name) {
    const routerLocation: RouterLocation = {
      name: 'home',
      params: {},
      query: { ...currentRoute.query },
    }
    try {
      await router.replace(routerLocation)
    } catch (error) {
      console.debug('NAVIGATION ABORTED', { error })
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
  #errorMsg {
    align-self: center;
    padding:2em 2em;
    font-weight: bold;
    font-size:120%;
    max-width: 80%;
    border: 2px solid var(--color-border-maxcontrast);
    border-radius: var(--border-radius-pill);
    background-color: var(--color-background-dark);
  }
}
</style>
