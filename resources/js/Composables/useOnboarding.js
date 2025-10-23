import { ref } from 'vue'

const showOnboarding = ref(false)
const currentVideoIndex = ref(0)
const videoProgress = ref(0)

const onboardingVideos = [
  {
    title: 'Equipamentos necessários',
    description: 'Para começar a usar o Vetfun, você precisará de alguns equipamentos. ',
    url: '/storage/onboarding/VetFun_equipamentos_necessarios.mp4',
  },
  {
    title: 'Criando sua primeira playlist',
    description: 'Aprenda a criar e organizar suas playlists de conteúdo',
    url: '/storage/onboarding/VetFun_criando_uma_playlist.mp4',
  }
]

export function useOnboarding() {


  function skipOnboarding() {
    showOnboarding.value = false
    localStorage.setItem('onboarding_completed', 'true')
    // Atualizar o estado no backend
    router.post(route('user.complete-onboarding'))
  }

  return {
    showOnboarding,
    currentVideoIndex,
    videoProgress,
    onboardingVideos,
    skipOnboarding,
  }
}
