import assert from 'node:assert/strict'
import { afterEach, test } from 'node:test'
import { createRenderer, nextTick } from 'vue'
import { useHeroVideo } from '../../resources/js/Composables/useHeroVideo.js'

const renderer = createRenderer({
  createComment: () => ({}),
  insert() {},
  remove() {},
  parentNode: () => null,
  nextSibling: () => null,
})
const originalWindow = globalThis.window
let activeApp

afterEach(() => {
  activeApp?.unmount()
  activeApp = undefined
  if (originalWindow === undefined) delete globalThis.window
  else globalThis.window = originalWindow
})

function mountPlayer(reduceMotion = false) {
  const listeners = new Set()
  const query = {
    matches: reduceMotion,
    addEventListener: (_, callback) => listeners.add(callback),
    removeEventListener: (_, callback) => listeners.delete(callback),
  }
  globalThis.window = { matchMedia: () => query }
  let player
  activeApp = renderer.createApp({
    setup() {
      player = useHeroVideo()
      return () => null
    },
  })
  activeApp.mount({})
  const video = {
    paused: true,
    plays: 0,
    pauses: 0,
    failure: null,
    play() {
      this.plays++
      if (this.failure) return Promise.reject(this.failure)
      this.paused = false
      return Promise.resolve()
    },
    pause() {
      this.pauses++
      this.paused = true
    },
  }
  player.backgroundVideo.value = video
  return {
    player, video, listeners,
    setMotionPreference(value) {
      query.matches = value
      for (const listener of listeners) listener()
    },
  }
}

test('starts the mounted video automatically with sound disabled and inline playback enabled', async () => {
  const { video } = mountPlayer()
  await nextTick()
  assert.equal(video.plays, 1)
  assert.equal(video.paused, false)
  assert.equal(video.muted, true)
  assert.equal(video.defaultMuted, true)
  assert.equal(video.playsInline, true)
})

test('autoplays the configured background even when the system reports reduced motion', async () => {
  const { player, video } = mountPlayer(true)
  await nextTick()
  assert.equal(video.plays, 1)
  await player.startVideo()
  assert.equal(video.plays, 1)
  assert.equal(video.paused, false)
})

test('can retry autoplay when the video becomes ready after a blocked attempt', async () => {
  const { player, video } = mountPlayer()
  video.failure = { name: 'NotAllowedError' }
  await nextTick()
  assert.equal(video.paused, true)
  video.failure = null
  await player.startVideo()
  assert.equal(video.paused, false)
})

test('handles an unsupported video without an unhandled rejection', async () => {
  const { player, video } = mountPlayer()
  video.failure = { name: 'NotSupportedError' }
  await nextTick()
  await assert.doesNotReject(() => player.startVideo())
  assert.equal(video.paused, true)
})

test('does not interrupt the background video when motion preferences change', async () => {
  const { video, setMotionPreference } = mountPlayer()
  await nextTick()
  setMotionPreference(true)
  await nextTick()
  assert.equal(video.paused, false)
  setMotionPreference(false)
  await nextTick()
  assert.equal(video.plays, 1)
  assert.equal(video.paused, false)
})

test('pauses the video when leaving the page', async () => {
  const { video, listeners } = mountPlayer()
  await nextTick()
  assert.equal(listeners.size, 0)
  activeApp.unmount()
  activeApp = undefined
  assert.equal(listeners.size, 0)
  assert.equal(video.paused, true)
})
