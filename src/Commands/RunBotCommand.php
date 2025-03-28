<?php

namespace LucasGiovanni\DiscordBotInstaller\Commands;

use Discord\Discord;
use Discord\Parts\Channel\Message;
use Discord\WebSockets\Event;
use Discord\WebSockets\Intents;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use LucasGiovanni\DiscordBotInstaller\Services\DiscordBotService;
use LucasGiovanni\DiscordBotInstaller\Services\DiscordEventHandler;
use LucasGiovanni\DiscordBotInstaller\Services\DiscordCommandHandler;
use LucasGiovanni\DiscordBotInstaller\Services\DiscordLogger;

class RunBotCommand extends Command
{
    protected $signature = 'bot:run {--debug : Executa o bot em modo debug}';
    protected $description = 'Inicia o bot Discord configurado';

    protected $discord;
    protected $eventHandler;
    protected $commandHandler;
    protected $logger;

    public function handle()
    {
        $this->info('Iniciando Bot Discord...');
        
        // Verificar se o token está configurado
        $token = config('discordbot.token');
        if (empty($token)) {
            $this->error('Token do Discord não configurado! Execute php artisan bot:install primeiro.');
            return Command::FAILURE;
        }
        
        // Configurar logger
        $this->setupLogger();
        
        // Configurar intents
        $intents = Intents::getDefaultIntents() | Intents::MESSAGE_CONTENT;
        
        try {
            // Inicializar Discord
            $this->discord = new Discord([
                'token' => $token,
                'intents' => $intents,
            ]);
            
            // Inicializar handlers
            $this->eventHandler = new DiscordEventHandler($this->discord, $this->logger);
            $this->commandHandler = new DiscordCommandHandler($this->discord, $this->logger);
            
            // Configurar handlers
            $this->setupEventHandlers();
            
            // Exibir informações quando estiver pronto
            $this->discord->on('ready', function (Discord $discord) {
                $botUser = $discord->user;
                $this->info("Bot conectado como {$botUser->username}#{$botUser->discriminator}");
                $this->info('ID do Bot: ' . $botUser->id);
                $this->info('🟢 Bot online e pronto para receber comandos!');
                
                // Configurar status/atividade
                $activity = config('discordbot.activity');
                if ($activity) {
                    $discord->updatePresence([
                        'status' => 'online',
                        'activities' => [
                            [
                                'name' => $activity['name'],
                                'type' => $this->getActivityType($activity['type']),
                            ]
                        ]
                    ]);
                }
                
                $this->logger->info('Bot iniciado com sucesso', [
                    'username' => $botUser->username,
                    'id' => $botUser->id
                ]);
            });
            
            // Executar bot
            $this->info('Conectando ao Discord...');
            $this->discord->run();
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Erro ao iniciar o bot: ' . $e->getMessage());
            $this->logger->error('Falha ao iniciar o bot', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return Command::FAILURE;
        }
    }
    
    protected function setupLogger()
    {
        $logConfig = config('discordbot.logging');
        $logEnabled = $logConfig['enabled'] ?? true;
        $logFile = $logConfig['file'] ?? storage_path('logs/discordbot.log');
        $logLevel = $logConfig['level'] ?? 'info';
        
        // Criar diretório de logs se não existir
        $logDir = dirname($logFile);
        if (!File::exists($logDir)) {
            File::makeDirectory($logDir, 0755, true);
        }
        
        $this->logger = new DiscordLogger($logFile, $logLevel, $logEnabled);
        
        if ($this->option('debug')) {
            $this->logger->setLevel('debug');
            $this->info('Bot executando em modo DEBUG');
        }
    }
    
    protected function setupEventHandlers()
    {
        $events = config('discordbot.events');
        $prefix = config('discordbot.command_prefix');
        
        // Registrar handler de mensagens
        if ($events['message_create'] ?? true) {
            $this->discord->on(Event::MESSAGE_CREATE, function (Message $message, Discord $discord) use ($prefix) {
                // Ignorar mensagens do próprio bot
                if ($message->author->id === $discord->user->id) {
                    return;
                }
                
                $content = $message->content;
                
                // Registrar mensagem no log
                $this->logger->debug('Mensagem recebida', [
                    'user' => $message->author->username,
                    'content' => $content,
                    'channel' => $message->channel->name ?? 'DM'
                ]);
                
                // Processar comandos se começar com o prefixo
                if (substr($content, 0, strlen($prefix)) === $prefix) {
                    $this->commandHandler->handleCommand($message, $prefix);
                }
            });
        }
        
        // Registrar outros handlers
        if ($events['guild_member_add'] ?? true) {
            $this->eventHandler->registerGuildMemberAddHandler();
        }
        
        if ($events['reaction_add'] ?? true) {
            $this->eventHandler->registerReactionAddHandler();
        }
        
        if ($events['interaction_create'] ?? true) {
            $this->eventHandler->registerInteractionCreateHandler();
        }
    }
    
    protected function getActivityType(string $type): int
    {
        switch (strtolower($type)) {
            case 'playing':
                return 0;
            case 'streaming':
                return 1;
            case 'listening':
                return 2;
            case 'watching':
                return 3;
            case 'competing':
                return 5;
            default:
                return 0;
        }
    }
} 