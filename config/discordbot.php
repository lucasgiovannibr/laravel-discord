<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Configurações do Discord Bot
    |--------------------------------------------------------------------------
    |
    | Configurações base do bot do Discord
    |
    */

    'token' => env('DISCORD_BOT_TOKEN', ''),

    // Prefixo usado para comandos de texto
    'command_prefix' => env('DISCORD_COMMAND_PREFIX', '!'),

    // Status de atividade do bot
    'activity' => [
        'type' => env('DISCORD_ACTIVITY_TYPE', 'playing'), // playing, streaming, listening, watching, competing
        'name' => env('DISCORD_ACTIVITY_NAME', 'Laravel Discord Bot'),
        'url' => env('DISCORD_ACTIVITY_URL', null), // Usado apenas para streaming
    ],

    // ID do servidor principal para o bot
    'main_guild_id' => env('DISCORD_MAIN_GUILD_ID'),

    /*
    |--------------------------------------------------------------------------
    | Eventos
    |--------------------------------------------------------------------------
    |
    | Configurações para eventos do Discord e seus handlers
    |
    */
    'events' => [
        'message_create' => true,
        'guild_member_add' => true,
        'reaction_add' => true,
        'interaction_create' => true,
        'guild_member_remove' => true,
        'voice_state_update' => true,
        'presence_update' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Comandos
    |--------------------------------------------------------------------------
    |
    | Registro de comandos disponíveis
    |
    */
    'commands' => [
        // Comandos básicos
        'ping' => [
            'description' => 'Verifica se o bot está online e mostra a latência',
            'handler' => \LucasGiovanni\DiscordBotInstaller\Commands\PingCommand::class,
        ],
        'help' => [
            'description' => 'Mostra a lista de comandos disponíveis',
            'handler' => \LucasGiovanni\DiscordBotInstaller\Commands\HelpCommand::class,
        ],

        // Comandos de moderação
        'ban' => [
            'description' => 'Bane um usuário do servidor',
            'handler' => \LucasGiovanni\DiscordBotInstaller\Commands\Moderation\BanCommand::class,
            'permissions' => ['BAN_MEMBERS'],
        ],
        'kick' => [
            'description' => 'Expulsa um usuário do servidor',
            'handler' => \LucasGiovanni\DiscordBotInstaller\Commands\Moderation\KickCommand::class,
            'permissions' => ['KICK_MEMBERS'],
        ],
        'mute' => [
            'description' => 'Silencia um usuário',
            'handler' => \LucasGiovanni\DiscordBotInstaller\Commands\Moderation\MuteCommand::class,
            'permissions' => ['MODERATE_MEMBERS'],
        ],
        'warn' => [
            'description' => 'Dá uma advertência a um usuário',
            'handler' => \LucasGiovanni\DiscordBotInstaller\Commands\Moderation\WarnCommand::class,
            'permissions' => ['MODERATE_MEMBERS'],
        ],
        'infractions' => [
            'description' => 'Mostra as infrações de um usuário',
            'handler' => \LucasGiovanni\DiscordBotInstaller\Commands\Moderation\InfractionsCommand::class,
            'permissions' => ['MODERATE_MEMBERS'],
        ],

        // Comandos utilitários
        'remind' => [
            'description' => 'Define um lembrete',
            'handler' => \LucasGiovanni\DiscordBotInstaller\Commands\Utility\RemindCommand::class,
        ],
        'poll' => [
            'description' => 'Cria uma enquete',
            'handler' => \LucasGiovanni\DiscordBotInstaller\Commands\Utility\PollCommand::class,
        ],
        'role' => [
            'description' => 'Gerencia cargos auto-atribuíveis',
            'handler' => \LucasGiovanni\DiscordBotInstaller\Commands\Utility\RoleCommand::class,
            'permissions' => ['MANAGE_ROLES'],
        ],
        'level' => [
            'description' => 'Mostra seu nível atual',
            'handler' => \LucasGiovanni\DiscordBotInstaller\Commands\Level\LevelCommand::class,
        ],
        'rank' => [
            'description' => 'Mostra o ranking do servidor',
            'handler' => \LucasGiovanni\DiscordBotInstaller\Commands\Level\RankCommand::class,
        ],

        // Comandos de economia
        'coins' => [
            'description' => 'Mostra seus coins',
            'handler' => \LucasGiovanni\DiscordBotInstaller\Commands\Economy\CoinsCommand::class,
        ],
        'daily' => [
            'description' => 'Resgata recompensa diária',
            'handler' => \LucasGiovanni\DiscordBotInstaller\Commands\Economy\DailyCommand::class,
        ],
        'shop' => [
            'description' => 'Mostra a loja',
            'handler' => \LucasGiovanni\DiscordBotInstaller\Commands\Economy\ShopCommand::class,
        ],
        'buy' => [
            'description' => 'Compra um item da loja',
            'handler' => \LucasGiovanni\DiscordBotInstaller\Commands\Economy\BuyCommand::class,
        ],
        'transfer' => [
            'description' => 'Transfere coins para outro usuário',
            'handler' => \LucasGiovanni\DiscordBotInstaller\Commands\Economy\TransferCommand::class,
        ],

        // Comandos de giveaway
        'giveaway' => [
            'description' => 'Gerencia sorteios',
            'handler' => \LucasGiovanni\DiscordBotInstaller\Commands\Giveaway\GiveawayCommand::class,
        ],

        // Comandos de reaction role
        'reactionrole' => [
            'description' => 'Configura reaction roles',
            'handler' => \LucasGiovanni\DiscordBotInstaller\Commands\ReactionRole\ReactionRoleCommand::class,
            'permissions' => ['MANAGE_ROLES'],
        ],

        // Comandos de ticket/suporte
        'ticket' => [
            'description' => 'Sistema de tickets',
            'handler' => \LucasGiovanni\DiscordBotInstaller\Commands\Ticket\TicketCommand::class,
        ],

        // Comandos de evento
        'event' => [
            'description' => 'Gerencia eventos temporários',
            'handler' => \LucasGiovanni\DiscordBotInstaller\Commands\Event\EventCommand::class,
        ],

        // Comandos de auto-moderação
        'automod' => [
            'description' => 'Configura auto-moderação',
            'handler' => \LucasGiovanni\DiscordBotInstaller\Commands\AutoMod\AutoModCommand::class,
            'permissions' => ['MANAGE_GUILD'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Middleware
    |--------------------------------------------------------------------------
    |
    | Sistema de middleware para processar comandos
    |
    */
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

    /*
    |--------------------------------------------------------------------------
    | Sistema de níveis
    |--------------------------------------------------------------------------
    |
    | Configurações para o sistema de níveis e experiência
    |
    */
    'levels' => [
        'enabled' => true,
        'xp_per_message' => env('DISCORD_XP_PER_MESSAGE', 10),
        'xp_cooldown' => env('DISCORD_XP_COOLDOWN', 60), // segundos
        'announce_level_up' => env('DISCORD_ANNOUNCE_LEVEL_UP', true),
        'announce_channel' => env('DISCORD_LEVEL_UP_CHANNEL', null), // nulo = mesmo canal da mensagem
        'roles_rewards' => [
            5 => env('DISCORD_LEVEL5_ROLE', null),
            10 => env('DISCORD_LEVEL10_ROLE', null),
            20 => env('DISCORD_LEVEL20_ROLE', null),
            30 => env('DISCORD_LEVEL30_ROLE', null),
            50 => env('DISCORD_LEVEL50_ROLE', null),
            100 => env('DISCORD_LEVEL100_ROLE', null),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Sistema de economia
    |--------------------------------------------------------------------------
    |
    | Configurações para o sistema de economia virtual
    |
    */
    'economy' => [
        'enabled' => true,
        'currency_name' => env('DISCORD_CURRENCY_NAME', 'coins'),
        'currency_emoji' => env('DISCORD_CURRENCY_EMOJI', '💰'),
        'daily_amount' => env('DISCORD_DAILY_AMOUNT', 100),
        'message_reward' => env('DISCORD_MESSAGE_REWARD', 1),
        'message_reward_cooldown' => env('DISCORD_MESSAGE_REWARD_COOLDOWN', 60), // segundos
        'shop_items' => [
            // Exemplo de item
            // 'item_id' => [
            //     'name' => 'Item de exemplo',
            //     'description' => 'Este é um item de exemplo',
            //     'price' => 500,
            //     'role_id' => null, // Opcional, dá um cargo ao comprar
            //     'custom_action' => null, // Opcional, classe para ação personalizada
            // ],
        ],
        // Configurações avançadas para sistema de economia
        'inventory_enabled' => true, // Sistema de inventário de itens
        'item_categories' => ['role', 'consumable', 'collectible', 'custom'],
        'max_inventory_size' => 50, // Número máximo de itens que um usuário pode possuir
        'trading_enabled' => true, // Permitir troca de itens entre usuários
        'trading_fee' => 5, // Porcentagem de taxa para transações
        'item_rarity' => [
            'common' => ['color' => '#b8b8b8', 'multiplier' => 1],
            'uncommon' => ['color' => '#4ae049', 'multiplier' => 1.5],
            'rare' => ['color' => '#4283f5', 'multiplier' => 2.5],
            'epic' => ['color' => '#b44bef', 'multiplier' => 4],
            'legendary' => ['color' => '#fcba03', 'multiplier' => 7],
        ],
        // Sistema de loja temporária
        'rotating_shop' => [
            'enabled' => true,
            'rotation_time' => 7, // Dias para rotação de itens
            'limited_items' => 5, // Número de itens limitados por rotação
        ],
        // Sistemas de ganho de moedas
        'voice_reward' => [
            'enabled' => true,
            'amount' => 5, // Quantidade por intervalo
            'interval' => 5, // Minutos
            'min_users' => 2, // Usuários mínimos no canal
        ],
        'streak_rewards' => [
            'enabled' => true,
            'max_streak' => 7,
            'bonus_formula' => 'daily_amount * (1 + (streak * 0.1))', // Fórmula para cálculo do bônus
        ],
        // Lootboxes e prêmios aleatórios
        'lootboxes' => [
            'enabled' => true,
            'types' => [
                'common' => ['price' => 100, 'items' => 1, 'min_value' => 50, 'max_value' => 150],
                'rare' => ['price' => 500, 'items' => 3, 'min_value' => 300, 'max_value' => 700],
                'epic' => ['price' => 1000, 'items' => 5, 'min_value' => 800, 'max_value' => 1500],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Sistema de Reaction Roles
    |--------------------------------------------------------------------------
    |
    | Configurações para o sistema de reaction roles
    |
    */
    'reaction_roles' => [
        'enabled' => true,
        'max_per_message' => 20, // Máximo de roles por mensagem
        'unique_roles' => false, // Se true, usuário só pode ter uma role do grupo
        // Configurações avançadas para reaction roles
        'types' => [
            'standard' => true, // Adiciona/remove role com reação
            'toggle' => true,   // Alterna entre ter ou não a role
            'temporary' => true, // Atribui role temporariamente
            'required' => true,  // Requer ter outra role para conseguir
            'level' => true,     // Requer nível mínimo para conseguir
        ],
        'reaction_menus' => [
            'enabled' => true,     // Menus de múltipla escolha com reações
            'confirmation' => true, // Confirmação ao selecionar
            'auto_remove' => true,  // Remove reações antigas
        ],
        'logging' => true,  // Registra mudanças de role
        'temp_duration' => 60, // Duração padrão em minutos para roles temporárias
        'premium_roles' => [], // IDs de roles que podem ser adquiridas apenas com economia
        'reaction_themes' => [
            'colors' => ['🔴', '🟠', '🟡', '🟢', '🔵', '🟣', '⚫', '⚪'],
            'gaming' => ['🎮', '🎲', '🎯', '🎪', '🎭', '🎨'],
            'nature' => ['🌲', '🌊', '🏔️', '🌙', '☀️', '🌍'],
            'custom' => [],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Sistema de Tickets
    |--------------------------------------------------------------------------
    |
    | Configurações para o sistema de tickets/suporte
    |
    */
    'tickets' => [
        'enabled' => true,
        'category_id' => env('DISCORD_TICKETS_CATEGORY', null), // Categoria para criar canais de ticket
        'support_roles' => [
            // IDs de cargos que podem acessar os tickets
        ],
        'ticket_limit' => 5, // Máximo de tickets por usuário
        'close_on_solved' => true, // Fecha o ticket quando resolvido
        'transcript' => true, // Gera transcrição ao fechar
        'auto_close' => 48, // Horas para fechar automaticamente tickets inativos (0 = desativado)
        // Configurações avançadas para o sistema de tickets
        'types' => [
            'support' => [
                'name' => 'Suporte Geral',
                'emoji' => '🔧',
                'description' => 'Suporte técnico e ajuda geral',
                'color' => '#3498db',
                'custom_form' => false, // Formulário personalizado ao criar
            ],
            'report' => [
                'name' => 'Denúncias',
                'emoji' => '🛡️',
                'description' => 'Reportar problemas com usuários',
                'color' => '#e74c3c',
                'custom_form' => true, // Formulário personalizado ao criar
                'required_fields' => ['user', 'reason', 'evidence'],
            ],
            'suggestion' => [
                'name' => 'Sugestões',
                'emoji' => '💡',
                'description' => 'Envie suas sugestões para o servidor',
                'color' => '#2ecc71',
                'custom_form' => true,
                'required_fields' => ['title', 'description'],
                'voting' => true, // Permite votar em sugestões
            ],
        ],
        'priority_levels' => [
            'low' => ['color' => '#3498db', 'response_time' => '24h'],
            'medium' => ['color' => '#f39c12', 'response_time' => '12h'],
            'high' => ['color' => '#e74c3c', 'response_time' => '3h'],
            'critical' => ['color' => '#9b59b6', 'response_time' => '30min'],
        ],
        'features' => [
            'ratings' => true, // Permitir avaliações ao fechar tickets
            'templates' => true, // Respostas pré-definidas
            'mentions' => true, // Menções para equipe de suporte
            'attachments' => true, // Permitir anexos
            'canned_responses' => [
                // Respostas rápidas pré-definidas
                'welcome' => 'Bem-vindo(a) ao seu ticket de suporte. Como podemos ajudar?',
                'closing' => 'Estamos encerrando este ticket, pois consideramos que o problema foi resolvido. Se precisar de mais ajuda, sinta-se à vontade para abrir um novo ticket.',
                'inactive' => 'Este ticket está inativo há algum tempo. Podemos ajudar com mais alguma coisa?',
            ],
            'transfer' => true, // Transferir tickets entre categorias
            'private_notes' => true, // Notas privadas para a equipe
        ],
        'privacy' => [
            'anonymous_reports' => true, // Permitir denúncias anônimas
            'data_retention' => 30, // Dias para manter transcrições
            'private_by_default' => true, // Tickets são privados por padrão
        ],
        'notifications' => [
            'staff_ping' => true, // Notificar equipe sobre novos tickets
            'updates' => true, // Notificar usuário sobre atualizações
            'inactivity' => true, // Notificar sobre tickets inativos
            'sla_alerts' => true, // Alertas de SLA não cumprido
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Sistema de Giveaways
    |--------------------------------------------------------------------------
    |
    | Configurações para o sistema de sorteios
    |
    */
    'giveaways' => [
        'enabled' => true,
        'emoji' => '🎉',
        'default_duration' => 3600, // 1 hora em segundos
        'store_previous' => 10, // Guardar histórico dos últimos X sorteios
    ],

    /*
    |--------------------------------------------------------------------------
    | Sistema de Eventos
    |--------------------------------------------------------------------------
    |
    | Configurações para o sistema de eventos temporários
    |
    */
    'events' => [
        'enabled' => true,
        'announcement_channel' => env('DISCORD_EVENTS_CHANNEL', null),
        'reminder_times' => [1440, 60, 15], // Minutos antes do evento para enviar lembretes (24h, 1h, 15min)
        // Configurações avançadas para o sistema de eventos
        'types' => [
            'meeting' => [
                'color' => '#3498db',
                'emoji' => '📅',
                'requires_approval' => false,
            ],
            'community' => [
                'color' => '#2ecc71',
                'emoji' => '🎮',
                'requires_approval' => false,
            ],
            'contest' => [
                'color' => '#e74c3c',
                'emoji' => '🏆',
                'requires_approval' => true,
            ],
            'important' => [
                'color' => '#f1c40f',
                'emoji' => '⭐',
                'requires_approval' => true,
                'ping_everyone' => true,
            ],
        ],
        'calendar' => [
            'enabled' => true,
            'view_command' => true, // Comando para ver calendário
            'embed_color' => '#5865F2',
            'max_display' => 10, // Eventos máximos a mostrar
            'timezone' => env('DISCORD_EVENTS_TIMEZONE', 'America/Sao_Paulo'),
        ],
        'rsvp' => [
            'enabled' => true,
            'buttons' => [
                'join' => [
                    'label' => 'Participar',
                    'emoji' => '✅',
                    'style' => 'success',
                ],
                'maybe' => [
                    'label' => 'Talvez',
                    'emoji' => '❓',
                    'style' => 'secondary',
                ],
                'decline' => [
                    'label' => 'Recusar',
                    'emoji' => '❌',
                    'style' => 'danger',
                ],
            ],
            'limit_participants' => false, // Limitar número de participantes
            'waitlist' => true, // Lista de espera se atingir limite
            'allow_comments' => true, // Permitir comentários ao participar
        ],
        'creation' => [
            'permitted_roles' => [], // Cargos que podem criar eventos
            'approval_required' => false, // Requer aprovação de moderador
            'approval_roles' => [], // Cargos que podem aprovar
            'modal_creation' => true, // Usar modal para criação
            'templates' => true, // Permitir templates de eventos
        ],
        'notifications' => [
            'start_ping' => true, // Pingar participantes no início
            'reminder_dm' => true, // Enviar lembretes por DM
            'embed_style' => true, // Usar embeds para notificações
        ],
        'voice_integration' => [
            'enabled' => true,
            'create_channel' => true, // Criar canal de voz para o evento
            'temp_category' => null, // Categoria para criar canais de evento
            'auto_delete' => true, // Excluir canal após o evento
            'prefix' => '🎪・', // Prefixo do nome do canal
        ],
        'recurring' => [
            'enabled' => true,
            'frequencies' => [
                'daily' => true,
                'weekly' => true,
                'biweekly' => true,
                'monthly' => true,
            ],
            'max_recurrences' => 20, // Máximo de recorrências
            'end_date_required' => true, // Requer data final
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Sistema de Auto-Moderação
    |--------------------------------------------------------------------------
    |
    | Configurações para o sistema de auto-moderação
    |
    */
    'auto_moderation' => [
        'enabled' => true,
        'spam_detection' => false, // Detecta spam
        'spam_threshold' => 5, // Mensagens em X segundos
        'spam_interval' => 3, // Segundos
        'caps_detection' => false, // Detecta CAPSLOCK excessivo
        'caps_threshold' => 70, // Porcentagem
        'caps_min_length' => 10, // Tamanho mínimo da mensagem para verificar
        'link_detection' => false, // Filtra links
        'allowed_domains' => ['discord.com', 'discord.gg'], // Domínios permitidos
        'word_filter' => false, // Filtra palavras proibidas
        'filtered_words' => [],
        'punishment' => 'warn', // warn, mute, kick, ban
        'punishment_duration' => 10, // Minutos (para mute)
        'log_channel' => env('DISCORD_AUTOMOD_LOG_CHANNEL', null),
        // Configurações avançadas para auto-moderação
        'raid_detection' => [
            'enabled' => false,
            'join_threshold' => 10, // Usuários em X segundos
            'join_interval' => 60, // Segundos
            'action' => 'lockdown', // lockdown, verification, notify
            'lockdown_duration' => 10, // Minutos
            'notify_roles' => [], // IDs de cargos para notificar
        ],
        'repeated_text' => [
            'enabled' => false,
            'threshold' => 3, // Repetições máximas
            'ignore_case' => true,
        ],
        'mass_mentions' => [
            'enabled' => false,
            'threshold' => 5, // Menções máximas
            'exclude_roles' => [], // Cargos excluídos da verificação
        ],
        'image_scanning' => [
            'enabled' => false,
            'nsfw_detection' => false,
            'nsfw_threshold' => 0.7, // Limiar para detecção NSFW
            'phishing_detection' => false,
            'duplicate_detection' => false, // Detecta spam de imagens duplicadas
        ],
        'anti_phishing' => [
            'enabled' => true,
            'domains_list_url' => 'https://raw.githubusercontent.com/discord/anti-phishing-list/main/domains.txt',
            'auto_update' => true, // Atualiza lista automaticamente
            'update_interval' => 24, // Horas
            'action' => 'delete_ban', // delete, delete_warn, delete_kick, delete_ban
        ],
        'intelligent_content_filter' => [
            'enabled' => false,
            'ai_powered' => false, // Usar IA para detecção avançada
            'sensitivity' => 'medium', // low, medium, high
            'custom_rules' => [], // Regras personalizadas de expressão regular
        ],
        'verification' => [
            'enabled' => false,
            'method' => 'reaction', // reaction, command, captcha
            'role_id' => null, // Cargo dado após verificação
            'captcha_type' => 'image', // image, text, math
            'welcome_channel' => null, // Canal para mensagem de verificação
        ],
        'server_invites' => [
            'enabled' => false,
            'action' => 'delete', // delete, delete_warn
            'whitelist' => [], // IDs de servidores permitidos
        ],
        'escalation' => [
            'enabled' => false,
            'strikes' => [
                3 => 'mute_10', // 3 strikes = mute por 10 minutos
                5 => 'mute_60', // 5 strikes = mute por 1 hora
                8 => 'kick',    // 8 strikes = expulsão
                10 => 'ban',    // 10 strikes = banimento
            ],
            'expire_after' => 7, // Dias para expirar strikes
        ],
        'exclusions' => [
            'channels' => [], // Canais excluídos da moderação
            'roles' => [],    // Cargos excluídos da moderação
            'users' => [],    // Usuários excluídos da moderação
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Boas-vindas
    |--------------------------------------------------------------------------
    |
    | Configurações para boas-vindas personalizadas
    |
    */
    'welcome' => [
        'enabled' => true,
        'channel_id' => env('DISCORD_WELCOME_CHANNEL', null),
        'custom_image' => true, // Gerar imagem personalizada
        'image_template' => storage_path('app/discord/welcome_template.png'),
        'text_color' => '#ffffff',
        'background_color' => '#333333',
        'dm_message' => false, // Enviar mensagem privada também
        // Configurações avançadas para boas-vindas
        'image_generator' => [
            'enabled' => true,
            'templates' => [
                'default' => [
                    'background' => storage_path('app/discord/templates/welcome_default.png'),
                    'avatar_position' => [400, 200],
                    'avatar_size' => 128,
                    'text_fields' => [
                        'username' => [
                            'position' => [400, 350],
                            'font' => storage_path('app/discord/fonts/Roboto-Bold.ttf'),
                            'size' => 36,
                            'color' => '#ffffff',
                            'align' => 'center',
                        ],
                        'member_count' => [
                            'position' => [400, 400],
                            'font' => storage_path('app/discord/fonts/Roboto-Regular.ttf'),
                            'size' => 24,
                            'color' => '#cccccc',
                            'align' => 'center',
                            'text' => 'Membro #{count}',
                        ],
                        'message' => [
                            'position' => [400, 450],
                            'font' => storage_path('app/discord/fonts/Roboto-Italic.ttf'),
                            'size' => 20,
                            'color' => '#aaaaaa',
                            'align' => 'center',
                            'text' => 'Bem-vindo(a) ao servidor!',
                        ],
                    ],
                    'effects' => [
                        'avatar_border' => [
                            'enabled' => true,
                            'color' => '#ffffff',
                            'thickness' => 4,
                        ],
                        'background_blur' => [
                            'enabled' => false,
                            'strength' => 5,
                        ],
                        'background_darken' => [
                            'enabled' => true,
                            'strength' => 30,
                        ],
                    ],
                ],
                'premium' => [
                    'background' => storage_path('app/discord/templates/welcome_premium.png'),
                    'avatar_position' => [300, 200],
                    'avatar_size' => 150,
                    'text_fields' => [
                        'username' => [
                            'position' => [500, 200],
                            'font' => storage_path('app/discord/fonts/Montserrat-Bold.ttf'),
                            'size' => 48,
                            'color' => '#ffffff',
                            'align' => 'left',
                        ],
                        'member_count' => [
                            'position' => [500, 260],
                            'font' => storage_path('app/discord/fonts/Montserrat-SemiBold.ttf'),
                            'size' => 24,
                            'color' => '#dddddd',
                            'align' => 'left',
                            'text' => 'Membro #{count}',
                        ],
                        'message' => [
                            'position' => [500, 310],
                            'font' => storage_path('app/discord/fonts/Montserrat-Regular.ttf'),
                            'size' => 20,
                            'color' => '#bbbbbb',
                            'align' => 'left',
                            'text' => 'Bem-vindo(a) à nossa comunidade!',
                        ],
                    ],
                    'effects' => [
                        'avatar_border' => [
                            'enabled' => true,
                            'color' => '#ffffff',
                            'thickness' => 5,
                        ],
                        'background_blur' => [
                            'enabled' => true,
                            'strength' => 10,
                        ],
                        'drop_shadow' => [
                            'enabled' => true,
                            'opacity' => 70,
                            'blur' => 5,
                            'offset' => [2, 2],
                        ],
                    ],
                ],
            ],
        ],
        'private_message' => [
            'enabled' => true,
            'content' => 'Olá {user}, bem-vindo(a) ao {server}! Por favor, leia nossas regras no canal {channel}.',
            'embed' => true,
            'embed_color' => '#5865F2',
            'embed_thumbnail' => true, // Usar avatar do servidor
            'embed_footer' => true, // Adicionar rodapé com data
        ],
        'welcome_roles' => [
            'enabled' => true,
            'roles' => [], // IDs dos cargos iniciais
            'delay' => 0, // Segundos de espera antes de atribuir
        ],
        'temporary_role' => [
            'enabled' => false,
            'role_id' => null, // ID do cargo temporário (ex: Novato)
            'duration' => 7, // Dias que o usuário mantém o cargo
        ],
        'customization' => [
            'per_milestone' => true, // Mensagens especiais a cada X membros
            'milestones' => [100, 500, 1000, 5000, 10000],
            'custom_messages' => [
                // 'user_id' => 'Mensagem personalizada para {user}'
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Componentes Interativos
    |--------------------------------------------------------------------------
    |
    | Configurações para componentes interativos (botões, menus, etc)
    |
    */
    'components' => [
        'enabled' => true,
        'timeout' => 300, // Segundos para timeout de interações
        'store_interactions' => true, // Armazena interações no banco de dados
        // Configurações avançadas para componentes interativos
        'button_styles' => [
            'primary' => '#5865F2', // Azul
            'success' => '#57F287', // Verde
            'danger' => '#ED4245',  // Vermelho
            'secondary' => '#4F545C', // Cinza
        ],
        'templates' => [
            'confirmation' => [
                'title' => 'Confirmação',
                'buttons' => ['Confirmar', 'Cancelar'],
                'timeout' => 60, // Segundos
            ],
            'pagination' => [
                'buttons' => ['⬅️', '➡️', '❌'],
                'timeout' => 120, // Segundos
                'items_per_page' => 5,
            ],
            'role_menu' => [
                'title' => 'Selecione seus cargos',
                'timeout' => 300, // Segundos
                'max_selections' => 5,
            ],
        ],
        'forms' => [
            'enabled' => true,
            'max_fields' => 10,
            'field_types' => [
                'short_text' => true,
                'long_text' => true,
                'number' => true,
                'select' => true,
                'user' => true,
                'channel' => true,
                'role' => true,
                'boolean' => true,
            ],
            'validation' => true, // Validar campos
            'storage' => true, // Armazenar respostas
        ],
        'context_menus' => [
            'enabled' => true,
            'user_commands' => [
                'profile' => true,
                'warn' => true,
                'avatar' => true,
                'roles' => true,
            ],
            'message_commands' => [
                'quote' => true,
                'save' => true,
                'report' => true,
                'pin' => true,
            ],
        ],
        'modals' => [
            'enabled' => true,
            'max_size' => 5, // Componentes por modal
            'custom_styling' => true,
            'templates' => [
                'report' => [
                    'title' => 'Reportar Usuário',
                    'fields' => [
                        ['name' => 'user', 'type' => 'user', 'required' => true],
                        ['name' => 'reason', 'type' => 'select', 'required' => true],
                        ['name' => 'evidence', 'type' => 'long_text', 'required' => false],
                    ],
                ],
                'feedback' => [
                    'title' => 'Enviar Feedback',
                    'fields' => [
                        ['name' => 'rating', 'type' => 'select', 'required' => true],
                        ['name' => 'comment', 'type' => 'long_text', 'required' => false],
                    ],
                ],
            ],
        ],
        'advanced_embeds' => [
            'enabled' => true,
            'templates' => [
                'info' => [
                    'color' => '#3498db',
                    'thumbnail' => true,
                    'footer' => true,
                ],
                'success' => [
                    'color' => '#2ecc71',
                    'thumbnail' => true,
                    'footer' => true,
                ],
                'warning' => [
                    'color' => '#f39c12',
                    'thumbnail' => true,
                    'footer' => true,
                ],
                'error' => [
                    'color' => '#e74c3c',
                    'thumbnail' => true,
                    'footer' => true,
                ],
            ],
            'builder' => [
                'enabled' => true, // Construtor visual de embeds
                'permission' => 'MANAGE_MESSAGES',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhooks
    |--------------------------------------------------------------------------
    |
    | Configurações para webhooks customizáveis
    |
    */
    'webhooks' => [
        'enabled' => true,
        'token_expiry' => 60, // Dias
        'verify_signature' => true, // Verificar assinatura HMAC
        'rate_limit' => 60, // Máximo de solicitações por minuto
        // Configurações avançadas para webhooks
        'endpoints' => [
            'notifications' => [
                'url' => '/api/discord/webhooks/notifications',
                'description' => 'Enviar notificações para o Discord',
                'method' => 'POST',
                'auth_required' => true,
                'signature_required' => true,
                'rate_limit' => 10, // Por minuto
                'parameters' => [
                    'channel_id' => [
                        'required' => true,
                        'type' => 'string',
                        'description' => 'ID do canal',
                    ],
                    'content' => [
                        'required' => false,
                        'type' => 'string',
                        'description' => 'Conteúdo da mensagem',
                    ],
                    'embed' => [
                        'required' => false,
                        'type' => 'object',
                        'description' => 'Embed para enviar',
                    ],
                ],
                'permissions' => [
                    'send_messages' => true,
                ],
            ],
            'moderation' => [
                'url' => '/api/discord/webhooks/moderation',
                'description' => 'Executar ações de moderação',
                'method' => 'POST',
                'auth_required' => true,
                'signature_required' => true,
                'rate_limit' => 5, // Por minuto
                'permissions' => [
                    'ban_members' => true,
                    'kick_members' => true,
                    'manage_messages' => true,
                ],
            ],
            'stats' => [
                'url' => '/api/discord/webhooks/stats',
                'description' => 'Obter estatísticas do servidor',
                'method' => 'GET',
                'auth_required' => true,
                'signature_required' => false,
                'rate_limit' => 10, // Por minuto
                'permissions' => [
                    'view_server' => true,
                ],
            ],
        ],
        'security' => [
            'api_keys' => [
                'enabled' => true,
                'rotation_period' => 90, // Dias
                'max_keys' => 5, // Máximo de chaves por aplicação
            ],
            'ip_whitelist' => [
                'enabled' => false,
                'ips' => [],
            ],
            'permissions' => [
                'roles' => [], // IDs de cargos com permissão para criar webhooks
                'granular' => true, // Permissões granulares por endpoint
            ],
        ],
        'templates' => [
            'notification' => [
                'embed' => [
                    'title' => '{title}',
                    'description' => '{description}',
                    'color' => '#3498db',
                    'footer' => [
                        'text' => 'Enviado via API • {date}',
                    ],
                ],
            ],
            'error' => [
                'embed' => [
                    'title' => 'Erro: {title}',
                    'description' => '{description}',
                    'color' => '#e74c3c',
                    'footer' => [
                        'text' => 'Erro reportado via API • {date}',
                    ],
                ],
            ],
            'success' => [
                'embed' => [
                    'title' => 'Sucesso: {title}',
                    'description' => '{description}',
                    'color' => '#2ecc71',
                    'footer' => [
                        'text' => 'Ação concluída via API • {date}',
                    ],
                ],
            ],
        ],
        'logging' => [
            'enabled' => true,
            'channel_id' => null, // Canal para logs de webhook
            'store_history' => true, // Armazenar histórico de requisições
            'history_limit' => 100, // Número máximo de registros por webhook
            'error_notification' => true, // Notificar erros
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Logs e monitoramento
    |--------------------------------------------------------------------------
    |
    | Configurações para logs e monitoramento do bot
    |
    */
    'logging' => [
        'enabled' => true,
        'file' => storage_path('logs/discordbot.log'),
        'level' => env('DISCORD_LOG_LEVEL', 'info'), // debug, info, warning, error
        'discord_channel' => env('DISCORD_LOG_CHANNEL', null),
        'database' => env('DISCORD_LOG_TO_DATABASE', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | API de Estatísticas
    |--------------------------------------------------------------------------
    |
    | Configurações para a API de estatísticas
    |
    */
    'stats' => [
        'enabled' => true,
        'cache_ttl' => 5, // Minutos para cache de estatísticas
        'track_commands' => true,
        'track_messages' => true,
        'track_members' => true,
        'detailed_logging' => false, // Logs detalhados (uso maior de banco)
        // Configurações avançadas para API de estatísticas
        'api' => [
            'enabled' => true,
            'endpoint' => '/api/discord/stats',
            'auth_required' => true,
            'rate_limit' => 100, // Requisições por hora
        ],
        'metrics' => [
            'activity' => [
                'enabled' => true,
                'timeframes' => ['daily', 'weekly', 'monthly'],
                'types' => [
                    'messages' => true,
                    'commands' => true,
                    'users' => true,
                    'voice' => true,
                ],
            ],
            'growth' => [
                'enabled' => true,
                'track_joins' => true,
                'track_leaves' => true,
                'track_retention' => true, // Acompanha taxa de retenção
            ],
            'engagement' => [
                'enabled' => true,
                'active_hours' => true, // Horas mais ativas
                'channel_activity' => true, // Atividade por canal
                'user_activity' => true, // Usuários mais ativos
                'command_usage' => true, // Comandos mais usados
            ],
            'voice' => [
                'enabled' => true,
                'track_time' => true, // Tempo gasto em canais de voz
                'track_channels' => true, // Canais mais usados
                'track_peak_hours' => true, // Horas de pico
            ],
        ],
        'visualizations' => [
            'enabled' => true,
            'charts' => [
                'enabled' => true,
                'library' => 'chartjs', // Biblioteca de gráficos
                'default_type' => 'line', // line, bar, pie, doughnut
                'colors' => [
                    'primary' => '#3498db',
                    'secondary' => '#2ecc71',
                    'tertiary' => '#e74c3c',
                    'quaternary' => '#f1c40f',
                    'quinary' => '#9b59b6',
                ],
                'themes' => [
                    'light' => [
                        'background' => '#ffffff',
                        'text' => '#333333',
                        'grid' => '#dddddd',
                    ],
                    'dark' => [
                        'background' => '#36393f',
                        'text' => '#ffffff',
                        'grid' => '#4f545c',
                    ],
                ],
            ],
            'dashboards' => [
                'enabled' => true, // Gerar dashboards com embed
                'refresh_rate' => 15, // Minutos para atualização
                'default_timeframe' => 'weekly', // daily, weekly, monthly
                'embed_color' => '#5865F2',
            ],
            'leaderboards' => [
                'enabled' => true,
                'types' => [
                    'activity' => true,
                    'voice' => true,
                    'commands' => true,
                    'invites' => true,
                ],
                'display_limit' => 10, // Número de usuários a mostrar
                'update_interval' => 60, // Minutos para atualização
            ],
        ],
        'server_insights' => [
            'enabled' => true,
            'growth_predictions' => true, // Previsões de crescimento
            'activity_trends' => true, // Tendências de atividade
            'channel_suggestions' => true, // Sugestões de canais
            'user_retention' => true, // Análise de retenção
            'report_frequency' => 'weekly', // daily, weekly, monthly
            'report_channel' => null, // Canal para relatórios
        ],
        'exports' => [
            'enabled' => true,
            'formats' => ['json', 'csv', 'image'],
            'scheduled_reports' => [
                'enabled' => false,
                'frequency' => 'monthly', // daily, weekly, monthly
                'delivery_method' => 'discord', // discord, email
                'recipients' => [], // IDs de usuários ou canais
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Mensagens
    |--------------------------------------------------------------------------
    |
    | Mensagens personalizáveis para diferentes situações
    |
    */
    'messages' => [
        'welcome' => env('DISCORD_WELCOME_MESSAGE', 'Bem-vindo(a) {user} ao servidor! 👋'),
        'level_up' => env('DISCORD_LEVEL_UP_MESSAGE', '🎉 Parabéns {user}! Você alcançou o nível **{level}**!'),
        'reminder' => env('DISCORD_REMINDER_MESSAGE', '⏰ **Lembrete:** {message}'),
        'ban' => env('DISCORD_BAN_MESSAGE', 'Usuário {user} foi banido por {moderator}. Motivo: {reason}'),
        'kick' => env('DISCORD_KICK_MESSAGE', 'Usuário {user} foi expulso por {moderator}. Motivo: {reason}'),
        'warn' => env('DISCORD_WARN_MESSAGE', 'Usuário {user} recebeu uma advertência de {moderator}. Motivo: {reason}'),
        'ticket_created' => env('DISCORD_TICKET_CREATED_MESSAGE', 'Ticket criado por {user}. Use este canal para obter suporte.'),
        'ticket_closed' => env('DISCORD_TICKET_CLOSED_MESSAGE', 'Ticket fechado por {user}.'),
        'giveaway_started' => env('DISCORD_GIVEAWAY_STARTED_MESSAGE', '🎉 **SORTEIO** 🎉\n\n{prize}\n\nClique no emoji 🎉 para participar!\nTérmino: {end_time}'),
        'giveaway_ended' => env('DISCORD_GIVEAWAY_ENDED_MESSAGE', '🎉 **SORTEIO ENCERRADO** 🎉\n\n{prize}\n\nVencedor(es): {winners}'),
        'event_created' => env('DISCORD_EVENT_CREATED_MESSAGE', '📅 **NOVO EVENTO** 📅\n\n{title}\n{description}\n\nData: {date}\nUse o comando `!event join {id}` para participar!'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Módulos e plugins
    |--------------------------------------------------------------------------
    |
    | Configurações para o sistema de módulos e plugins
    |
    */
    'modules' => [
        'enabled' => true,
        'auto_discover' => true,
        'directory' => app_path('DiscordModules'),
        'active' => [
            // 'nome_modulo' => true/false,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Localização e idiomas
    |--------------------------------------------------------------------------
    |
    | Configurações para suporte a múltiplos idiomas
    |
    */
    'localization' => [
        'enabled' => true,
        'default' => env('DISCORD_DEFAULT_LOCALE', 'pt_BR'),
        'fallback' => env('DISCORD_FALLBACK_LOCALE', 'en'),
        'server_specific' => env('DISCORD_SERVER_SPECIFIC_LOCALE', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Processamento assíncrono
    |--------------------------------------------------------------------------
    |
    | Configurações para operações pesadas em filas de background
    |
    */
    'queue' => [
        'enabled' => env('DISCORD_QUEUE_ENABLED', true),
        'connection' => env('DISCORD_QUEUE_CONNECTION', env('QUEUE_CONNECTION', 'sync')),
        'heavy_commands' => [
            'ban', 'kick', 'poll', 'remind', 'giveaway', 'welcome', 'ticket'
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Integrações externas
    |--------------------------------------------------------------------------
    |
    | Configurações para integrações com APIs externas
    |
    */
    'integrations' => [
        'github' => [
            'enabled' => env('DISCORD_GITHUB_ENABLED', false),
            'webhook_channel' => env('DISCORD_GITHUB_WEBHOOK_CHANNEL', null),
            'repositories' => [],
        ],
        'youtube' => [
            'enabled' => env('DISCORD_YOUTUBE_ENABLED', false),
            'api_key' => env('DISCORD_YOUTUBE_API_KEY', null),
            'channel_id' => env('DISCORD_YOUTUBE_CHANNEL_ID', null),
            'notification_channel' => env('DISCORD_YOUTUBE_NOTIFICATION_CHANNEL', null),
        ],
        'twitch' => [
            'enabled' => env('DISCORD_TWITCH_ENABLED', false),
            'client_id' => env('DISCORD_TWITCH_CLIENT_ID', null),
            'client_secret' => env('DISCORD_TWITCH_CLIENT_SECRET', null),
            'channels' => [],
            'notification_channel' => env('DISCORD_TWITCH_NOTIFICATION_CHANNEL', null),
        ],
        'spotify' => [
            'enabled' => env('DISCORD_SPOTIFY_ENABLED', false),
            'client_id' => env('DISCORD_SPOTIFY_CLIENT_ID', null),
            'client_secret' => env('DISCORD_SPOTIFY_CLIENT_SECRET', null),
        ],
        'openai' => [
            'enabled' => env('DISCORD_OPENAI_ENABLED', false),
            'api_key' => env('DISCORD_OPENAI_API_KEY', null),
            'model' => env('DISCORD_OPENAI_MODEL', 'gpt-4o'),
            'temperature' => 0.7,
            'max_tokens' => 200,
            'moderation' => false, // Usar API de moderação para conteúdo
            // Configurações avançadas para IA
            'features' => [
                'chat' => [
                    'enabled' => true,
                    'prompt_system' => env('DISCORD_AI_CHAT_PROMPT', 'Você é um assistente amigável que responde perguntas dos membros do Discord.'),
                    'max_history' => 10, // Número de mensagens de contexto
                    'channels' => [], // IDs de canais onde o chat AI é permitido
                ],
                'moderation' => [
                    'enabled' => false,
                    'threshold' => 0.8, // Limite para detecção de conteúdo nocivo
                    'auto_action' => 'warn', // warn, delete, mute, kick, ban
                    'categories' => [
                        'hate' => true,
                        'harassment' => true,
                        'sexual' => true,
                        'violence' => true,
                        'self_harm' => true,
                    ],
                ],
                'image_generation' => [
                    'enabled' => false,
                    'model' => 'dall-e-3',
                    'size' => '1024x1024',
                    'quality' => 'standard',
                    'limit_per_user' => 5, // Gerações por dia por usuário
                    'cost' => 50, // Custo em moedas da economia virtual
                ],
                'sentiment_analysis' => [
                    'enabled' => false,
                    'track_server_mood' => true,
                    'alert_threshold' => -0.7, // Alerta de sentimento negativo
                ],
                'translation' => [
                    'enabled' => false,
                    'source_language' => 'auto',
                    'target_language' => 'pt',
                ],
                'summarization' => [
                    'enabled' => false,
                    'max_length' => 400,
                ],
            ],
            'custom_commands' => [
                // 'prompt_name' => 'Descrição do prompt personalizado',
            ],
        ],
        'giphy' => [
            'enabled' => env('DISCORD_GIPHY_ENABLED', false),
            'api_key' => env('DISCORD_GIPHY_API_KEY', null),
        ],
        'imdb' => [
            'enabled' => env('DISCORD_IMDB_ENABLED', false),
            'api_key' => env('DISCORD_IMDB_API_KEY', null),
        ],
        'steam' => [
            'enabled' => env('DISCORD_STEAM_ENABLED', false),
            'api_key' => env('DISCORD_STEAM_API_KEY', null),
        ],
        // Novas integrações
        'google_calendar' => [
            'enabled' => env('DISCORD_GCALENDAR_ENABLED', false),
            'credentials_json' => env('DISCORD_GCALENDAR_CREDENTIALS', null),
            'calendar_id' => env('DISCORD_GCALENDAR_ID', null),
            'sync_events' => true,
            'notification_channel' => env('DISCORD_GCALENDAR_CHANNEL', null),
        ],
        'jira' => [
            'enabled' => env('DISCORD_JIRA_ENABLED', false),
            'site_url' => env('DISCORD_JIRA_URL', null),
            'username' => env('DISCORD_JIRA_USERNAME', null),
            'api_token' => env('DISCORD_JIRA_TOKEN', null),
            'project_key' => env('DISCORD_JIRA_PROJECT', null),
            'notification_channel' => env('DISCORD_JIRA_CHANNEL', null),
        ],
        'weather' => [
            'enabled' => env('DISCORD_WEATHER_ENABLED', false),
            'api_key' => env('DISCORD_WEATHER_API_KEY', null),
            'default_location' => env('DISCORD_WEATHER_DEFAULT', 'São Paulo, BR'),
            'units' => 'metric', // metric, imperial
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache
    |--------------------------------------------------------------------------
    |
    | Configurações para o sistema de cache
    |
    */
    'cache' => [
        'enabled' => true,
        'store' => env('DISCORD_CACHE_STORE', env('CACHE_DRIVER', 'file')),
        'ttl' => env('DISCORD_CACHE_TTL', 3600), // 1 hora
    ],

    /*
    |--------------------------------------------------------------------------
    | Votação avançada
    |--------------------------------------------------------------------------
    |
    | Configurações para o sistema de votação avançada
    |
    */
    'voting' => [
        'enabled' => true,
        'default_duration' => 60, // minutos
        'show_voters' => true, // Mostrar quem votou
        'allow_multiple_options' => false, // Permitir votar em múltiplas opções
        'charts' => true, // Gerar gráficos de resultado
        // Configurações avançadas para o sistema de votação
        'types' => [
            'simple' => [
                'emoji' => ['👍', '👎'],
                'anonymous' => false,
                'show_count' => true,
            ],
            'multiple_choice' => [
                'max_options' => 10,
                'custom_emoji' => true, // Permitir emoji personalizados
                'numbered' => true, // Numerar automaticamente
            ],
            'weighted' => [
                'enabled' => true, // Votação com pesos
                'roles' => [
                    // 'role_id' => 2, // Peso do voto para o cargo
                ],
                'display_weights' => true, // Mostrar pesos no resultado
            ],
            'ranked_choice' => [
                'enabled' => true, // Votação por preferência
                'max_ranks' => 5, // Máximo de classificações
            ],
            'reaction' => [
                'enabled' => true,
                'default_emojis' => ['1️⃣', '2️⃣', '3️⃣', '4️⃣', '5️⃣', '6️⃣', '7️⃣', '8️⃣', '9️⃣', '🔟'],
                'allow_custom' => true, // Permitir emojis personalizados
            ],
            'button' => [
                'enabled' => true,
                'styles' => [
                    'primary' => '#5865F2',
                    'success' => '#57F287',
                    'danger' => '#ED4245',
                    'secondary' => '#4F545C',
                ],
                'show_count' => true, // Mostrar contagem nos botões
            ],
        ],
        'templates' => [
            'yes_no' => [
                'title' => 'Votação Sim/Não',
                'options' => ['Sim', 'Não'],
                'emoji' => ['✅', '❌'],
                'type' => 'reaction',
            ],
            'rating' => [
                'title' => 'Avaliação',
                'options' => ['⭐', '⭐⭐', '⭐⭐⭐', '⭐⭐⭐⭐', '⭐⭐⭐⭐⭐'],
                'type' => 'reaction',
                'anonymous' => true,
            ],
            'scheduling' => [
                'title' => 'Agendamento',
                'description' => 'Vote nas melhores datas/horários:',
                'type' => 'multiple_choice',
                'allow_multiple' => true,
            ],
        ],
        'visualization' => [
            'live_results' => true, // Atualizar resultados em tempo real
            'chart_type' => 'bar', // bar, pie, line, doughnut
            'chart_colors' => ['#3498db', '#2ecc71', '#e74c3c', '#f1c40f', '#9b59b6'],
            'show_percentages' => true, // Mostrar porcentagens
            'show_total_votes' => true, // Mostrar total de votos
        ],
        'security' => [
            'prevent_double_voting' => true, // Evitar votos duplicados
            'role_restrictions' => false, // Restringir votação por cargo
            'allowed_roles' => [], // Cargos permitidos para votar
            'minimum_account_age' => 0, // Idade mínima da conta em dias
        ],
        'notifications' => [
            'start' => true, // Notificar início da votação
            'reminders' => true, // Lembretes antes do término
            'results' => true, // Notificar resultados
            'dm_creator' => true, // Enviar DM ao criador com resultados
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Sistema de Gráficos
    |--------------------------------------------------------------------------
    |
    | Configurações para geração de gráficos com dados do Discord
    |
    */
    'charts' => [
        'enabled' => true,
        'library' => 'chartjs', // Biblioteca utilizada para geração
        'default_type' => 'bar', // Tipo padrão de gráfico
        'default_style' => [
            'width' => 800,
            'height' => 400,
            'background' => '#36393f', // Cor de fundo
            'text_color' => '#ffffff',
            'border_color' => '#4f545c',
            'padding' => 20,
            'responsive' => true,
            'animation' => true,
            'font_family' => 'Whitney,Helvetica Neue,Helvetica,Arial,sans-serif',
        ],
        'color_scheme' => [
            'default' => [
                'primary' => '#5865F2',
                'secondary' => '#57F287',
                'tertiary' => '#ED4245',
                'quaternary' => '#FEE75C',
                'quinary' => '#EB459E',
            ],
            'pastel' => [
                'primary' => '#a8d8ea',
                'secondary' => '#aa96da',
                'tertiary' => '#fcbad3',
                'quaternary' => '#ffffd2',
                'quinary' => '#d5f4e6',
            ],
            'dark' => [
                'primary' => '#254cdd',
                'secondary' => '#41a361',
                'tertiary' => '#b62d31',
                'quaternary' => '#dfb511',
                'quinary' => '#c82b74',
            ],
        ],
        'types' => [
            'activity' => [
                'title' => 'Atividade do Servidor',
                'description' => 'Mensagens por dia/hora',
                'type' => 'line',
                'data_source' => 'message_stats',
                'timeframes' => ['daily', 'weekly', 'monthly'],
            ],
            'members' => [
                'title' => 'Crescimento de Membros',
                'description' => 'Entradas/saídas de membros',
                'type' => 'line',
                'data_source' => 'member_stats',
                'show_joins' => true,
                'show_leaves' => true,
                'show_net' => true,
            ],
            'voice' => [
                'title' => 'Atividade de Voz',
                'description' => 'Tempo em canais de voz',
                'type' => 'bar',
                'data_source' => 'voice_stats',
                'per_channel' => true,
                'timeframes' => ['daily', 'weekly'],
            ],
            'commands' => [
                'title' => 'Uso de Comandos',
                'description' => 'Comandos mais utilizados',
                'type' => 'pie',
                'data_source' => 'command_stats',
                'limit' => 10, // Top 10 comandos
            ],
            'custom' => [
                'enabled' => true,
                'allowed_metrics' => [
                    'messages', 'voice_time', 'members', 'commands',
                    'reactions', 'warns', 'economy', 'level',
                ],
                'max_series' => 5,
                'permissions' => [
                    'roles' => [], // Cargos que podem criar gráficos personalizados
                ],
            ],
        ],
        'auto_charts' => [
            'enabled' => true,
            'frequency' => 'weekly', // daily, weekly, monthly
            'channel_id' => null, // Canal para enviar gráficos automáticos
            'types' => ['activity', 'members', 'commands'],
        ],
        'interaction' => [
            'enabled' => true,
            'allow_filtering' => true, // Permitir filtrar dados
            'allow_timeframe_change' => true, // Permitir mudar período
            'allow_type_change' => true, // Permitir mudar tipo de gráfico
        ],
        'export' => [
            'enabled' => true,
            'formats' => ['png', 'jpg', 'pdf'],
            'include_data' => true, // Incluir dados brutos
        ],
    ],
]; 