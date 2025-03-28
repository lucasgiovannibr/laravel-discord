# Laravel Discord Bot Installer

Um pacote Laravel completo para instalação e gerenciamento de bots do Discord de forma simples e rápida.

[![Latest Version on Packagist](https://img.shields.io/packagist/v/lucasgiovanni/laravel-discord-bot-installer.svg?style=flat-square)](https://packagist.org/packages/lucasgiovanni/laravel-discord-bot-installer)
[![Total Downloads](https://img.shields.io/packagist/dt/lucasgiovanni/laravel-discord-bot-installer.svg?style=flat-square)](https://packagist.org/packages/lucasgiovanni/laravel-discord-bot-installer)

## Recursos

- 🚀 Instalação automatizada com um único comando
- 💬 Suporte completo a eventos do Discord (mensagens, novos membros, reações, interações)
- ⚙️ Sistema de configuração flexível
- 🔌 Sistema de comandos personalizáveis e middleware
- 📊 Sistema de logs para monitoramento do bot
- 🛠️ Compatível com Laravel 12+
- 🛡️ Sistema de moderação (banimentos, avisos, expulsões)
- ⏰ Sistema de lembretes e mensagens agendadas
- 📈 Sistema de níveis e experiência
- 📦 Suporte a módulos/plugins
- 🌐 Suporte a múltiplos idiomas
- 🧠 Sistema de cache para otimização
- 🔄 Integração com APIs externas
- 📝 Editor de embeds visual
- 🚥 Suporte a slash commands
- 🔁 Processamento assíncrono via filas
- 📊 Sistema de telemetria opcional
- 🎭 Sistema de reaction roles automático
- 🧩 Componentes interativos (botões, menus dropdown)
- 🎫 Sistema de tickets/suporte
- 📊 API de estatísticas para integração
- 💰 Sistema de economia virtual com loja
- 🤖 Integrações com IA para moderação e respostas
- 👋 Gerador de imagens de boas-vindas
- 🛡️ Sistema avançado de auto-moderação
- 🎁 Sistema completo de giveaways e sorteios
- 📆 Eventos temporários com RSVP
- 🎯 Configurações específicas por servidor
- 🪝 Webhooks customizáveis
- 📊 Sistema de votação avançado com gráficos

## Requisitos

- PHP 8.2 ou superior
- Laravel 12.x
- Conta no Discord e um bot criado no [Discord Developer Portal](https://discord.com/developers/applications)

## Instalação

### 1. Instale o pacote via Composer

```bash
composer require lucasgiovanni/laravel-discord-bot-installer
```

### 2. Execute o comando de instalação

```bash
php artisan bot:install
```

Este comando irá:
- Publicar os arquivos de configuração
- Configurar as variáveis de ambiente necessárias
- Criar pastas e arquivos necessários

### 3. Configure seu bot

Edite o arquivo `.env` e adicione seu token do bot Discord:

```
DISCORD_BOT_TOKEN=seu_token_aqui
```

Ou configure durante a instalação quando solicitado.

### 4. Execute as migrações (Opcional)

Para utilizar os recursos avançados como sistema de níveis, advertências e lembretes, execute:

```bash
php artisan migrate
```

## Configuração

Você pode personalizar o comportamento do bot editando o arquivo `config/discordbot.php`.

### Configurações principais:

```php
// Token do bot Discord
'token' => env('DISCORD_BOT_TOKEN', ''),

// Prefixo usado para comandos
'command_prefix' => env('DISCORD_COMMAND_PREFIX', '!'),

// Status de atividade do bot
'activity' => [
    'type' => 'playing', // playing, streaming, listening, watching, competing
    'name' => env('DISCORD_BOT_ACTIVITY', 'Laravel Discord Bot'),
],

// Mensagens personalizáveis
'messages' => [
    'welcome' => 'Bem-vindo(a) {user} ao servidor! 👋',
],
```

### Sistema de Middleware:

O pacote agora inclui um sistema de middleware para processar comandos:

```php
'middleware' => [
    'global' => [
        \LucasGiovanni\DiscordBotInstaller\Middleware\RateLimiter::class,
        \LucasGiovanni\DiscordBotInstaller\Middleware\ErrorHandler::class,
    ],
    'groups' => [
        'moderation' => [
            \LucasGiovanni\DiscordBotInstaller\Middleware\PermissionCheck::class,
        ],
    ],
],
```

### Sistema de Níveis:

Configure o sistema de níveis e experiência:

```php
'levels' => [
    'enabled' => true,
    'xp_per_message' => 10,
    'xp_cooldown' => 60, // segundos
    'announce_level_up' => true,
    'roles_rewards' => [
        5 => env('DISCORD_LEVEL5_ROLE', null),
        10 => env('DISCORD_LEVEL10_ROLE', null),
    ],
],
```

### Adicionando comandos personalizados:

```php
'commands' => [
    'meucomando' => [
        'description' => 'Descrição do meu comando',
        'handler' => \App\Discord\Commands\MeuComandoPersonalizado::class,
        'permissions' => ['SEND_MESSAGES'], // Permissões necessárias
    ],
],
```

## Uso

### Iniciando o bot

```bash
php artisan bot:run
```

Para modo debug:

```bash
php artisan bot:run --debug
```

### Comandos padrão

O bot vem com vários comandos padrão:

#### Comandos básicos:
- `!ping` - Verificar se o bot está online e mostra a latência
- `!help` - Mostra a lista de comandos disponíveis

#### Comandos de moderação:
- `!ban` - Bane um usuário do servidor
- `!kick` - Expulsa um usuário do servidor
- `!mute` - Silencia um usuário
- `!warn` - Dá uma advertência a um usuário
- `!infractions` - Mostra as infrações de um usuário

#### Comandos utilitários:
- `!remind` - Define um lembrete
- `!poll` - Cria uma enquete
- `!role` - Gerencia cargos auto-atribuíveis

### Lembretes agendados

Para criar um lembrete:

```
!remind 30m Verificar o forno
!remind 2h Reunião com a equipe
!remind amanhã 15:00 Entrega do projeto
```

### Sistema de níveis e XP

O bot agora rastreia a atividade dos usuários e concede XP automaticamente. Para ver o seu nível ou o ranking:

```
!level
!rank
```

### Slash Commands

O bot suporta slash commands. Para registrar comandos, execute:

```bash
php artisan bot:register-commands
```

### Sistema de Reaction Roles

Configure reaction roles para permitir que membros obtenham cargos ao reagir a mensagens:

```php
use LucasGiovanni\DiscordBotInstaller\Facades\DiscordBot;

// Configurar uma mensagem de reaction role
DiscordBot::createReactionRole($channelId, $messageId, [
    '👍' => 'ID_DO_CARGO_1',
    '🎮' => 'ID_DO_CARGO_2',
    '🎵' => 'ID_DO_CARGO_3',
]);
```

Você também pode usar o comando:

```
!reactionrole create #channel-name
```

### Sistema de Tickets

Para utilizar o sistema de tickets:

```
!ticket setup #canal-suporte   // Configura o canal para tickets
!ticket create                  // Cria um novo ticket
!ticket close                   // Fecha o ticket atual
```

### Sistema de Economia

O bot inclui um sistema completo de economia virtual:

```
!coins                  // Mostra seus coins
!daily                  // Resgata recompensa diária
!shop                   // Mostra a loja
!buy <item>             // Compra um item da loja
!transfer @user <valor> // Transfere coins para outro usuário
```

### Giveaways

Para criar sorteios no servidor:

```
!giveaway start 1h Um título legal // Inicia um sorteio que durará 1 hora
!giveaway end <id>                 // Finaliza um sorteio
!giveaway reroll <id>              // Sorteia um novo vencedor
```

### Eventos

Para gerenciar eventos temporários:

```
!event create "Título do Evento" "Descrição" 2023-12-31 20:00
!event list
!event info <id>
!event join <id>
!event leave <id>
```

### Componentes Interativos

O bot suporta botões, menus dropdown e outros componentes interativos da API do Discord:

```php
use LucasGiovanni\DiscordBotInstaller\Facades\DiscordBot;

// Enviar mensagem com botões
DiscordBot::sendButtons($channelId, 'Escolha uma opção:', [
    ['label' => 'Opção 1', 'style' => 'primary', 'custom_id' => 'option_1'],
    ['label' => 'Opção 2', 'style' => 'secondary', 'custom_id' => 'option_2'],
]);

// Enviar menu dropdown
DiscordBot::sendSelectMenu($channelId, 'Selecione seu cargo:', [
    ['label' => 'Programador', 'value' => 'programmer', 'description' => 'Para desenvolvedores'],
    ['label' => 'Designer', 'value' => 'designer', 'description' => 'Para designers'],
]);
```

### Auto-Moderação

Configure regras de auto-moderação para seu servidor:

```
!automod setup
!automod word add <palavra>    // Adiciona uma palavra à lista de palavras proibidas
!automod word remove <palavra> // Remove uma palavra da lista
!automod links <on/off>        // Ativa/desativa bloqueio de links
!automod spam <on/off>         // Ativa/desativa proteção contra spam
!automod punish <warn/kick/ban> // Define a punição para violações
```

## Middleware

Use o sistema de middleware para filtrar comandos:

```php
namespace App\Discord\Middleware;

use LucasGiovanni\DiscordBotInstaller\Middleware\Middleware;
use Discord\Parts\Channel\Message;

class MeuMiddleware implements Middleware
{
    public function handle(Message $message, callable $next)
    {
        // Sua lógica aqui
        
        // Continue para o próximo middleware
        return $next($message);
    }
}
```

## Módulos e plugins

Você pode estender o bot com módulos:

1. Crie um módulo em `app/DiscordModules/MeuModulo`
2. Crie um service provider para o módulo
3. Registre-o em `config/discordbot.php`

```php
'modules' => [
    'enabled' => true,
    'active' => [
        'meumodulo' => true,
    ],
],
```

## Traduções e suporte multi-idioma

O bot suporta múltiplos idiomas:

```php
'localization' => [
    'default' => 'pt_BR',
    'fallback' => 'en',
    'server_specific' => true,
],
```

## Comandos Artisan

- `php artisan bot:install` - Instala e configura o bot
- `php artisan bot:run` - Inicia o bot
- `php artisan bot:create-command {nome}` - Cria um novo comando para o bot
- `php artisan bot:create-slash-command {nome}` - Cria um novo slash command
- `php artisan bot:process-reminders` - Processa lembretes pendentes
- `php artisan bot:cleanup-warnings` - Limpa advertências expiradas
- `php artisan bot:publish-module {nome}` - Publica um módulo
- `php artisan bot:generate-welcome-image` - Gera template de imagem de boas-vindas
- `php artisan bot:register-reaction-roles` - Sincroniza reaction roles
- `php artisan bot:process-giveaways` - Processa sorteios ativos

## API de Estatísticas

O pacote fornece uma API de estatísticas que pode ser consumida pelo seu aplicativo Laravel:

```php
use LucasGiovanni\DiscordBotInstaller\Facades\DiscordStats;

// Obter estatísticas gerais
$stats = DiscordStats::getGeneralStats();

// Obter estatísticas de comandos
$commandStats = DiscordStats::getCommandStats();

// Obter estatísticas de usuários
$userStats = DiscordStats::getUserStats($serverId);
```

## Webhooks Customizáveis

Crie webhooks para integrar o Discord com outros sistemas:

```php
use LucasGiovanni\DiscordBotInstaller\Facades\DiscordBot;

// Criar um webhook
$webhook = DiscordBot::createWebhook('meu-webhook', $channelId);

// Para usar o webhook
$webhookUrl = route('discord.webhook', ['id' => $webhook->id]);

// Enviar dados para o webhook
DiscordBot::executeWebhook($webhook->id, [
    'content' => 'Mensagem do sistema externo',
    'embeds' => [
        [
            'title' => 'Título do embed',
            'description' => 'Descrição do embed',
            'color' => 0x00FF00,
        ]
    ]
]);
```

## Criando comandos personalizados

1. Crie uma classe para seu comando:

```php
<?php

namespace App\Discord\Commands;

use Discord\Discord;
use Discord\Parts\Channel\Message;
use LucasGiovanni\DiscordBotInstaller\Services\DiscordLogger;

class MeuComandoPersonalizado
{
    protected $discord;
    protected $logger;
    
    public function __construct(Discord $discord, DiscordLogger $logger)
    {
        $this->discord = $discord;
        $this->logger = $logger;
    }
    
    public function handle(Message $message, array $args = []): void
    {
        $message->channel->sendMessage('Este é meu comando personalizado!');
    }
    
    // Opcional: informações de ajuda para o comando !help
    public function getHelp(): array
    {
        return [
            'usage' => '[argumento]',
            'examples' => ['exemplo1', 'exemplo2'],
            'notes' => 'Observações adicionais sobre o comando.'
        ];
    }
}
```

2. Registre o comando no arquivo `config/discordbot.php`:

```php
'commands' => [
    'meucomando' => [
        'description' => 'Meu comando personalizado',
        'handler' => \App\Discord\Commands\MeuComandoPersonalizado::class,
    ],
],
```

## Eventos do Discord

O pacote suporta os seguintes eventos do Discord:

- `MESSAGE_CREATE` - Quando uma mensagem é enviada
- `GUILD_MEMBER_ADD` - Quando um novo membro entra no servidor
- `REACTION_ADD` - Quando uma reação é adicionada a uma mensagem
- `INTERACTION_CREATE` - Quando uma interação (slash command, botão) ocorre
- `GUILD_MEMBER_REMOVE` - Quando um membro sai do servidor
- `VOICE_STATE_UPDATE` - Quando alguém entra/sai de um canal de voz
- `PRESENCE_UPDATE` - Quando o status de um usuário muda

Você pode ativar/desativar eventos específicos no arquivo de configuração.

## Usando a Facade

Para acessar as funcionalidades do bot em qualquer lugar do seu aplicativo:

```php
use LucasGiovanni\DiscordBotInstaller\Facades\DiscordBot;

// Enviar uma mensagem
DiscordBot::sendMessage($channelId, 'Olá do Laravel!');

// Adicionar XP a um usuário
DiscordBot::addUserExperience($userId, $guildId, 50);

// Criar um lembrete
DiscordBot::createReminder($userId, $channelId, 'Fazer algo importante', now()->addHour());

// Gerar imagem de boas-vindas
DiscordBot::generateWelcomeImage($userId, $guildId, 'Bem-vindo ao servidor!');

// Adicionar coins a um usuário
DiscordBot::addCoins($userId, $guildId, 500);

// Criar um sorteio
DiscordBot::createGiveaway($channelId, 'Prêmio legal', Carbon::now()->addDay(), 1);
```

## Integrações com APIs externas

O pacote suporta integrações com:

- GitHub (webhooks e notificações)
- YouTube (notificações de novos vídeos)
- Spotify (status e informações)
- Twitch (notificações ao vivo)
- OpenAI (respostas inteligentes e moderação de conteúdo)
- Tenor/Giphy (GIFs)
- IMDB (informações de filmes e séries)
- Steam (informações de jogos e perfis)

Configure na seção `integrations` do arquivo de configuração.

## Logs e Monitoramento

O bot registra informações no arquivo `storage/logs/discordbot.log`. Você pode configurar o nível de log no `.env`:

```
DISCORD_LOG_LEVEL=info  # debug, info, warning, error
```

Agora também é possível enviar logs para um canal do Discord ou armazenar no banco de dados:

```php
'logging' => [
    'enabled' => true,
    'file' => storage_path('logs/discordbot.log'),
    'level' => env('DISCORD_LOG_LEVEL', 'info'),
    'discord_channel' => env('DISCORD_LOG_CHANNEL', false),
    'database' => env('DISCORD_LOG_TO_DATABASE', false),
],
```

## Processamento assíncrono

Operações pesadas são executadas em segundo plano usando filas:

```php
'queue' => [
    'enabled' => true,
    'connection' => env('DISCORD_QUEUE_CONNECTION', 'redis'),
    'heavy_commands' => [
        'ban', 'kick', 'poll', 'remind'
    ],
],
```

## Sharding para bots grandes

Para bots em muitos servidores, ative o sharding:

```php
'sharding' => [
    'enabled' => true,
    'total_shards' => 'auto', // 'auto' ou número
],
```

## Contribuindo

Contribuições são bem-vindas! Por favor, sinta-se à vontade para submeter um Pull Request.

## Licença

Este pacote é open-source e está disponível sob a [licença MIT](LICENSE.md).

## Créditos

- [Lucas Giovanni](https://github.com/lucasgiovanni)
- [Todos os Contribuidores](../../contributors)

Este pacote utiliza a biblioteca [Discord-PHP](https://github.com/discord-php/DiscordPHP) para interagir com a API do Discord. 