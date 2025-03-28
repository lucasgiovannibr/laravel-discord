<?php

namespace LucasGiovanni\DiscordBotInstaller\Commands\Giveaway;

use Discord\Discord;
use Discord\Parts\Channel\Message;
use LucasGiovanni\DiscordBotInstaller\Services\DiscordLogger;
use LucasGiovanni\DiscordBotInstaller\Models\DiscordGiveaway;
use Carbon\Carbon;
use Exception;

class GiveawayCommand
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
     * Subcomandos disponíveis
     *
     * @var array
     */
    protected $subcommands = [
        'start' => 'startGiveaway',
        'end' => 'endGiveaway',
        'reroll' => 'rerollGiveaway',
        'cancel' => 'cancelGiveaway',
        'list' => 'listGiveaways',
        'info' => 'giveawayInfo',
    ];

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
     * Manipula o comando
     *
     * @param Message $message
     * @param array $args
     * @return void
     */
    public function handle(Message $message, array $args = []): void
    {
        // Verifica permissões
        if (!$message->member->permissions->has('MANAGE_GUILD')) {
            $message->reply('Você não tem permissão para gerenciar sorteios.');
            return;
        }
        
        if (empty($args)) {
            $this->showHelp($message);
            return;
        }
        
        $subcommand = strtolower($args[0]);
        
        if (!isset($this->subcommands[$subcommand])) {
            $message->reply('Subcomando inválido. Use `!giveaway` para ver a lista de subcomandos disponíveis.');
            return;
        }
        
        $method = $this->subcommands[$subcommand];
        array_shift($args); // Remove o subcomando dos argumentos
        
        try {
            $this->$method($message, $args);
        } catch (Exception $e) {
            $message->reply('Ocorreu um erro ao processar o comando: ' . $e->getMessage());
            $this->logger->error('Erro no comando giveaway', [
                'error' => $e->getMessage(),
                'user_id' => $message->author->id,
                'guild_id' => $message->guild_id
            ]);
        }
    }

    /**
     * Mostra a ajuda do comando
     *
     * @param Message $message
     * @return void
     */
    protected function showHelp(Message $message): void
    {
        $prefix = config('discordbot.command_prefix', '!');
        
        $embed = [
            'title' => '🎁 Comandos de Sorteio',
            'description' => 'Gerencie sorteios no servidor',
            'color' => 0x9B59B6, // Roxo
            'fields' => [
                [
                    'name' => "{$prefix}giveaway start <duração> <prêmio>",
                    'value' => "Inicia um novo sorteio.\nExemplo: `{$prefix}giveaway start 24h Nitro por 1 mês`",
                    'inline' => false
                ],
                [
                    'name' => "{$prefix}giveaway end <id>",
                    'value' => "Finaliza um sorteio imediatamente e seleciona vencedores",
                    'inline' => false
                ],
                [
                    'name' => "{$prefix}giveaway reroll <id>",
                    'value' => "Seleciona novos vencedores para um sorteio finalizado",
                    'inline' => false
                ],
                [
                    'name' => "{$prefix}giveaway cancel <id>",
                    'value' => "Cancela um sorteio ativo",
                    'inline' => false
                ],
                [
                    'name' => "{$prefix}giveaway list",
                    'value' => "Lista todos os sorteios ativos no servidor",
                    'inline' => false
                ],
                [
                    'name' => "{$prefix}giveaway info <id>",
                    'value' => "Exibe informações detalhadas sobre um sorteio específico",
                    'inline' => false
                ]
            ],
            'footer' => [
                'text' => "Dica: a duração pode ser especificada em minutos (m), horas (h) ou dias (d)"
            ]
        ];
        
        $message->channel->sendMessage('', false, $embed);
    }

    /**
     * Inicia um novo sorteio
     *
     * @param Message $message
     * @param array $args
     * @return void
     */
    protected function startGiveaway(Message $message, array $args): void
    {
        if (count($args) < 2) {
            $message->reply('Uso incorreto. Exemplo: `!giveaway start 24h Nitro por 1 mês`');
            return;
        }
        
        $durationText = $args[0];
        array_shift($args);
        $prize = implode(' ', $args);
        
        // Parse da duração
        $duration = $this->parseDuration($durationText);
        
        if ($duration === false) {
            $message->reply('Duração inválida. Use formatos como 30m, 6h, 2d');
            return;
        }
        
        $endTime = Carbon::now()->addSeconds($duration);
        
        // Configurações do sorteio
        $emoji = config('discordbot.giveaways.emoji', '🎉');
        $winnersCount = 1; // Padrão é 1 vencedor
        
        // Cria a mensagem de sorteio
        $giveawayMessage = str_replace(
            ['{prize}', '{end_time}'],
            [$prize, $endTime->format('d/m/Y H:i')],
            config('discordbot.messages.giveaway_started', '🎉 **SORTEIO** 🎉\n\n{prize}\n\nClique no emoji 🎉 para participar!\nTérmino: {end_time}')
        );
        
        // Envia a mensagem
        $message->channel->sendMessage($giveawayMessage)->done(function ($giveawayMsg) use ($message, $prize, $endTime, $winnersCount, $emoji) {
            // Adiciona a reação inicial
            $giveawayMsg->react($emoji);
            
            // Registra o sorteio no banco de dados
            $giveaway = new DiscordGiveaway([
                'guild_id' => $message->guild_id,
                'channel_id' => $message->channel_id,
                'message_id' => $giveawayMsg->id,
                'creator_id' => $message->author->id,
                'prize' => $prize,
                'description' => '', // Opcional
                'winners_count' => $winnersCount,
                'ends_at' => $endTime,
                'ended' => false,
                'winners' => null
            ]);
            
            $giveaway->save();
            
            // Registra o log
            $this->logger->info('Novo sorteio criado', [
                'giveaway_id' => $giveaway->id,
                'creator_id' => $message->author->id,
                'guild_id' => $message->guild_id,
                'prize' => $prize,
                'end_time' => $endTime->toIso8601String()
            ]);
            
            // Confirma para o usuário
            $message->reply("Sorteio criado com sucesso! ID: {$giveaway->id}");
        });
    }

    /**
     * Finaliza um sorteio imediatamente
     *
     * @param Message $message
     * @param array $args
     * @return void
     */
    protected function endGiveaway(Message $message, array $args): void
    {
        if (empty($args)) {
            $message->reply('Por favor, forneça o ID do sorteio. Exemplo: `!giveaway end 123`');
            return;
        }
        
        $giveawayId = intval($args[0]);
        
        // Busca o sorteio
        $giveaway = DiscordGiveaway::where('id', $giveawayId)
                                  ->where('guild_id', $message->guild_id)
                                  ->where('ended', false)
                                  ->first();
        
        if (!$giveaway) {
            $message->reply('Sorteio não encontrado ou já finalizado.');
            return;
        }
        
        // Recupera a mensagem original
        $channel = $this->discord->getChannel($giveaway->channel_id);
        
        if (!$channel) {
            $message->reply('Canal do sorteio não encontrado.');
            return;
        }
        
        $channel->messages->fetch($giveaway->message_id)->done(function ($giveawayMsg) use ($message, $giveaway) {
            // Recupera os participantes (reações)
            $emoji = config('discordbot.giveaways.emoji', '🎉');
            
            if ($giveawayMsg && isset($giveawayMsg->reactions[$emoji])) {
                $reaction = $giveawayMsg->reactions[$emoji];
                
                // Busca os usuários que reagiram
                $reaction->getUsers()->done(function ($users) use ($message, $giveaway, $giveawayMsg) {
                    // Filtra o ID do bot
                    $participants = [];
                    foreach ($users as $user) {
                        if (!$user->bot) {
                            $participants[] = $user->id;
                        }
                    }
                    
                    // Finaliza o sorteio
                    $winners = $giveaway->end($participants);
                    
                    // Prepara mensagem de resultado
                    $winnersText = empty($winners) ? "Nenhum participante" : implode(", ", array_map(function ($id) {
                        return "<@{$id}>";
                    }, $winners));
                    
                    // Atualiza a mensagem original
                    $updatedContent = str_replace(
                        ['{prize}', '{winners}'],
                        [$giveaway->prize, $winnersText],
                        config('discordbot.messages.giveaway_ended', '🎉 **SORTEIO ENCERRADO** 🎉\n\n{prize}\n\nVencedor(es): {winners}')
                    );
                    
                    $giveawayMsg->edit($updatedContent);
                    
                    // Responde no canal atual
                    $resultMessage = empty($winners)
                        ? "O sorteio #{$giveaway->id} terminou, mas não houve participantes."
                        : "O sorteio #{$giveaway->id} terminou! Vencedor(es): {$winnersText}";
                    
                    $message->reply($resultMessage);
                    
                    // Notifica os vencedores via ping no canal do sorteio
                    if (!empty($winners)) {
                        $channel = $this->discord->getChannel($giveaway->channel_id);
                        $channel->sendMessage("Parabéns {$winnersText}! Você ganhou **{$giveaway->prize}**!");
                    }
                    
                    // Registra o log
                    $this->logger->info('Sorteio finalizado', [
                        'giveaway_id' => $giveaway->id,
                        'ended_by' => $message->author->id,
                        'winners' => $winners,
                        'total_participants' => count($participants)
                    ]);
                });
            } else {
                // Sem reações ou mensagem não encontrada
                $giveaway->end([]);
                $message->reply("O sorteio #{$giveaway->id} terminou, mas não houve participantes.");
            }
        });
    }

    /**
     * Seleciona novos vencedores para um sorteio já finalizado
     *
     * @param Message $message
     * @param array $args
     * @return void
     */
    protected function rerollGiveaway(Message $message, array $args): void
    {
        if (empty($args)) {
            $message->reply('Por favor, forneça o ID do sorteio. Exemplo: `!giveaway reroll 123`');
            return;
        }
        
        $giveawayId = intval($args[0]);
        
        // Busca o sorteio
        $giveaway = DiscordGiveaway::where('id', $giveawayId)
                                  ->where('guild_id', $message->guild_id)
                                  ->where('ended', true)
                                  ->first();
        
        if (!$giveaway) {
            $message->reply('Sorteio não encontrado ou ainda não finalizado.');
            return;
        }
        
        // Recupera a mensagem original
        $channel = $this->discord->getChannel($giveaway->channel_id);
        
        if (!$channel) {
            $message->reply('Canal do sorteio não encontrado.');
            return;
        }
        
        $channel->messages->fetch($giveaway->message_id)->done(function ($giveawayMsg) use ($message, $giveaway) {
            // Recupera os participantes (reações)
            $emoji = config('discordbot.giveaways.emoji', '🎉');
            
            if ($giveawayMsg && isset($giveawayMsg->reactions[$emoji])) {
                $reaction = $giveawayMsg->reactions[$emoji];
                
                // Busca os usuários que reagiram
                $reaction->getUsers()->done(function ($users) use ($message, $giveaway) {
                    // Filtra o ID do bot
                    $participants = [];
                    foreach ($users as $user) {
                        if (!$user->bot) {
                            $participants[] = $user->id;
                        }
                    }
                    
                    // Faz o reroll
                    $newWinner = $giveaway->reroll($participants);
                    
                    if (empty($newWinner)) {
                        $message->reply("Não foi possível selecionar um novo vencedor para o sorteio #{$giveaway->id}. Não há participantes disponíveis.");
                        return;
                    }
                    
                    // Notifica o novo vencedor
                    $message->reply("🎉 Novo vencedor para o sorteio #{$giveaway->id}: <@{$newWinner}>!");
                    
                    // Notifica no canal do sorteio
                    $channel = $this->discord->getChannel($giveaway->channel_id);
                    $channel->sendMessage("🎉 Um novo vencedor foi sorteado para **{$giveaway->prize}**: <@{$newWinner}>! Parabéns!");
                    
                    // Registra o log
                    $this->logger->info('Reroll de sorteio', [
                        'giveaway_id' => $giveaway->id,
                        'reroll_by' => $message->author->id,
                        'new_winner' => $newWinner
                    ]);
                });
            } else {
                $message->reply("Não foi possível encontrar participantes para o sorteio #{$giveaway->id}.");
            }
        });
    }

    /**
     * Cancela um sorteio ativo
     *
     * @param Message $message
     * @param array $args
     * @return void
     */
    protected function cancelGiveaway(Message $message, array $args): void
    {
        if (empty($args)) {
            $message->reply('Por favor, forneça o ID do sorteio. Exemplo: `!giveaway cancel 123`');
            return;
        }
        
        $giveawayId = intval($args[0]);
        
        // Busca o sorteio
        $giveaway = DiscordGiveaway::where('id', $giveawayId)
                                  ->where('guild_id', $message->guild_id)
                                  ->where('ended', false)
                                  ->first();
        
        if (!$giveaway) {
            $message->reply('Sorteio não encontrado ou já finalizado.');
            return;
        }
        
        // Recupera a mensagem original
        $channel = $this->discord->getChannel($giveaway->channel_id);
        
        if ($channel) {
            $channel->messages->fetch($giveaway->message_id)->done(function ($giveawayMsg) {
                if ($giveawayMsg) {
                    $giveawayMsg->edit('🚫 **SORTEIO CANCELADO** 🚫');
                }
            });
        }
        
        // Marca como finalizado sem vencedores
        $giveaway->ended = true;
        $giveaway->winners = [];
        $giveaway->save();
        
        $message->reply("Sorteio #{$giveaway->id} cancelado com sucesso.");
        
        // Registra o log
        $this->logger->info('Sorteio cancelado', [
            'giveaway_id' => $giveaway->id,
            'cancelled_by' => $message->author->id
        ]);
    }

    /**
     * Lista sorteios ativos no servidor
     *
     * @param Message $message
     * @param array $args
     * @return void
     */
    protected function listGiveaways(Message $message, array $args): void
    {
        // Busca sorteios ativos
        $giveaways = DiscordGiveaway::where('guild_id', $message->guild_id)
                                   ->where('ended', false)
                                   ->orderBy('ends_at', 'asc')
                                   ->get();
        
        if ($giveaways->isEmpty()) {
            $message->reply('Não há sorteios ativos no momento.');
            return;
        }
        
        $embed = [
            'title' => '🎁 Sorteios Ativos',
            'description' => 'Lista de sorteios atualmente ativos no servidor.',
            'color' => 0x9B59B6, // Roxo
            'fields' => []
        ];
        
        foreach ($giveaways as $giveaway) {
            $embed['fields'][] = [
                'name' => "#{$giveaway->id}: {$giveaway->prize}",
                'value' => "Canal: <#{$giveaway->channel_id}>\nTérmino: {$giveaway->getTimeLeftFormatted()}\nVencedores: {$giveaway->winners_count}",
                'inline' => false
            ];
        }
        
        $message->channel->sendMessage('', false, $embed);
    }

    /**
     * Mostra informações detalhadas sobre um sorteio específico
     *
     * @param Message $message
     * @param array $args
     * @return void
     */
    protected function giveawayInfo(Message $message, array $args): void
    {
        if (empty($args)) {
            $message->reply('Por favor, forneça o ID do sorteio. Exemplo: `!giveaway info 123`');
            return;
        }
        
        $giveawayId = intval($args[0]);
        
        // Busca o sorteio
        $giveaway = DiscordGiveaway::where('id', $giveawayId)
                                  ->where('guild_id', $message->guild_id)
                                  ->first();
        
        if (!$giveaway) {
            $message->reply('Sorteio não encontrado.');
            return;
        }
        
        // Status do sorteio
        $status = $giveaway->hasEnded() 
            ? "Encerrado" 
            : "Ativo (termina em {$giveaway->getTimeLeftFormatted()})";
        
        // Informações sobre vencedores
        $winnersInfo = "Quantidade: {$giveaway->winners_count}";
        
        if ($giveaway->hasEnded() && !empty($giveaway->winners)) {
            $winnersText = implode(", ", array_map(function ($id) {
                return "<@{$id}>";
            }, $giveaway->winners));
            $winnersInfo .= "\nVencedores: {$winnersText}";
        }
        
        // Cria o embed
        $embed = [
            'title' => "Sorteio #{$giveaway->id}: {$giveaway->prize}",
            'description' => $giveaway->description ?? "Sem descrição adicional",
            'color' => $giveaway->hasEnded() ? 0x95A5A6 : 0x9B59B6, // Cinza se encerrado, roxo se ativo
            'fields' => [
                [
                    'name' => "Status",
                    'value' => $status,
                    'inline' => true
                ],
                [
                    'name' => "Criado por",
                    'value' => "<@{$giveaway->creator_id}>",
                    'inline' => true
                ],
                [
                    'name' => "Canal",
                    'value' => "<#{$giveaway->channel_id}>",
                    'inline' => true
                ],
                [
                    'name' => "Data de Término",
                    'value' => $giveaway->ends_at->format('d/m/Y H:i:s'),
                    'inline' => true
                ],
                [
                    'name' => "Vencedores",
                    'value' => $winnersInfo,
                    'inline' => false
                ]
            ],
            'footer' => [
                'text' => "ID da Mensagem: {$giveaway->message_id}"
            ],
            'timestamp' => $giveaway->created_at->toIso8601String()
        ];
        
        $message->channel->sendMessage('', false, $embed);
    }

    /**
     * Converte uma string de duração em segundos
     *
     * @param string $duration
     * @return int|bool
     */
    protected function parseDuration(string $duration)
    {
        if (preg_match('/^(\d+)([mhd])$/', $duration, $matches)) {
            $value = (int) $matches[1];
            $unit = $matches[2];
            
            switch ($unit) {
                case 'm': // Minutos
                    return $value * 60;
                case 'h': // Horas
                    return $value * 3600;
                case 'd': // Dias
                    return $value * 86400;
            }
        }
        
        return false;
    }

    /**
     * Obtém informações de ajuda para o comando
     *
     * @return array
     */
    public function getHelp(): array
    {
        return [
            'description' => 'Gerencia sorteios no servidor',
            'usage' => '<subcomando> [argumentos]',
            'examples' => [
                'start 24h Nitro por 1 mês',
                'end 123',
                'reroll 123',
                'list'
            ],
            'notes' => 'Subcomandos disponíveis: start, end, reroll, cancel, list, info'
        ];
    }
} 