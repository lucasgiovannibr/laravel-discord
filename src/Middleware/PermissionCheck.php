<?php

namespace LucasGiovanni\DiscordBotInstaller\Middleware;

use Discord\Parts\Channel\Message;
use LucasGiovanni\DiscordBotInstaller\Services\DiscordLogger;

class PermissionCheck implements Middleware
{
    /**
     * O logger
     *
     * @var DiscordLogger
     */
    protected $logger;

    /**
     * Construtor.
     *
     * @param DiscordLogger $logger
     */
    public function __construct(DiscordLogger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Manipula a mensagem e verifica permissões do usuário.
     *
     * @param Message $message
     * @param callable $next
     * @return mixed
     */
    public function handle(Message $message, callable $next)
    {
        // Obter o prefixo de comando
        $prefix = config('discordbot.command_prefix', '!');
        
        // Verificar se é um comando
        if (substr($message->content, 0, strlen($prefix)) !== $prefix) {
            return $next($message);
        }
        
        // Extrair o comando
        $commandName = $this->extractCommandName($message->content, $prefix);
        
        // Obter as permissões necessárias para o comando
        $requiredPermissions = $this->getRequiredPermissions($commandName);
        
        // Se não houver permissões necessárias, continua
        if (empty($requiredPermissions)) {
            return $next($message);
        }
        
        // Verificar permissões do usuário
        if (!$this->userHasPermission($message, $requiredPermissions)) {
            // Registrar falha de permissão
            $this->logger->warning('Permissão negada para comando', [
                'user' => $message->author->username,
                'user_id' => $message->author->id,
                'command' => $commandName,
                'required_permissions' => $requiredPermissions
            ]);
            
            // Informar ao usuário sobre a permissão insuficiente
            $message->channel->sendMessage(
                "⛔ Desculpe, {$message->author->username}, você não tem permissão para executar este comando."
            )->done(function ($sentMessage) {
                // Auto-delete após 5 segundos
                $sentMessage->delayedDelete(5000);
            });
            
            // Interrompe a cadeia de middleware
            return null;
        }
        
        // Continua para o próximo middleware
        return $next($message);
    }
    
    /**
     * Extrai o nome do comando da mensagem.
     *
     * @param string $content
     * @param string $prefix
     * @return string
     */
    protected function extractCommandName(string $content, string $prefix): string
    {
        $withoutPrefix = substr($content, strlen($prefix));
        $parts = explode(' ', $withoutPrefix);
        return strtolower($parts[0]);
    }
    
    /**
     * Obtém as permissões necessárias para um comando.
     *
     * @param string $commandName
     * @return array
     */
    protected function getRequiredPermissions(string $commandName): array
    {
        $commands = config('discordbot.commands', []);
        
        if (isset($commands[$commandName]['permissions'])) {
            return $commands[$commandName]['permissions'];
        }
        
        return [];
    }
    
    /**
     * Verifica se o usuário possui as permissões necessárias.
     *
     * @param Message $message
     * @param array $requiredPermissions
     * @return bool
     */
    protected function userHasPermission(Message $message, array $requiredPermissions): bool
    {
        // Se a mensagem for de DM, verifica apenas para comandos permitidos em DM
        if ($message->channel->type === 'dm') {
            // Comandos em DM geralmente não precisam de permissões especiais
            // Podemos implementar uma lista de comandos permitidos em DM
            return false;
        }
        
        // Obtém o membro do servidor
        $member = $message->member;
        
        // Se não conseguir obter o membro, nega permissão
        if (!$member) {
            return false;
        }
        
        // Verifica se é o dono do servidor (sempre tem todas as permissões)
        if ($member->id === $message->guild->owner_id) {
            return true;
        }
        
        // Verifica cada permissão necessária
        foreach ($requiredPermissions as $permission) {
            if (!$member->getPermissions()->has($permission)) {
                return false;
            }
        }
        
        return true;
    }
} 