<script setup>
import Badge from '@/Components/shadcn/ui/badge/Badge.vue'
import { Icon } from '@iconify/vue'
import { computed } from 'vue'
import { useHeroVideo } from '@/Composables/useHeroVideo.js'

const props = defineProps({
  settings: { type: Object, required: true },
  quickSearchSubcategories: { type: Array, default: () => [] },
  singleCard: { type: Boolean, default: false },
  underHeader: { type: Boolean, default: false },
})

const { backgroundVideo, startVideo } = useHeroVideo()

const hasMedia = computed(() =>
  (props.settings.background_type === 'image' && !!props.settings.image_url)
  || (props.settings.background_type === 'video' && !!props.settings.video_url),
)
const heroStyles = computed(() => ({
  backgroundColor: props.settings.background_color,
  '--hero-description': props.settings.description_color,
  '--hero-button': props.settings.button_color,
  '--hero-button-text': props.settings.button_text_color,
  '--hero-header-offset': props.underHeader ? '4rem' : '0px',
}))

function quickSearchHref(subcategory) {
  return `/buscar?${new URLSearchParams({ 'subcategorias[]': subcategory })}`
}

</script>

<template>
  <section
    class="relative isolate overflow-hidden bg-white pb-4 pt-6 sm:py-12 lg:pb-6 lg:pt-8"
    :class="{ 'home-hero-single-card': singleCard, 'home-hero-under-header': underHeader, 'home-hero-with-media': hasMedia }"
    :style="heroStyles"
  >
    <div v-if="hasMedia" class="pointer-events-none absolute inset-0 z-0 isolate overflow-hidden" aria-hidden="true">
      <img v-if="settings.background_type === 'image' && settings.image_url" :src="settings.image_url" alt="" fetchpriority="high" class="h-full w-full object-cover">
      <img
        v-if="settings.background_type === 'video' && settings.poster_url"
        :src="settings.poster_url"
        alt="" fetchpriority="high" loading="eager"
        class="absolute inset-0 h-full w-full object-cover"
      >
      <video
        v-if="settings.background_type === 'video' && settings.video_url"
        ref="backgroundVideo"
        :key="settings.video_url"
        :src="settings.video_url"
        :poster="settings.poster_url || undefined"
        autoplay muted loop playsinline preload="auto" tabindex="-1"
        class="absolute inset-0 h-full w-full object-cover"
        @canplay="startVideo"
      />
      <div
        v-if="settings.overlay_enabled !== false"
        class="hero-media-overlay absolute inset-0 z-10"
        :style="{ '--hero-overlay-color': settings.overlay_color, opacity: settings.overlay_opacity / 100 }"
      />
    </div>
    <div class="site-shell relative z-10 lg:max-w-[1600px]">
      <div class="mx-auto max-w-3xl">
        <div class="text-center">
          <!-- Badge -->
          <div v-if="settings.badge" class="mb-4 inline-flex justify-center sm:mb-6">
            <Badge variant="outline" class="rounded-full border border-teal-100 bg-teal-50 px-4 py-1 text-xs text-teal-800 sm:text-sm">
              <Icon icon="lucide:award" class="size-4" aria-hidden="true" /> {{ settings.badge }}
            </Badge>
          </div>

          <!-- Main Heading -->
          <div class="mx-auto max-w-3xl">
            <h1
              class="text-[clamp(1.625rem,8vw,2.125rem)] font-extrabold leading-[1.08] tracking-tight sm:text-5xl lg:text-[2.65rem] xl:text-5xl 2xl:text-[3.4rem]"
            >
              <span class="block" :style="{ color: settings.title_color }">{{ settings.title }}</span>
              <span class="mt-1 block sm:mt-2" :style="{ color: settings.highlight_color }">
                {{ settings.highlight }}
              </span>
            </h1>
          </div>

          <!-- Subtitle - Add priority hint -->
          <p
            class="mx-auto mt-4 max-w-2xl text-center text-sm font-medium leading-5 text-[color:var(--hero-description)] sm:mt-6 sm:text-lg sm:leading-normal"
          >
            {{ settings.description }}
          </p>

          <form
            action="/buscar"
            method="get"
            role="search"
            aria-label="Buscar lojas e fornecedores"
            class="mt-5 flex items-center gap-2 rounded-2xl border border-slate-200 bg-white p-1.5 shadow-[0_8px_28px_rgba(15,23,42,0.08)] sm:mt-8 sm:p-2"
          >
            <div class="relative min-w-0 flex-1">
              <label for="home-store-search" class="sr-only">Nome da loja, produto ou categoria</label>
              <Icon icon="lucide:search" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400 sm:left-4 sm:size-5" aria-hidden="true" />
              <input
                id="home-store-search"
                type="search"
                name="busca"
                maxlength="150"
                placeholder="Busque por loja, produto ou categoria (ex.: jeans)"
                class="h-11 w-full rounded-xl border-0 bg-transparent pl-9 pr-2 text-sm text-teal-950 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-teal-600/30 sm:h-12 sm:pl-12 sm:pr-4"
              >
            </div>
            <button
              type="submit"
              class="inline-flex min-h-11 shrink-0 items-center justify-center gap-2 hero-search-button max-w-[50%] rounded-xl px-3 py-2 text-sm font-bold transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-teal-700 focus-visible:ring-offset-2 sm:min-h-12 sm:gap-3 sm:px-6 sm:py-3"
            >
              <span v-if="settings.button_label === 'Buscar lojas'">Buscar<span class="hidden sm:inline"> lojas</span></span>
              <span v-else>{{ settings.button_label }}</span>
              <Icon icon="lucide:arrow-right" class="size-4" aria-hidden="true" />
            </button>
          </form>

          <nav v-if="quickSearchSubcategories.length" aria-label="Buscas rápidas por subcategoria" class="mt-3 flex max-w-full items-center gap-2 text-xs sm:mt-4 sm:justify-center">
            <span class="hero-quick-search-label mr-1 shrink-0 whitespace-nowrap text-[color:var(--hero-description)]">Buscas rápidas:</span>
            <div class="flex min-w-0 flex-1 flex-nowrap items-center gap-2 overflow-x-auto overscroll-x-contain py-1 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden sm:flex-initial sm:flex-wrap sm:justify-center sm:overflow-visible">
              <a
                v-for="subcategory in quickSearchSubcategories"
                :key="subcategory"
                :href="quickSearchHref(subcategory)"
                class="inline-flex min-h-8 shrink-0 items-center whitespace-nowrap rounded-lg bg-slate-50 px-3 py-1.5 font-medium text-teal-800 transition-colors hover:bg-teal-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-teal-700"
              >
                {{ subcategory }}
              </a>
            </div>
          </nav>

        </div>

      </div>
    </div>

  </section>

</template>

<style scoped>
.hero-media-overlay {
  background: radial-gradient(ellipse 70% 65% at 50% 42%, var(--hero-overlay-color) 0%, var(--hero-overlay-color) 24%, transparent 100%);
}

.home-hero-with-media h1 {
  text-shadow: 0 2px 5px rgb(0 0 0 / 45%);
}

.home-hero-with-media p,
.home-hero-with-media .hero-quick-search-label {
  text-shadow: 0 1px 3px rgb(0 0 0 / 65%);
}

.home-hero-under-header {
  padding-top: calc(1.5rem + var(--hero-header-offset));
}
@media (min-width: 640px) {
  .home-hero-under-header {
    padding-top: calc(3rem + var(--hero-header-offset));
  }
}
@media (min-width: 1024px) {
  .home-hero-under-header {
    padding-top: calc(2rem + var(--hero-header-offset));
  }
}

.hero-search-button {
  background-color: var(--hero-button);
  color: var(--hero-button-text);
}
.hero-search-button:hover {
  background-color: color-mix(in srgb, var(--hero-button), black 15%);
}

@media (max-width: 639px) {
  .home-hero-single-card {
    /* Reserve the header, bottom navigation, one 16:10 card and its spacing. */
    min-height: calc(100svh - 8rem - env(safe-area-inset-bottom, 0px) - (62.5vw - 1.25rem) - 1.5rem - 1px + var(--hero-header-offset, 0px));
    display: flex;
    flex-direction: column;
    justify-content: center;
  }
}
</style>
