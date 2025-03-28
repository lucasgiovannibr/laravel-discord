<?php

namespace LucasGiovanni\DiscordBotInstaller\Services;

use Discord\Discord;
use Discord\Parts\Channel\Message;
use LucasGiovanni\DiscordBotInstaller\Commands\Discord\PingCommand;
use LucasGiovanni\DiscordBotInstaller\Commands\Discord\HelpCommand;

class DiscordCommandHandler
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
     * Comandos registrados
     */
    protected $commands = [];
    
    /**
     * Construtor
     */
    public function __construct(Discord $discord, DiscordLogger $logger)
    {
        $this->discord = $discord;
        $this->logger = $logger;
        $this->registerCommands();
    }
    
    /**
     * Registra os comandos disponíveis
     */
    protected function registerCommands(): void
    {
        // Registrar comandos padrão
        $this->registerDefaultCommands();
        
        // Registrar comandos personalizados da configuração
        $this->registerCustomCommands();
    }
    
    /**
     * Registra os comandos padrão
     */
    protected function registerDefaultCommands(): void
    {
        // Comandos internos padrão
        $pingCommand = new PingCommand($this->discord, $this->logger);
        $helpCommand = new HelpCommand($this->discord, $this->logger, $this->commands);
        
        $this->commands['ping'] = [
            'description' => 'Verificar se o bot está online',
            'handler' => $pingCommand,
        ];
        
        $this->commands['help'] = [
            'description' => 'Exibir lista de comandos disponíveis',
            'handler' => $helpCommand,
        ];
    }
    
    /**
     * Registra comandos personalizados da configuração
     */
    protected function registerCustomCommands(): void
    {
        $commandsConfig = config('discordbot.commands', []);
        
        foreach ($commandsConfig as $name => $commandData) {
            // Ignorar comandos já registrados
            if (isset($this->commands[$name])) {
                $this->logger->warning("Comando '{$name}' já registrado, ignorando", [
                    'source' => 'config'
                ]);
                continue;
            }
            
            // Verificar se o handler existe
            if (empty($commandData['handler']) || !class_exists($commandData['handler'])) {
                $this->logger->warning("Handler para comando '{$name}' não encontrado", [
                    'handler' => $commandData['handler'] ?? 'não especificado'
                ]);
                continue;
            }
            
            try {
                // Instanciar o handler
                $handler = new $commandData['handler']($this->discord, $this->logger);
                
                // Registrar o comando
                $this->commands[$name] = [
                    'description' => $commandData['description'] ?? "Comando {$name}",
                    'handler' => $handler,
                ];
                
                $this->logger->info("Comando '{$name}' registrado com sucesso");
            } catch (\Exception $e) {
                $this->logger->error("Erro ao registrar comando '{$name}'", [
                    'error' => $e->getMessage()
                ]);
            }
        }
    }
    
    /**
     * Manipula um comando recebido
     */
    public function handleCommand(Message $message, string $prefix): void
    {
        $content = $message->content;
        
        // Extrair o comando e os argumentos
        $parts = explode(' ', substr($content, strlen($prefix)));
        $commandName = strtolower(array_shift($parts));
        $args = $parts;
        
        $this->logger->debug("Comando recebido: {$commandName}", [
            'args' => $args,
            'user' => $message->author->username,
            'channel' => $message->channel->name ?? 'DM'
        ]);
        
        // Verificar se o comando existe
        if (!isset($this->commands[$commandName])) {
            $this->logger->debug("Comando '{$commandName}' não encontrado");
            return;
        }
        
        try {
            // Executar o comando
            $command = $this->commands[$commandName]['handler'];
            
            if (method_exists($command, 'handle')) {
                $command->handle($message, $args);
            } else {
                $this->logger->warning("Handler para '{$commandName}' não possui método handle()");
            }
        } catch (\Exception $e) {
            $this->logger->error("Erro ao executar comando '{$commandName}'", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Tentar enviar mensagem de erro para o canal
            try {
                $message->channel->sendMessage('Ocorreu um erro ao processar este comando. Tente novamente mais tarde.');
            } catch (\Exception $e) {
                // Ignorar erros ao enviar mensagem de erro
            }
        }
    }
    
    /**
     * Retorna a lista de comandos registrados
     */
    public function getCommands(): array
    {
        return $this->commands;
    }
} 