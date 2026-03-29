const GA_MEASUREMENT_ID = (import.meta.env.VITE_GA_MEASUREMENT_ID || '').trim()

let started = false
let lastTrackedPage = null

function isEnabled() {
  return typeof window !== 'undefined' && GA_MEASUREMENT_ID !== ''
}

function ensureGtagGlobals() {
  window.dataLayer = window.dataLayer || []

  if (typeof window.gtag !== 'function') {
    window.gtag = function gtag(...args) {
      window.dataLayer.push(args)
    }
  }
}

function ensureGaScriptTag() {
  const existingScript = document.querySelector(`script[data-ga4-id="${GA_MEASUREMENT_ID}"]`)
  if (existingScript) {
    return
  }

  const script = document.createElement('script')
  script.async = true
  script.src = `https://www.googletagmanager.com/gtag/js?id=${encodeURIComponent(GA_MEASUREMENT_ID)}`
  script.dataset.ga4Id = GA_MEASUREMENT_ID
  document.head.appendChild(script)
}

function normalizePageFromUrl(url) {
  try {
    const parsed = new URL(String(url || ''), window.location.origin)
    return `${parsed.pathname}${parsed.search}`
  }
  catch {
    return `${window.location.pathname}${window.location.search}`
  }
}

function resolveLocationFromUrl(url) {
  try {
    return new URL(String(url || ''), window.location.origin).toString()
  }
  catch {
    return window.location.href
  }
}

function trackPageView(url) {
  if (typeof window.gtag !== 'function') {
    return
  }

  const pagePath = normalizePageFromUrl(url)
  if (pagePath === lastTrackedPage) {
    return
  }

  lastTrackedPage = pagePath

  window.gtag('event', 'page_view', {
    page_title: document.title,
    page_location: resolveLocationFromUrl(url),
    page_path: pagePath,
  })
}

export function startGoogleAnalytics(router) {
  if (started || !isEnabled()) {
    return
  }

  started = true

  ensureGtagGlobals()
  ensureGaScriptTag()

  window.gtag('js', new Date())

  window.gtag('config', GA_MEASUREMENT_ID, {
    send_page_view: false,
    anonymize_ip: true,
  })

  trackPageView(window.location.href)

  router.on('navigate', (event) => {
    const nextUrl = event?.detail?.page?.url || window.location.href
    trackPageView(nextUrl)
  })
}

export function trackGoogleAnalyticsEvent(eventName, params = {}) {
  if (!isEnabled() || typeof window.gtag !== 'function') {
    return
  }

  if (!eventName) {
    return
  }

  window.gtag('event', eventName, params)
}
