# Funcionalidades Adicionadas ao Laravel Discord Bot

Este documento lista todas as novas funcionalidades que foram adicionadas ao pacote Laravel Discord Bot, incluindo detalhes sobre cada sistema e como configurá-lo.

## Sistemas Principais

### 🎭 Sistema de Reaction Roles

Um sistema completo para permitir que usuários obtenham cargos automaticamente através de reações em mensagens específicas.

**Recursos:**
- Diferentes tipos de reaction roles (padrão, alternável, temporário, com requisitos)
- Grupos de reaction roles com configurações compartilhadas
- Suporte a roles exclusivas (o usuário só pode ter um cargo do grupo)
- Requisitos de nível mínimo e cargos necessários
- Cargos temporários com expiração automática
- Temas de emojis pré-configurados
- Menus de seleção via reação

### 🎫 Sistema de Tickets/Suporte

Sistema completo de tickets para atendimento, suporte e denúncias.

**Recursos:**
- Diferentes tipos de tickets (suporte, denúncia, sugestão)
- Formulários personalizados por tipo de ticket
- Níveis de prioridade configuráveis
- Sistema de atribuição de tickets para equipe
- Geração de transcrições ao fechar tickets
- Auto-fechamento de tickets inativos
- Sistema de avaliação de atendimento
- Respostas pré-definidas para agilizar atendimento
- Notas privadas para equipe
- Configurações de privacidade e retenção de dados

### 📊 API de Estatísticas

Interface para acesso a estatísticas e métricas do servidor.

**Recursos:**
- Métricas de atividade (mensagens, comandos, usuários)
- Métricas de crescimento (entradas, saídas, retenção)
- Métricas de engajamento (horas ativas, canais mais usados)
- Estatísticas de canais de voz
- Visualizações com gráficos customizáveis
- Dashboards embeds atualizados automaticamente
- Leaderboards por diferentes métricas
- Insights e tendências automáticas
- Exportação em diversos formatos

### 💰 Sistema de Economia Virtual

Sistema de economia virtual completo com moedas, itens e loja.

**Recursos:**
- Sistema de inventário de itens
- Categorias de itens (cargos, consumíveis, colecionáveis)
- Sistema de comércio entre usuários
- Raridades de itens com multiplicadores
- Loja rotativa com itens limitados
- Recompensas por tempo em canais de voz
- Sistema de streak diário com bônus
- Lootboxes e prêmios aleatórios

### 🧩 Componentes Interativos

Sistema avançado de componentes interativos para Discord.

**Recursos:**
- Botões customizáveis com diferentes estilos
- Templates pré-definidos (confirmação, paginação, etc)
- Sistema de formulários completo
- Comandos de contexto para usuários e mensagens
- Modais interativos customizáveis
- Sistema avançado de embeds com templates

### 🤖 Integrações com IA

Integrações com OpenAI e outros serviços de IA.

**Recursos:**
- Chat com contexto persistente
- Moderação automática de conteúdo
- Geração de imagens via comando
- Análise de sentimento do servidor
- Tradução automática de mensagens
- Resumo de conversas longas
- Comandos personalizados baseados em prompts

### 👋 Gerador de Imagens de Boas-vindas

Sistema para gerar imagens personalizadas de boas-vindas.

**Recursos:**
- Templates múltiplos pré-configurados
- Customização de posição de avatar e texto
- Efeitos visuais (bordas, blur, sombras)
- Mensagens personalizadas para marcos específicos
- Mensagem privada customizável
- Atribuição automática de cargos

### 🛡️ Sistema de Auto-moderação Avançado

Ferramentas de moderação automática para manter o servidor seguro.

**Recursos:**
- Detecção de raid e proteção automática
- Detecção de texto repetido
- Limite de menções em massa
- Escaneamento de imagens com detecção NSFW
- Sistema anti-phishing com atualizações automáticas
- Filtro de conteúdo inteligente com IA
- Sistema de verificação customizável
- Controle de convites de outros servidores
- Sistema de escalação progressiva de punições

### 🎁 Sistema de Sorteios e Rifas

Sistema completo para organizar sorteios e rifas no servidor.

**Recursos:**
- Configuração flexível de duração
- Múltiplos vencedores
- Requisitos para participação
- Reroll automático de vencedores inativos
- Histórico de sorteios passados
- Anúncios automáticos de vencedores

### 📆 Sistema de Eventos Temporários com RSVP

Sistema para organizar e gerenciar eventos no servidor.

**Recursos:**
- Diferentes tipos de eventos configuráveis
- Calendário de eventos por comando
- Sistema de RSVP (confirmação, talvez, recusa)
- Limite de participantes com lista de espera
- Lembretes automáticos para eventos
- Eventos recorrentes (diários, semanais, etc)
- Integração com canais de voz temporários
- Aprovação de eventos por moderadores

### 🎯 Configurações Específicas por Servidor

Permite customizar configurações para diferentes servidores.

**Recursos:**
- Configurações independentes por servidor
- Perfis de configuração reutilizáveis
- Sistema de permissões granular por servidor
- Backup e restauração de configurações
- Importação/exportação entre servidores

### 🪝 Webhooks Customizáveis

Sistema de webhooks para integração com sistemas externos.

**Recursos:**
- Endpoints personalizáveis
- Verificação de assinatura HMAC
- Controle granular de permissões
- Templates pré-definidos para diferentes usos
- Sistema de rotação de API keys
- Whitelist de IPs para maior segurança
- Histórico e logs de requisições

### 📊 Sistema de Votação Avançado

Sistema de votação e enquetes com funcionalidades avançadas.

**Recursos:**
- Múltiplos tipos de votação (simples, múltipla escolha)
- Votação com pesos por cargo
- Votação por preferência (ranked choice)
- Votação por reação ou botões
- Templates pré-configurados
- Visualização de resultados em tempo real
- Restrições de votação por cargo ou idade de conta
- Notificações de início, lembretes e resultados

### 📈 Sistema de Gráficos

Sistema para geração e visualização de dados em forma de gráficos.

**Recursos:**
- Múltiplos tipos de gráficos (barras, linha, pizza)
- Esquemas de cores customizáveis
- Gráficos temáticos para diferentes métricas
- Geração automática periódica
- Interação com filtros e timeframes
- Exportação em diferentes formatos

## Integrações Externas Adicionadas

- **Google Calendar**: Sincronização de eventos
- **Jira**: Integração com sistema de tickets e projetos
- **OpenWeather**: Informações de clima por comando
- **Github**: Notificações de commits e pull requests
- **YouTube**: Notificações de novos vídeos
- **Twitch**: Alertas quando streamers ficam online
- **Spotify**: Integração com sistema de música
- **IMDB**: Pesquisa de filmes e séries
- **Steam**: Informações sobre jogos e perfis

## Como Configurar

Cada funcionalidade pode ser ativada/desativada e configurada no arquivo `config/discordbot.php`. A maioria das configurações também pode ser definida via variáveis de ambiente no arquivo `.env`.

Para mais detalhes sobre cada sistema, consulte a documentação específica na seção correspondente do README principal.

## Migrações de Banco de Dados

As novas funcionalidades requerem tabelas adicionais no banco de dados. Todas as migrações serão executadas automaticamente ao usar o comando:

```bash
php artisan discord:install
```

Ou individualmente:

```bash
php artisan migrate --path=/database/migrations/discord
``` 