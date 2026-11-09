import { flushPromises, mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { defineComponent, h } from 'vue'
import { createMemoryHistory, createRouter } from 'vue-router'

const initialState = vi.hoisted(() => ({ value: null as Record<string, unknown> | null }))

vi.mock('../../src/toolkit/util/initial-state.ts', () => ({
  default: () => initialState.value,
}))

import App from '../../src/App.vue'

// Stands in for the RoundCube iframe wrapper and exposes the prop under test.
const RouteStub = defineComponent({
  props: { hideTopLine: { type: Boolean, default: false } },
  setup(props) {
    return () => h('div', { class: 'route-stub', 'data-hide-top-line': String(props.hideTopLine) })
  },
})

const mountApp = async (state: Record<string, unknown>) => {
  initialState.value = state
  const router = createRouter({
    history: createMemoryHistory(),
    routes: [{ path: '/', name: 'home', component: RouteStub }],
  })
  router.push('/')
  await router.isReady()
  const wrapper = mount(App, { global: { plugins: [router] } })
  await flushPromises()
  return wrapper
}

const baseState = {
  state: 'success',
  reason: null,
  emailUserId: 'user@example.com',
  externalLocation: 'https://rc.example.com/',
  showTopLine: false,
  loginMode: 'auto',
  loginFormShown: false,
}

describe('App.vue login modes', () => {
  beforeEach(() => {
    initialState.value = null
  })

  it('hides the top line when automatic login is used', async () => {
    const wrapper = await mountApp(baseState)
    expect(wrapper.find('.route-stub').attributes('data-hide-top-line')).toBe('true')
    expect(wrapper.find('.error-message').exists()).toBe(false)
  })

  it('shows the top line when the login form is shown (fallback or form only)', async () => {
    for (const loginMode of ['autoWithFallback', 'formOnly']) {
      const wrapper = await mountApp({ ...baseState, loginMode, loginFormShown: true })
      expect(wrapper.find('.route-stub').attributes('data-hide-top-line')).toBe('false')
      expect(wrapper.find('.error-message').exists()).toBe(false)
    }
  })

  it('does not show the top line for a fallback mode which logged in automatically', async () => {
    const wrapper = await mountApp({ ...baseState, loginMode: 'autoWithFallback', loginFormShown: false })
    expect(wrapper.find('.route-stub').attributes('data-hide-top-line')).toBe('true')
  })

  it('still honours the showTopLine setting', async () => {
    const wrapper = await mountApp({ ...baseState, showTopLine: true })
    expect(wrapper.find('.route-stub').attributes('data-hide-top-line')).toBe('false')
  })

  it.each([
    ['login', true],
    ['noemail', true],
    ['carddav', false],
    ['norcurl', false],
  ])('shows the error for reason "%s" instead of the login form in automatic mode', async (reason, hasSettingsLink) => {
    const wrapper = await mountApp({ ...baseState, state: 'error', reason })
    expect(wrapper.find('.error-message').exists()).toBe(true)
    expect(wrapper.find('.error-message').text()).not.toBe('')
    expect(wrapper.find('a[href*="/settings/user/"]').exists()).toBe(hasSettingsLink)
  })
})
