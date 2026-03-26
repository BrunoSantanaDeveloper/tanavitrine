import { useHead, useSeoMeta } from '@unhead/vue'

const fallbackSiteUrl = 'https://tanavitrine.com.br'

function getSiteUrl() {
  if (typeof window !== 'undefined' && window.location?.origin)
    return window.location.origin

  return fallbackSiteUrl
}

function toAbsoluteUrl(path) {
  if (!path)
    return `${getSiteUrl()}/images/og.png`

  if (path.startsWith('http://') || path.startsWith('https://'))
    return path

  const baseUrl = getSiteUrl()
  const normalizedPath = path.startsWith('/') ? path : `/${path}`
  return `${baseUrl}${normalizedPath}`
}

function getDefaultSeoMeta() {
  const siteUrl = getSiteUrl()
  const defaultOgImage = toAbsoluteUrl('/images/og.png')

  return {
    title: 'Home',
    titleTemplate: '%s | Tá na Vitrine',
    description: 'Tá na Vitrine conecta fornecedores e lojistas de moda em todo o Brasil. Também conhecida como Tanavitrine, reúne lojas de atacado e varejo com contato direto.',
    keywords: 'tá na vitrine, tanavitrine, tana vitrine, ta na vitrine, atacado de moda, varejo de moda, fornecedores de moda, catálogo de lojas, marketplace de moda, lojas atacadistas, lojas varejistas',
    robots: 'index, follow',
    themeColor: '#0f766e',

    // Open Graph
    ogTitle: '%s | Tá na Vitrine',
    ogDescription: 'Descubra fornecedores e lojas de moda em um só lugar. Tá na Vitrine (Tanavitrine) conecta atacado e varejo com contato direto.',
    ogUrl: siteUrl,
    ogType: 'website',
    ogImage: defaultOgImage,
    ogSiteName: 'Tá na Vitrine',
    ogLocale: 'pt_BR',

    // Twitter
    twitterTitle: '%s | Tá na Vitrine',
    twitterDescription: 'Conecte-se com lojas e fornecedores de moda no maior catálogo de atacado e varejo do Brasil. Tá na Vitrine (Tanavitrine).',
    twitterCard: 'summary_large_image',
    twitterImage: defaultOgImage,
    twitterSite: '@tanavitrine',
  }
}

/**
 * Composable for managing SEO meta tags
 * @param {object|null} seoMeta - Custom SEO meta tags to apply
 * @param {object} options - Configuration options
 * @param {boolean} options.merge - When true, merges custom meta tags with defaults.
 *                                 When false, only uses custom meta tags.
 *                                 Useful for pages that need completely custom SEO
 *                                 without inheriting defaults.
 * @returns {void}
 */
export function useSeoMetaTags(seoMeta, options = { merge: true }) {
  const defaultSeoMeta = getDefaultSeoMeta()
  const currentPageUrl = typeof window !== 'undefined' ? window.location.href : defaultSeoMeta.ogUrl

  if (!seoMeta) {
    useHead({
      link: [{ rel: 'canonical', href: currentPageUrl }],
    })
    return useSeoMeta({ ...defaultSeoMeta, ogUrl: currentPageUrl })
  }

  const mergedSeoMeta = options.merge
    ? { ...defaultSeoMeta, ...seoMeta }
    : seoMeta

  if (options.merge) {
    if (!('ogUrl' in seoMeta))
      mergedSeoMeta.ogUrl = currentPageUrl

    if (!('ogImage' in seoMeta))
      mergedSeoMeta.ogImage = defaultSeoMeta.ogImage

    if (!('twitterImage' in seoMeta))
      mergedSeoMeta.twitterImage = mergedSeoMeta.ogImage
  }

  const canonicalUrl = mergedSeoMeta.canonical || mergedSeoMeta.ogUrl || currentPageUrl
  useHead({
    link: [{ rel: 'canonical', href: canonicalUrl }],
  })

  const { canonical, ...seoMetaWithoutCanonical } = mergedSeoMeta
  return useSeoMeta(seoMetaWithoutCanonical)
}
