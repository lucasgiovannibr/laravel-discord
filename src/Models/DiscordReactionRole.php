<?php

namespace LucasGiovanni\DiscordBotInstaller\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiscordReactionRole extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'discord_reaction_roles';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'guild_id',
        'channel_id',
        'message_id',
        'emoji',
        'role_id',
        'type',
        'group_id',
        'level_requirement',
        'required_role_id',
        'is_temporary',
        'temp_duration',
        'premium_only',
        'description',
        'created_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'level_requirement' => 'integer',
        'is_temporary' => 'boolean',
        'temp_duration' => 'integer',
        'premium_only' => 'boolean',
    ];

    /**
     * Relacionamento com o grupo de reaction roles
     *
     * @return BelongsTo
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(DiscordReactionRoleGroup::class, 'group_id');
    }

    /**
     * Verifica se um usuário atende aos requisitos para ganhar esta role
     *
     * @param string $userId ID do usuário
     * @param string $guildId ID do servidor
     * @return bool
     */
    public function checkRequirements(string $userId, string $guildId): bool
    {
        // Verificar requisito de nível mínimo
        if ($this->level_requirement > 0) {
            $userLevel = DiscordUserLevel::where('user_id', $userId)
                                        ->where('guild_id', $guildId)
                                        ->first();
            
            if (!$userLevel || $userLevel->level < $this->level_requirement) {
                return false;
            }
        }

        // Verificar cargo requerido
        if ($this->required_role_id) {
            $hasRole = app('discord')->hasRole($userId, $this->required_role_id, $guildId);
            
            if (!$hasRole) {
                return false;
            }
        }

        // Verificar requisito premium
        if ($this->premium_only) {
            $isPremium = DiscordUserPremium::where('user_id', $userId)
                                          ->where('is_active', true)
                                          ->exists();
            
            if (!$isPremium) {
                return false;
            }
        }

        return true;
    }

    /**
     * Adiciona a role ao usuário, aplicando lógica de temporário se necessário
     *
     * @param string $userId ID do usuário
     * @param string $guildId ID do servidor
     * @return bool
     */
    public function assignRoleToUser(string $userId, string $guildId): bool
    {
        try {
            // Verificar se já tem a role (para o caso de tipo toggle)
            $hasRole = app('discord')->hasRole($userId, $this->role_id, $guildId);
            
            // Tipo Toggle: remove a role se já tiver
            if ($this->type === 'toggle' && $hasRole) {
                return app('discord')->removeRoleFromMember($userId, $this->role_id, $guildId);
            }
            
            // Verifica requisitos
            if (!$this->checkRequirements($userId, $guildId)) {
                return false;
            }
            
            // Se for role temporária, registrar em tabela separada
            if ($this->is_temporary && $this->temp_duration > 0) {
                $expiresAt = now()->addMinutes($this->temp_duration);
                
                DiscordTemporaryRole::updateOrCreate(
                    [
                        'user_id' => $userId,
                        'guild_id' => $guildId,
                        'role_id' => $this->role_id,
                    ],
                    [
                        'expires_at' => $expiresAt,
                    ]
                );
            }
            
            // Verificar exclusividade de grupo
            if ($this->group_id && $this->group && $this->group->is_unique) {
                $otherGroupRoles = self::where('group_id', $this->group_id)
                                      ->where('id', '!=', $this->id)
                                      ->pluck('role_id')
                                      ->toArray();
                
                // Remover outras roles do mesmo grupo
                foreach ($otherGroupRoles as $roleId) {
                    app('discord')->removeRoleFromMember($userId, $roleId, $guildId);
                }
            }
            
            // Adicionar a role
            $success = app('discord')->addRoleToMember($userId, $this->role_id, $guildId);
            
            // Registrar na tabela de log
            if ($success) {
                DiscordRoleLog::create([
                    'user_id' => $userId,
                    'guild_id' => $guildId,
                    'role_id' => $this->role_id,
                    'action' => 'add',
                    'source' => 'reaction',
                    'source_id' => $this->id,
                ]);
            }
            
            return $success;
        } catch (\Exception $e) {
            app('discord.logger')->error('Erro ao atribuir reaction role', [
                'error' => $e->getMessage(),
                'reaction_role_id' => $this->id,
                'user_id' => $userId,
            ]);
            
            return false;
        }
    }

    /**
     * Remove a role do usuário
     *
     * @param string $userId ID do usuário
     * @param string $guildId ID do servidor
     * @return bool
     */
    public function removeRoleFromUser(string $userId, string $guildId): bool
    {
        try {
            // Remover a role
            $success = app('discord')->removeRoleFromMember($userId, $this->role_id, $guildId);
            
            // Remover registro temporário se existir
            if ($this->is_temporary) {
                DiscordTemporaryRole::where('user_id', $userId)
                                     ->where('guild_id', $guildId)
                                     ->where('role_id', $this->role_id)
                                     ->delete();
            }
            
            // Registrar na tabela de log
            if ($success) {
                DiscordRoleLog::create([
                    'user_id' => $userId,
                    'guild_id' => $guildId,
                    'role_id' => $this->role_id,
                    'action' => 'remove',
                    'source' => 'reaction',
                    'source_id' => $this->id,
                ]);
            }
            
            return $success;
        } catch (\Exception $e) {
            app('discord.logger')->error('Erro ao remover reaction role', [
                'error' => $e->getMessage(),
                'reaction_role_id' => $this->id,
                'user_id' => $userId,
            ]);
            
            return false;
        }
    }

    /**
     * Busca todas as configurações de reaction role para uma mensagem específica
     *
     * @param string $messageId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function findByMessage(string $messageId)
    {
        return self::where('message_id', $messageId)->get();
    }

    /**
     * Busca uma configuração de reaction role específica
     *
     * @param string $messageId
     * @param string $emoji
     * @return \LucasGiovanni\DiscordBotInstaller\Models\DiscordReactionRole|null
     */
    public static function findByMessageAndEmoji(string $messageId, string $emoji)
    {
        return self::where('message_id', $messageId)
                  ->where('emoji', $emoji)
                  ->first();
    }

    /**
     * Busca todas as configurações de reaction role para um grupo específico
     *
     * @param string $groupId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function findByGroup(string $groupId)
    {
        return self::where('group_id', $groupId)->get();
    }

    /**
     * Remove todas as configurações de reaction role para uma mensagem específica
     *
     * @param string $messageId
     * @return int
     */
    public static function deleteByMessage(string $messageId)
    {
        return self::where('message_id', $messageId)->delete();
    }

    /**
     * Scope para reaction roles em um servidor específico
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $guildId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInGuild($query, string $guildId)
    {
        return $query->where('guild_id', $guildId);
    }

    /**
     * Scope para reaction roles em um canal específico
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $channelId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInChannel($query, string $channelId)
    {
        return $query->where('channel_id', $channelId);
    }

    /**
     * Scope para reaction roles com um cargo específico
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $roleId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithRole($query, string $roleId)
    {
        return $query->where('role_id', $roleId);
    }

    /**
     * Scope para reaction roles que são únicas (só pode ter um cargo do grupo)
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeUnique($query)
    {
        return $query->where('is_unique', true);
    }
} 