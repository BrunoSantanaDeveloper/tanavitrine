<script setup>
import { Button } from '@/Components/shadcn/ui/button'
import { Card, CardContent } from '@/Components/shadcn/ui/card'
import { Input } from '@/Components/shadcn/ui/input'
import { useToast } from '@/Components/shadcn/ui/toast/use-toast'
import { Copy, Download } from 'lucide-vue-next'
import QRCode from 'qrcode.vue'

const props = defineProps({
  playlistId: {
    type: [String, Number],
    required: true,
  },
  playlistTitle: {
    type: String,
    required: true,
  },
  qrCodeUrl: {
    type: String,
    required: true,
  },
  displayLink: {
    type: String,
    required: true,
  },
})

const { toast } = useToast()

function copyLinkToClipboard() {
  navigator.clipboard.writeText(props.displayLink)
    .then(() => {
      toast({
        title: 'Link copiado!',
        description: 'O link de exibição foi copiado para a área de transferência.',
      })
    })
    .catch((error) => {
      console.error('Erro ao copiar link:', error)
      toast({
        title: 'Erro ao copiar link',
        description: 'Não foi possível copiar o link para a área de transferência.',
        variant: 'destructive',
      })
    })
}

function downloadQRCode() {
  const link = document.createElement('a')
  link.href = props.qrCodeUrl
  link.download = `qrcode-playlist-${props.playlistId}.png`
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)

  toast({
    title: 'Download iniciado',
    description: 'O QR Code está sendo baixado.',
  })
}
</script>

<template>
  <Card class="max-w-md mx-auto">
    <CardContent class="p-6 text-center">
      <h2 class="text-xl font-semibold mb-2">
        {{ playlistTitle }}
      </h2>
      <p class="text-gray-500 mb-6">
        Escaneie o QR Code para exibir esta playlist
      </p>

      <div class="mb-6 bg-white p-4 rounded-lg shadow-sm border">
        <QRCode
          :value="qrCodeUrl"
          :size="200"
          level="H"
          class="w-full h-auto"
        />
      </div>

      <div class="space-y-4">
        <div>
          <p class="text-sm font-medium mb-2">
            Link para exibição:
          </p>
          <div class="flex items-center">
            <Input
              :value="displayLink"
              readonly
              class="flex-1 text-sm bg-gray-50"
            />
            <Button
              class="rounded-l-none"
              aria-label="Copiar link"
              @click="copyLinkToClipboard"
            >
              <Copy class="h-4 w-4" />
            </Button>
          </div>
        </div>

        <Button
          variant="outline"
          class="w-full"
          @click="downloadQRCode"
        >
          <Download class="h-4 w-4 mr-2" />
          Baixar QR Code
        </Button>

        <p class="text-xs text-gray-500 mt-4">
          Imprima e exiba este QR Code na recepção para que os visitantes possam acessar facilmente sua playlist.
        </p>
      </div>
    </CardContent>
  </Card>
</template>
