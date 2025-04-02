<!--
 - Orchestra member, musicion and project management application.
 -
 - CAFEVDB -- Camerata Academica Freiburg e.V. DataBase.
 -
 - @author Claus-Justus Heine
 - @copyright 2025 Claus-Justus Heine <himself@claus-justus-heine.de>
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
 -
 -->
<template>
  <div ref="container"
       class="container dynamic-svg-icon"
       @click="$emit('click', $event)"
  />
</template>
<script setup lang="ts">
import { onMounted, ref, watch } from 'vue'
import { appName } from '../config.ts'
import { translate as t } from '@nextcloud/l10n'
import sanitzeSVG from '../util/sanitize-inline-svg.ts'
// import Console from '../util/console.ts'

// const COMPONENT_NAME = 'DynamicSvgIcon'
// const logger = new Console(COMPONENT_NAME)

// Something like the following:
//
// <svg xmlns="http://www.w3.org/2000/svg" width="61.25" height="61.25" viewBox="0 0 61.25 61.25">
// ...
// </svg>

const props = withDefaults(
  defineProps<{
    size?: number,
    width?: number,
    height?: number,
    viewBox?: [number, number, number, number],
    title?: string,
    data: string, // the svg image data
  }>(), {
    size: undefined,
    width: undefined,
    height: undefined,
    viewBox: undefined,
    title: undefined,
  },
)

defineEmits(['click'])

const dummy = document.createElement('div')

const makeSVGElement = () => {

  dummy.innerHTML = sanitzeSVG(props.data)

  const svg = dummy.firstChild

  if (!(svg instanceof SVGSVGElement)) {
    throw new TypeError(
      t(appName, 'Provided data is not a valid SVG image: "{data}".', { data: props.data }),
    )
  }

  const width = props.size || props.width
  const height = props.size || props.height

  if (width !== undefined) {
    svg.width.baseVal.newValueSpecifiedUnits(SVGLength.SVG_LENGTHTYPE_NUMBER, width)
    // svg.width.animVal.newValueSpecifiedUnits(SVGLength.SVG_LENGTHTYPE_NUMBER, width)
    // logger.debug('ANIM WIDTH AFTER')
  }
  if (height !== undefined) {
    svg.height.baseVal.newValueSpecifiedUnits(SVGLength.SVG_LENGTHTYPE_NUMBER, height)
    // svg.height.animVal.newValueSpecifiedUnits(SVGLength.SVG_LENGTHTYPE_NUMBER, height)
  }
  if (props.viewBox !== undefined) {
    svg.viewBox.baseVal.x = props.viewBox[0]
    svg.viewBox.baseVal.y = props.viewBox[1]
    svg.viewBox.baseVal.width = props.viewBox[2]
    svg.viewBox.baseVal.height = props.viewBox[3]
  }
  if (props.title) {
    let titleElement = dummy.querySelector('svg title')
    if (!titleElement) {
      titleElement = document.createElementNS('http://www.w3.org/2000/svg', 'title')
      svg.append(titleElement)
    }
    titleElement.textContent = props.title
  }

  // logger.debug('SVG DOM ELEMENT', svg)

  return svg
}

const container = ref<HTMLDivElement|null>(null)

onMounted(() => {
  container.value!.replaceChildren(makeSVGElement())
})

watch(props, () => {
  if (container.value) {
    container.value!.replaceChildren(makeSVGElement())
  }
})
</script>
<style scoped lang="scss">
.container.dynamic-svg-icon {
  display: flex;
}
</style>
