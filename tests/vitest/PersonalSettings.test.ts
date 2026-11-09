import { flushPromises, mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'

const loaded = vi.hoisted(() => ({ settings: {} as Record<string, unknown> }))
const saveConfirmedSetting = vi.hoisted(() => vi.fn())

vi.mock('../../src/toolkit/util/settings-sync.ts', () => ({
  fetchSettings: async ({ settings }: { settings: Record<string, unknown> }) => {
    Object.assign(settings, loaded.settings)
  },
  saveConfirmedSetting,
}))

vi.hoisted(() => {
  (globalThis as Record<string, unknown>).OC = { config: { versionstring: '33.0.0' } }
})

import PersonalSettings from '../../src/PersonalSettings.vue'

const mask = '●●●●●●●●'

const mountSettings = async (settings: Record<string, unknown> = {}) => {
  loaded.settings = { emailPassword: '', humanEmailPassword: mask, ...settings }
  const wrapper = mount(PersonalSettings)
  await flushPromises()
  return wrapper
}

type Wrapper = Awaited<ReturnType<typeof mountSettings>>
const password = (wrapper: Wrapper) => wrapper.get('input.password')
const toggle = (wrapper: Wrapper) => wrapper.get('button[aria-label$="password"]')
const clearButton = (wrapper: Wrapper) => wrapper.findAll('button')
  .find((button) => button.text() === 'Clear stored password')!

describe('PersonalSettings.vue password editing', () => {
  beforeEach(() => {
    saveConfirmedSetting.mockReset()
    saveConfirmedSetting.mockImplementation(async ({ value, settings }) => {
      settings.emailPassword = ''
      settings.humanEmailPassword = value ? mask : ''
      return true
    })
  })

  it('shows a stored password only as a placeholder and disables reveal', async () => {
    const wrapper = await mountSettings()
    expect((password(wrapper).element as HTMLInputElement).value).toBe('')
    expect(password(wrapper).attributes('placeholder')).toBe(mask)
    expect(password(wrapper).attributes('type')).toBe('password')
    expect(toggle(wrapper).attributes('disabled')).toBeDefined()
    expect(clearButton(wrapper).exists()).toBe(true)
    expect(saveConfirmedSetting).not.toHaveBeenCalled()
  })

  it('does not submit an empty replacement or the placeholder', async () => {
    const wrapper = await mountSettings()
    await password(wrapper).trigger('keydown.enter')
    expect(saveConfirmedSetting).not.toHaveBeenCalled()
  })

  it('does not show a mask or a clear action when no password is stored', async () => {
    const wrapper = await mountSettings({ humanEmailPassword: '' })
    expect(password(wrapper).attributes('placeholder')).toBe('Email Password')
    expect(toggle(wrapper).attributes('disabled')).toBeDefined()
    expect(clearButton(wrapper)).toBeUndefined()
  })

  it('reveals only newly entered text and hides it again when erased', async () => {
    const wrapper = await mountSettings()
    await password(wrapper).setValue('new-secret')
    expect(toggle(wrapper).attributes('disabled')).toBeUndefined()
    await toggle(wrapper).trigger('click')
    expect(password(wrapper).attributes('type')).toBe('text')
    expect((password(wrapper).element as HTMLInputElement).value).toBe('new-secret')
    await password(wrapper).setValue('')
    expect(toggle(wrapper).attributes('disabled')).toBeDefined()
    expect(password(wrapper).attributes('type')).toBe('password')
    expect(saveConfirmedSetting).not.toHaveBeenCalled()
  })

  it('saves a replacement and restores the hidden, empty field', async () => {
    const wrapper = await mountSettings({ humanEmailPassword: '' })
    await password(wrapper).setValue('new-secret')
    await toggle(wrapper).trigger('click')
    await password(wrapper).trigger('keydown.enter')
    await flushPromises()
    expect(saveConfirmedSetting).toHaveBeenCalledExactlyOnceWith(expect.objectContaining({
      value: 'new-secret',
      section: 'personal',
      settingsKey: 'emailPassword',
    }))
    expect((password(wrapper).element as HTMLInputElement).value).toBe('')
    expect(password(wrapper).attributes('placeholder')).toBe(mask)
    expect(password(wrapper).attributes('type')).toBe('password')
    expect(toggle(wrapper).attributes('disabled')).toBeDefined()
  })

  it('clears a stored password only through the explicit action', async () => {
    const wrapper = await mountSettings()
    await clearButton(wrapper).trigger('click')
    await flushPromises()
    expect(saveConfirmedSetting).toHaveBeenCalledExactlyOnceWith(expect.objectContaining({
      value: '',
      section: 'personal',
      settingsKey: 'emailPassword',
    }))
    expect(password(wrapper).attributes('placeholder')).toBe('Email Password')
    expect(clearButton(wrapper)).toBeUndefined()
  })

  it('keeps the input and stored-password indicator after a failed save', async () => {
    saveConfirmedSetting.mockResolvedValue(false)
    const wrapper = await mountSettings()
    await password(wrapper).setValue('new-secret')
    await password(wrapper).trigger('keydown.enter')
    await flushPromises()
    expect((password(wrapper).element as HTMLInputElement).value).toBe('new-secret')
    expect(password(wrapper).attributes('placeholder')).toBe(mask)
    expect(password(wrapper).attributes('disabled')).toBeUndefined()
  })

  it('preserves the stored-password indicator when clearing fails', async () => {
    saveConfirmedSetting.mockResolvedValue(false)
    const wrapper = await mountSettings()
    await clearButton(wrapper).trigger('click')
    await flushPromises()
    expect(password(wrapper).attributes('placeholder')).toBe(mask)
    expect(clearButton(wrapper).attributes('disabled')).toBeUndefined()
  })

  it('disables password actions while a save is pending', async () => {
    let finishSave!: (saved: boolean) => void
    saveConfirmedSetting.mockReturnValue(new Promise<boolean>((resolve) => {
      finishSave = resolve
    }))
    const wrapper = await mountSettings()
    await password(wrapper).setValue('new-secret')
    await password(wrapper).trigger('keydown.enter')
    expect(password(wrapper).attributes('disabled')).toBeDefined()
    expect(toggle(wrapper).attributes('disabled')).toBeDefined()
    expect(clearButton(wrapper).attributes('disabled')).toBeDefined()
    await password(wrapper).trigger('keydown.enter')
    expect(saveConfirmedSetting).toHaveBeenCalledTimes(1)
    finishSave(false)
    await flushPromises()
    expect(password(wrapper).attributes('disabled')).toBeUndefined()
  })

  it.each([
    { forceSSOAdmin: true },
    { emailAddressChoiceAdmin: 'fixedSingleAddress', fixedSingleEmailAddressAdmin: 'shared@example.com' },
  ])('disables password actions when policy prevents editing: %j', async (settings) => {
    const wrapper = await mountSettings(settings)
    expect(password(wrapper).attributes('disabled')).toBeDefined()
    expect(toggle(wrapper).attributes('disabled')).toBeDefined()
    expect(clearButton(wrapper).attributes('disabled')).toBeDefined()
    await password(wrapper).trigger('keydown.enter')
    expect(saveConfirmedSetting).not.toHaveBeenCalled()
  })
})
