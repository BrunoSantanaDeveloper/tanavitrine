<script setup>
import AuthenticationCardLogo from '@/Components/LogoRedirect.vue'
import { useSeoMetaTags } from '@/Composables/useSeoMetaTags'
import WebLayout from '@/Layouts/WebLayout.vue'

useSeoMetaTags({
  title: 'Termos de Serviço - TanaVitrine',
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
            <h1 class="text-3xl font-bold mb-4">Termos de Serviço – TanaVitrine</h1>
            <p class="text-sm text-gray-600 mb-6">Última atualização: {{ currentDate }}</p>

            <p class="mb-4">
              Ao criar uma vitrine ou utilizar os serviços da TanaVitrine, você concorda com os seguintes termos e condições:
            </p>

            <h2 class="text-2xl font-semibold mt-8 mb-4">1. Descrição do Serviço</h2>
            <p class="mb-4">
              A TanaVitrine é um marketplace online que conecta fornecedores atacadistas e lojistas varejistas de moda em todo o Brasil. O serviço inclui:
            </p>
            <ul class="list-disc pl-6 mb-4">
              <li>Criação e gerenciamento de vitrine online para exibição de produtos.</li>
              <li>Divulgação da sua loja para potenciais clientes (atacadistas e varejistas).</li>
              <li>Ferramentas de contato direto via WhatsApp.</li>
              <li>Métricas e análises sobre visualizações e interações.</li>
              <li>Sistema de favoritos para compradores salvarem lojas de interesse.</li>
              <li>Filtros de busca por categoria, localização e tipo de negócio.</li>
            </ul>

            <h2 class="text-2xl font-semibold mt-8 mb-4">2. Planos e Assinaturas</h2>
            <p class="mb-4">
              O acesso à plataforma TanaVitrine é feito mediante a contratação de um dos planos disponíveis. Cada plano oferece diferentes funcionalidades, limites de fotos, destaque e recursos adicionais, conforme descrito em nosso site.
            </p>
            <p class="mb-4">
              O pagamento é realizado através de processadores seguros e os valores são cobrados conforme a periodicidade escolhida (mensal ou anual).
            </p>

            <h2 class="text-2xl font-semibold mt-8 mb-4">3. Obrigações do Lojista</h2>
            <p class="mb-4">O lojista se compromete a:</p>
            <ul class="list-disc pl-6 mb-4">
              <li>Fornecer informações verdadeiras e precisas sobre sua loja e produtos.</li>
              <li>Manter os dados cadastrais e de contato atualizados.</li>
              <li>Utilizar a plataforma conforme as leis vigentes e boas práticas comerciais.</li>
              <li>Não veicular conteúdos ofensivos, discriminatórios, ilícitos ou que violem direitos de terceiros.</li>
              <li>Não publicar fotos de produtos falsificados, piratas ou de origem duvidosa.</li>
              <li>Respeitar os direitos autorais e de propriedade intelectual nas fotos e descrições publicadas.</li>
              <li>Responder prontamente às solicitações de contato de potenciais clientes.</li>
              <li>Não utilizar a plataforma para práticas fraudulentas ou enganosas.</li>
            </ul>

            <h2 class="text-2xl font-semibold mt-8 mb-4">4. Direitos da TanaVitrine</h2>
            <p class="mb-4">A TanaVitrine se reserva o direito de:</p>
            <ul class="list-disc pl-6 mb-4">
              <li>Atualizar e modificar os termos a qualquer momento, com aviso prévio de 30 dias aos usuários.</li>
              <li>Remover conteúdos que violem estes termos ou qualquer lei aplicável.</li>
              <li>Suspender ou encerrar contas em caso de descumprimento contratual, uso indevido ou atividades suspeitas.</li>
              <li>Modificar ou descontinuar funcionalidades da plataforma mediante aviso prévio.</li>
              <li>Utilizar as informações públicas das vitrines (nome, fotos, categoria) para fins de marketing e divulgação da plataforma.</li>
            </ul>

            <h2 class="text-2xl font-semibold mt-8 mb-4">5. Propriedade Intelectual</h2>
            <ul class="list-disc pl-6 mb-4">
              <li>A marca TanaVitrine, logotipos, design da plataforma e códigos-fonte são propriedade exclusiva da empresa e protegidos por direitos autorais.</li>
              <li>As fotos e descrições enviadas pelos lojistas são de sua responsabilidade e propriedade.</li>
              <li>Ao publicar conteúdo na plataforma, o lojista concede à TanaVitrine licença não exclusiva para exibir, reproduzir e distribuir esse conteúdo exclusivamente para fins de operação do marketplace.</li>
              <li>O lojista garante ter todos os direitos necessários sobre as imagens e textos publicados.</li>
            </ul>

            <h2 class="text-2xl font-semibold mt-8 mb-4">6. Responsabilidades e Limitações</h2>
            <p class="mb-4">A TanaVitrine não se responsabiliza por:</p>
            <ul class="list-disc pl-6 mb-4">
              <li>Negociações, transações e acordos comerciais realizados entre lojistas e compradores.</li>
              <li>Qualidade, autenticidade ou legalidade dos produtos anunciados.</li>
              <li>Conflitos, disputas ou inadimplências entre as partes.</li>
              <li>Interrupções temporárias causadas por manutenção programada ou problemas técnicos.</li>
              <li>Perdas financeiras decorrentes de problemas de conexão, indisponibilidade ou erros do usuário.</li>
              <li>Conteúdos publicados diretamente pelos lojistas.</li>
            </ul>
            <p class="mb-4">
              A plataforma atua apenas como intermediadora, conectando fornecedores e compradores. A TanaVitrine não participa das negociações comerciais.
            </p>

            <h2 class="text-2xl font-semibold mt-8 mb-4">7. Cancelamento e Reembolso</h2>
            <p class="mb-4">
              O lojista pode cancelar sua assinatura a qualquer momento através do painel de controle. As regras de cancelamento são:
            </p>
            <ul class="list-disc pl-6 mb-4">
              <li><strong>Planos mensais:</strong> O cancelamento entra em vigor no final do período pago. Não há reembolso proporcional.</li>
              <li><strong>Planos anuais:</strong> O cancelamento entra em vigor no final do período anual. Não há reembolso para o período restante.</li>
              <li><strong>Período de garantia:</strong> Reembolso total disponível nos primeiros 7 dias após a contratação, mediante solicitação.</li>
            </ul>
            <p class="mb-4">
              Após o cancelamento, a vitrine permanece ativa até o final do período pago e depois é desativada automaticamente.
            </p>

            <h2 class="text-2xl font-semibold mt-8 mb-4">8. Modificações no Serviço</h2>
            <p class="mb-4">
              A TanaVitrine pode, a qualquer momento, adicionar, modificar ou remover funcionalidades da plataforma. Alterações substanciais que afetem planos contratados serão comunicadas com antecedência mínima de 30 dias.
            </p>

            <h2 class="text-2xl font-semibold mt-8 mb-4">9. Lei Aplicável e Foro</h2>
            <p class="mb-4">
              Estes Termos são regidos pelas leis brasileiras. Eventuais disputas serão resolvidas no foro da comarca da sede da TanaVitrine, salvo disposição legal em contrário.
            </p>

            <hr class="my-8" />

            <h2 class="text-2xl font-semibold mt-8 mb-4">Contato</h2>
            <p class="mb-4">
              Para dúvidas, suporte ou questões relacionadas aos Termos de Serviço, entre em contato através do e-mail: <strong>suporte@tanavitrine.com.br</strong>
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
