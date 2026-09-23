import { usePage } from '@inertiajs/vue3'
import { useHead, useSeoMeta } from '@unhead/vue'
import { unref } from 'vue'

function getCurrentPageUrl() {
  if (typeof window === 'undefined')
    return 'https://tanavitrine.com.br'

  return `${window.location.origin}${window.location.pathname}`
}

function getFallbackMeta(pageProps) {
  const currentPageUrl = getCurrentPageUrl()
  const siteName = pageProps?.name || 'Tá na Vitrine'
  const origin = typeof window !== 'undefined' ? window.location.origin : 'https://tanavitrine.com.br'
  const description = 'Tá na Vitrine conecta fornecedores e lojistas de moda em todo o Brasil. Encontre lojas de atacado e varejo com contato direto.'

  return {
    title: siteName,
    description,
    robots: 'noindex, nofollow',
    canonical: currentPageUrl,
    themeColor: '#0f766e',
    ogTitle: siteName,
    ogDescription: description,
    ogUrl: currentPageUrl,
    ogType: 'website',
    ogImage: `${origin}/images/og.png`,
    ogSiteName: siteName,
    ogLocale: 'pt_BR',
    twitterTitle: siteName,
    twitterDescription: description,
    twitterCard: 'summary_large_image',
    twitterImage: `${origin}/images/og.png`,
    twitterSite: '@tanavitrine',
  }
}

/**
 * Publish the final SEO payload supplied by Laravel without altering its title.
 * Missing social fields inherit from the final title, description, URL and image.
 */
export function useSeoMetaTags(seoMeta = null, options = { merge: true }) {
  const page = usePage()
  const sharedSeo = page.props.seo || {}
  const suppliedSeo = unref(seoMeta) || sharedSeo
  const fallbackMeta = getFallbackMeta(page.props)

  const mergedSeoMeta = options.merge
    ? { ...fallbackMeta, ...sharedSeo, ...suppliedSeo }
    : { ...suppliedSeo }

  if (!Object.prototype.hasOwnProperty.call(suppliedSeo, 'ogTitle'))
    mergedSeoMeta.ogTitle = mergedSeoMeta.title

  if (!Object.prototype.hasOwnProperty.call(suppliedSeo, 'ogDescription'))
    mergedSeoMeta.ogDescription = mergedSeoMeta.description

  if (!Object.prototype.hasOwnProperty.call(suppliedSeo, 'ogUrl'))
    mergedSeoMeta.ogUrl = mergedSeoMeta.canonical || getCurrentPageUrl()

  if (!Object.prototype.hasOwnProperty.call(suppliedSeo, 'twitterTitle'))
    mergedSeoMeta.twitterTitle = mergedSeoMeta.ogTitle

  if (!Object.prototype.hasOwnProperty.call(suppliedSeo, 'twitterDescription'))
    mergedSeoMeta.twitterDescription = mergedSeoMeta.ogDescription

  if (!Object.prototype.hasOwnProperty.call(suppliedSeo, 'twitterImage'))
    mergedSeoMeta.twitterImage = mergedSeoMeta.ogImage

  const canonicalUrl = mergedSeoMeta.canonical || mergedSeoMeta.ogUrl || getCurrentPageUrl()

  useHead({
    link: [{ rel: 'canonical', href: canonicalUrl }],
  })

  const { canonical, ...seoMetaWithoutCanonical } = mergedSeoMeta
  return useSeoMeta(seoMetaWithoutCanonical)
}
