<?php

namespace LucasGiovanni\DiscordBotInstaller\Middleware;

use Discord\Parts\Channel\Message;
use LucasGiovanni\DiscordBotInstaller\Services\DiscordLogger;
use Illuminate\Support\Facades\Cache;

class RateLimiter implements Middleware
{
    /**
     * O logger
     *
     * @var DiscordLogger
     */
    protected $logger;

    /**
     * Configuração de limite de uso
     *
     * @var array
     */
    protected $config;

    /**
     * Construtor.
     *
     * @param DiscordLogger $logger
     */
    public function __construct(DiscordLogger $logger)
    {
        $this->logger = $logger;
        $this->config = config('discordbot.advanced.rate_limits', [
            'enabled' => true,
            'max_commands' => 5, // máximo de 5 comandos por período
            'period' => 60, // em 60 segundos
            'exclude_channels' => [], // IDs de canais excluídos do rate limit
            'exclude_users' => [], // IDs de usuários excluídos do rate limit
        ]);
    }

    /**
     * Manipula a mensagem e verifica limites de taxa.
     *
     * @param Message $message
     * @param callable $next
     * @return mixed
     */
    public function handle(Message $message, callable $next)
    {
        // Se o rate limit estiver desativado, continua
        if (!($this->config['enabled'] ?? true)) {
            return $next($message);
        }

        // Verifica se o canal ou usuário está excluído do rate limit
        if (
            in_array($message->channel->id, $this->config['exclude_channels'] ?? []) ||
            in_array($message->author->id, $this->config['exclude_users'] ?? [])
        ) {
            return $next($message);
        }

        // Chave única para o usuário
        $key = 'discord_rate_limit:' . $message->author->id;
        
        // Período em segundos
        $period = $this->config['period'] ?? 60;
        
        // Número máximo de comandos no período
        $maxCommands = $this->config['max_commands'] ?? 5;

        // Obter o número atual de comandos no período
        $currentCount = Cache::get($key, 0);

        // Se excedeu o limite, informa o usuário
        if ($currentCount >= $maxCommands) {
            $this->logger->warning('Rate limit excedido', [
                'user' => $message->author->username,
                'user_id' => $message->author->id,
                'channel' => $message->channel->name
            ]);

            // Envia mensagem informando sobre o rate limit
            $message->channel->sendMessage(
                "⚠️ Calma lá, {$message->author->username}! Você está enviando comandos rápido demais. " .
                "Aguarde um pouco antes de enviar outro comando."
            )->done(function ($sentMessage) use ($period) {
                // Auto-delete após 5 segundos
                $sentMessage->delayedDelete(5000);
            });

            // Não continua para o próximo middleware
            return null;
        }

        // Incrementa o contador
        Cache::put($key, $currentCount + 1, $period);

        // Continua para o próximo middleware
        return $next($message);
    }
} 