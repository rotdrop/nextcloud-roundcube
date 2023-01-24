<script>
/**
 * @copyright Copyright (c) 2022, 2023 Claus-Justus Heine <himself@claus-justus-heine.de>
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
 */
</script>
<template>
  <div class="app-container">
    <div :id="loaderId" />
    <iframe v-if="state !== 'error'"
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
<script>
import { appName } from './config.js'
import { set as vueSet } from 'vue'
import { loadHandler, resizeHandler } from './roundcube.js';
import { getInitialState } from './toolkit/services/InitialStateService.js';

const jQuery = require('jquery');

const loadTimeout = 1000; // 1 second

export default {
  name: 'App',
  components: {
  },
  data() {
    return {
      loading: 0,
      state: null,
      reason: null,
      emailUserId: null,
      externalLocation: null,
      showTopLine: null,
      gotLoadEvent: false,
      timerCount: 0,
      $frame: null,
    }
  },
  mixins: [
    settingsSync,
  ],
  computed: {
    frameId() {
      return appName + 'Frame'
    },
    loaderId() {
      return appName + 'LoaderContainer'
    },
    errorMessage() {
      if (this.state !== 'error') {
        return null
      }
      switch (this.reason) {
        case 'login':
          return t(appName, 'Unable to login into roundcube, there are login errors. Please check your personal Roundcube settings. Maybe a re-login to Nextcloud helps. Otherwise contact your system administrator.')
        case 'noemail':
          return t(appName, 'Unable to obtain email credentials for "{emailUserId}". Please check your personal Roundcube settings.', this)
        default:
          return null
      }
    }
  },
  watch: {},
  created() {
    this.getData()
  },
  mounted() {
    this.$frame = jQuery(this.$refs.roundCubeFrame)
    window.addEventListener('resize', this.resizeHandlerWrapper)
    setTimeout(this.loadTimerHandler, loadTimeout);
  },
  unmounted() {
    window.removeEventListener('resize', this.resizeHandlerWrapper)
  },
  methods: {
    info() {
      console.info(...arguments)
    },
    async getData() {
      const initialState = getInitialState()
      for (const [key, value] of Object.entries(initialState)) {
        vueSet(this, key, value)
      }
    },
    loadHandlerWrapper() {
      gotLoadEvent = true
      loadHandler(this.$frame)
    },
    resizeHandlerWrapper() {
      resizeHandler(this.$frame)
    },
    loadTimerHandler() {
      if (this.gotLoadEvent) {
        return
      }
      this.timerCount++
      const $rcfContents = this.$frame.contents();

      if ($rcfContents.find('#layout').length > 0) {
        console.info('LOAD EVENT FROM TIMER AFTER ' + (loadTimeout * timerCount) + ' ms');
        this.$frame.trigger('load', 'synthesized');
      } else {
        setTimeout(this.loadTimerHandler, loadTimeout);
      }
    },
  },
}
</script>
<style lang="scss" scoped>
##{$appName}LoaderContainer {
  background-image: url('../img/loader.gif');
  background-repeat: no-repeat;
  background-position: center;
}
#errorMsg {
  padding:1em 1em;
  font-weight: bold;
  font-size:120%;
}
</style>
