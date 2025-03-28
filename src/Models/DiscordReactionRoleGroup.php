<?php

namespace LucasGiovanni\DiscordBotInstaller\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DiscordReactionRoleGroup extends Model
{
    /**
     * A tabela associada ao modelo.
     *
     * @var string
     */
    protected $table = 'discord_reaction_role_groups';

    /**
     * Os atributos que são atribuíveis em massa.
     *
     * @var array
     */
    protected $fillable = [
        'guild_id',
        'name',
        'description',
        'is_unique',
        'emoji_theme',
        'level_requirement',
        'required_role_id',
        'created_by',
        'is_active',
    ];

    /**
     * Os atributos que devem ser convertidos.
     *
     * @var array
     */
    protected $casts = [
        'is_unique' => 'boolean',
        'level_requirement' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Relacionamento com as reaction roles do grupo
     *
     * @return HasMany
     */
    public function reactionRoles(): HasMany
    {
        return $this->hasMany(DiscordReactionRole::class, 'group_id');
    }

    /**
     * Obter todos os grupo ativos para um servidor específico
     *
     * @param string $guildId ID do servidor
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getActiveGroupsForGuild(string $guildId)
    {
        return self::where('guild_id', $guildId)
                   ->where('is_active', true)
                   ->with('reactionRoles')
                   ->get();
    }

    /**
     * Verifica se um usuário atende aos requisitos do grupo
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

        return true;
    }

    /**
     * Cria uma mensagem com as reaction roles deste grupo
     *
     * @param string $channelId ID do canal onde enviar
     * @return string|null ID da mensagem criada, ou null em caso de erro
     */
    public function createMessage(string $channelId): ?string
    {
        try {
            $embed = [
                'title' => $this->name,
                'description' => $this->description ?? 'Selecione uma reação para receber o cargo correspondente.',
                'color' => 0x5865F2, // Cor Discord Blurple
                'fields' => [],
                'footer' => [
                    'text' => "ID do grupo: {$this->id} • Reação única: " . ($this->is_unique ? 'Sim' : 'Não')
                ]
            ];

            // Adicionar campos para cada reaction role
            foreach ($this->reactionRoles as $reactionRole) {
                $roleMention = "<@&{$reactionRole->role_id}>";
                $emoji = $reactionRole->emoji;
                
                $requirements = [];
                
                if ($reactionRole->level_requirement > 0) {
                    $requirements[] = "Nível mínimo: {$reactionRole->level_requirement}";
                }
                
                if ($reactionRole->required_role_id) {
                    $requirements[] = "Requer cargo: <@&{$reactionRole->required_role_id}>";
                }
                
                if ($reactionRole->is_temporary) {
                    $requirements[] = "Temporário: {$reactionRole->temp_duration} minutos";
                }
                
                if ($reactionRole->premium_only) {
                    $requirements[] = "Apenas premium";
                }
                
                $description = $reactionRole->description ?? '';
                
                if (!empty($requirements)) {
                    $description .= "\n" . implode(" • ", $requirements);
                }
                
                $embed['fields'][] = [
                    'name' => "{$emoji} {$roleMention}",
                    'value' => $description ?: "Reaja com {$emoji} para receber este cargo.",
                    'inline' => false
                ];
            }

            // Enviar a mensagem
            $messageId = app('discord')->sendEmbed($channelId, $embed);
            
            if ($messageId) {
                // Adicionar reações à mensagem
                foreach ($this->reactionRoles as $reactionRole) {
                    app('discord')->addReaction($channelId, $messageId, $reactionRole->emoji);
                    
                    // Atualizar o message_id e channel_id na reaction role
                    $reactionRole->update([
                        'message_id' => $messageId,
                        'channel_id' => $channelId,
                    ]);
                }
            }
            
            return $messageId;
        } catch (\Exception $e) {
            app('discord.logger')->error('Erro ao criar mensagem de reaction role', [
                'error' => $e->getMessage(),
                'group_id' => $this->id,
            ]);
            
            return null;
        }
    }
} 