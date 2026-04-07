<!--
 - @copyright Copyright (c) 2025 Claus-Justus Heine <himself@claus-justus-heine.de>
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
  <div ref="container"
       :class="appName + '-container'"
  >
    <div ref="loaderContainer"
         class="loader-container"
    />
    <div ref="frameWrapper"
         :class="appName + '-frame-wrapper'"
    >
      <iframe :id="frameId"
              ref="externalFrame"
              :src="iFrameLocation"
              :name="appName"
              @load="loadHandler"
      />
    </div>
  </div>
</template>
<script setup lang="ts">
import { appName } from './config.ts'
import { translate as t } from '@nextcloud/l10n'
import { hideTopLine as removeTopLine } from './roundcube.ts'
import {
  computed,
  onBeforeMount,
  onBeforeUnmount,
  onMounted,
  ref,
  watch,
} from 'vue'
import logger from './logger.ts'
import type { Route } from 'vue-router'

const wrappedApp = 'RoundCube'

const props = withDefaults(defineProps<{
  externalLocation: string,
  fullScreen?: boolean,
  hideTopLine?: boolean,
  query?: Route['query'],
}>(), {
  fullScreen: true,
  hideTopLine: true,
  query: () => ({
    _task: 'mail',
  }),
})

interface IFrameLoadedEventData {
  query: Record<string, string>,
  iFrame: HTMLIFrameElement,
  window: Window,
  document: Document,
}

interface ErrorEventData {
  error: Error,
  hint: string,
}

const emit = defineEmits<{
  (event: 'iframe-loaded', eventData: IFrameLoadedEventData): void,
  (event: 'iframe-resize', eventData: ResizeObserverEntry): void,
  (event: 'update-loading', loading: boolean): void,
  (event: 'error', eventData: ErrorEventData): void,
}>()

const loading = ref(true)

watch(loading, (value) => emit('update-loading', value))

const queryString = computed(() => (new URLSearchParams(props.query as Record<string, string>)).toString().replace(/\+/g, '%20'))

const requestedLocation = computed(() => {
  return props.externalLocation + '/' + (queryString.value ? '?' + queryString.value : '')
})
/**
 * Value of src attribute of iframe.
 */
const iFrameLocation = ref(requestedLocation.value)
/**
 * Actual location which in general is different from the src attribute.
 */
const currentLocation = ref(requestedLocation.value)

const frameId = computed(() => appName + '-frame')

const loadTimeout = 1000 // 1 second

let timerCount = 0

let loadTimer: undefined|ReturnType<typeof setTimeout>

const container = ref<null|HTMLDivElement>(null)
const loaderContainer = ref<null|HTMLDivElement>(null)
const frameWrapper = ref<null|HTMLDivElement>(null)
const externalFrame = ref<null|HTMLIFrameElement>(null)
let iFrameBody: undefined | HTMLBodyElement

// Flag to track if URL change originates from iframe navigation (to prevent reload loop)
let changeFromIframe = false

const contentObserver = new MutationObserver((entries) => {
  logger.info('MUTATION OBSERVED', { entries })
  const iFrame = externalFrame.value!
  emitLoaded(iFrame)
})

watch(queryString, (_value) => {
  if (changeFromIframe) {
    logger.debug('SKIP IFRAME REFRESH (change from iframe)', { request: requestedLocation.value, current: currentLocation.value })
    changeFromIframe = false
    return
  }
  if (requestedLocation.value !== currentLocation.value) {
    logger.debug('TRIGGER IFRAME REFRESH', { request: requestedLocation.value, current: currentLocation.value })
    loading.value = true
    contentObserver.disconnect()
    iFrameLocation.value = requestedLocation.value
    const iFrame = externalFrame.value
    if (iFrame?.contentWindow) {
      iFrame.contentWindow.location.href = requestedLocation.value
    }
  } else {
    logger.debug('NOT CHANGING IFRAME SOURCE', { request: requestedLocation.value, current: currentLocation.value })
  }
})

const setIFrameSize = ({ width, height }: DOMRectReadOnly) => {
  if (!externalFrame.value) {
    return
  }
  const iFrame = externalFrame.value
  iFrame.style.width = width + 'px'
  iFrame.style.height = height + 'px'
}

const resizeObserver = new ResizeObserver((entries) => {
  for (const entry of entries) {
    if (entry.target === iFrameBody) {
      emit('iframe-resize', entry)
      continue
    }
    if (props.fullScreen && entry.target === container.value) {
      setIFrameSize(entry.contentRect)
    }
  }
})

const emitError = (error: unknown) => {
  loaderContainer.value!.classList.toggle('fading', true)
  emit('error', {
    error: error instanceof Error ? error : new Error('Non-error error', { cause: error }),
    hint: t(
      appName,
      `Unable to access the contents of the wrapped {wrappedApp} instance.
This may be caused by cross-domain access restrictions.
Please check that your Nextcloud instance ({nextcloudUrl}) and the wrapped {wrappedApp} instance ({iFrameUrl}) are served from the same domain.`,
      {
        nextcloudUrl: window.location.protocol + '//' + window.location.host,
        iFrameUrl: props.externalLocation || '',
        wrappedApp,
      },
    ),
  })
}

const emitLoaded = (iFrame: HTMLIFrameElement) => {
  const iFrameWindow = iFrame.contentWindow!
  const iFrameDocument = iFrame.contentDocument!
  currentLocation.value = iFrameWindow.location.href
  const search = iFrameWindow.location.search
  const query = Object.fromEntries((new URLSearchParams(search)).entries())

  // Set flag to prevent watch from triggering iframe reload
  // when the URL change originates from iframe navigation
  changeFromIframe = true

  emit('iframe-loaded', {
    query,
    iFrame,
    window: iFrameWindow,
    document: iFrameDocument,
  })
}

const loadHandler = () => {
  logger.debug('GOT LOAD EVENT')
  const iFrame = externalFrame.value
  const iFrameWindow = iFrame?.contentWindow
  if (!iFrame || !iFrameWindow) {
    return
  }
  loading.value = true // if not already set ...
  let iFrameDocument: Document|null
  try {
    iFrameDocument = iFrame.contentDocument
  } catch (error: unknown) {
    logger.error('UNABLE TO ACCESS IFRAME CONTENTS', { error })
    emitError(error)
    return
  }
  if (props.hideTopLine) {
    try {
      removeTopLine(iFrame)
    } catch (error: unknown) {
      logger.error('UNABLE TO TUNE IFRAME CONTENTS', { error })
      emitError(error)
      return
    }
  }
  if (props.fullScreen) {
    setIFrameSize(container.value!.getBoundingClientRect())
  }
  iFrameBody = iFrameDocument?.body as undefined|HTMLBodyElement
  logger.debug('IFRAME BODY', { iFrameBody })
  if (iFrameBody) {
    resizeObserver.observe(iFrameBody)
    contentObserver.observe(iFrameBody, { childList: true, subtree: true })
  }
  loaderContainer.value!.classList.toggle('fading', true)
  logger.debug('IFRAME IS NOW', {
    iFrame,
    location: iFrameWindow.location,
  })
  emitLoaded(iFrame)
  loading.value = false
}

const loadTimerHandler = () => {
  loadTimer = undefined
  if (!loading.value) {
    return
  }
  timerCount++
  try {
    const iFrameContents = externalFrame.value!.contentWindow!.document
    if (iFrameContents.querySelector('#layout')) {
      logger.debug('ROUNDCUBE: LOAD EVENT FROM TIMER AFTER ' + (loadTimeout * timerCount) + ' ms')
      externalFrame.value!.dispatchEvent(new Event('load'))
    } else {
      loadTimer = setTimeout(loadTimerHandler, loadTimeout)
    }
  } catch (error: unknown) {
    logger.error('UNABLE TO ACCESS IFRAME CONTENTS', { error })
    emitError(error)
  }
}

onBeforeMount(() => {
  loading.value = true
  iFrameLocation.value = requestedLocation.value
})

watch(() => props.fullScreen, (value) => {
  if (value) {
    // if this mutation really happens we trigger an iframe reload by
    // touching its src attribute
    const iFrame = externalFrame.value
    if (iFrame) {
      if (iFrameLocation.value !== requestedLocation.value) {
        iFrameLocation.value = requestedLocation.value
      } else if (iFrame.contentWindow) {
        iFrame.contentWindow.location.href = requestedLocation.value
      }
    }
  }
})

onMounted(() => {
  if (!loadTimer) {
    loadTimer = setTimeout(loadTimerHandler, loadTimeout)
  }
  resizeObserver.observe(container.value!)
})

onBeforeUnmount(() => {
  resizeObserver.disconnect()
  contentObserver.disconnect()
})

defineExpose({
  currentLocation,
  redaxoIFrame: externalFrame,
})

</script>
<style scoped lang="scss">
.#{$roundCubeAppName}-container {
  display: flex;
  flex-direction: column;
  flex-wrap: wrap;
  justify-content: center;
  align-items: stretch;
  align-content: stretch;
  height: 100%;
  .loader-container {
    background-image: url('../img/loader.gif');
    background-repeat: no-repeat;
    background-position: center;
    z-index:10;
    width:100%;
    height:100%;
    position:absolute;
    transition: visibility 1s, opacity 1s;
    &.fading {
      opacity: 0;
      visibility: hidden;
    }
  }
  * {
    flex-grow: 10;
    max-width: 100%;
    max-height: 100%;
  }
}
</style>
