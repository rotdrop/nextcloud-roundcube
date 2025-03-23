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
import { hideTopLine as removeTopLine } from './roundcube.ts'
import {
  computed,
  onBeforeMount,
  onBeforeUnmount,
  onMounted,
  ref,
  watch,
} from 'vue'

const props = withDefaults(defineProps<{
  externalLocation: string,
  fullScreen?: boolean,
  hideTopLine?: boolean,
  query?: Record<string, string>,
}>(), {
  fullScreen: true,
  hideTopLine: true,
  query: () => ({
    _task: 'mail',
  }),
})

const loading = ref(true)

interface IFrameLoadedEventData {
  query: Record<string, string>,
  iFrame: HTMLIFrameElement,
  window: Window,
  document: Document,
}

const emit = defineEmits<{
  (event: 'iframe-loaded', eventData: IFrameLoadedEventData): void,
  (event: 'iframe-resize', eventData: ResizeObserverEntry): void,
  (event: 'update-loading', loading: boolean): void,
}>()

watch(loading, (value) => emit('update-loading', value))

const queryString = computed(() => (new URLSearchParams(props.query)).toString())

const requestedLocation = computed(() => {
  return props.externalLocation + (queryString.value ? '?' + queryString.value : '')
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

watch(queryString, (_value) => {
  if (requestedLocation.value !== currentLocation.value) {
    console.debug('TRIGGER IFRAME REFRESH', { request: requestedLocation.value, current: currentLocation.value })
    loading.value = true
    iFrameLocation.value = requestedLocation.value
  } else {
    console.debug('NOT CHANGING IFRAME SOURCE', { request: requestedLocation.value, current: currentLocation.value })
  }
})

const loadTimeout = 1000 // 1 second

let timerCount = 0

let loadTimer: undefined|ReturnType<typeof setTimeout>

const container = ref<null|HTMLDivElement>(null)
const loaderContainer = ref<null|HTMLDivElement>(null)
const frameWrapper = ref<null|HTMLDivElement>(null)
const externalFrame = ref<null|HTMLIFrameElement>(null)
let iFrameBody: undefined | HTMLBodyElement

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

const loadHandler = () => {
  console.debug('DOKUWIKI: GOT LOAD EVENT')
  const iFrame = externalFrame.value
  const iFrameWindow = iFrame?.contentWindow
  if (!iFrame || !iFrameWindow) {
    return
  }
  loading.value = true // if not already set ...
  const iFrameDocument = iFrame.contentDocument
  if (props.hideTopLine) {
    removeTopLine(iFrame)
  }
  if (props.fullScreen) {
    setIFrameSize(container.value!.getBoundingClientRect())
  }
  iFrameBody = iFrameDocument?.body as undefined|HTMLBodyElement
  console.debug('IFRAME BODY', { iFrameBody })
  if (iFrameBody) {
    resizeObserver.observe(iFrameBody)
  }
  loaderContainer.value!.classList.toggle('fading', true)
  console.debug('IFRAME IS NOW', {
    iFrame,
    location: iFrameWindow.location,
  })
  currentLocation.value = iFrameWindow.location.href
  const search = iFrameWindow.location.search
  const query = Object.fromEntries((new URLSearchParams(search)).entries())
  emit('iframe-loaded', {
    query,
    iFrame,
    window: iFrameWindow,
    document: iFrameDocument!,
  })
}

const loadTimerHandler = () => {
  loadTimer = undefined
  if (!loading.value) {
    return
  }
  timerCount++
  const rcfContents = externalFrame.value!.contentWindow!.document
  if (rcfContents.querySelector('#layout')) {
    console.debug('ROUNDCUBE: LOAD EVENT FROM TIMER AFTER ' + (loadTimeout * timerCount) + ' ms')
    externalFrame.value!.dispatchEvent(new Event('load'))
  } else {
    loadTimer = setTimeout(loadTimerHandler, loadTimeout)
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
