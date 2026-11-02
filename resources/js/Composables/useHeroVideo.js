import { onBeforeUnmount, ref, watch } from 'vue'

export function useHeroVideo() {
  const backgroundVideo = ref(null)

  async function startVideo() {
    const video = backgroundVideo.value
    if (!video || !video.paused) return
    // Set DOM properties before play(), including for browsers that block audio autoplay.
    video.muted = true
    video.defaultMuted = true
    video.playsInline = true
    try {
      await video.play()
    } catch {
      // Keep the decorative background free of player controls if playback is blocked.
    }
  }

  watch(backgroundVideo, (video) => {
    if (!video) return
    void startVideo()
  }, { flush: 'post' })

  onBeforeUnmount(() => {
    backgroundVideo.value?.pause()
  })

  return { backgroundVideo, startVideo }
}
