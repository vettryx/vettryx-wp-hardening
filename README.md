# VETTRYX WP Hardening

> ⚠️ **Atenção:** Este repositório atua exclusivamente como um **Submódulo** do ecossistema principal `VETTRYX WP Core`. Ele não deve ser instalado como um plugin standalone (isolado) nos clientes.

Este submódulo implementa a estratégia de **Defesa em Profundidade (Defense in Depth)** diretamente na camada da aplicação. O objetivo é blindar o WordPress fechando portas internas e removendo assinaturas que facilitam o trabalho de bots e atacantes, reduzindo a dependência exclusiva de firewalls de rede externos (WAF/CDN).

## 🚀 Funcionalidades

* **Zero UI / Zero Bloat:** Arquitetura invisível. Sem painéis de configuração complexos ou tabelas extras no banco de dados. A segurança é aplicada *by default* (ativou o módulo, está protegido).
* **Ocultação de Identidade (Anti-Fingerprinting):** * Remoção de tags `<meta name="generator">` nativas e forçadas por construtores (ex: Elementor).
  * Limpeza de strings de versão (`?ver=X.X.X`) dos arquivos CSS e JS.
  * Bloqueio de links de descoberta (RSD, WLW e REST API) no cabeçalho.
* **Fechamento de Vetores de Ataque (API e RPC):**
  * **REST API:** Interceptação no pré-despacho (`rest_pre_dispatch`) para bloquear o endpoint `/wp/v2/users` para visitantes anônimos, retornando `401 Unauthorized` e impedindo o vazamento de slugs de administrador.
  * **XML-RPC:** Desativação completa do protocolo obsoleto, mitigando ataques de força bruta e DDoS.
* **Prevenção contra Enumeração e Invasão:**
  * Bloqueio do redirecionamento passivo via URL (`/?author=N`), forçando a renderização de uma página de Erro 404 nativa do tema para despistar scanners automatizados.
* **Hardening do Painel Administrativo:**
  * Desativação compulsória do Editor de Temas e Plugins (`DISALLOW_FILE_EDIT`) em tempo de execução, impedindo injeção de código malicioso direto pelo painel caso uma credencial seja comprometida.

## ⚙️ Arquitetura e Deploy (CI/CD)

Este repositório não gera arquivos `.zip` para instalação manual. O fluxo de deploy é 100% automatizado e integrado ao Core via OTA (Over-The-Air):

1. Qualquer push ou nova Release (Tag) na branch principal deste repositório dispara um webhook (Repository Dispatch) via GitHub Actions para o repositório principal do Core.
2. O repositório do Core escuta o evento, atualiza o ponteiro (SHA) do submódulo na pasta `/modules/hardening/` e gera um commit automático de sincronização.
3. A atualização de segurança fica imediatamente disponível para distribuição na próxima build do ecossistema.

## 📖 Como Usar

Uma vez que o **VETTRYX WP Core** esteja instalado no ambiente do cliente e o módulo Hardening ativado no painel central da agência:

1. **Ativação Silenciosa:** Não há telas para configurar. O código entra em ação injetando as restrições diretamente nos ganchos nativos do WordPress (`init`, `template_redirect`, etc.).
2. **Validação de Segurança (Pentest Básico):**
    * Para testar a REST API, acesse `/wp-json/wp/v2/users?nocache=1` em uma aba anônima (esperado: Erro 401).
    * Para testar a enumeração, acesse `/?author=1&nocache=1` em uma aba anônima (esperado: Erro 404).
    * *Nota: O parâmetro `?nocache=1` é crucial para forçar o bypass temporário de CDNs (como o QUIC.cloud) e garantir que você está testando a resposta real do servidor.*

---

**VETTRYX Tech**
*Transformando ideias em experiências digitais.*
