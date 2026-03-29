# Configuração do Deploy Automático

## Passo 1: Gerar Chave SSH

No seu computador local, execute:

```bash
ssh-keygen -t ed25519 -C "github-actions-deploy" -f ~/.ssh/github_deploy_key -N ""
```

## Passo 2: Adicionar Chave Pública na VPS

```bash
# Copiar conteúdo da chave pública
cat ~/.ssh/github_deploy_key.pub

# Conectar na VPS e adicionar
ssh root@217.196.62.124
echo "COLE_A_CHAVE_PUBLICA_AQUI" >> ~/.ssh/authorized_keys
```

## Passo 3: Configurar Secrets no GitHub

Acesse: **Settings → Secrets and variables → Actions → New repository secret**

| Secret Name | Valor |
|-------------|-------|
| `VPS_HOST` | `217.196.62.124` |
| `VPS_USER` | `root` |
| `SSH_PRIVATE_KEY` | Conteúdo de `~/.ssh/github_deploy_key` (chave PRIVADA) |
| `VITE_GA_MEASUREMENT_ID` | `G-W2BHEGSWG3` (ou o seu ID de métricas GA4) |

## Passo 4: Garantir Repositório na VPS

```bash
ssh root@217.196.62.124
cd /root
git clone https://github.com/BrunoSantanaDeveloper/tanavitrine.git
# Ou se já existe:
cd /root/tanavitrine && git pull
```

## Passo 5: Testar

Faça um push na branch `main` e acompanhe em:
https://github.com/BrunoSantanaDeveloper/tanavitrine/actions

---

## Trigger Manual

Você também pode disparar o deploy manualmente:
1. Vá em **Actions** no GitHub
2. Selecione **Deploy to Production**
3. Clique em **Run workflow**
