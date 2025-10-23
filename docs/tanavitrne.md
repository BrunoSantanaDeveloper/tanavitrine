# 🛍️ TanaVitrine — Especificação de Funcionalidades  
**Domínio:** [https://tanavitrine.com.br](https://tanavitrine.com.br)  
**Referência visual:** [https://chaozao.com.br](https://chaozao.com.br)

---

## 📘 Conceito Geral e Estrutura do Projeto

A **TanaVitrine** é uma plataforma de **anúncios de lojas de moda** que conecta **fornecedores (atacadistas e varejistas)** a **compradores (lojistas e consumidores)**.  
Cada loja possui **um único anúncio (vitrine)**, que contém suas informações, fotos e formas de contato.

A plataforma **não processa pagamentos**.  
Seu foco é **gerar visibilidade e leads qualificados**, conectando compradores e vendedores através dos canais que cada loja disponibilizar, como:
- **WhatsApp (botão principal de contato)**  
- **Redes sociais (Instagram, Facebook, TikTok)**  
- **Site ou e-commerce próprio**  
- **E-mail comercial**

A navegação e estrutura da plataforma seguem a referência visual do portal **Chãozão**, adaptada para o segmento de **moda, acessórios e comércio em geral**, com foco em **vendas de atacado e varejo**.

---

## 🧾 Tela 01 — Página Inicial (Home)

### 🎯 Objetivo
Apresentar a plataforma e oferecer uma busca centralizada por vitrines de lojas, segmentadas por tipo de venda e localização.

---

### 🧩 Estrutura Visual
Inspirada no modelo da página inicial do **Chãozão**, com ajustes visuais e funcionais para o segmento de moda:

#### 🔹 API / Dados superiores
- Retirar integração padrão da API de dados superior (Dados Agricolas).

---

#### 🔹 Logo
- Substituir a **logo do Chãozão** pela **logo da TanaVitrine**.  
- Aplicar proporção e espaçamento semelhantes ao modelo original.

---

#### 🔹 Paginação / Menu Superior
**Alterações sugeridas:**
- **Home** → Manter  
- **Comprar Imóvel** → Alterar para **Vitrine Atacado**  
- **Arrendar Imóvel** → Alterar para **Vitrine Varejo**  
- **Quem Somos** → Manter  
- **Planos** → Manter  
- **Entrar** → Manter  
- **Anunciar** → Manter (botão principal em destaque)

---

#### 🔹 Banner Principal
- **Função:** Destacar o propósito da plataforma e facilitar a busca.  
- **Exemplos de chamadas:**
  - “O maior catálogo de lojas de moda atacadista do Brasil.”  
  - “Conecte-se com as melhores lojas do Brasil!”  
  - “Aqui você compra direto com o lojista.”
- **Alterações visuais:**
  - Substituir imagem atual (paisagem rural) por uma com **fundo urbano ou de moda**, representando:
    - Vitrine de roupas  
    - Showroom de atacado  
    - Araras com cabides  
    - Caixas, feiras ou espaços comerciais  
  - Evitar imagens posadas com modelos; priorizar cenas comerciais reais.

---

#### 🔹 Formulário de Busca Flutuante
- **Função:** Permitir ao usuário filtrar rapidamente vitrines por tipo de venda e loja.  
- **Alterações:**  
  - Dividir busca entre **Atacado** e **Varejo** (duas abas, como no Chãozão).  

##### 🏷️ Aba Atacado
**Filtros:**
| Campo | Substituição / Alteração | Opções |
|--------|--------------------------|---------|
| **Tipos de Propriedade** | Alterar para **Categorias** | Roupas / Calçados / Acessórios |
| **Aptidões** | Alterar para **Tipo de Loja** | Física / Virtual |
| **Localização** | Manter | Cidade / Estado |
| **Área** | Alterar para **Gênero** | Masculino / Feminino / Unissex |
| **Valor** | **Remover filtro** | — |

##### 🛍️ Aba Varejo
*(mesma estrutura visual, podendo compartilhar os mesmos filtros)*

---

#### 🔹 Seções inferiores

##### **Anúncios de Topo (Cabeceiras)**
- **Alterar nome para:** **As Vitrines**  
- **Descrição:** Exibir vitrines em destaque (planos premium).  
- Layout: carrossel horizontal ou grid 3x2.

##### **Anúncios Recentes (Mais Recentes)**
- **Manter** a funcionalidade existente do Chãozão.  
- Adaptar para listar vitrines mais novas cadastradas na plataforma.

---

### 💡 Considerações Técnicas
- A home deve carregar vitrines dinâmicas por API.  
- O formulário deve redirecionar para `/vitrine-atacado` ou `/vitrine-varejo` conforme a aba selecionada.  
- Layout responsivo, priorizando versão mobile.  
- Componentes reutilizáveis para banners e cards.
            
                
                
                
  