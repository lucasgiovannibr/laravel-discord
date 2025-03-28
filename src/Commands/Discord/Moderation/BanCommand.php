<?php

namespace LucasGiovanni\DiscordBotInstaller\Commands\Discord\Moderation;

use Discord\Discord;
use Discord\Parts\Channel\Message;
use LucasGiovanni\DiscordBotInstaller\Services\DiscordLogger;
use LucasGiovanni\DiscordBotInstaller\Models\DiscordWarning;

class BanCommand
{
    /**
     * Instância do cliente Discord
     */
    protected $discord;
    
    /**
     * Logger
     */
    protected $logger;
    
    /**
     * Construtor
     */
    public function __construct(Discord $discord, DiscordLogger $logger)
    {
        $this->discord = $discord;
        $this->logger = $logger;
    }
    
    /**
     * Manipula o comando
     */
    public function handle(Message $message, array $args = []): void
    {
        // Verificar se há argumentos suficientes
        if (count($args) < 1) {
            $this->showHelp($message);
            return;
        }
        
        // Extrair menção do usuário, número de dias de mensagens para deletar, e razão
        $userMention = $args[0];
        $deleteMessageDays = 0;
        $reason = 'Banido por um moderador';
        
        // Verificar se há um número de dias para deletar mensagens
        if (count($args) > 1 && is_numeric($args[1]) && (int) $args[1] >= 0 && (int) $args[1] <= 7) {
            $deleteMessageDays = (int) $args[1];
            $reasonArgs = array_slice($args, 2);
        } else {
            $reasonArgs = array_slice($args, 1);
        }
        
        // Extrair razão, se fornecida
        if (!empty($reasonArgs)) {
            $reason = implode(' ', $reasonArgs);
        }
        
        try {
            // Extrair ID do usuário da menção
            $userId = $this->extractUserId($userMention);
            
            if (!$userId) {
                $message->channel->sendMessage('⚠️ Mencione um usuário válido para banir. Exemplo: @usuario');
                return;
            }
            
            // Obter informações do servidor
            $guild = $message->guild;
            
            // Verificar se o bot tem permissão para banir
            $botMember = $guild->members->get('id', $this->discord->id);
            if (!$botMember || !$botMember->getPermissions()->has('BAN_MEMBERS')) {
                $message->channel->sendMessage('❌ Não tenho permissão para banir membros neste servidor.');
                return;
            }
            
            // Verificar se o usuário já está banido
            $bans = $guild->bans;
            if ($bans->has($userId)) {
                $message->channel->sendMessage('⚠️ Este usuário já está banido.');
                return;
            }
            
            // Verificar se o usuário a ser banido não é o proprietário do servidor
            if ($userId === $guild->owner_id) {
                $message->channel->sendMessage('❌ Não posso banir o proprietário do servidor.');
                return;
            }
            
            // Obter o membro a ser banido
            $member = $guild->members->get('id', $userId);
            
            // Verificar se o usuário a ser banido tem cargo maior que o do moderador
            if ($member) {
                $moderator = $message->member;
                if ($member->getHighestRole()->position >= $moderator->getHighestRole()->position) {
                    $message->channel->sendMessage('❌ Você não pode banir um usuário com cargo igual ou superior ao seu.');
                    return;
                }
            }
            
            // Coletar informações do usuário antes de banir (para o registro)
            $targetUsername = $member ? $member->username : "Usuário {$userId}";
            
            // Processar o banimento
            $guild->bans->ban($userId, $deleteMessageDays, $reason)->done(
                function () use ($message, $userId, $targetUsername, $reason, $deleteMessageDays) {
                    // Responder com sucesso
                    $message->channel->sendMessage("✅ {$targetUsername} foi banido. Motivo: {$reason}");
                    
                    // Registrar no banco de dados
                    $this->registerInfraction($message, $userId, $reason);
                    
                    // Enviar log para o canal de moderação, se configurado
                    $this->sendModerationLog($message, $userId, $targetUsername, $reason, $deleteMessageDays);
                    
                    // Registrar no logger
                    $this->logger->info('Usuário banido', [
                        'moderator' => $message->author->username,
                        'target_user' => $targetUsername,
                        'target_id' => $userId,
                        'reason' => $reason,
                        'delete_days' => $deleteMessageDays,
                        'server' => $message->guild->name
                    ]);
                }
            )->otherwise(
                function ($error) use ($message) {
                    // Responder com erro
                    $errorMessage = "❌ Erro ao banir usuário: " . $error->getMessage();
                    $message->channel->sendMessage($errorMessage);
                    
                    // Registrar no logger
                    $this->logger->error('Erro ao banir usuário', [
                        'error' => $error->getMessage(),
                        'moderator' => $message->author->username,
                        'server' => $message->guild->name
                    ]);
                }
            );
            
        } catch (\Exception $e) {
            // Responder com erro
            $message->channel->sendMessage('❌ Erro ao processar o comando: ' . $e->getMessage());
            
            // Registrar no logger
            $this->logger->error('Erro no comando ban', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
    
    /**
     * Extrai o ID do usuário de uma menção
     */
    protected function extractUserId(string $mention): ?string
    {
        // Formato de menção: <@!123456789012345678> ou <@123456789012345678>
        if (preg_match('/<@!?(\d+)>/', $mention, $matches)) {
            return $matches[1];
        }
        
        // Se for apenas o ID
        if (preg_match('/^\d+$/', $mention)) {
            return $mention;
        }
        
        return null;
    }
    
    /**
     * Registra a infração no banco de dados
     */
    protected function registerInfraction(Message $message, string $userId, string $reason): void
    {
        try {
            // Criar registro de infração
            $warning = new DiscordWarning([
                'user_id' => $userId,
                'server_id' => $message->guild->id,
                'moderator_id' => $message->author->id,
                'type' => 'ban',
                'reason' => $reason,
                'active' => true
            ]);
            
            $warning->save();
        } catch (\Exception $e) {
            $this->logger->error('Erro ao registrar infração no banco de dados', [
                'error' => $e->getMessage(),
                'user_id' => $userId,
                'server_id' => $message->guild->id
            ]);
        }
    }
    
    /**
     * Envia log de moderação para o canal configurado
     */
    protected function sendModerationLog(Message $message, string $userId, string $username, string $reason, int $deleteMessageDays): void
    {
        // Obter ID do canal de moderação
        $modLogChannelId = config('discordbot.channels.moderation');
        
        if (!$modLogChannelId) {
            return;
        }
        
        // Obter o canal
        $channel = $this->discord->getChannel($modLogChannelId);
        
        if (!$channel) {
            return;
        }
        
        // Construir embed de log
        $embed = [
            'title' => '🔨 Usuário Banido',
            'color' => 0xFF0000, // Vermelho
            'fields' => [
                [
                    'name' => 'Usuário',
                    'value' => "{$username} ({$userId})",
                    'inline' => true
                ],
                [
                    'name' => 'Moderador',
                    'value' => $message->author->username,
                    'inline' => true
                ],
                [
                    'name' => 'Motivo',
                    'value' => $reason,
                    'inline' => false
                ],
                [
                    'name' => 'Mensagens excluídas',
                    'value' => $deleteMessageDays . ' dias',
                    'inline' => true
                ]
            ],
            'timestamp' => date('c')
        ];
        
        // Enviar embed
        $channel->sendMessage('', false, $embed);
    }
    
    /**
     * Exibe ajuda sobre o comando
     */
    protected function showHelp(Message $message): void
    {
        $prefix = config('discordbot.command_prefix', '!');
        
        $helpText = "**🔨 Comando Ban**\n\n";
        $helpText .= "Use `{$prefix}ban @usuário [dias] [razão]` para banir um usuário do servidor.\n\n";
        $helpText .= "**Parâmetros:**\n";
        $helpText .= "- `@usuário`: Menção ou ID do usuário a ser banido (obrigatório)\n";
        $helpText .= "- `dias`: Número de dias de mensagens para apagar (0-7, opcional, padrão: 0)\n";
        $helpText .= "- `razão`: Motivo do banimento (opcional)\n\n";
        $helpText .= "**Exemplos:**\n";
        $helpText .= "`{$prefix}ban @usuário`\n";
        $helpText .= "`{$prefix}ban @usuário Violação das regras`\n";
        $helpText .= "`{$prefix}ban @usuário 7 Spam em vários canais`";
        
        $message->channel->sendMessage($helpText);
    }
    
    /**
     * Retorna informações sobre o comando para o sistema de ajuda
     */
    public function getHelp(): array
    {
        return [
            'usage' => '@usuário [dias] [razão]',
            'examples' => [
                '@usuário',
                '@usuário Violação das regras',
                '@usuário 7 Spam em vários canais',
            ],
            'notes' => "Dias se refere ao número de dias de mensagens para apagar (0-7)."
        ];
    }
} 