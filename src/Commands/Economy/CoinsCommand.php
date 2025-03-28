<?php

namespace LucasGiovanni\DiscordBotInstaller\Commands\Economy;

use Discord\Discord;
use Discord\Parts\Channel\Message;
use LucasGiovanni\DiscordBotInstaller\Services\DiscordLogger;
use LucasGiovanni\DiscordBotInstaller\Models\DiscordEconomy;
use LucasGiovanni\DiscordBotInstaller\Models\DiscordUser;

class CoinsCommand
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
        
        // Se foi mencionado um usuário, mostra as moedas desse usuário
        if (isset($args[0]) && !empty($args[0])) {
            // Verifica se é uma menção
            if (preg_match('/<@!?(\d+)>/', $args[0], $matches)) {
                $userId = $matches[1];
            } else {
                // Tenta encontrar o usuário pelo nome
                $userName = implode(' ', $args);
                
                $members = $message->guild->members;
                $targetMember = $members->filter(function ($member) use ($userName) {
                    return stripos($member->username, $userName) !== false || 
                           (isset($member->nick) && stripos($member->nick, $userName) !== false);
                })->first();
                
                if (!$targetMember) {
                    $message->reply('Usuário não encontrado. Tente mencionar o usuário ou verificar o nome.');
                    return;
                }
                
                $userId = $targetMember->id;
            }
        } else {
            // Se não foi especificado um usuário, mostra as moedas do autor
            $userId = $message->author->id;
        }
        
        // Busca o usuário no banco
        $user = DiscordUser::where('user_id', $userId)
                          ->where('guild_id', $guildId)
                          ->first();
        
        if (!$user) {
            $message->reply('Usuário não encontrado no banco de dados.');
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
        
        // Busca o membro no Discord para exibir o nome correto
        $member = $message->guild->members->get('id', $userId);
        $displayName = $member ? ($member->nick ?? $member->username) : $user->username;
        
        // Configuração do bot
        $currencyName = config('discordbot.economy.currency_name', 'coins');
        $currencyEmoji = config('discordbot.economy.currency_emoji', '💰');
        
        // Cria um embed com as informações econômicas
        $embed = [
            'title' => "{$currencyEmoji} Saldo de {$displayName}",
            'description' => "Aqui estão as informações econômicas de {$displayName}",
            'color' => 0xF1C40F, // Dourado
            'fields' => [
                [
                    'name' => "Saldo Atual",
                    'value' => "{$economy->balance} {$currencyName}",
                    'inline' => true
                ],
                [
                    'name' => "Total Ganho",
                    'value' => "{$economy->total_earned} {$currencyName}",
                    'inline' => true
                ],
                [
                    'name' => "Total Gasto",
                    'value' => "{$economy->total_spent} {$currencyName}",
                    'inline' => true
                ],
                [
                    'name' => "Sequência Diária",
                    'value' => $economy->streak > 0 ? "{$economy->streak} dias" : "Nenhuma",
                    'inline' => true
                ],
                [
                    'name' => "Último Resgate Diário",
                    'value' => $economy->last_daily ? $economy->last_daily->diffForHumans() : "Nunca",
                    'inline' => true
                ],
                [
                    'name' => "Próximo Resgate",
                    'value' => $economy->canClaimDaily() ? "**Disponível Agora!**" : $economy->last_daily->addHours(24)->diffForHumans(),
                    'inline' => true
                ]
            ],
            'footer' => [
                'text' => "Use !daily para receber sua recompensa diária"
            ],
            'timestamp' => date('c')
        ];
        
        // Adiciona avatar do usuário ao embed
        if ($member && $member->avatar) {
            $embed['thumbnail'] = [
                'url' => $member->avatar
            ];
        }
        
        // Envia o embed
        $message->channel->sendMessage('', false, $embed);
        
        // Registra o uso do comando
        $this->logger->info(
            "Comando !coins usado por {$message->author->username} para verificar saldo de {$displayName}",
            [
                'user_id' => $message->author->id,
                'target_user_id' => $userId,
                'guild_id' => $guildId
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
            'description' => 'Mostra seu saldo atual de coins ou o saldo de outro usuário',
            'usage' => '[@usuário]',
            'examples' => ['', '@username'],
            'notes' => 'Se nenhum usuário for especificado, mostra seu próprio saldo.'
        ];
    }
} 