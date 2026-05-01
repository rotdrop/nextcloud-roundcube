/**
 * Composable for bridging operations between Nextcloud and an embedded RoundCube iframe.
 *
 * Handles communication via postMessage to:
 * - Pick files from Nextcloud and send them to the iframe
 * - Save files from the iframe to Nextcloud
 * - Manage calendars and add events via CalDAV
 *
 * @author Laurent Dinclaux <laurent@gecka.nc>
 * @copyright 2025 Gecka
 * @license AGPL-3.0-or-later
 */

import { ref, onMounted, onBeforeUnmount } from 'vue'
import { generateRemoteUrl, generateOcsUrl, generateUrl } from '@nextcloud/router'
import { getCurrentUser, getRequestToken } from '@nextcloud/auth'
import axios from '@nextcloud/axios'
import type { Node } from '@nextcloud/files'
import logger from '../logger'

// Message types for iframe communication
export interface PickFileMessage {
  action: 'pickFile'
  requestId: string
  multiple?: boolean
  mimeTypes?: string[]
}

export interface SaveFileMessage {
  action: 'saveFile'
  requestId: string
  filename: string
  content: string // base64 encoded
  mimeType?: string
}

export interface SaveFilesMessage {
  action: 'saveFiles'
  requestId: string
  files: Array<{
    filename: string
    content: string // base64 encoded
    mimeType?: string
  }>
}

export interface CreateShareLinkMessage {
  action: 'createShareLink'
  requestId: string
}

export interface GetCalendarsMessage {
  action: 'getCalendars'
  requestId: string
}

export interface AddToCalendarMessage {
  action: 'addToCalendar'
  requestId: string
  calendarUrl: string
  icsContent: string
}

export interface FilePickedResponse {
  action: 'filePicked'
  requestId: string
  success: boolean
  files?: Array<{
    name: string
    path: string
    mimeType: string
    size: number
    content: string // base64 encoded
  }>
  error?: string
}

export interface FileSavedResponse {
  action: 'fileSaved'
  requestId: string
  success: boolean
  path?: string
  error?: string
}

export interface ShareLinkCreatedResponse {
  action: 'shareLinkCreated'
  requestId: string
  success: boolean
  url?: string
  filename?: string
  error?: string
}

export interface CalendarInfo {
  url: string
  displayname: string
  color: string
}

export interface CalendarsResponse {
  action: 'calendarsLoaded'
  requestId: string
  success: boolean
  calendars?: CalendarInfo[]
  error?: string
}

export interface EventAddedResponse {
  action: 'eventAdded'
  requestId: string
  success: boolean
  updated?: boolean // true if event was updated, false if created
  error?: string
}

type IframeMessage = PickFileMessage | SaveFileMessage | SaveFilesMessage | CreateShareLinkMessage | GetCalendarsMessage | AddToCalendarMessage

// Pending request state
interface PendingPickRequest {
  requestId: string
  multiple: boolean
  mimeTypes?: string[]
}

interface PendingSaveRequest {
  requestId: string
  filename: string
  content: string
  mimeType?: string
}

interface PendingSaveFilesRequest {
  requestId: string
  files: Array<{
    filename: string
    content: string
    mimeType?: string
  }>
}

interface PendingShareLinkRequest {
  requestId: string
}

interface PendingGetCalendarsRequest {
  requestId: string
}

interface PendingAddToCalendarRequest {
  requestId: string
  calendarUrl: string
  icsContent: string
}

/**
 * Composable to bridge file operations between Nextcloud and an iframe.
 *
 * @param iframeRef - Ref to the iframe element
 * @param options - Options object
 * @param options.allowedOrigin - Origin to accept messages from (optional, defaults to same origin)
 * @param options.enabled - Whether the bridge is enabled (optional, defaults to true)
 */
export function useIframeBridge(
  iframeRef: { value: HTMLIFrameElement | null },
  options?: { allowedOrigin?: string; enabled?: boolean }
) {
  const isProcessing = ref(false)
  const enabled = options?.enabled ?? true
  const allowedOrigin = options?.allowedOrigin
  const currentUser = getCurrentUser()

  // File picker state (for Vue component control)
  const isFilePickerOpen = ref(false)
  const isFileSaverOpen = ref(false)
  const isShareLinkPickerOpen = ref(false)
  const pendingPickRequest = ref<PendingPickRequest | null>(null)
  const pendingSaveRequest = ref<PendingSaveRequest | null>(null)
  const pendingSaveFilesRequest = ref<PendingSaveFilesRequest | null>(null)
  const pendingShareLinkRequest = ref<PendingShareLinkRequest | null>(null)

  /**
   * Get the WebDAV base URL for the current user.
   */
  const getWebDavUrl = (path: string = ''): string => {
    if (!currentUser?.uid) {
      throw new Error('No user logged in')
    }
    // generateRemoteUrl expects path without leading slash
    const basePath = `dav/files/${currentUser.uid}`
    const cleanPath = path.startsWith('/') ? path : `/${path}`
    return generateRemoteUrl(basePath + cleanPath)
  }

  /**
   * Download a file from Nextcloud via WebDAV.
   */
  const downloadFile = async (path: string): Promise<{ content: ArrayBuffer; mimeType: string }> => {
    const url = getWebDavUrl(path)
    logger.debug('Downloading file from WebDAV', { url, path })

    const response = await fetch(url, {
      method: 'GET',
      credentials: 'include',
    })

    if (!response.ok) {
      throw new Error(`Failed to download file: ${response.status} ${response.statusText}`)
    }

    const content = await response.arrayBuffer()
    const mimeType = response.headers.get('Content-Type') || 'application/octet-stream'

    return { content, mimeType }
  }

  /**
   * Check if a file exists via WebDAV.
   */
  const fileExists = async (path: string): Promise<boolean> => {
    const url = getWebDavUrl(path)
    try {
      const response = await fetch(url, {
        method: 'HEAD',
        credentials: 'include',
      })
      return response.ok
    } catch {
      return false
    }
  }

  /**
   * Find a unique filename by adding (2), (3), etc. if the file already exists.
   * Mimics the behavior of Nextcloud Mail app.
   */
  const findUniqueFilename = async (folderPath: string, filename: string): Promise<string> => {
    // Split filename into name and extension
    const lastDot = filename.lastIndexOf('.')
    const hasExtension = lastDot > 0
    const baseName = hasExtension ? filename.substring(0, lastDot) : filename
    const extension = hasExtension ? filename.substring(lastDot) : ''

    // Try the original filename first
    let fullPath = `${folderPath}/${filename}`.replace(/\/+/g, '/')
    if (!(await fileExists(fullPath))) {
      return fullPath
    }

    // File exists, try with counter
    let counter = 2
    while (counter <= 100) { // Safety limit
      const newFilename = `${baseName} (${counter})${extension}`
      fullPath = `${folderPath}/${newFilename}`.replace(/\/+/g, '/')
      if (!(await fileExists(fullPath))) {
        return fullPath
      }
      counter++
    }

    // Fallback: use timestamp
    const timestamp = Date.now()
    const newFilename = `${baseName} (${timestamp})${extension}`
    return `${folderPath}/${newFilename}`.replace(/\/+/g, '/')
  }

  /**
   * Upload a file to Nextcloud via WebDAV.
   */
  const uploadFile = async (path: string, content: ArrayBuffer, mimeType?: string): Promise<void> => {
    const url = getWebDavUrl(path)
    logger.debug('Uploading file to WebDAV', { url, path })

    const requestToken = getRequestToken()
    const headers: Record<string, string> = {
      'Content-Type': mimeType || 'application/octet-stream',
    }
    if (requestToken) {
      headers['requesttoken'] = requestToken
    }

    const response = await fetch(url, {
      method: 'PUT',
      credentials: 'include',
      headers,
      body: content,
    })

    if (!response.ok) {
      throw new Error(`Failed to upload file: ${response.status} ${response.statusText}`)
    }
  }

  /**
   * Get the CalDAV base URL for the current user's calendars.
   */
  const getCalDavUrl = (path: string = ''): string => {
    if (!currentUser?.uid) {
      throw new Error('No user logged in')
    }
    const basePath = `dav/calendars/${currentUser.uid}`
    const cleanPath = path.startsWith('/') ? path : path ? `/${path}` : ''
    return generateRemoteUrl(basePath + cleanPath)
  }

  /**
   * Fetch user's calendars via CalDAV PROPFIND.
   */
  const fetchCalendars = async (): Promise<CalendarInfo[]> => {
    const url = getCalDavUrl()
    logger.debug('Fetching calendars from CalDAV', { url })

    const requestToken = getRequestToken()
    const headers: Record<string, string> = {
      'Content-Type': 'application/xml; charset=utf-8',
      Depth: '1',
    }
    if (requestToken) {
      headers.requesttoken = requestToken
    }

    const propfindBody = `<?xml version="1.0" encoding="utf-8"?>
<d:propfind xmlns:d="DAV:" xmlns:cs="http://calendarserver.org/ns/" xmlns:c="urn:ietf:params:xml:ns:caldav" xmlns:oc="http://owncloud.org/ns" xmlns:nc="http://nextcloud.org/ns" xmlns:x1="http://apple.com/ns/ical/">
  <d:prop>
    <d:resourcetype/>
    <d:displayname/>
    <x1:calendar-color/>
    <cs:getctag/>
    <c:supported-calendar-component-set/>
    <oc:calendar-enabled/>
  </d:prop>
</d:propfind>`

    const response = await fetch(url, {
      method: 'PROPFIND',
      credentials: 'include',
      headers,
      body: propfindBody,
    })

    if (!response.ok) {
      throw new Error(`Failed to fetch calendars: ${response.status} ${response.statusText}`)
    }

    const text = await response.text()
    const calendars: CalendarInfo[] = []

    // Parse XML response
    const parser = new DOMParser()
    const doc = parser.parseFromString(text, 'application/xml')
    const responses = doc.getElementsByTagNameNS('DAV:', 'response')

    for (let i = 0; i < responses.length; i++) {
      const resp = responses[i]

      // Check if it's a calendar (has calendar resource type)
      const resourceTypes = resp.getElementsByTagNameNS('DAV:', 'resourcetype')[0]
      if (!resourceTypes) continue

      const isCalendar = resourceTypes.getElementsByTagNameNS('urn:ietf:params:xml:ns:caldav', 'calendar').length > 0
      if (!isCalendar) continue

      // Check if calendar supports VEVENT
      const supportedComponents = resp.getElementsByTagNameNS('urn:ietf:params:xml:ns:caldav', 'supported-calendar-component-set')[0]
      let supportsVevent = false
      if (supportedComponents) {
        const comps = supportedComponents.getElementsByTagNameNS('urn:ietf:params:xml:ns:caldav', 'comp')
        for (let j = 0; j < comps.length; j++) {
          if (comps[j].getAttribute('name') === 'VEVENT') {
            supportsVevent = true
            break
          }
        }
      }
      if (!supportsVevent) continue

      // Check if calendar is enabled
      const enabledEl = resp.getElementsByTagNameNS('http://owncloud.org/ns', 'calendar-enabled')[0]
      if (enabledEl && enabledEl.textContent === '0') continue

      // Get calendar URL
      const hrefEl = resp.getElementsByTagNameNS('DAV:', 'href')[0]
      if (!hrefEl) continue
      const href = hrefEl.textContent || ''

      // Get display name
      const displaynameEl = resp.getElementsByTagNameNS('DAV:', 'displayname')[0]
      const displayname = displaynameEl?.textContent || 'Calendar'

      // Get calendar color
      const colorEl = resp.getElementsByTagNameNS('http://apple.com/ns/ical/', 'calendar-color')[0]
      let color = colorEl?.textContent || '#0082c9'
      // Normalize color (remove alpha if present: #RRGGBBAA -> #RRGGBB)
      if (color.length === 9 && color.startsWith('#')) {
        color = color.substring(0, 7)
      }

      calendars.push({ url: href, displayname, color })
    }

    return calendars
  }

  /**
   * Add an event to a calendar via the API.
   * The API handles orphaned UIDs from soft-deleted events.
   * @param calendarUrl - The calendar URL (e.g., /remote.php/dav/calendars/user/personal/)
   * @param icsContent - The ICS content of the event
   * @returns Object with updated: true if event was updated, false if created
   */
  const addEventToCalendar = async (calendarUrl: string, icsContent: string): Promise<{ updated: boolean }> => {
    logger.debug('Adding event to calendar via API', { calendarUrl })

    const response = await axios.post(
      generateUrl('/apps/mail_roundcube/api/calendar/event'),
      {
        calendarUri: calendarUrl,
        icsContent: icsContent,
      }
    )

    if (!response.data.success) {
      throw new Error(response.data.error || 'Failed to add event')
    }

    logger.debug('Event added successfully', { updated: response.data.updated, uid: response.data.uid })
    return { updated: response.data.updated }
  }

  /**
   * Convert ArrayBuffer to base64 string.
   */
  const arrayBufferToBase64 = (buffer: ArrayBuffer): string => {
    const bytes = new Uint8Array(buffer)
    let binary = ''
    for (let i = 0; i < bytes.byteLength; i++) {
      binary += String.fromCharCode(bytes[i])
    }
    return btoa(binary)
  }

  /**
   * Convert base64 string to ArrayBuffer.
   */
  const base64ToArrayBuffer = (base64: string): ArrayBuffer => {
    const binary = atob(base64)
    const bytes = new Uint8Array(binary.length)
    for (let i = 0; i < binary.length; i++) {
      bytes[i] = binary.charCodeAt(i)
    }
    return bytes.buffer
  }

  /**
   * Send a message to the iframe.
   */
  const sendToIframe = (message: FilePickedResponse | FileSavedResponse | ShareLinkCreatedResponse | CalendarsResponse | EventAddedResponse): void => {
    const iframe = iframeRef.value
    if (!iframe?.contentWindow) {
      logger.error('Cannot send message: iframe not available')
      return
    }
    const targetOrigin = allowedOrigin || window.location.origin
    iframe.contentWindow.postMessage(message, targetOrigin)
    logger.debug('Message sent to iframe', { message, targetOrigin })
  }

  /**
   * Handle file pick request from iframe - opens the picker.
   */
  const handlePickFile = (message: PickFileMessage): void => {
    logger.info('Handling pickFile request', { message })
    isProcessing.value = true
    pendingPickRequest.value = {
      requestId: message.requestId,
      multiple: message.multiple ?? true,
      mimeTypes: message.mimeTypes,
    }
    isFilePickerOpen.value = true
  }

  /**
   * Callback when files are picked from the FilePicker component.
   */
  const onFilesPicked = async (nodes: Node[]): Promise<void> => {
    const request = pendingPickRequest.value
    if (!request) {
      logger.error('No pending pick request')
      return
    }

    // Clear pending request immediately to prevent onFilePickerClose from sending "Cancelled"
    pendingPickRequest.value = null

    try {
      const files: FilePickedResponse['files'] = []

      for (const node of nodes) {
        try {
          const path = node.path || ''
          const { content, mimeType } = await downloadFile(path)
          const name = node.basename || path.split('/').pop() || 'file'

          files.push({
            name,
            path,
            mimeType,
            size: content.byteLength,
            content: arrayBufferToBase64(content),
          })
        } catch (error) {
          logger.error('Failed to download file', { path: node.path, error })
        }
      }

      sendToIframe({
        action: 'filePicked',
        requestId: request.requestId,
        success: files.length > 0,
        files,
      })
    } catch (error) {
      logger.error('File picker error', { error })
      sendToIframe({
        action: 'filePicked',
        requestId: request.requestId,
        success: false,
        error: error instanceof Error ? error.message : 'Unknown error',
      })
    } finally {
      isProcessing.value = false
      isFilePickerOpen.value = false
    }
  }

  /**
   * Callback when the file picker is closed without selection.
   */
  const onFilePickerClose = (): void => {
    const request = pendingPickRequest.value
    // Only send "Cancelled" if there's still a pending request
    // (onFilesPicked clears it when user clicks the button)
    if (request) {
      sendToIframe({
        action: 'filePicked',
        requestId: request.requestId,
        success: false,
        error: 'Cancelled',
      })
      pendingPickRequest.value = null
    }
    isProcessing.value = false
    isFilePickerOpen.value = false
  }

  /**
   * Handle file save request from iframe - opens the folder picker.
   */
  const handleSaveFile = (message: SaveFileMessage): void => {
    logger.info('Handling saveFile request', { filename: message.filename })
    isProcessing.value = true
    pendingSaveRequest.value = {
      requestId: message.requestId,
      filename: message.filename,
      content: message.content,
      mimeType: message.mimeType,
    }
    pendingSaveFilesRequest.value = null
    isFileSaverOpen.value = true
  }

  /**
   * Handle multiple files save request from iframe - opens the folder picker once.
   */
  const handleSaveFiles = (message: SaveFilesMessage): void => {
    logger.info('Handling saveFiles request', { count: message.files.length })
    isProcessing.value = true
    pendingSaveFilesRequest.value = {
      requestId: message.requestId,
      files: message.files,
    }
    pendingSaveRequest.value = null
    isFileSaverOpen.value = true
  }

  /**
   * Callback when a folder is selected for saving.
   */
  const onFolderSelected = async (nodes: Node[]): Promise<void> => {
    const singleRequest = pendingSaveRequest.value
    const multiRequest = pendingSaveFilesRequest.value

    if (!singleRequest && !multiRequest) {
      logger.error('No pending save request')
      return
    }

    // Clear pending requests immediately to prevent onFileSaverClose from sending "Cancelled"
    pendingSaveRequest.value = null
    pendingSaveFilesRequest.value = null

    const folderPath = nodes[0]?.path || '/'

    // Handle multiple files save
    if (multiRequest) {
      const savedPaths: string[] = []
      const errors: string[] = []

      for (const file of multiRequest.files) {
        try {
          const destinationPath = await findUniqueFilename(folderPath, file.filename)
          logger.debug('Saving file to', { destinationPath })
          const content = base64ToArrayBuffer(file.content)
          await uploadFile(destinationPath, content, file.mimeType)
          savedPaths.push(destinationPath)
        } catch (error) {
          logger.error('File save error', { filename: file.filename, error })
          errors.push(file.filename)
        }
      }

      sendToIframe({
        action: 'fileSaved',
        requestId: multiRequest.requestId,
        success: savedPaths.length > 0,
        path: savedPaths.join(', '),
        error: errors.length > 0 ? `Failed: ${errors.join(', ')}` : undefined,
      })

      isProcessing.value = false
      isFileSaverOpen.value = false
      return
    }

    // Handle single file save
    if (singleRequest) {
      try {
        const destinationPath = await findUniqueFilename(folderPath, singleRequest.filename)
        logger.debug('Saving file to', { destinationPath })
        const content = base64ToArrayBuffer(singleRequest.content)
        await uploadFile(destinationPath, content, singleRequest.mimeType)

        sendToIframe({
          action: 'fileSaved',
          requestId: singleRequest.requestId,
          success: true,
          path: destinationPath,
        })
      } catch (error) {
        logger.error('File save error', { error })
        sendToIframe({
          action: 'fileSaved',
          requestId: singleRequest.requestId,
          success: false,
          error: error instanceof Error ? error.message : 'Unknown error',
        })
      } finally {
        isProcessing.value = false
        isFileSaverOpen.value = false
      }
    }
  }

  /**
   * Callback when the folder picker is closed without selection.
   */
  const onFileSaverClose = (): void => {
    const singleRequest = pendingSaveRequest.value
    const multiRequest = pendingSaveFilesRequest.value

    // Only send "Cancelled" if there's still a pending request
    // (onFolderSelected clears it when user clicks the button)
    if (singleRequest) {
      sendToIframe({
        action: 'fileSaved',
        requestId: singleRequest.requestId,
        success: false,
        error: 'Cancelled',
      })
      pendingSaveRequest.value = null
    }
    if (multiRequest) {
      sendToIframe({
        action: 'fileSaved',
        requestId: multiRequest.requestId,
        success: false,
        error: 'Cancelled',
      })
      pendingSaveFilesRequest.value = null
    }
    isProcessing.value = false
    isFileSaverOpen.value = false
  }

  /**
   * Create a public share link for a file using OCS Sharing API.
   * @param path - File path from user's root (e.g., /Documents/file.pdf)
   * @returns The share URL
   */
  const createShareLink = async (path: string): Promise<string> => {
    const url = generateOcsUrl('apps/files_sharing/api/v1/shares')

    const response = await axios.post(url, {
      shareType: 3, // SHARE_TYPE_LINK
      path,
    })

    return response.data.ocs.data.url
  }

  /**
   * Handle create share link request from iframe - opens file picker.
   */
  const handleCreateShareLink = (message: CreateShareLinkMessage): void => {
    logger.info('Handling createShareLink request')
    isProcessing.value = true
    pendingShareLinkRequest.value = {
      requestId: message.requestId,
    }
    isShareLinkPickerOpen.value = true
  }

  /**
   * Callback when a file is picked for share link creation.
   */
  const onShareLinkFilePicked = async (nodes: Node[]): Promise<void> => {
    const request = pendingShareLinkRequest.value
    if (!request) {
      logger.error('No pending share link request')
      return
    }

    // Clear pending request immediately
    pendingShareLinkRequest.value = null

    const node = nodes[0]
    if (!node?.path) {
      sendToIframe({
        action: 'shareLinkCreated',
        requestId: request.requestId,
        success: false,
        error: 'No file selected',
      })
      isProcessing.value = false
      isShareLinkPickerOpen.value = false
      return
    }

    try {
      const url = await createShareLink(node.path)
      const filename = node.basename || node.path.split('/').pop() || 'file'

      sendToIframe({
        action: 'shareLinkCreated',
        requestId: request.requestId,
        success: true,
        url,
        filename,
      })
    } catch (error: unknown) {
      logger.error('Failed to create share link', { error })
      let errorMessage = 'Failed to create share link'
      // Try to extract error message from OCS response
      const axiosError = error as { response?: { data?: { ocs?: { meta?: { message?: string } } } } }
      if (axiosError.response?.data?.ocs?.meta?.message) {
        errorMessage = axiosError.response.data.ocs.meta.message
      }
      sendToIframe({
        action: 'shareLinkCreated',
        requestId: request.requestId,
        success: false,
        error: errorMessage,
      })
    } finally {
      isProcessing.value = false
      isShareLinkPickerOpen.value = false
    }
  }

  /**
   * Callback when the share link file picker is closed without selection.
   */
  const onShareLinkPickerClose = (): void => {
    const request = pendingShareLinkRequest.value
    if (request) {
      sendToIframe({
        action: 'shareLinkCreated',
        requestId: request.requestId,
        success: false,
        error: 'Cancelled',
      })
      pendingShareLinkRequest.value = null
    }
    isProcessing.value = false
    isShareLinkPickerOpen.value = false
  }

  /**
   * Handle get calendars request from iframe.
   */
  const handleGetCalendars = async (message: GetCalendarsMessage): Promise<void> => {
    logger.info('Handling getCalendars request')

    try {
      const calendars = await fetchCalendars()
      sendToIframe({
        action: 'calendarsLoaded',
        requestId: message.requestId,
        success: true,
        calendars,
      })
    } catch (error) {
      logger.error('Failed to fetch calendars', { error })
      sendToIframe({
        action: 'calendarsLoaded',
        requestId: message.requestId,
        success: false,
        error: error instanceof Error ? error.message : 'Failed to fetch calendars',
      })
    }
  }

  /**
   * Handle add to calendar request from iframe.
   */
  const handleAddToCalendar = async (message: AddToCalendarMessage): Promise<void> => {
    logger.info('Handling addToCalendar request', { calendarUrl: message.calendarUrl })

    try {
      const result = await addEventToCalendar(message.calendarUrl, message.icsContent)
      sendToIframe({
        action: 'eventAdded',
        requestId: message.requestId,
        success: true,
        updated: result.updated,
      })
    } catch (error) {
      logger.error('Failed to add event to calendar', { error })
      sendToIframe({
        action: 'eventAdded',
        requestId: message.requestId,
        success: false,
        error: error instanceof Error ? error.message : 'Failed to add event',
      })
    }
  }

  /**
   * Handle incoming postMessage from iframe.
   */
  const handleMessage = (event: MessageEvent): void => {
    const expectedOrigin = allowedOrigin || window.location.origin
    if (event.origin !== expectedOrigin) {
      return
    }

    const message = event.data as IframeMessage
    if (!message?.action) {
      return
    }

    logger.debug('Received message from iframe', { message })

    switch (message.action) {
      case 'pickFile':
        handlePickFile(message)
        break
      case 'saveFile':
        handleSaveFile(message)
        break
      case 'saveFiles':
        handleSaveFiles(message)
        break
      case 'createShareLink':
        handleCreateShareLink(message)
        break
      case 'getCalendars':
        handleGetCalendars(message)
        break
      case 'addToCalendar':
        handleAddToCalendar(message)
        break
      default:
        logger.debug('Unknown action', { action: (message as { action: string }).action })
    }
  }

  // Setup and cleanup
  onMounted(() => {
    if (!enabled) {
      logger.debug('IframeBridge: disabled, not listening for messages')
      return
    }
    window.addEventListener('message', handleMessage)
    logger.info('IframeBridge: listening for messages')
  })

  onBeforeUnmount(() => {
    if (!enabled) {
      return
    }
    window.removeEventListener('message', handleMessage)
    logger.info('IframeBridge: stopped listening')
  })

  return {
    // State
    isProcessing,
    enabled,
    isFilePickerOpen,
    isFileSaverOpen,
    isShareLinkPickerOpen,
    pendingPickRequest,
    pendingSaveRequest,
    // Callbacks for FilePicker component
    onFilesPicked,
    onFilePickerClose,
    onFolderSelected,
    onFileSaverClose,
    // Callbacks for Share Link FilePicker
    onShareLinkFilePicked,
    onShareLinkPickerClose,
  }
}
