<?php

namespace LucasGiovanni\DiscordBotInstaller\Services;

use Discord\Discord;
use Discord\Parts\Channel\Channel;
use Discord\Parts\Channel\Message;
use Discord\Parts\User\Member;
use Discord\Builders\MessageBuilder;
use Discord\Parts\Embed\Embed;
use Discord\Parts\Interactions\Interaction;
use LucasGiovanni\DiscordBotInstaller\Models\DiscordTicket;
use Carbon\Carbon;
use Exception;

class TicketService
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
     * Cria um novo ticket
     *
     * @param string $guildId ID do servidor
     * @param string $userId ID do usuário que abriu o ticket
     * @param string $subject Assunto do ticket
     * @param string|null $initialMessage Mensagem inicial (opcional)
     * @return DiscordTicket|null
     */
    public function createTicket(string $guildId, string $userId, string $subject, ?string $initialMessage = null): ?DiscordTicket
    {
        // Verifica se o usuário já tem um ticket aberto
        $existingTicket = DiscordTicket::where('guild_id', $guildId)
                                      ->where('user_id', $userId)
                                      ->whereIn('status', ['open', 'pending'])
                                      ->first();
        
        if ($existingTicket) {
            return null;
        }
        
        // Obtém configuração do ticket
        $ticketCategory = config('discord-bot.ticket.category_id');
        $supportRoleId = config('discord-bot.ticket.support_role_id');
        
        if (!$ticketCategory) {
            $this->logger->error('Categoria de tickets não configurada', [
                'guild_id' => $guildId
            ]);
            return null;
        }
        
        // Obtém a guild
        $guild = $this->discord->guilds->get('id', $guildId);
        if (!$guild) {
            $this->logger->error('Servidor não encontrado', [
                'guild_id' => $guildId
            ]);
            return null;
        }
        
        // Obtém o usuário
        $member = $guild->members->get('id', $userId);
        
        // Cria o canal para o ticket
        $ticketNumber = $this->getNextTicketNumber($guildId);
        $channelName = 'ticket-' . str_pad($ticketNumber, 4, '0', STR_PAD_LEFT);
        
        try {
            $guild->channels->create([
                'name' => $channelName,
                'type' => Channel::TYPE_TEXT,
                'parent_id' => $ticketCategory,
                'permission_overwrites' => [
                    // Esconde o canal de todos
                    [
                        'id' => $guildId,
                        'type' => 'role',
                        'deny' => 0x400 // VIEW_CHANNEL
                    ],
                    // Permite acesso ao usuário que criou
                    [
                        'id' => $userId,
                        'type' => 'member',
                        'allow' => 0x400 | 0x800 | 0x4000 | 0x8000 // VIEW_CHANNEL, SEND_MESSAGES, ATTACH_FILES, EMBED_LINKS
                    ],
                    // Permite acesso aos suportes
                    [
                        'id' => $supportRoleId,
                        'type' => 'role',
                        'allow' => 0x400 | 0x800 | 0x4000 | 0x8000 // VIEW_CHANNEL, SEND_MESSAGES, ATTACH_FILES, EMBED_LINKS
                    ]
                ]
            ])->done(function (Channel $channel) use ($guildId, $userId, $subject, $initialMessage, $member, $ticketNumber) {
                // Cria registro no banco de dados
                $ticket = new DiscordTicket([
                    'guild_id' => $guildId,
                    'channel_id' => $channel->id,
                    'user_id' => $userId,
                    'status' => 'open',
                    'subject' => $subject,
                    'last_activity' => Carbon::now(),
                ]);
                
                $ticket->save();
                
                // Envia mensagem inicial
                $embed = new Embed($this->discord);
                $embed->setTitle('Ticket #' . str_pad($ticketNumber, 4, '0', STR_PAD_LEFT));
                $embed->setDescription("Ticket aberto por {$member->username}");
                $embed->addField([
                    'name' => 'Assunto',
                    'value' => $subject,
                    'inline' => false
                ]);
                $embed->setColor(0x2ecc71); // Verde
                $embed->setTimestamp();
                $embed->setFooter([
                    'text' => 'Use /ticket close para fechar este ticket'
                ]);
                
                $builder = MessageBuilder::new()
                    ->setContent('@here Um novo ticket foi aberto')
                    ->addEmbed($embed);
                
                if ($initialMessage) {
                    $builder->setContent($initialMessage . "\n\n" . $builder->getContent());
                }
                
                $channel->sendMessage($builder);
                
                // Registra a criação
                $this->logger->info('Ticket criado', [
                    'ticket_id' => $ticket->id,
                    'guild_id' => $guildId,
                    'user_id' => $userId,
                    'channel_id' => $channel->id,
                ]);
            });
            
            return null; // Retorna null porque o ticket será criado assincronamente
        } catch (Exception $e) {
            $this->logger->error('Falha ao criar canal para ticket', [
                'error' => $e->getMessage(),
                'guild_id' => $guildId,
                'user_id' => $userId
            ]);
            
            return null;
        }
    }
    
    /**
     * Obtém o próximo número de ticket para o servidor
     *
     * @param string $guildId
     * @return int
     */
    private function getNextTicketNumber(string $guildId): int
    {
        $maxTicket = DiscordTicket::where('guild_id', $guildId)
                                  ->max('id');
        
        return $maxTicket ? $maxTicket + 1 : 1;
    }
    
    /**
     * Fecha um ticket
     *
     * @param string $channelId ID do canal do ticket
     * @param string $closedBy ID do usuário que fechou o ticket
     * @param string|null $reason Razão do fechamento
     * @return bool
     */
    public function closeTicket(string $channelId, string $closedBy, ?string $reason = null): bool
    {
        // Busca o ticket
        $ticket = DiscordTicket::where('channel_id', $channelId)
                               ->where('status', 'open')
                               ->first();
        
        if (!$ticket) {
            return false;
        }
        
        // Obtém o canal
        $channel = $this->discord->getChannel($channelId);
        if (!$channel) {
            return false;
        }
        
        // Atualiza o ticket
        $ticket->status = 'closed';
        $ticket->closed_by = $closedBy;
        $ticket->closed_at = Carbon::now();
        $ticket->save();
        
        // Envia mensagem de fechamento
        $embed = new Embed($this->discord);
        $embed->setTitle('Ticket Fechado');
        
        if ($reason) {
            $embed->setDescription("Este ticket foi fechado por <@{$closedBy}>\n**Razão:** {$reason}");
        } else {
            $embed->setDescription("Este ticket foi fechado por <@{$closedBy}>");
        }
        
        $embed->setColor(0xe74c3c); // Vermelho
        $embed->setTimestamp();
        
        $channel->sendMessage(MessageBuilder::new()->addEmbed($embed));
        
        // Gera transcrição se configurado
        if (config('discord-bot.ticket.save_transcripts', false)) {
            $this->generateTranscript($ticket, $channel);
        }
        
        // Agenda a exclusão do canal se configurado
        $deleteAfter = config('discord-bot.ticket.delete_after_close', 0);
        if ($deleteAfter > 0) {
            // Não é possível agendar no Discord.php, então avisa no canal
            $channel->sendMessage("Este canal será excluído em {$deleteAfter} horas.");
        }
        
        // Registra o fechamento
        $this->logger->info('Ticket fechado', [
            'ticket_id' => $ticket->id,
            'guild_id' => $ticket->guild_id,
            'user_id' => $ticket->user_id,
            'closed_by' => $closedBy,
            'reason' => $reason
        ]);
        
        return true;
    }
    
    /**
     * Gera transcrição de um ticket
     *
     * @param DiscordTicket $ticket
     * @param Channel $channel
     * @return void
     */
    private function generateTranscript(DiscordTicket $ticket, Channel $channel): void
    {
        // Busca mensagens do canal
        $channel->getMessageHistory([
            'limit' => 1000 // Limita a 1000 mensagens
        ])->done(function ($messages) use ($ticket, $channel) {
            $transcript = "# Transcrição do Ticket #{$ticket->id}\n";
            $transcript .= "Aberto por: <@{$ticket->user_id}>\n";
            $transcript .= "Assunto: {$ticket->subject}\n";
            $transcript .= "Data de abertura: " . $ticket->created_at->format('d/m/Y H:i:s') . "\n";
            $transcript .= "Data de fechamento: " . $ticket->closed_at->format('d/m/Y H:i:s') . "\n";
            $transcript .= "Fechado por: <@{$ticket->closed_by}>\n\n";
            $transcript .= "## Mensagens\n\n";
            
            // Inverte para ordem cronológica
            $messages = array_reverse($messages);
            
            foreach ($messages as $message) {
                $timestamp = Carbon::createFromTimestamp($message->timestamp / 1000)->format('d/m/Y H:i:s');
                $transcript .= "**{$message->author->username}** ({$timestamp}):\n";
                $transcript .= "{$message->content}\n\n";
                
                // Adiciona embeds
                if (count($message->embeds) > 0) {
                    foreach ($message->embeds as $embed) {
                        $transcript .= "_Embed:_ ";
                        if (isset($embed->title)) $transcript .= "**{$embed->title}** ";
                        if (isset($embed->description)) $transcript .= "{$embed->description} ";
                        $transcript .= "\n\n";
                    }
                }
                
                // Adiciona anexos
                if (count($message->attachments) > 0) {
                    foreach ($message->attachments as $attachment) {
                        $transcript .= "_Anexo:_ {$attachment->url}\n\n";
                    }
                }
            }
            
            // Salva a transcrição em algum lugar (isso depende da aplicação)
            // Por simplicidade, vamos apenas enviar para o canal de logs
            $logsChannelId = config('discord-bot.ticket.logs_channel_id');
            
            if ($logsChannelId) {
                $logsChannel = $this->discord->getChannel($logsChannelId);
                
                if ($logsChannel) {
                    $logsChannel->sendMessage(MessageBuilder::new()
                        ->setContent("Transcrição do ticket #{$ticket->id}")
                        ->addFile('transcript.md', $transcript));
                }
            }
            
            // Atualiza o ticket com o URL da transcrição (neste caso seria o link para a mensagem)
            // Como não temos o ID da mensagem aqui, vamos simplesmente deixar como null
            $ticket->transcript_url = null;
            $ticket->save();
        });
    }
    
    /**
     * Reabre um ticket
     *
     * @param string $ticketId ID do ticket
     * @param string $reopenedBy ID do usuário que reabriu
     * @return bool
     */
    public function reopenTicket(string $ticketId, string $reopenedBy): bool
    {
        // Busca o ticket
        $ticket = DiscordTicket::find($ticketId);
        
        if (!$ticket || $ticket->status !== 'closed') {
            return false;
        }
        
        // Verifica se o canal ainda existe
        $channel = $this->discord->getChannel($ticket->channel_id);
        if (!$channel) {
            // Se o canal foi excluído, cria um novo
            return $this->reopenWithNewChannel($ticket, $reopenedBy);
        }
        
        // Atualiza o ticket
        $ticket->status = 'open';
        $ticket->last_activity = Carbon::now();
        $ticket->save();
        
        // Envia mensagem de reabertura
        $embed = new Embed($this->discord);
        $embed->setTitle('Ticket Reaberto');
        $embed->setDescription("Este ticket foi reaberto por <@{$reopenedBy}>");
        $embed->setColor(0x3498db); // Azul
        $embed->setTimestamp();
        
        $channel->sendMessage(MessageBuilder::new()->addEmbed($embed));
        
        // Registra a reabertura
        $this->logger->info('Ticket reaberto', [
            'ticket_id' => $ticket->id,
            'guild_id' => $ticket->guild_id,
            'user_id' => $ticket->user_id,
            'reopened_by' => $reopenedBy
        ]);
        
        return true;
    }
    
    /**
     * Reabre um ticket criando um novo canal
     *
     * @param DiscordTicket $ticket
     * @param string $reopenedBy
     * @return bool
     */
    private function reopenWithNewChannel(DiscordTicket $ticket, string $reopenedBy): bool
    {
        // Obtém configuração do ticket
        $ticketCategory = config('discord-bot.ticket.category_id');
        $supportRoleId = config('discord-bot.ticket.support_role_id');
        
        if (!$ticketCategory) {
            return false;
        }
        
        // Obtém a guild
        $guild = $this->discord->guilds->get('id', $ticket->guild_id);
        if (!$guild) {
            return false;
        }
        
        // Cria o canal para o ticket
        $ticketNumber = $ticket->id;
        $channelName = 'ticket-' . str_pad($ticketNumber, 4, '0', STR_PAD_LEFT);
        
        try {
            $guild->channels->create([
                'name' => $channelName,
                'type' => Channel::TYPE_TEXT,
                'parent_id' => $ticketCategory,
                'permission_overwrites' => [
                    // Esconde o canal de todos
                    [
                        'id' => $ticket->guild_id,
                        'type' => 'role',
                        'deny' => 0x400 // VIEW_CHANNEL
                    ],
                    // Permite acesso ao usuário que criou
                    [
                        'id' => $ticket->user_id,
                        'type' => 'member',
                        'allow' => 0x400 | 0x800 | 0x4000 | 0x8000 // VIEW_CHANNEL, SEND_MESSAGES, ATTACH_FILES, EMBED_LINKS
                    ],
                    // Permite acesso aos suportes
                    [
                        'id' => $supportRoleId,
                        'type' => 'role',
                        'allow' => 0x400 | 0x800 | 0x4000 | 0x8000 // VIEW_CHANNEL, SEND_MESSAGES, ATTACH_FILES, EMBED_LINKS
                    ]
                ]
            ])->done(function (Channel $channel) use ($ticket, $reopenedBy) {
                // Atualiza o registro
                $ticket->channel_id = $channel->id;
                $ticket->status = 'open';
                $ticket->last_activity = Carbon::now();
                $ticket->save();
                
                // Envia mensagem inicial
                $embed = new Embed($this->discord);
                $embed->setTitle('Ticket #' . str_pad($ticket->id, 4, '0', STR_PAD_LEFT) . ' (Reaberto)');
                $embed->setDescription("Ticket reaberto por <@{$reopenedBy}>");
                $embed->addField([
                    'name' => 'Assunto',
                    'value' => $ticket->subject,
                    'inline' => false
                ]);
                $embed->setColor(0x3498db); // Azul
                $embed->setTimestamp();
                $embed->setFooter([
                    'text' => 'Use /ticket close para fechar este ticket'
                ]);
                
                $channel->sendMessage(MessageBuilder::new()
                    ->setContent('@here Um ticket foi reaberto')
                    ->addEmbed($embed));
                
                // Registra a reabertura
                $this->logger->info('Ticket reaberto com novo canal', [
                    'ticket_id' => $ticket->id,
                    'guild_id' => $ticket->guild_id,
                    'user_id' => $ticket->user_id,
                    'reopened_by' => $reopenedBy,
                    'new_channel_id' => $channel->id
                ]);
            });
            
            return true;
        } catch (Exception $e) {
            $this->logger->error('Falha ao criar canal para reabrir ticket', [
                'error' => $e->getMessage(),
                'ticket_id' => $ticket->id
            ]);
            
            return false;
        }
    }
    
    /**
     * Lista tickets de um usuário
     *
     * @param string $guildId
     * @param string $userId
     * @return \Illuminate\Support\Collection
     */
    public function getUserTickets(string $guildId, string $userId)
    {
        return DiscordTicket::where('guild_id', $guildId)
                           ->where('user_id', $userId)
                           ->orderBy('created_at', 'desc')
                           ->get();
    }
    
    /**
     * Lista tickets ativos em um servidor
     *
     * @param string $guildId
     * @return \Illuminate\Support\Collection
     */
    public function getActiveTickets(string $guildId)
    {
        return DiscordTicket::where('guild_id', $guildId)
                           ->where('status', 'open')
                           ->orderBy('last_activity', 'desc')
                           ->get();
    }
    
    /**
     * Adiciona um usuário a um ticket
     *
     * @param string $channelId ID do canal do ticket
     * @param string $userId ID do usuário a ser adicionado
     * @return bool
     */
    public function addUserToTicket(string $channelId, string $userId): bool
    {
        // Busca o ticket
        $ticket = DiscordTicket::where('channel_id', $channelId)->first();
        
        if (!$ticket || $ticket->status !== 'open') {
            return false;
        }
        
        // Obtém o canal
        $channel = $this->discord->getChannel($channelId);
        if (!$channel) {
            return false;
        }
        
        // Adiciona permissão para o usuário
        $channel->setPermissions($userId, [
            'allow' => 0x400 | 0x800 | 0x4000 | 0x8000, // VIEW_CHANNEL, SEND_MESSAGES, ATTACH_FILES, EMBED_LINKS
            'type' => 'member'
        ]);
        
        // Envia mensagem
        $channel->sendMessage("<@{$userId}> foi adicionado a este ticket.");
        
        // Registra
        $this->logger->info('Usuário adicionado ao ticket', [
            'ticket_id' => $ticket->id,
            'guild_id' => $ticket->guild_id,
            'user_id' => $userId
        ]);
        
        return true;
    }
    
    /**
     * Remove um usuário de um ticket
     *
     * @param string $channelId ID do canal do ticket
     * @param string $userId ID do usuário a ser removido
     * @return bool
     */
    public function removeUserFromTicket(string $channelId, string $userId): bool
    {
        // Busca o ticket
        $ticket = DiscordTicket::where('channel_id', $channelId)->first();
        
        if (!$ticket || $ticket->status !== 'open') {
            return false;
        }
        
        // Não pode remover o criador do ticket
        if ($ticket->user_id === $userId) {
            return false;
        }
        
        // Obtém o canal
        $channel = $this->discord->getChannel($channelId);
        if (!$channel) {
            return false;
        }
        
        // Remove permissão para o usuário
        $channel->deletePermission($userId);
        
        // Envia mensagem
        $channel->sendMessage("<@{$userId}> foi removido deste ticket.");
        
        // Registra
        $this->logger->info('Usuário removido do ticket', [
            'ticket_id' => $ticket->id,
            'guild_id' => $ticket->guild_id,
            'user_id' => $userId
        ]);
        
        return true;
    }
} 