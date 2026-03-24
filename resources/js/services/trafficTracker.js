import axios from 'axios'

const VISITOR_STORAGE_KEY = 'tnv_visitor_id'
const HEARTBEAT_INTERVAL_MS = 30_000
const TRACK_ENDPOINT = '/track/traffic'

let started = false
let heartbeatTimer = null
let currentPath = '/'
let visitorId = ''

function generateVisitorId() {
  if (typeof crypto !== 'undefined' && typeof crypto.randomUUID === 'function') {
    return crypto.randomUUID()
  }

  return `v_${Date.now()}_${Math.random().toString(36).slice(2, 12)}`
}

function loadVisitorId() {
  try {
    const existing = window.localStorage.getItem(VISITOR_STORAGE_KEY)
    if (existing && existing.length >= 16) {
      return existing
    }

    const next = generateVisitorId()
    window.localStorage.setItem(VISITOR_STORAGE_KEY, next)
    return next
  } catch {
    return generateVisitorId()
  }
}

function normalizePath(pathname) {
  const raw = String(pathname || '/').trim()
  if (!raw) return '/'

  const withSlash = raw.startsWith('/') ? raw : `/${raw}`
  const withoutHash = withSlash.split('#')[0]
  const withoutQuery = withoutHash.split('?')[0]

  return withoutQuery || '/'
}

function isTrackablePath(path) {
  if (path === '/') return true
  if (['/atacado', '/varejo', '/prices', '/about'].includes(path)) return true
  return path.startsWith('/loja/')
}

function resolvePageType(path) {
  if (path === '/') return 'home'
  if (path === '/atacado') return 'atacado'
  if (path === '/varejo') return 'varejo'
  if (path === '/prices') return 'prices'
  if (path === '/about') return 'about'
  if (path.startsWith('/loja/')) return 'store'
  return 'other'
}

function resolveTeamSlug(path) {
  if (!path.startsWith('/loja/')) return null

  const slug = path.replace('/loja/', '').split('/')[0]
  return slug ? decodeURIComponent(slug) : null
}

function stopHeartbeat() {
  if (heartbeatTimer !== null) {
    clearInterval(heartbeatTimer)
    heartbeatTimer = null
  }
}

function sendEvent(eventType) {
  if (!visitorId || !isTrackablePath(currentPath)) return

  axios.post(TRACK_ENDPOINT, {
    visitor_id: visitorId,
    event_type: eventType,
    path: currentPath,
    page_type: resolvePageType(currentPath),
    team_slug: resolveTeamSlug(currentPath),
  }).catch(() => {
    // Tracking must never block navigation or user actions.
  })
}

function startHeartbeat() {
  stopHeartbeat()

  if (!isTrackablePath(currentPath)) return

  heartbeatTimer = window.setInterval(() => {
    if (document.hidden) return
    sendEvent('heartbeat')
  }, HEARTBEAT_INTERVAL_MS)
}

function pathFromInertiaUrl(url) {
  try {
    const normalized = new URL(url, window.location.origin)
    return normalizePath(normalized.pathname)
  } catch {
    return normalizePath(window.location.pathname)
  }
}

function handlePathChange(path, firePageView = false) {
  currentPath = normalizePath(path)

  if (!isTrackablePath(currentPath)) {
    stopHeartbeat()
    return
  }

  if (firePageView) {
    sendEvent('page_view')
  }

  startHeartbeat()
}

export function startTrafficTracker(router) {
  if (started || typeof window === 'undefined') return
  started = true

  visitorId = loadVisitorId()
  handlePathChange(window.location.pathname, true)

  router.on('navigate', (event) => {
    const nextPath = pathFromInertiaUrl(event?.detail?.page?.url || window.location.pathname)

    if (nextPath === currentPath) return
    handlePathChange(nextPath, true)
  })

  document.addEventListener('visibilitychange', () => {
    if (document.hidden) {
      stopHeartbeat()
      return
    }

    if (isTrackablePath(currentPath)) {
      sendEvent('heartbeat')
      startHeartbeat()
    }
  })

  window.addEventListener('beforeunload', () => {
    stopHeartbeat()
  })
}
