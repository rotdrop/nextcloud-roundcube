import { defineComponent, h } from 'vue'

const passThrough = (tag = 'div') => defineComponent({
  inheritAttrs: false,
  setup(_, { attrs, slots }) {
    return () => h(tag, attrs, Object.values(slots).map((slot) => slot?.()))
  },
})

export const NcContent = passThrough()
export const NcAppContent = passThrough()
export const NcEmptyContent = passThrough()
export const NcSettingsSection = passThrough('section')
export const NcListItem = passThrough('li')
export const NcActionButton = passThrough('button')

export const NcButton = defineComponent({
  inheritAttrs: false,
  setup(_, { attrs, slots }) {
    return () => h(attrs.href ? 'a' : 'button', attrs, Object.values(slots).map((slot) => slot?.()))
  },
})
