<?php

namespace LucasGiovanni\DiscordBotInstaller\Services;

use Discord\Discord;
use Discord\Parts\Channel\Message;
use Discord\Parts\Guild\Guild;
use Discord\Parts\User\Member;
use LucasGiovanni\DiscordBotInstaller\Models\DiscordReactionRole;
use Illuminate\Support\Str;
use Exception;

class ReactionRoleService
{
    /**
     * @var Discord
     */
    protected $discord;
    
    /**
     * @var DiscordLogger
     */
    protected $logger;
    
    /**
     * Construtor
     *
     * @param Discord $discord
     * @param DiscordLogger $logger
     */
    public function __construct(Discord $discord, DiscordLogger $logger)
    {
        $this->discord = $discord;
        $this->logger = $logger;
    }
    
    /**
     * Cria uma nova configuração de reaction role
     *
     * @param string $guildId ID do servidor
     * @param string $channelId ID do canal
     * @param string $messageId ID da mensagem
     * @param string $emoji Emoji a ser usado (Unicode ou ID customizado)
     * @param string $roleId ID do cargo a ser concedido
     * @param string $creatorId ID do usuário que criou a configuração
     * @param string|null $groupId ID do grupo (opcional, para agrupar reaction roles)
     * @param bool $isUnique Se o usuário só pode ter um cargo do grupo
     * @return DiscordReactionRole
     */
    public function createReactionRole(
        string $guildId,
        string $channelId,
        string $messageId,
        string $emoji,
        string $roleId,
        string $creatorId,
        ?string $groupId = null,
        bool $isUnique = false
    ): DiscordReactionRole {
        // Verifica se já existe uma configuração idêntica
        $existing = DiscordReactionRole::where('message_id', $messageId)
                                      ->where('emoji', $emoji)
                                      ->first();
        
        if ($existing) {
            throw new Exception('Já existe uma configuração de reaction role para este emoji nesta mensagem.');
        }
        
        // Cria o grupo se necessário
        if (!$groupId && $isUnique) {
            $groupId = Str::uuid()->toString();
        }
        
        // Cria a nova configuração
        $reactionRole = new DiscordReactionRole([
            'guild_id' => $guildId,
            'channel_id' => $channelId,
            'message_id' => $messageId,
            'emoji' => $emoji,
            'role_id' => $roleId,
            'group_id' => $groupId,
            'created_by' => $creatorId,
            'is_unique' => $isUnique
        ]);
        
        $reactionRole->save();
        
        // Adiciona a reação à mensagem
        $this->addReactionToMessage($channelId, $messageId, $emoji);
        
        // Registra a criação
        $this->logger->info('Reaction role criado', [
            'guild_id' => $guildId,
            'channel_id' => $channelId,
            'message_id' => $messageId,
            'emoji' => $emoji,
            'role_id' => $roleId,
            'creator_id' => $creatorId
        ]);
        
        return $reactionRole;
    }
    
    /**
     * Cria várias configurações de reaction role de uma vez
     *
     * @param string $guildId ID do servidor
     * @param string $channelId ID do canal
     * @param string $messageId ID da mensagem
     * @param array $emojiToRoleMap Mapa de emoji => roleId
     * @param string $creatorId ID do usuário que criou a configuração
     * @param string|null $groupId ID do grupo (opcional)
     * @param bool $isUnique Se o usuário só pode ter um cargo do grupo
     * @return array Array de DiscordReactionRole
     */
    public function createMultipleReactionRoles(
        string $guildId,
        string $channelId,
        string $messageId,
        array $emojiToRoleMap,
        string $creatorId,
        ?string $groupId = null,
        bool $isUnique = false
    ): array {
        // Cria o grupo se necessário
        if (!$groupId && $isUnique) {
            $groupId = Str::uuid()->toString();
        }
        
        $results = [];
        
        foreach ($emojiToRoleMap as $emoji => $roleId) {
            try {
                $reactionRole = $this->createReactionRole(
                    $guildId,
                    $channelId,
                    $messageId,
                    $emoji,
                    $roleId,
                    $creatorId,
                    $groupId,
                    $isUnique
                );
                
                $results[] = $reactionRole;
            } catch (Exception $e) {
                $this->logger->warning('Falha ao criar reaction role', [
                    'error' => $e->getMessage(),
                    'emoji' => $emoji,
                    'role_id' => $roleId
                ]);
            }
        }
        
        return $results;
    }
    
    /**
     * Remove uma configuração de reaction role
     *
     * @param string $guildId ID do servidor
     * @param string $messageId ID da mensagem
     * @param string $emoji Emoji a ser removido
     * @return bool
     */
    public function removeReactionRole(string $guildId, string $messageId, string $emoji): bool
    {
        $reactionRole = DiscordReactionRole::where('guild_id', $guildId)
                                          ->where('message_id', $messageId)
                                          ->where('emoji', $emoji)
                                          ->first();
        
        if (!$reactionRole) {
            return false;
        }
        
        // Remove a reação da mensagem (do bot)
        try {
            $channel = $this->discord->getChannel($reactionRole->channel_id);
            if ($channel) {
                $channel->messages->fetch($messageId)->done(function ($message) use ($emoji) {
                    if ($message) {
                        $message->deleteReaction($emoji);
                    }
                });
            }
        } catch (Exception $e) {
            $this->logger->warning('Falha ao remover reação da mensagem', [
                'error' => $e->getMessage(),
                'message_id' => $messageId,
                'emoji' => $emoji
            ]);
        }
        
        // Remove do banco de dados
        $reactionRole->delete();
        
        // Registra a remoção
        $this->logger->info('Reaction role removido', [
            'guild_id' => $guildId,
            'message_id' => $messageId,
            'emoji' => $emoji,
            'role_id' => $reactionRole->role_id
        ]);
        
        return true;
    }
    
    /**
     * Remove todas as configurações de reaction role para uma mensagem
     *
     * @param string $guildId ID do servidor
     * @param string $messageId ID da mensagem
     * @return int Número de configurações removidas
     */
    public function removeAllReactionRoles(string $guildId, string $messageId): int
    {
        $reactionRoles = DiscordReactionRole::where('guild_id', $guildId)
                                           ->where('message_id', $messageId)
                                           ->get();
        
        if ($reactionRoles->isEmpty()) {
            return 0;
        }
        
        // Remove todas as reações da mensagem
        try {
            $channelId = $reactionRoles->first()->channel_id;
            $channel = $this->discord->getChannel($channelId);
            
            if ($channel) {
                $channel->messages->fetch($messageId)->done(function ($message) {
                    if ($message) {
                        $message->deleteAllReactions();
                    }
                });
            }
        } catch (Exception $e) {
            $this->logger->warning('Falha ao remover todas as reações da mensagem', [
                'error' => $e->getMessage(),
                'message_id' => $messageId
            ]);
        }
        
        // Remove do banco de dados
        $count = DiscordReactionRole::where('guild_id', $guildId)
                                   ->where('message_id', $messageId)
                                   ->delete();
        
        // Registra a remoção
        $this->logger->info('Todas as reaction roles removidas', [
            'guild_id' => $guildId,
            'message_id' => $messageId,
            'count' => $count
        ]);
        
        return $count;
    }
    
    /**
     * Manipula o evento de adição de reação
     *
     * @param Guild $guild Servidor
     * @param Message $message Mensagem
     * @param string $emoji Emoji adicionado
     * @param string $userId ID do usuário que adicionou a reação
     * @return bool
     */
    public function handleReactionAdd(Guild $guild, Message $message, string $emoji, string $userId): bool
    {
        // Ignora reações do próprio bot
        if ($userId === $this->discord->id) {
            return false;
        }
        
        // Busca a configuração
        $reactionRole = DiscordReactionRole::where('guild_id', $guild->id)
                                          ->where('message_id', $message->id)
                                          ->where('emoji', $emoji)
                                          ->first();
        
        if (!$reactionRole) {
            return false;
        }
        
        // Se for um cargo único no grupo, remove outros cargos do mesmo grupo
        if ($reactionRole->is_unique && $reactionRole->group_id) {
            $this->removeOtherGroupRoles($guild, $userId, $reactionRole->group_id, $reactionRole->role_id);
        }
        
        // Adiciona o cargo ao usuário
        try {
            $member = $guild->members->get('id', $userId);
            if (!$member) {
                $guild->members->fetch($userId)->done(function ($member) use ($reactionRole, $emoji) {
                    $this->addRoleToMember($member, $reactionRole->role_id, $emoji);
                });
            } else {
                $this->addRoleToMember($member, $reactionRole->role_id, $emoji);
            }
            
            return true;
        } catch (Exception $e) {
            $this->logger->error('Falha ao adicionar cargo via reaction role', [
                'error' => $e->getMessage(),
                'user_id' => $userId,
                'guild_id' => $guild->id,
                'role_id' => $reactionRole->role_id
            ]);
            
            return false;
        }
    }
    
    /**
     * Manipula o evento de remoção de reação
     *
     * @param Guild $guild Servidor
     * @param Message $message Mensagem
     * @param string $emoji Emoji removido
     * @param string $userId ID do usuário que removeu a reação
     * @return bool
     */
    public function handleReactionRemove(Guild $guild, Message $message, string $emoji, string $userId): bool
    {
        // Ignora reações do próprio bot
        if ($userId === $this->discord->id) {
            return false;
        }
        
        // Busca a configuração
        $reactionRole = DiscordReactionRole::where('guild_id', $guild->id)
                                          ->where('message_id', $message->id)
                                          ->where('emoji', $emoji)
                                          ->first();
        
        if (!$reactionRole) {
            return false;
        }
        
        // Remove o cargo do usuário
        try {
            $member = $guild->members->get('id', $userId);
            if (!$member) {
                $guild->members->fetch($userId)->done(function ($member) use ($reactionRole, $emoji) {
                    $this->removeRoleFromMember($member, $reactionRole->role_id, $emoji);
                });
            } else {
                $this->removeRoleFromMember($member, $reactionRole->role_id, $emoji);
            }
            
            return true;
        } catch (Exception $e) {
            $this->logger->error('Falha ao remover cargo via reaction role', [
                'error' => $e->getMessage(),
                'user_id' => $userId,
                'guild_id' => $guild->id,
                'role_id' => $reactionRole->role_id
            ]);
            
            return false;
        }
    }
    
    /**
     * Adiciona uma reação a uma mensagem
     *
     * @param string $channelId
     * @param string $messageId
     * @param string $emoji
     * @return void
     */
    protected function addReactionToMessage(string $channelId, string $messageId, string $emoji): void
    {
        $channel = $this->discord->getChannel($channelId);
        
        if (!$channel) {
            throw new Exception('Canal não encontrado: ' . $channelId);
        }
        
        $channel->messages->fetch($messageId)->done(function ($message) use ($emoji) {
            if (!$message) {
                throw new Exception('Mensagem não encontrada');
            }
            
            $message->react($emoji);
        });
    }
    
    /**
     * Adiciona um cargo a um membro
     *
     * @param Member $member
     * @param string $roleId
     * @param string $emoji
     * @return void
     */
    protected function addRoleToMember(Member $member, string $roleId, string $emoji): void
    {
        // Verifica se o usuário já possui o cargo
        if ($member->roles->has($roleId)) {
            return;
        }
        
        // Adiciona o cargo
        $member->addRole($roleId)->done(function () use ($member, $roleId, $emoji) {
            $this->logger->info('Cargo adicionado via reaction role', [
                'user_id' => $member->id,
                'guild_id' => $member->guild_id,
                'role_id' => $roleId,
                'emoji' => $emoji
            ]);
        });
    }
    
    /**
     * Remove um cargo de um membro
     *
     * @param Member $member
     * @param string $roleId
     * @param string $emoji
     * @return void
     */
    protected function removeRoleFromMember(Member $member, string $roleId, string $emoji): void
    {
        // Verifica se o usuário possui o cargo
        if (!$member->roles->has($roleId)) {
            return;
        }
        
        // Remove o cargo
        $member->removeRole($roleId)->done(function () use ($member, $roleId, $emoji) {
            $this->logger->info('Cargo removido via reaction role', [
                'user_id' => $member->id,
                'guild_id' => $member->guild_id,
                'role_id' => $roleId,
                'emoji' => $emoji
            ]);
        });
    }
    
    /**
     * Remove outros cargos do mesmo grupo
     *
     * @param Guild $guild
     * @param string $userId
     * @param string $groupId
     * @param string $excludeRoleId Cargo a ser mantido
     * @return void
     */
    protected function removeOtherGroupRoles(Guild $guild, string $userId, string $groupId, string $excludeRoleId): void
    {
        // Busca todos os reaction roles do mesmo grupo
        $groupRoles = DiscordReactionRole::where('guild_id', $guild->id)
                                        ->where('group_id', $groupId)
                                        ->where('role_id', '!=', $excludeRoleId)
                                        ->get();
        
        if ($groupRoles->isEmpty()) {
            return;
        }
        
        // Obtém o membro
        $member = $guild->members->get('id', $userId);
        if (!$member) {
            $guild->members->fetch($userId)->done(function ($member) use ($groupRoles) {
                $this->removeRolesFromMember($member, $groupRoles);
            });
        } else {
            $this->removeRolesFromMember($member, $groupRoles);
        }
    }
    
    /**
     * Remove vários cargos de um membro
     *
     * @param Member $member
     * @param \Illuminate\Support\Collection $reactionRoles
     * @return void
     */
    protected function removeRolesFromMember(Member $member, $reactionRoles): void
    {
        foreach ($reactionRoles as $reactionRole) {
            // Verifica se o usuário possui o cargo
            if ($member->roles->has($reactionRole->role_id)) {
                // Remove o cargo
                $member->removeRole($reactionRole->role_id)->done(function () use ($member, $reactionRole) {
                    $this->logger->info('Cargo exclusivo removido via reaction role', [
                        'user_id' => $member->id,
                        'guild_id' => $member->guild_id,
                        'role_id' => $reactionRole->role_id,
                        'group_id' => $reactionRole->group_id
                    ]);
                });
                
                // Também remove a reação correspondente
                try {
                    $channel = $this->discord->getChannel($reactionRole->channel_id);
                    if ($channel) {
                        $channel->messages->fetch($reactionRole->message_id)->done(function ($message) use ($reactionRole, $member) {
                            if ($message) {
                                $message->deleteReaction($reactionRole->emoji, $member->id);
                            }
                        });
                    }
                } catch (Exception $e) {
                    $this->logger->warning('Falha ao remover reação ao trocar cargo exclusivo', [
                        'error' => $e->getMessage(),
                        'user_id' => $member->id,
                        'message_id' => $reactionRole->message_id,
                        'emoji' => $reactionRole->emoji
                    ]);
                }
            }
        }
    }
    
    /**
     * Obtém todas as configurações de reaction role para um servidor
     *
     * @param string $guildId
     * @return \Illuminate\Support\Collection
     */
    public function getGuildReactionRoles(string $guildId)
    {
        return DiscordReactionRole::where('guild_id', $guildId)->get();
    }
    
    /**
     * Sincroniza todas as reaction roles ao iniciar o bot
     *
     * @return void
     */
    public function syncAllReactionRoles(): void
    {
        $this->logger->info('Iniciando sincronização de reaction roles');
        
        // Busca todas as configurações
        $allReactionRoles = DiscordReactionRole::all();
        
        // Agrupa por mensagem
        $byMessage = [];
        foreach ($allReactionRoles as $reactionRole) {
            $key = "{$reactionRole->channel_id}:{$reactionRole->message_id}";
            if (!isset($byMessage[$key])) {
                $byMessage[$key] = [];
            }
            $byMessage[$key][] = $reactionRole;
        }
        
        // Sincroniza cada mensagem
        foreach ($byMessage as $key => $roles) {
            [$channelId, $messageId] = explode(':', $key);
            
            try {
                $channel = $this->discord->getChannel($channelId);
                if (!$channel) continue;
                
                $channel->messages->fetch($messageId)->done(function ($message) use ($roles) {
                    if (!$message) return;
                    
                    // Adiciona as reações que faltam
                    foreach ($roles as $role) {
                        if (!isset($message->reactions[$role->emoji])) {
                            $message->react($role->emoji);
                        }
                    }
                });
            } catch (Exception $e) {
                $this->logger->error('Falha ao sincronizar reaction roles para mensagem', [
                    'error' => $e->getMessage(),
                    'channel_id' => $channelId,
                    'message_id' => $messageId
                ]);
            }
        }
        
        $this->logger->info('Sincronização de reaction roles concluída', [
            'total' => $allReactionRoles->count(),
            'messages' => count($byMessage)
        ]);
    }
} 