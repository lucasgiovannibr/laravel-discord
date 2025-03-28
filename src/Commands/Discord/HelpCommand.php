<?php

namespace LucasGiovanni\DiscordBotInstaller\Commands\Discord;

use Discord\Discord;
use Discord\Parts\Channel\Message;
use LucasGiovanni\DiscordBotInstaller\Services\DiscordLogger;

class HelpCommand
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
     * Comandos disponíveis
     */
    protected $commands;
    
    /**
     * Construtor
     */
    public function __construct(Discord $discord, DiscordLogger $logger, array $commands = [])
    {
        $this->discord = $discord;
        $this->logger = $logger;
        $this->commands = $commands;
    }
    
    /**
     * Manipula o comando
     */
    public function handle(Message $message, array $args = []): void
    {
        $prefix = config('discordbot.command_prefix', '!');
        
        // Se foi especificado um comando, mostrar ajuda detalhada
        if (!empty($args) && isset($this->commands[$args[0]])) {
            $this->showCommandHelp($message, $args[0], $prefix);
            return;
        }
        
        // Caso contrário, mostrar lista de comandos
        $this->showCommandList($message, $prefix);
    }
    
    /**
     * Mostra a lista de comandos disponíveis
     */
    protected function showCommandList(Message $message, string $prefix): void
    {
        $content = "**📝 Lista de Comandos Disponíveis**\n\n";
        
        foreach ($this->commands as $name => $data) {
            $content .= "**{$prefix}{$name}** - {$data['description']}\n";
        }
        
        $content .= "\nUse `{$prefix}help <comando>` para mais informações sobre um comando específico.";
        
        $message->channel->sendMessage($content);
        
        $this->logger->info('Comando help executado: lista de comandos', [
            'user' => $message->author->username,
            'channel' => $message->channel->name ?? 'DM'
        ]);
    }
    
    /**
     * Mostra ajuda detalhada sobre um comando específico
     */
    protected function showCommandHelp(Message $message, string $commandName, string $prefix): void
    {
        $command = $this->commands[$commandName];
        
        $content = "**ℹ️ Ajuda: {$prefix}{$commandName}**\n\n";
        $content .= "**Descrição:** {$command['description']}\n";
        
        // Verificar se o handler tem um método getHelp para obter mais informações
        if (method_exists($command['handler'], 'getHelp')) {
            $helpInfo = $command['handler']->getHelp();
            
            if (isset($helpInfo['usage'])) {
                $content .= "**Uso:** `{$prefix}{$commandName} {$helpInfo['usage']}`\n";
            }
            
            if (isset($helpInfo['examples']) && !empty($helpInfo['examples'])) {
                $content .= "**Exemplos:**\n";
                foreach ($helpInfo['examples'] as $example) {
                    $content .= "- `{$prefix}{$commandName} {$example}`\n";
                }
            }
            
            if (isset($helpInfo['notes']) && !empty($helpInfo['notes'])) {
                $content .= "**Observações:**\n{$helpInfo['notes']}\n";
            }
        } else {
            $content .= "**Uso:** `{$prefix}{$commandName}`\n";
        }
        
        $message->channel->sendMessage($content);
        
        $this->logger->info('Comando help executado: ajuda específica', [
            'command' => $commandName,
            'user' => $message->author->username,
            'channel' => $message->channel->name ?? 'DM'
        ]);
    }
} 