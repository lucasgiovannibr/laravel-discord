<?php

namespace LucasGiovanni\DiscordBotInstaller\Middleware;

use Discord\Parts\Channel\Message;

interface Middleware
{
    /**
     * Manipula a mensagem e decide se ela deve continuar para o próximo middleware.
     *
     * @param Message $message A mensagem do Discord
     * @param callable $next A próxima função na cadeia de middleware
     * @return mixed
     */
    public function handle(Message $message, callable $next);
} 