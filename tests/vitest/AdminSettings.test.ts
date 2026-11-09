import { flushPromises, mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'

type Settings = Record<string, unknown>

const loaded = vi.hoisted(() => ({ settings: {} as Record<string, unknown> }))
const saveSimpleSetting = vi.hoisted(() => vi.fn())

vi.mock('../../src/toolkit/util/settings-sync.ts', () => ({
  fetchSettings: async ({ settings }: { settings: Settings }) => {
    Object.assign(settings, loaded.settings)
  },
  saveSimpleSetting,
  saveConfirmedSetting: vi.fn(),
}))

// Nextcloud's legacy global, read when the toolkit module is loaded.
vi.hoisted(() => {
  (globalThis as Record<string, unknown>).OC = { config: { versionstring: '33.0.0' } }
})

import AdminSettings from '../../src/AdminSettings.vue'

const mountSettings = async (settings: Settings = {}) => {
  loaded.settings = settings
  const wrapper = mount(AdminSettings, { attachTo: document.body })
  await flushPromises()
  return wrapper
}

type Wrapper = Awaited<ReturnType<typeof mountSettings>>

const radio = (wrapper: Wrapper, mode: string) =>
  wrapper.get(`input[name="loginMode"][value="${mode}"]`)

describe('AdminSettings.vue login modes', () => {
  beforeEach(() => {
    saveSimpleSetting.mockReset()
  })

  it('offers exactly the three modes and no legacy checkbox', async () => {
    const wrapper = await mountSettings()
    const values = wrapper.findAll('input[name="loginMode"]').map((input) => input.attributes('value'))
    expect(values).toEqual(['auto', 'autoWithFallback', 'formOnly'])
    expect(wrapper.find('input[name="allowManualLogin"]').exists()).toBe(false)
  })

  it.each(['auto', 'autoWithFallback', 'formOnly'])('preselects the loaded mode "%s"', async (mode) => {
    const wrapper = await mountSettings({ loginMode: mode })
    expect((radio(wrapper, mode).element as HTMLInputElement).checked).toBe(true)
  })

  it('saves the setting when another mode is chosen', async () => {
    const wrapper = await mountSettings({ loginMode: 'auto' })
    await radio(wrapper, 'autoWithFallback').setValue()
    expect(saveSimpleSetting).toHaveBeenCalledTimes(1)
    expect(saveSimpleSetting.mock.calls[0][0]).toMatchObject({ settingsKey: 'loginMode', section: 'admin' })
    expect(saveSimpleSetting.mock.calls[0][0].settings.loginMode).toBe('autoWithFallback')
  })

  it('does not save while the settings are loaded', async () => {
    await mountSettings({ loginMode: 'formOnly' })
    expect(saveSimpleSetting).not.toHaveBeenCalled()
  })

  it('disables the top-line option only for "formOnly"', async () => {
    const disabled = async (loginMode: string) =>
      (await mountSettings({ loginMode })).get('input[name="showTopLine"]').attributes('disabled') !== undefined
    expect(await disabled('auto')).toBe(false)
    expect(await disabled('autoWithFallback')).toBe(false)
    expect(await disabled('formOnly')).toBe(true)
  })
})
