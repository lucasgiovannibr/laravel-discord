<?php

namespace LucasGiovanni\DiscordBotInstaller\Services;

use Discord\Discord;
use Discord\Parts\User\User;
use Discord\Parts\Channel\Channel;
use Discord\Parts\Guild\Guild;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Cache\Repository as Cache;
use Illuminate\Support\Facades\Queue;
use LucasGiovanni\DiscordBotInstaller\Models\DiscordLevel;
use LucasGiovanni\DiscordBotInstaller\Models\DiscordWarning;
use LucasGiovanni\DiscordBotInstaller\Models\DiscordReminder;
use LucasGiovanni\DiscordBotInstaller\Jobs\SendDiscordMessage;

class DiscordBotService
{
    /**
     * A instância do Discord
     *
     * @var Discord|null
     */
    protected ?Discord $discord = null;
    
    /**
     * O cliente está conectado
     *
     * @var bool
     */
    protected $connected = false;
    
    /**
     * O logger
     *
     * @var DiscordLogger
     */
    protected DiscordLogger $logger;
    
    /**
     * Cache
     *
     * @var Cache
     */
    protected Cache $cache;
    
    /**
     * Construtor
     */
    public function __construct(DiscordLogger $logger, Cache $cache)
    {
        $this->logger = $logger;
        $this->cache = $cache;
    }
    
    /**
     * Obtém ou inicializa a conexão com o Discord
     */
    public function getClient(): ?Discord
    {
        if ($this->discord === null) {
            $this->discord = $this->initializeClient();
        }
        
        return $this->discord;
    }
    
    /**
     * Inicializa o cliente Discord
     */
    protected function initializeClient(): Discord
    {
        $token = config('discordbot.token');
        
        if (empty($token)) {
            $this->logger->error('Token do Discord não configurado');
            return null;
        }
        
        $intents = 0;
        $intentConfig = config('discordbot.advanced.intents', []);
        
        // Discord\WebSockets\Intents
        // Configurar intents com base na configuração
        if (!empty($intentConfig)) {
            // Adicionar intents padrão
            $intents = 32767; // Todas exceto as privilegiadas
            
            // Adicionar ou remover intents privilegiadas
            if (isset($intentConfig['message_content']) && $intentConfig['message_content']) {
                $intents |= 32768; // MESSAGE_CONTENT
            }
            
            if (isset($intentConfig['guild_presences']) && $intentConfig['guild_presences']) {
                $intents |= 8; // GUILD_PRESENCES
            }
            
            if (isset($intentConfig['guild_members']) && $intentConfig['guild_members']) {
                $intents |= 2; // GUILD_MEMBERS
            }
        }
        
        try {
            $this->discord = new Discord([
                'token' => $token,
                'intents' => $intents,
            ]);
            
            $this->connected = true;
            
            $this->logger->info('Cliente Discord inicializado com sucesso');
        } catch (\Exception $e) {
            $this->logger->error('Erro ao inicializar cliente Discord', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            $this->discord = null;
            $this->connected = false;
        }
        
        return $this->discord;
    }
    
    /**
     * Envia uma mensagem para um canal
     */
    public function sendMessage(string $channelId, string $message, array $embed = null): void
    {
        try {
            $client = $this->getClient();
            
            if (!$client) {
                throw new \Exception('Cliente Discord não inicializado');
            }
            
            $channel = $client->getChannel($channelId);
            
            if (!$channel) {
                throw new \Exception('Canal não encontrado: ' . $channelId);
            }
            
            if ($embed) {
                $channel->sendMessage($message, false, $embed);
            } else {
                $channel->sendMessage($message);
            }
            
            $this->logger->info('Mensagem enviada', [
                'channel_id' => $channelId,
                'has_embed' => !empty($embed)
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Erro ao enviar mensagem', [
                'error' => $e->getMessage(),
                'channel_id' => $channelId
            ]);
            
            throw $e;
        }
    }
    
    /**
     * Registra um slash command
     */
    public function registerSlashCommand(string $name, string $description, array $options = []): void
    {
        try {
            $client = $this->getClient();
            
            if (!$client) {
                throw new \Exception('Cliente Discord não inicializado');
            }
            
            $command = [
                'name' => $name,
                'description' => $description,
                'options' => $options
            ];
            
            $guildId = config('discordbot.slash_commands.guild_id');
            
            if ($guildId) {
                // Registrar comando apenas para um servidor específico (desenvolvimento)
                $client->application->commands->save($command, $guildId);
                $this->logger->info("Slash command '{$name}' registrado para o servidor {$guildId}");
            } else {
                // Registrar comando global
                $client->application->commands->save($command);
                $this->logger->info("Slash command '{$name}' registrado globalmente");
            }
        } catch (\Exception $e) {
            $this->logger->error("Erro ao registrar slash command '{$name}'", [
                'error' => $e->getMessage(),
                'command' => $name
            ]);
            
            throw $e;
        }
    }
    
    /**
     * Agenda uma mensagem para ser enviada posteriormente
     */
    public function scheduleMessage(string $channelId, string $message, Carbon $when, array $embed = null): void
    {
        try {
            // Validar se o canal existe
            $client = $this->getClient();
            
            if (!$client) {
                throw new \Exception('Cliente Discord não inicializado');
            }
            
            $channel = $client->getChannel($channelId);
            
            if (!$channel) {
                throw new \Exception('Canal não encontrado: ' . $channelId);
            }
            
            // Usar o sistema de filas do Laravel para agendar a mensagem
            Queue::later($when, new SendDiscordMessage($channelId, $message, $embed));
            
            $this->logger->info('Mensagem agendada', [
                'channel_id' => $channelId,
                'when' => $when->toIso8601String(),
                'has_embed' => !empty($embed)
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Erro ao agendar mensagem', [
                'error' => $e->getMessage(),
                'channel_id' => $channelId,
                'when' => $when->toIso8601String()
            ]);
            
            throw $e;
        }
    }
    
    /**
     * Obtém a lista de servidores
     */
    public function getGuilds(): array
    {
        try {
            $client = $this->getClient();
            
            if (!$client) {
                throw new \Exception('Cliente Discord não inicializado');
            }
            
            $guilds = [];
            
            foreach ($client->guilds as $guild) {
                $guilds[] = [
                    'id' => $guild->id,
                    'name' => $guild->name,
                    'icon' => $guild->icon,
                    'member_count' => $guild->member_count,
                    'owner_id' => $guild->owner_id
                ];
            }
            
            return $guilds;
        } catch (\Exception $e) {
            $this->logger->error('Erro ao obter lista de servidores', [
                'error' => $e->getMessage()
            ]);
            
            return [];
        }
    }
    
    /**
     * Obtém a lista de canais de um servidor
     */
    public function getChannels(string $guildId = null): array
    {
        try {
            $client = $this->getClient();
            
            if (!$client) {
                throw new \Exception('Cliente Discord não inicializado');
            }
            
            $channels = [];
            
            // Se guildId for fornecido, busca apenas canais daquele servidor
            if ($guildId) {
                $guild = $client->guilds->get('id', $guildId);
                
                if (!$guild) {
                    throw new \Exception('Servidor não encontrado: ' . $guildId);
                }
                
                foreach ($guild->channels as $channel) {
                    $channels[] = $this->formatChannel($channel);
                }
            } else {
                // Busca todos os canais de todos os servidores
                foreach ($client->guilds as $guild) {
                    foreach ($guild->channels as $channel) {
                        $channels[] = $this->formatChannel($channel, $guild);
                    }
                }
            }
            
            return $channels;
        } catch (\Exception $e) {
            $this->logger->error('Erro ao obter lista de canais', [
                'error' => $e->getMessage(),
                'guild_id' => $guildId
            ]);
            
            return [];
        }
    }
    
    /**
     * Formata um canal para o formato padronizado
     */
    protected function formatChannel(Channel $channel, Guild $guild = null): array
    {
        return [
            'id' => $channel->id,
            'name' => $channel->name,
            'type' => $channel->type,
            'guild_id' => $guild ? $guild->id : $channel->guild_id,
            'guild_name' => $guild ? $guild->name : ($channel->guild ? $channel->guild->name : null),
            'position' => $channel->position,
            'parent_id' => $channel->parent_id,
            'nsfw' => $channel->nsfw
        ];
    }
    
    /**
     * Obtém a lista de membros de um servidor
     */
    public function getMembers(string $guildId): array
    {
        try {
            $client = $this->getClient();
            
            if (!$client) {
                throw new \Exception('Cliente Discord não inicializado');
            }
            
            $guild = $client->guilds->get('id', $guildId);
            
            if (!$guild) {
                throw new \Exception('Servidor não encontrado: ' . $guildId);
            }
            
            $members = [];
            
            foreach ($guild->members as $member) {
                $members[] = [
                    'id' => $member->id,
                    'username' => $member->username,
                    'discriminator' => $member->discriminator,
                    'avatar' => $member->avatar,
                    'nick' => $member->nick,
                    'roles' => $member->roles->map(function ($role) {
                        return [
                            'id' => $role->id,
                            'name' => $role->name,
                            'color' => $role->color,
                            'position' => $role->position
                        ];
                    })->toArray(),
                    'joined_at' => $member->joined_at,
                    'bot' => $member->user->bot ?? false
                ];
            }
            
            return $members;
        } catch (\Exception $e) {
            $this->logger->error('Erro ao obter lista de membros', [
                'error' => $e->getMessage(),
                'guild_id' => $guildId
            ]);
            
            return [];
        }
    }
    
    /**
     * Obtém a lista de cargos de um servidor
     */
    public function getRoles(string $guildId): array
    {
        try {
            $client = $this->getClient();
            
            if (!$client) {
                throw new \Exception('Cliente Discord não inicializado');
            }
            
            $guild = $client->guilds->get('id', $guildId);
            
            if (!$guild) {
                throw new \Exception('Servidor não encontrado: ' . $guildId);
            }
            
            $roles = [];
            
            foreach ($guild->roles as $role) {
                $roles[] = [
                    'id' => $role->id,
                    'name' => $role->name,
                    'color' => $role->color,
                    'position' => $role->position,
                    'permissions' => $role->permissions->getPermissions(),
                    'mentionable' => $role->mentionable,
                    'managed' => $role->managed,
                    'hoist' => $role->hoist
                ];
            }
            
            return $roles;
        } catch (\Exception $e) {
            $this->logger->error('Erro ao obter lista de cargos', [
                'error' => $e->getMessage(),
                'guild_id' => $guildId
            ]);
            
            return [];
        }
    }
    
    /**
     * Obtém o nível de um usuário em um servidor
     */
    public function getUserLevel(string $userId, string $guildId): ?DiscordLevel
    {
        try {
            return DiscordLevel::findOrCreateFor($userId, $guildId);
        } catch (\Exception $e) {
            $this->logger->error('Erro ao obter nível do usuário', [
                'error' => $e->getMessage(),
                'user_id' => $userId,
                'guild_id' => $guildId
            ]);
            
            return null;
        }
    }
    
    /**
     * Obtém o ranking de usuários em um servidor
     */
    public function getLeaderboard(string $guildId, int $limit = 10): array
    {
        try {
            return DiscordLevel::leaderboard($guildId, $limit)->toArray();
        } catch (\Exception $e) {
            $this->logger->error('Erro ao obter ranking', [
                'error' => $e->getMessage(),
                'guild_id' => $guildId
            ]);
            
            return [];
        }
    }
    
    /**
     * Obtém as advertências de um usuário em um servidor
     */
    public function getWarnings(string $userId, string $guildId): array
    {
        try {
            return DiscordWarning::where('user_id', $userId)
                ->where('server_id', $guildId)
                ->orderBy('created_at', 'desc')
                ->get()
                ->toArray();
        } catch (\Exception $e) {
            $this->logger->error('Erro ao obter advertências do usuário', [
                'error' => $e->getMessage(),
                'user_id' => $userId,
                'guild_id' => $guildId
            ]);
            
            return [];
        }
    }
    
    /**
     * Adiciona experiência para um usuário
     */
    public function addUserExperience(string $userId, string $guildId, int $amount): bool
    {
        try {
            $level = $this->getUserLevel($userId, $guildId);
            
            if (!$level) {
                return false;
            }
            
            // Verificar restrições de cooldown
            $cooldown = config('discordbot.levels.xp_cooldown', 60);
            
            if (!$level->canGainXp($cooldown)) {
                return false;
            }
            
            // Adicionar XP e verificar se subiu de nível
            $leveledUp = $level->addXp($amount);
            
            // Se subiu de nível e as notificações estão ativadas
            if ($leveledUp && config('discordbot.levels.announce_level_up', true)) {
                // Enviar notificação de subida de nível (pode ser implementado depois)
                $this->checkLevelRoles($userId, $guildId, $level->level);
            }
            
            return $leveledUp;
        } catch (\Exception $e) {
            $this->logger->error('Erro ao adicionar experiência', [
                'error' => $e->getMessage(),
                'user_id' => $userId,
                'guild_id' => $guildId,
                'amount' => $amount
            ]);
            
            return false;
        }
    }
    
    /**
     * Verifica e atribui cargos baseados em níveis
     */
    protected function checkLevelRoles(string $userId, string $guildId, int $level): void
    {
        try {
            $client = $this->getClient();
            
            if (!$client) {
                return;
            }
            
            $guild = $client->guilds->get('id', $guildId);
            
            if (!$guild) {
                return;
            }
            
            $member = $guild->members->get('id', $userId);
            
            if (!$member) {
                return;
            }
            
            // Obter configuração de cargos por nível
            $roleRewards = config('discordbot.levels.roles_rewards', []);
            
            // Verificar se o nível atual tem um cargo associado
            if (isset($roleRewards[$level]) && $roleRewards[$level]) {
                $roleId = $roleRewards[$level];
                
                // Atribuir o cargo ao membro
                $member->addRole($roleId)->done(function () use ($member, $roleId, $level) {
                    $this->logger->info('Cargo de nível atribuído', [
                        'user' => $member->username,
                        'level' => $level,
                        'role_id' => $roleId
                    ]);
                });
            }
        } catch (\Exception $e) {
            $this->logger->error('Erro ao verificar cargos de nível', [
                'error' => $e->getMessage(),
                'user_id' => $userId,
                'guild_id' => $guildId,
                'level' => $level
            ]);
        }
    }
    
    /**
     * Cria um lembrete
     */
    public function createReminder(string $userId, string $channelId, string $message, Carbon $when): ?DiscordReminder
    {
        try {
            $client = $this->getClient();
            
            if (!$client) {
                throw new \Exception('Cliente Discord não inicializado');
            }
            
            $channel = $client->getChannel($channelId);
            
            if (!$channel) {
                throw new \Exception('Canal não encontrado: ' . $channelId);
            }
            
            // Criar o registro no banco de dados
            $reminder = new DiscordReminder([
                'user_id' => $userId,
                'channel_id' => $channelId,
                'server_id' => $channel->guild_id ?? null,
                'message' => $message,
                'remind_at' => $when
            ]);
            
            $reminder->save();
            
            $this->logger->info('Lembrete criado', [
                'user_id' => $userId,
                'channel_id' => $channelId,
                'remind_at' => $when->toIso8601String()
            ]);
            
            return $reminder;
        } catch (\Exception $e) {
            $this->logger->error('Erro ao criar lembrete', [
                'error' => $e->getMessage(),
                'user_id' => $userId,
                'channel_id' => $channelId,
                'when' => $when->toIso8601String()
            ]);
            
            return null;
        }
    }
} 