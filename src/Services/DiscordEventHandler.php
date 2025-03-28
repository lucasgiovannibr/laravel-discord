<?php

namespace LucasGiovanni\DiscordBotInstaller\Services;

use Discord\Discord;
use Discord\Parts\User\Member;
use Discord\WebSockets\Event;
use Discord\Parts\Channel\Message;

class DiscordEventHandler
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
     * Registra o handler para evento de novo membro
     */
    public function registerGuildMemberAddHandler(): void
    {
        $this->discord->on(Event::GUILD_MEMBER_ADD, function (Member $member, Discord $discord) {
            $this->logger->info('Novo membro adicionado ao servidor', [
                'user' => $member->user->username,
                'guild' => $member->guild->name
            ]);
            
            // Buscar o canal de boas-vindas
            $welcomeChannelId = config('discordbot.channels.welcome');
            if (empty($welcomeChannelId)) {
                return;
            }
            
            // Buscar a mensagem de boas-vindas
            $welcomeMessage = config('discordbot.messages.welcome', 'Bem-vindo(a) {user} ao servidor!');
            $welcomeMessage = str_replace('{user}', "<@{$member->user->id}>", $welcomeMessage);
            
            try {
                // Encontrar o canal
                $channel = $discord->getChannel($welcomeChannelId);
                if ($channel) {
                    $channel->sendMessage($welcomeMessage);
                } else {
                    $this->logger->warning('Canal de boas-vindas não encontrado', [
                        'channel_id' => $welcomeChannelId
                    ]);
                }
            } catch (\Exception $e) {
                $this->logger->error('Erro ao enviar mensagem de boas-vindas', [
                    'error' => $e->getMessage()
                ]);
            }
        });
    }
    
    /**
     * Registra o handler para evento de reação em mensagem
     */
    public function registerReactionAddHandler(): void
    {
        $this->discord->on(Event::MESSAGE_REACTION_ADD, function ($reaction, Discord $discord) {
            // Ignorar reações do próprio bot
            if ($reaction->user_id === $discord->user->id) {
                return;
            }
            
            $this->logger->debug('Reação adicionada a uma mensagem', [
                'emoji' => $reaction->emoji->name,
                'message_id' => $reaction->message_id,
                'user_id' => $reaction->user_id
            ]);
            
            // Aqui você pode adicionar lógica personalizada para reações
            // Por exemplo, role assignment, reaction roles, etc.
        });
    }
    
    /**
     * Registra o handler para interações (slash commands, buttons, etc)
     */
    public function registerInteractionCreateHandler(): void
    {
        $this->discord->on(Event::INTERACTION_CREATE, function ($interaction, Discord $discord) {
            $this->logger->debug('Interação recebida', [
                'type' => $interaction->type,
                'user' => $interaction->user->username ?? 'Desconhecido'
            ]);
            
            // Lidar com diferentes tipos de interação
            switch ($interaction->type) {
                // Slash Commands
                case 2:
                    $this->handleSlashCommand($interaction);
                    break;
                    
                // Componentes (botões, select menus)
                case 3:
                    $this->handleComponentInteraction($interaction);
                    break;
                    
                default:
                    $this->logger->warning('Tipo de interação não suportado', [
                        'type' => $interaction->type
                    ]);
                    break;
            }
        });
    }
    
    /**
     * Manipula slash commands
     */
    protected function handleSlashCommand($interaction): void
    {
        $commandName = $interaction->data->name ?? 'unknown';
        
        $this->logger->info("Slash command recebido: /{$commandName}", [
            'options' => $interaction->data->options ?? []
        ]);
        
        // Responder que recebeu a interação
        try {
            $interaction->acknowledge()->done(function () use ($interaction, $commandName) {
                // Responder com uma mensagem
                $interaction->sendFollowUpMessage([
                    'content' => "Comando /{$commandName} recebido! Esta funcionalidade está em desenvolvimento."
                ]);
            });
        } catch (\Exception $e) {
            $this->logger->error('Erro ao responder slash command', [
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Manipula interações de componentes (botões, select menus)
     */
    protected function handleComponentInteraction($interaction): void
    {
        $customId = $interaction->data->custom_id ?? 'unknown';
        
        $this->logger->info("Interação de componente recebida: {$customId}", [
            'component_type' => $interaction->data->component_type ?? 0
        ]);
        
        // Responder que recebeu a interação
        try {
            $interaction->acknowledge()->done(function () use ($interaction, $customId) {
                // Responder com uma mensagem
                $interaction->updateOriginalMessage([
                    'content' => "Interação com '{$customId}' processada com sucesso."
                ]);
            });
        } catch (\Exception $e) {
            $this->logger->error('Erro ao responder interação de componente', [
                'error' => $e->getMessage()
            ]);
        }
    }
} 