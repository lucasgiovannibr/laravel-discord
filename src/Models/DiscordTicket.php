<?php

namespace LucasGiovanni\DiscordBotInstaller\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class DiscordTicket extends Model
{
    /**
     * A tabela associada ao modelo.
     *
     * @var string
     */
    protected $table = 'discord_tickets';

    /**
     * Os atributos que são atribuíveis em massa.
     *
     * @var array
     */
    protected $fillable = [
        'guild_id',
        'channel_id',
        'user_id',
        'title',
        'description',
        'type',
        'status',
        'priority',
        'closed_by',
        'closed_at',
        'closed_reason',
        'last_activity',
        'assigned_to',
        'rating',
        'metadata',
    ];

    /**
     * Os atributos que devem ser convertidos.
     *
     * @var array
     */
    protected $casts = [
        'last_activity' => 'datetime',
        'closed_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Status possíveis para tickets
     */
    const STATUS_OPEN = 'open';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_ON_HOLD = 'on_hold';
    const STATUS_SOLVED = 'solved';
    const STATUS_CLOSED = 'closed';

    /**
     * Níveis de prioridade
     */
    const PRIORITY_LOW = 'low';
    const PRIORITY_MEDIUM = 'medium';
    const PRIORITY_HIGH = 'high';
    const PRIORITY_CRITICAL = 'critical';

    /**
     * Tipos de ticket
     */
    const TYPE_SUPPORT = 'support';
    const TYPE_REPORT = 'report';
    const TYPE_SUGGESTION = 'suggestion';
    const TYPE_OTHER = 'other';

    /**
     * Retorna o usuário do Discord associado a este ticket.
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(DiscordUser::class, 'user_id', 'user_id');
    }

    /**
     * Retorna o usuário do Discord que fechou este ticket.
     *
     * @return BelongsTo|null
     */
    public function closedByUser(): ?BelongsTo
    {
        if (!$this->closed_by) {
            return null;
        }
        
        return $this->belongsTo(DiscordUser::class, 'closed_by', 'user_id');
    }

    /**
     * Verifica se o ticket está aberto
     *
     * @return bool
     */
    public function isOpen(): bool
    {
        return $this->status === self::STATUS_OPEN || $this->status === self::STATUS_IN_PROGRESS || $this->status === self::STATUS_ON_HOLD;
    }

    /**
     * Verifica se o ticket está fechado
     *
     * @return bool
     */
    public function isClosed(): bool
    {
        return $this->status === self::STATUS_CLOSED || $this->status === self::STATUS_SOLVED;
    }

    /**
     * Atualiza a última atividade do ticket
     *
     * @return bool
     */
    public function updateActivity(): bool
    {
        $this->last_activity = Carbon::now();
        return $this->save();
    }

    /**
     * Fecha o ticket
     *
     * @param string $userId ID do usuário que está fechando o ticket
     * @param string $status Status para definir (solved ou closed)
     * @param string|null $transcriptUrl URL para a transcrição do ticket
     * @return bool
     */
    public function close(string $userId, string $status = self::STATUS_CLOSED, ?string $transcriptUrl = null): bool
    {
        if (!in_array($status, [self::STATUS_CLOSED, self::STATUS_SOLVED])) {
            $status = self::STATUS_CLOSED;
        }
        
        $this->status = $status;
        $this->closed_by = $userId;
        $this->closed_at = Carbon::now();
        
        if ($transcriptUrl) {
            $this->transcript_url = $transcriptUrl;
        }
        
        return $this->save();
    }

    /**
     * Reabre o ticket
     *
     * @return bool
     */
    public function reopen(): bool
    {
        if (!$this->isClosed()) {
            return false;
        }
        
        $this->status = self::STATUS_OPEN;
        $this->closed_by = null;
        $this->closed_at = null;
        $this->updateActivity();
        
        return $this->save();
    }

    /**
     * Scope para tickets abertos
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOpen($query)
    {
        return $query->whereIn('status', [self::STATUS_OPEN, self::STATUS_IN_PROGRESS, self::STATUS_ON_HOLD]);
    }

    /**
     * Scope para tickets fechados
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeClosed($query)
    {
        return $query->whereIn('status', [self::STATUS_CLOSED, self::STATUS_SOLVED]);
    }

    /**
     * Scope para tickets inativos há X horas
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $hours
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInactiveSince($query, int $hours)
    {
        return $query->where('status', self::STATUS_OPEN)
                     ->where('last_activity', '<=', Carbon::now()->subHours($hours));
    }

    /**
     * Scope para tickets de um usuário específico
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $userId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOfUser($query, string $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope para tickets em um servidor específico
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
     * Relacionamento com as mensagens de log do ticket
     * 
     * @return HasMany
     */
    public function messages(): HasMany
    {
        return $this->hasMany(DiscordTicketMessage::class, 'ticket_id');
    }

    /**
     * Relacionamento com as notas privadas do ticket
     * 
     * @return HasMany
     */
    public function notes(): HasMany
    {
        return $this->hasMany(DiscordTicketNote::class, 'ticket_id');
    }

    /**
     * Obtém os tickets abertos para um usuário
     * 
     * @param string $userId ID do usuário
     * @param string $guildId ID do servidor
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getOpenTicketsForUser(string $userId, string $guildId)
    {
        return self::where('user_id', $userId)
                   ->where('guild_id', $guildId)
                   ->whereNotIn('status', [self::STATUS_CLOSED, self::STATUS_SOLVED])
                   ->get();
    }

    /**
     * Verifica se um usuário atingiu o limite de tickets
     * 
     * @param string $userId ID do usuário
     * @param string $guildId ID do servidor
     * @return bool
     */
    public static function hasReachedLimit(string $userId, string $guildId): bool
    {
        $ticketLimit = config('discordbot.tickets.ticket_limit', 5);
        $openTicketsCount = self::getOpenTicketsForUser($userId, $guildId)->count();
        
        return $openTicketsCount >= $ticketLimit;
    }

    /**
     * Cria um novo ticket no sistema
     * 
     * @param array $data Dados do ticket
     * @return self|null
     */
    public static function createTicket(array $data): ?self
    {
        try {
            // Verificar se o usuário atingiu o limite
            if (self::hasReachedLimit($data['user_id'], $data['guild_id'])) {
                throw new \Exception('Limite de tickets ativos atingido.');
            }
            
            // Determinar o tipo de ticket e validar campos obrigatórios
            $type = $data['type'] ?? self::TYPE_SUPPORT;
            $ticketTypes = config('discordbot.tickets.types', []);
            
            if (isset($ticketTypes[$type]['custom_form']) && 
                $ticketTypes[$type]['custom_form'] && 
                isset($ticketTypes[$type]['required_fields'])) {
                
                foreach ($ticketTypes[$type]['required_fields'] as $requiredField) {
                    if (!isset($data['metadata'][$requiredField]) || empty($data['metadata'][$requiredField])) {
                        throw new \Exception("Campo obrigatório '{$requiredField}' não fornecido.");
                    }
                }
            }
            
            // Criar o canal do ticket no Discord
            $channelId = self::createTicketChannel($data);
            
            if (!$channelId) {
                throw new \Exception('Não foi possível criar o canal do ticket.');
            }
            
            // Criar o registro do ticket no banco de dados
            $ticket = self::create([
                'guild_id' => $data['guild_id'],
                'channel_id' => $channelId,
                'user_id' => $data['user_id'],
                'title' => $data['title'] ?? "Ticket #{$data['user_id']}",
                'description' => $data['description'] ?? '',
                'type' => $type,
                'status' => self::STATUS_OPEN,
                'priority' => $data['priority'] ?? self::PRIORITY_MEDIUM,
                'last_activity' => now(),
                'metadata' => $data['metadata'] ?? [],
            ]);
            
            // Enviar mensagem inicial
            self::sendInitialMessage($ticket);
            
            // Registrar primeira mensagem de log
            $ticket->messages()->create([
                'message_id' => null,
                'user_id' => $data['user_id'], 
                'content' => 'Ticket criado',
                'type' => 'system',
            ]);
            
            return $ticket;
        } catch (\Exception $e) {
            app('discord.logger')->error('Erro ao criar ticket', [
                'error' => $e->getMessage(),
                'user_id' => $data['user_id'] ?? null,
            ]);
            
            return null;
        }
    }

    /**
     * Cria o canal do ticket no Discord
     * 
     * @param array $data Dados do ticket
     * @return string|null ID do canal criado
     */
    protected static function createTicketChannel(array $data): ?string
    {
        try {
            $guild = $data['guild_id'];
            $userId = $data['user_id'];
            $type = $data['type'] ?? self::TYPE_SUPPORT;
            
            // Buscar configurações
            $categoryId = config('discordbot.tickets.category_id');
            $supportRoles = config('discordbot.tickets.support_roles', []);
            
            // Determinar o nome do canal
            $username = app('discord')->getUsername($userId, $guild) ?? $userId;
            $username = preg_replace('/[^a-z0-9]/i', '', $username); // Limpar o nome de usuário
            
            $ticketTypes = config('discordbot.tickets.types', []);
            $typePrefix = '';
            
            if (isset($ticketTypes[$type]['emoji'])) {
                $typePrefix = $ticketTypes[$type]['emoji'] . '・';
            }
            
            $channelName = strtolower($typePrefix . 'ticket-' . $username);
            
            // Permissões do canal
            $permissions = [
                // Ocultar para todos
                [
                    'id' => $guild,
                    'type' => 'role',
                    'deny' => ['VIEW_CHANNEL'],
                ],
                // Permitir para o usuário
                [
                    'id' => $userId,
                    'type' => 'member',
                    'allow' => ['VIEW_CHANNEL', 'SEND_MESSAGES', 'EMBED_LINKS', 'ATTACH_FILES', 'READ_MESSAGE_HISTORY'],
                ],
                // Permitir para os cargos de suporte
                // (admin, mod, etc)
            ];
            
            foreach ($supportRoles as $roleId) {
                $permissions[] = [
                    'id' => $roleId,
                    'type' => 'role',
                    'allow' => ['VIEW_CHANNEL', 'SEND_MESSAGES', 'EMBED_LINKS', 'ATTACH_FILES', 'READ_MESSAGE_HISTORY', 'MANAGE_MESSAGES'],
                ];
            }
            
            // Criar o canal
            return app('discord')->createChannel($guild, [
                'name' => $channelName,
                'type' => 0, // Canal de texto
                'parent_id' => $categoryId,
                'permission_overwrites' => $permissions,
            ]);
        } catch (\Exception $e) {
            app('discord.logger')->error('Erro ao criar canal de ticket', [
                'error' => $e->getMessage(),
            ]);
            
            return null;
        }
    }

    /**
     * Envia a mensagem inicial no canal do ticket
     * 
     * @param self $ticket
     * @return void
     */
    protected static function sendInitialMessage(self $ticket): void
    {
        try {
            $type = $ticket->type;
            $ticketTypes = config('discordbot.tickets.types', []);
            
            // Determinar cor do embed baseado no tipo
            $color = 0x5865F2; // Padrão: Discord Blurple
            
            if (isset($ticketTypes[$type]['color'])) {
                $color = hexdec(str_replace('#', '', $ticketTypes[$type]['color']));
            }
            
            // Construir o embed
            $typeName = $ticketTypes[$type]['name'] ?? ucfirst($type);
            
            $embed = [
                'title' => "Ticket: {$ticket->title}",
                'description' => $ticket->description ?: config('discordbot.messages.ticket_created', 'Ticket criado por {user}. Use este canal para obter suporte.'),
                'color' => $color,
                'fields' => [
                    [
                        'name' => 'Tipo',
                        'value' => $typeName,
                        'inline' => true,
                    ],
                    [
                        'name' => 'Status',
                        'value' => 'Aberto',
                        'inline' => true,
                    ],
                    [
                        'name' => 'Prioridade',
                        'value' => ucfirst($ticket->priority),
                        'inline' => true,
                    ],
                ],
                'footer' => [
                    'text' => "ID: {$ticket->id} • Criado em " . $ticket->created_at->format('d/m/Y H:i')
                ]
            ];
            
            // Adicionar campos do metadata, se existirem
            if (!empty($ticket->metadata) && is_array($ticket->metadata)) {
                foreach ($ticket->metadata as $key => $value) {
                    if (!empty($value)) {
                        $embed['fields'][] = [
                            'name' => ucfirst($key),
                            'value' => $value,
                            'inline' => true,
                        ];
                    }
                }
            }
            
            // Menção ao usuário
            $message = "<@{$ticket->user_id}>";
            
            // Menção aos cargos de suporte, se configurado
            $supportRoles = config('discordbot.tickets.notifications.staff_ping', true);
            
            if ($supportRoles) {
                $roles = config('discordbot.tickets.support_roles', []);
                foreach ($roles as $roleId) {
                    $message .= " <@&{$roleId}>";
                }
            }
            
            // Componentes (botões)
            $components = [
                [
                    'type' => 1, // ActionRow
                    'components' => [
                        [
                            'type' => 2, // Button
                            'style' => 3, // Success (green)
                            'label' => 'Marcar como resolvido',
                            'custom_id' => "ticket:solve:{$ticket->id}",
                        ],
                        [
                            'type' => 2, // Button
                            'style' => 4, // Danger (red)
                            'label' => 'Fechar ticket',
                            'custom_id' => "ticket:close:{$ticket->id}",
                        ],
                    ]
                ]
            ];
            
            // Adicionar botões para equipe de suporte
            if (!empty(config('discordbot.tickets.support_roles', []))) {
                $components[] = [
                    'type' => 1, // ActionRow
                    'components' => [
                        [
                            'type' => 2, // Button
                            'style' => 1, // Primary (blue)
                            'label' => 'Assumir ticket',
                            'custom_id' => "ticket:assign:{$ticket->id}",
                        ],
                        [
                            'type' => 2, // Button
                            'style' => 2, // Secondary (grey)
                            'label' => 'Alterar prioridade',
                            'custom_id' => "ticket:priority:{$ticket->id}",
                        ],
                    ]
                ];
            }
            
            // Enviar a mensagem
            $messageId = app('discord')->sendMessage($ticket->channel_id, $message, $embed, $components);
            
            if ($messageId) {
                // Fixar a mensagem
                app('discord')->pinMessage($ticket->channel_id, $messageId);
                
                // Registrar mensagem de log
                $ticket->messages()->create([
                    'message_id' => $messageId,
                    'user_id' => app('discord')->getClientId(),
                    'content' => 'Mensagem inicial',
                    'type' => 'system',
                ]);
            }
        } catch (\Exception $e) {
            app('discord.logger')->error('Erro ao enviar mensagem inicial do ticket', [
                'error' => $e->getMessage(),
                'ticket_id' => $ticket->id,
            ]);
        }
    }

    /**
     * Fecha o ticket
     * 
     * @param string $userId ID do usuário que está fechando
     * @param string|null $reason Motivo do fechamento
     * @param bool $createTranscript Se deve criar um transcript
     * @return bool
     */
    public function closeTicket(string $userId, ?string $reason = null, bool $createTranscript = true): bool
    {
        try {
            // Verificar se o ticket já está fechado
            if ($this->status === self::STATUS_CLOSED) {
                return false;
            }
            
            // Atualizar o ticket
            $this->status = self::STATUS_CLOSED;
            $this->closed_by = $userId;
            $this->closed_at = now();
            $this->closed_reason = $reason;
            $this->save();
            
            // Registrar mensagem de fechamento
            $this->messages()->create([
                'message_id' => null,
                'user_id' => $userId,
                'content' => "Ticket fechado" . ($reason ? ": {$reason}" : ''),
                'type' => 'system',
            ]);
            
            // Enviar mensagem de fechamento
            $embed = [
                'title' => 'Ticket fechado',
                'description' => config('discordbot.messages.ticket_closed', 'Ticket fechado por {user}.'),
                'color' => 0xED4245, // Vermelho do Discord
                'fields' => []
            ];
            
            if ($reason) {
                $embed['fields'][] = [
                    'name' => 'Motivo',
                    'value' => $reason,
                    'inline' => false,
                ];
            }
            
            $components = [];
            
            // Adicionar componente para avaliar atendimento
            if (config('discordbot.tickets.features.ratings', true)) {
                $components[] = [
                    'type' => 1, // ActionRow
                    'components' => [
                        [
                            'type' => 2, // Button
                            'style' => 1, // Primary (blue)
                            'label' => 'Avaliar atendimento',
                            'custom_id' => "ticket:rate:{$this->id}",
                        ]
                    ]
                ];
            }
            
            // Enviar mensagem
            app('discord')->sendEmbed($this->channel_id, $embed, $components);
            
            // Criar transcrição
            if ($createTranscript && config('discordbot.tickets.transcript', true)) {
                $this->createTranscript();
            }
            
            // Modificar permissões do canal para impedir o usuário de enviar mensagens
            app('discord')->updateChannelPermission(
                $this->channel_id,
                $this->user_id,
                'member',
                ['SEND_MESSAGES'], // Deny
                ['VIEW_CHANNEL', 'READ_MESSAGE_HISTORY'] // Allow
            );
            
            return true;
        } catch (\Exception $e) {
            app('discord.logger')->error('Erro ao fechar ticket', [
                'error' => $e->getMessage(),
                'ticket_id' => $this->id,
            ]);
            
            return false;
        }
    }

    /**
     * Cria uma transcrição do ticket
     * 
     * @return string|null Caminho para o arquivo de transcrição
     */
    public function createTranscript(): ?string
    {
        // Implementação básica - na versão completa, isso gerará um arquivo HTML ou PDF
        // com todas as mensagens do ticket
        try {
            // Obter todas as mensagens do canal
            $messages = app('discord')->getChannelMessages($this->channel_id, 100);
            
            if (empty($messages)) {
                return null;
            }
            
            // Preparar o conteúdo da transcrição
            $content = "Transcrição do Ticket #{$this->id}\n";
            $content .= "Título: {$this->title}\n";
            $content .= "Tipo: {$this->type}\n";
            $content .= "Aberto por: {$this->user_id} em " . $this->created_at->format('d/m/Y H:i') . "\n";
            $content .= "Fechado por: {$this->closed_by} em " . ($this->closed_at ? $this->closed_at->format('d/m/Y H:i') : 'N/A') . "\n";
            $content .= "Motivo do fechamento: " . ($this->closed_reason ?: 'Não especificado') . "\n\n";
            $content .= "--- MENSAGENS ---\n\n";
            
            // Adicionar todas as mensagens (ordem inversa para ficar cronológica)
            foreach (array_reverse($messages) as $msg) {
                $author = $msg['author']['username'] ?? 'Desconhecido';
                $timestamp = Carbon::parse($msg['timestamp'])->format('d/m/Y H:i');
                $msgContent = $msg['content'] ?? '';
                
                if (empty($msgContent) && !empty($msg['embeds'])) {
                    $msgContent = '[Embed]';
                    
                    foreach ($msg['embeds'] as $embed) {
                        if (!empty($embed['title'])) {
                            $msgContent .= " {$embed['title']}";
                        }
                        
                        if (!empty($embed['description'])) {
                            $msgContent .= " - {$embed['description']}";
                        }
                    }
                }
                
                $content .= "[{$timestamp}] {$author}: {$msgContent}\n";
                
                // Adicionar anexos, se houver
                if (!empty($msg['attachments'])) {
                    foreach ($msg['attachments'] as $attachment) {
                        $content .= "Anexo: {$attachment['url']}\n";
                    }
                }
                
                $content .= "\n";
            }
            
            // Salvar a transcrição em um arquivo
            $filename = "ticket_{$this->id}_" . time() . ".txt";
            $path = storage_path("app/discord/transcripts/{$filename}");
            
            // Criar diretório se não existir
            if (!file_exists(dirname($path))) {
                mkdir(dirname($path), 0755, true);
            }
            
            file_put_contents($path, $content);
            
            // Enviar o arquivo como anexo no canal
            app('discord')->sendFile($this->channel_id, $path, "Transcrição do Ticket #{$this->id}");
            
            return $path;
        } catch (\Exception $e) {
            app('discord.logger')->error('Erro ao criar transcrição do ticket', [
                'error' => $e->getMessage(),
                'ticket_id' => $this->id,
            ]);
            
            return null;
        }
    }

    /**
     * Obtém tickets que precisam ser verificados para auto-fechamento
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getInactiveTickets()
    {
        $autoCloseHours = config('discordbot.tickets.auto_close', 48);
        
        if ($autoCloseHours <= 0) {
            return collect();
        }
        
        $cutoffTime = now()->subHours($autoCloseHours);
        
        return self::whereNotIn('status', [self::STATUS_CLOSED, self::STATUS_SOLVED])
                   ->where('last_activity', '<', $cutoffTime)
                   ->get();
    }

    /**
     * Atualiza o status do ticket
     * 
     * @param string $status Novo status
     * @param string $userId Usuário que fez a alteração
     * @return bool
     */
    public function updateStatus(string $status, string $userId): bool
    {
        $oldStatus = $this->status;
        
        if ($oldStatus === $status) {
            return true;
        }
        
        $this->status = $status;
        $this->last_activity = now();
        $saved = $this->save();
        
        if ($saved) {
            // Registrar mensagem de log
            $this->messages()->create([
                'message_id' => null,
                'user_id' => $userId,
                'content' => "Status alterado de {$oldStatus} para {$status}",
                'type' => 'system',
            ]);
            
            // Se for marcado como resolvido e closeOnSolved estiver ativado, fechar
            if ($status === self::STATUS_SOLVED && config('discordbot.tickets.close_on_solved', true)) {
                return $this->closeTicket($userId, 'Ticket marcado como resolvido');
            }
        }
        
        return $saved;
    }

    /**
     * Atualiza a prioridade do ticket
     * 
     * @param string $priority Nova prioridade
     * @param string $userId Usuário que fez a alteração
     * @return bool
     */
    public function updatePriority(string $priority, string $userId): bool
    {
        $oldPriority = $this->priority;
        
        if ($oldPriority === $priority) {
            return true;
        }
        
        $this->priority = $priority;
        $saved = $this->save();
        
        if ($saved) {
            // Registrar mensagem de log
            $this->messages()->create([
                'message_id' => null,
                'user_id' => $userId,
                'content' => "Prioridade alterada de {$oldPriority} para {$priority}",
                'type' => 'system',
            ]);
            
            // Atualizar a mensagem fixada, se existir
            $this->updatePinnedMessage();
        }
        
        return $saved;
    }

    /**
     * Atualiza a mensagem fixada com as informações atuais do ticket
     * 
     * @return void
     */
    protected function updatePinnedMessage(): void
    {
        try {
            // Implementação simplificada - a versão completa buscaria a mensagem fixada
            // e a atualizaria com os dados atuais
        } catch (\Exception $e) {
            app('discord.logger')->error('Erro ao atualizar mensagem fixada do ticket', [
                'error' => $e->getMessage(),
                'ticket_id' => $this->id,
            ]);
        }
    }
} 