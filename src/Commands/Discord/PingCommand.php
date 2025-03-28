<?php

namespace LucasGiovanni\DiscordBotInstaller\Commands\Discord;

use Discord\Discord;
use Discord\Parts\Channel\Message;
use LucasGiovanni\DiscordBotInstaller\Services\DiscordLogger;

class PingCommand
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
        $start = microtime(true);
        
        // Enviar mensagem inicial
        $message->channel->sendMessage('Calculando latência...')->done(function ($sentMessage) use ($message, $start) {
            // Calcular tempo decorrido
            $latency = round((microtime(true) - $start) * 1000);
            
            // Editar mensagem com o resultado
            $sentMessage->edit("🏓 Pong! Latência: {$latency}ms | API: {$this->discord->heartbeatInterval}ms");
            
            $this->logger->info('Comando ping executado', [
                'latency' => $latency,
                'user' => $message->author->username,
                'channel' => $message->channel->name ?? 'DM'
            ]);
        });
    }
} 