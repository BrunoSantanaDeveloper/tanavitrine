<script setup>
import AuthenticationCardLogo from '@/Components/LogoRedirect.vue'
import { useSeoMetaTags } from '@/Composables/useSeoMetaTags'
import WebLayout from '@/Layouts/WebLayout.vue'

useSeoMetaTags({
  title: 'Política de Privacidade - TanaVitrine',
  description: 'Conheça a Política de Privacidade da TanaVitrine e como tratamos os dados dos usuários da plataforma.',
})

const currentDate = new Date().toLocaleDateString('pt-BR', {
  day: '2-digit',
  month: '2-digit',
  year: 'numeric'
})
</script>

<template>
  <WebLayout :can-login="true" :can-register="true">
    <div class="font-sans antialiased">
      <div class="py-8">
        <div class="flex min-h-screen flex-col items-center pt-6 sm:pt-0">
          <div>
            <AuthenticationCardLogo />
          </div>

          <div class="prose prose-slate dark:prose-invert mt-6 w-full max-w-4xl overflow-hidden p-8 shadow-md sm:rounded-lg bg-white">
            <h1 class="text-3xl font-bold mb-4">Política de Privacidade – TanaVitrine</h1>
            <p class="text-sm text-gray-600 mb-6">Última atualização: {{ currentDate }}</p>

            <p class="mb-4">
              A TanaVitrine se compromete com a privacidade dos dados dos seus usuários. Esta Política descreve como coletamos, usamos e protegemos as informações pessoais.
            </p>

            <h2 class="text-2xl font-semibold mt-8 mb-4">1. Coleta de Dados</h2>
            <p class="mb-4">Coletamos informações como:</p>
            <ul class="list-disc pl-6 mb-4">
              <li>Nome, e-mail, telefone (WhatsApp) e dados de pagamento.</li>
              <li>Informações sobre sua loja (nome, categoria, localização, descrição).</li>
              <li>Fotos e logotipos enviados para exibição na vitrine.</li>
              <li>Informações sobre o uso da plataforma e interações dos visitantes.</li>
            </ul>

            <h2 class="text-2xl font-semibold mt-8 mb-4">2. Uso das Informações</h2>
            <p class="mb-4">Os dados coletados são utilizados para:</p>
            <ul class="list-disc pl-6 mb-4">
              <li>Criar e gerenciar sua vitrine online.</li>
              <li>Conectar fornecedores atacadistas e lojistas varejistas.</li>
              <li>Exibir informações públicas da sua loja para potenciais clientes.</li>
              <li>Melhorar a experiência de uso da plataforma.</li>
              <li>Comunicar novidades, atualizações e ofertas.</li>
              <li>Fornecer métricas e análises sobre sua vitrine.</li>
              <li>Cumprir obrigações legais.</li>
            </ul>

            <h2 class="text-2xl font-semibold mt-8 mb-4">3. Compartilhamento de Dados</h2>
            <p class="mb-4">Suas informações de contato e descrição da loja são exibidas publicamente no marketplace. Não compartilhamos dados pessoais com terceiros, exceto:</p>
            <ul class="list-disc pl-6 mb-4">
              <li>Informações públicas da vitrine (nome da loja, categoria, localização, fotos, WhatsApp) visíveis para visitantes do site.</li>
              <li>Para fins legais, mediante ordem judicial.</li>
              <li>Com parceiros contratados (processadores de pagamento, hospedagem), exclusivamente para operação do serviço, sob cláusula de confidencialidade.</li>
            </ul>

            <h2 class="text-2xl font-semibold mt-8 mb-4">4. Segurança</h2>
            <p class="mb-4">
              Adotamos medidas de segurança para proteger os dados dos nossos clientes, incluindo criptografia SSL/TLS, armazenamento seguro de senhas (hash bcrypt), controle de acesso e backups regulares.
            </p>

            <h2 class="text-2xl font-semibold mt-8 mb-4">5. Direitos do Usuário</h2>
            <p class="mb-4">Você pode, a qualquer momento:</p>
            <ul class="list-disc pl-6 mb-4">
              <li>Acessar e editar as informações da sua vitrine através do painel de controle.</li>
              <li>Solicitar acesso completo aos seus dados.</li>
              <li>Corrigir ou atualizar informações cadastrais.</li>
              <li>Solicitar a exclusão da sua conta e dados (direito ao esquecimento).</li>
              <li>Revogar o consentimento para uso dos dados.</li>
              <li>Exportar seus dados em formato legível.</li>
            </ul>

            <h2 class="text-2xl font-semibold mt-8 mb-4">6. Cookies e Tecnologias</h2>
            <p class="mb-4">
              Utilizamos cookies e tecnologias similares para melhorar sua experiência, realizar análises de uso e personalizar conteúdo. Você pode gerenciar as preferências de cookies através das configurações do seu navegador.
            </p>

            <h2 class="text-2xl font-semibold mt-8 mb-4">7. Retenção de Dados</h2>
            <p class="mb-4">
              Mantemos seus dados pelo período necessário para fornecer os serviços contratados e cumprir obrigações legais. Após o cancelamento da conta, os dados públicos da vitrine podem ser removidos conforme solicitação.
            </p>

            <hr class="my-8" />

            <h2 class="text-2xl font-semibold mt-8 mb-4">Contato</h2>
            <p class="mb-4">
              Para questões sobre privacidade, exercício de direitos ou exclusão de dados, entre em contato através do e-mail: <strong>privacidade@tanavitrine.com.br</strong>
            </p>

            <h2 class="text-2xl font-semibold mt-8 mb-4">Aceite dos Termos</h2>
            <p class="mb-4">
              Ao criar uma vitrine ou contratar qualquer plano da TanaVitrine, você declara estar de acordo com estes <strong>Termos de Serviço</strong> e com a <strong>Política de Privacidade</strong>, ciente de seus direitos e obrigações.
            </p>
          </div>
        </div>
      </div>
    </div>
  </WebLayout>
</template>
