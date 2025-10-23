<script setup>
import { Button } from '@/Components/shadcn/ui/button'
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/Components/shadcn/ui/dialog'
import { Progress } from '@/Components/shadcn/ui/progress'
import { useOnboarding } from '@/Composables/useOnboarding'
import { ChevronLeft, ChevronRight } from 'lucide-vue-next'
import { computed } from 'vue'

const {
  showOnboarding,
  currentVideoIndex,
  videoProgress,
  onboardingVideos,
  handleVideoEnded,
  skipOnboarding,
} = useOnboarding()

// Calcular o progresso baseado no índice atual e total de vídeos
const progress = computed(() => {
  return ((currentVideoIndex.value + 1) / onboardingVideos.length) * 100
})

function goToPreviousVideo() {
  if (currentVideoIndex.value > 0) {
    currentVideoIndex.value--
  }
}

function goToNextVideo() {
  if (currentVideoIndex.value < onboardingVideos.length - 1) {
    currentVideoIndex.value++
  } else {
    handleVideoEnded()
  }
}
</script>

<template>
  <Dialog v-model:open="showOnboarding">
    <DialogContent class="sm:max-w-[800px]">
      <DialogHeader>
        <DialogTitle>{{ onboardingVideos[currentVideoIndex].title }}</DialogTitle>
      </DialogHeader>

      <div class="space-y-4">
        <p class="text-muted-foreground">
          {{ onboardingVideos[currentVideoIndex].description }}
        </p>

        <div class="aspect-video bg-black rounded-lg overflow-hidden">
          <video
            :src="onboardingVideos[currentVideoIndex].url"
            class="w-full h-full"
            controls
            autoplay
            @ended="handleVideoEnded"
          />
        </div>

        <div class="space-y-2">
          <div class="flex justify-between text-sm">
            <span>Progresso do tutorial</span>
            <span>{{ Math.round(progress) }}%</span>
          </div>
          <Progress :modelValue="progress" />
        </div>

        <div class="flex justify-between items-center">
          <div class="flex items-center gap-2">
            <Button
              variant="outline"
              size="icon"
              :disabled="currentVideoIndex === 0"
              @click="goToPreviousVideo"
            >
              <ChevronLeft class="h-4 w-4" />
            </Button>
            <Button
              variant="outline"
              size="icon"
              :disabled="currentVideoIndex === onboardingVideos.length - 1"
              @click="goToNextVideo"
            >
              <ChevronRight class="h-4 w-4" />
            </Button>
            <Button variant="outline" @click="skipOnboarding">
              Pular tutorial
            </Button>
          </div>
          <span class="text-sm text-muted-foreground">
            {{ currentVideoIndex + 1 }} de {{ onboardingVideos.length }}
          </span>
        </div>
      </div>
    </DialogContent>
  </Dialog>
</template> 