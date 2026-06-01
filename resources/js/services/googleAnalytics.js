const GA_MEASUREMENT_ID = (import.meta.env.VITE_GA_MEASUREMENT_ID || '').trim()
const GOOGLE_ADS_ID = (import.meta.env.VITE_GOOGLE_ADS_ID || '').trim()

let started = false
let lastTrackedPage = null
let gaConfigured = false

function isEnabled() {
  return typeof window !== 'undefined' && (GA_MEASUREMENT_ID !== '' || GOOGLE_ADS_ID !== '')
}

function ensureGtagGlobals() {
  window.dataLayer = window.dataLayer || []

  if (typeof window.gtag !== 'function') {
    // Keep the same shape used in the official GA4 snippet.
    // eslint-disable-next-line prefer-rest-params
    window.gtag = function gtag() {
      // eslint-disable-next-line prefer-rest-params
      window.dataLayer.push(arguments)
    }
  }
}

function ensureGaScriptTag() {
  const scriptId = GA_MEASUREMENT_ID || GOOGLE_ADS_ID
  if (!scriptId) {
    return null
  }

  const existingScript = document.querySelector(`script[data-gtag-id="${scriptId}"]`)
  if (existingScript) {
    return existingScript
  }

  const script = document.createElement('script')
  script.async = true
  script.src = `https://www.googletagmanager.com/gtag/js?id=${encodeURIComponent(scriptId)}`
  script.dataset.gtagId = scriptId
  document.head.appendChild(script)

  return script
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

function configureGoogleAnalytics() {
  if (gaConfigured || typeof window.gtag !== 'function') {
    return
  }

  gaConfigured = true

  window.gtag('js', new Date())

  if (GA_MEASUREMENT_ID !== '') {
    window.gtag('config', GA_MEASUREMENT_ID, {
      send_page_view: false,
      anonymize_ip: true,
    })
  }

  if (GOOGLE_ADS_ID !== '') {
    window.gtag('config', GOOGLE_ADS_ID)
  }

  trackPageView(window.location.href)
}

export function startGoogleAnalytics(router) {
  if (started || !isEnabled()) {
    return
  }

  started = true

  ensureGtagGlobals()
  const script = ensureGaScriptTag()
  configureGoogleAnalytics()

  if (script instanceof HTMLScriptElement) {
    script.addEventListener('load', () => {
      configureGoogleAnalytics()
    }, { once: true })
  }

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
