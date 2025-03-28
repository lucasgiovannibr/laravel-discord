<?php

namespace LucasGiovanni\DiscordBotInstaller\Commands\Economy;

use Discord\Discord;
use Discord\Parts\Channel\Message;
use LucasGiovanni\DiscordBotInstaller\Services\DiscordLogger;
use LucasGiovanni\DiscordBotInstaller\Models\DiscordEconomy;
use LucasGiovanni\DiscordBotInstaller\Models\DiscordUser;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DailyCommand
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
     * Manipula o comando
     *
     * @param Message $message
     * @param array $args
     * @return void
     */
    public function handle(Message $message, array $args = []): void
    {
        $guildId = $message->guild_id;
        $userId = $message->author->id;
        
        // Busca o usuário no banco
        $user = DiscordUser::where('user_id', $userId)
                          ->where('guild_id', $guildId)
                          ->first();
        
        if (!$user) {
            $message->reply('Você não está registrado no sistema. Por favor, tente novamente em alguns instantes.');
            return;
        }
        
        // Busca a economia do usuário
        $economy = DiscordEconomy::where('user_id', $userId)
                                ->where('guild_id', $guildId)
                                ->first();
        
        // Se o usuário não tiver registro de economia, cria um
        if (!$economy) {
            $economy = new DiscordEconomy([
                'user_id' => $userId,
                'guild_id' => $guildId,
                'balance' => 0,
                'total_earned' => 0,
                'total_spent' => 0,
                'streak' => 0
            ]);
            $economy->save();
        }
        
        // Configuração do bot
        $baseAmount = config('discordbot.economy.daily_amount', 100);
        $currencyName = config('discordbot.economy.currency_name', 'coins');
        $currencyEmoji = config('discordbot.economy.currency_emoji', '💰');
        
        // Tenta resgatar a recompensa diária
        $result = $economy->claimDaily($baseAmount);
        
        if (!$result['success']) {
            // Não foi possível resgatar
            $embed = [
                'title' => "Recompensa Diária Indisponível",
                'description' => "Você já resgatou sua recompensa diária hoje!",
                'color' => 0xE74C3C, // Vermelho
                'fields' => [
                    [
                        'name' => "Próximo Resgate",
                        'value' => $result['next_claim'],
                        'inline' => true
                    ],
                    [
                        'name' => "Saldo Atual",
                        'value' => "{$economy->balance} {$currencyName}",
                        'inline' => true
                    ]
                ],
                'footer' => [
                    'text' => "Volte {$result['next_claim']} para resgatar novamente!"
                ],
                'timestamp' => date('c')
            ];
        } else {
            // Resgate bem-sucedido
            $streakBonus = $result['streak'] > 1 ? " (+{$result['streak']}% de bônus por sequência)" : "";
            
            $embed = [
                'title' => "{$currencyEmoji} Recompensa Diária Resgatada!",
                'description' => "Você resgatou sua recompensa diária com sucesso!",
                'color' => 0x2ECC71, // Verde
                'fields' => [
                    [
                        'name' => "Recompensa",
                        'value' => "+{$result['amount']} {$currencyName}{$streakBonus}",
                        'inline' => true
                    ],
                    [
                        'name' => "Saldo Atual",
                        'value' => "{$economy->balance} {$currencyName}",
                        'inline' => true
                    ],
                    [
                        'name' => "Sequência",
                        'value' => $result['streak_broken'] 
                            ? "Sua sequência foi reiniciada (agora: {$result['streak']})" 
                            : "{$result['streak']} dias seguidos",
                        'inline' => false
                    ],
                    [
                        'name' => "Próximo Resgate",
                        'value' => $result['next_claim'],
                        'inline' => true
                    ]
                ],
                'footer' => [
                    'text' => "Volte amanhã para aumentar sua sequência e ganhar mais {$currencyName}!"
                ],
                'timestamp' => date('c')
            ];
        }
        
        // Adiciona avatar do usuário ao embed
        if ($message->author->avatar) {
            $embed['thumbnail'] = [
                'url' => $message->author->avatar
            ];
        }
        
        // Envia o embed
        $message->channel->sendMessage('', false, $embed);
        
        // Registra o uso do comando
        $this->logger->info(
            "Comando !daily usado por {$message->author->username} - " . 
            ($result['success'] ? "Sucesso (+{$result['amount']})" : "Falha"),
            [
                'user_id' => $userId,
                'guild_id' => $guildId,
                'success' => $result['success'],
                'amount' => $result['success'] ? $result['amount'] : 0,
                'streak' => $result['success'] ? $result['streak'] : 0
            ]
        );
    }

    /**
     * Obtém informações de ajuda para o comando
     *
     * @return array
     */
    public function getHelp(): array
    {
        return [
            'description' => 'Resgata sua recompensa diária de coins',
            'usage' => '',
            'examples' => [''],
            'notes' => 'Você ganha um bônus por resgatar em dias consecutivos (5% por dia, máximo de 50%).'
        ];
    }
} 