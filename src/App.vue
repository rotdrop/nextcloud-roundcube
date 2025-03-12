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
  <div :class="['app-container', state]">
    <div ref="loaderContainer" class="loader-container" />
    <iframe v-show="state !== 'error'"
            :id="frameId"
            ref="roundCubeFrame"
            :src="externalLocation + '?_task=mail'"
            :class="[{ showTopLine }]"
            :name="appName"
            @load="loadHandlerWrapper"
    />
    <div v-if="state === 'error'" id="errorMsg">
      <p>{{ errorMessage }}</p>
    </div>
  </div>
</template>
<script setup lang="ts">
import { appName } from './config.ts'
import {
  computed,
  onMounted,
  onUnmounted,
  ref,
} from 'vue'
import { translate as t } from '@nextcloud/l10n'
import { loadHandler, resizeHandler } from './roundcube.ts'
import { getInitialState } from './toolkit/services/InitialStateService.js'

interface InitialState {
  state: 'error'|'success',
  reason: 'norcurl'|'noemail'|'login'|'carddav',
  emailUserId: string|null,
  externalLocation: string|null,
  showTopLine: boolean,
}

const initialState = getInitialState() as InitialState

const state = computed(() => initialState.state)
const reason = computed(() => initialState.reason)
const externalLocation = computed(() => initialState.externalLocation)
const showTopLine = computed(() => initialState.showTopLine)

let gotLoadEvent = false

const loaderContainer = ref<null|HTMLElement>(null)
const roundCubeFrame = ref<null|HTMLIFrameElement>(null)

const loadHandlerWrapper = () => {
  console.info('ROUNDCUBD: GOT LOAD EVENT')
  loadHandler(roundCubeFrame.value)
  if (!gotLoadEvent) {
    loaderContainer.value!.classList.add('fading')
  }
  gotLoadEvent = true
}

const resizeHandlerWrapper = () => {
  resizeHandler(roundCubeFrame.value)
}

const loadTimeout = 1000 // 1 second
let timerCount = 0

const loadTimerHandler = () => {
  if (gotLoadEvent) {
    return
  }
  timerCount++
  const rcfContents = roundCubeFrame.value!.contentWindow!.document
  if (rcfContents.querySelector('#layout')) {
    console.info('ROUNDCUBE: LOAD EVENT FROM TIMER AFTER ' + (loadTimeout * timerCount) + ' ms')
    roundCubeFrame.value!.dispatchEvent(new Event('load'))
  } else {
    setTimeout(loadTimerHandler, loadTimeout)
  }
}

const frameId = computed(() => appName + 'Frame')

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

onMounted(() => {
  window.addEventListener('resize', resizeHandlerWrapper)
  setTimeout(loadTimerHandler, loadTimeout)
})

onUnmounted(() => {
  window.removeEventListener('resize', resizeHandlerWrapper)
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
  &.error {
    .loader-container {
      display:none; // do not further annoy the user
    }
  }
  .loader-container {
    background-image: url('../img/loader.gif');
    background-repeat: no-repeat;
    background-position: center;
    z-index:10;
    width:100%;
    height:100%;
    position:fixed;
    transition: visibility 1s, opacity 1s;
    &.fading {
      opacity: 0;
      visibility: hidden;
    }
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
  iframe {
    flex-grow: 10;
  }
}
</style>
