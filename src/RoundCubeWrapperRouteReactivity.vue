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
  <RoundCubeWrapper v-bind="$attrs"
                    :external-location="externalLocation"
                    :query="routeQuery"
                    :enable-bridge="enableBridge"
                    v-on="$listeners"
  />
</template>
<script setup lang="ts">
import RoundCubeWrapper from './RoundCubeWrapper.vue'
import {
  onBeforeMount,
  ref,
} from 'vue'
import {
  onBeforeRouteUpdate,
  useRoute,
} from 'vue-router/composables'
import type { Route } from 'vue-router'
import getInitialState from './toolkit/util/initial-state.ts'
import type { InitialState } from './types/initial-state.d.ts'
import logger from './logger.ts'

const currentRoute = useRoute()

const routeQuery = ref<Route['query']>({})

const initialState = getInitialState<InitialState>()
const externalLocation = ref<string>(initialState?.externalLocation || '')
const enableBridge = ref<boolean>(initialState?.enableBridge ?? false)

const onRouteChange = (to: Route) => {
  routeQuery.value = to.query
}

onBeforeMount(() => {
  logger.debug('ON BEFORE MOUNT', { ...currentRoute }, { ...window?.history?.state })
  onRouteChange(currentRoute)
})

onBeforeRouteUpdate((to, from, next) => {
  logger.debug('ON BEFORE ROUTE UPDATE', {
    to: { ...to },
    from: { ...from },
    windowState: { ...(window?.history?.state || {}) },
  })
  onRouteChange(to)
  next()
})

</script>
