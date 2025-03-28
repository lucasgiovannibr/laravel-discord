<?php

namespace LucasGiovanni\DiscordBotInstaller\Commands\Ticket;

use Discord\Discord;
use Discord\Parts\Channel\Message;
use Discord\Builders\MessageBuilder;
use Discord\Parts\Embed\Embed;
use Discord\Parts\Interactions\Interaction;
use Discord\Parts\Interactions\Command\Option;
use LucasGiovanni\DiscordBotInstaller\Services\TicketService;
use LucasGiovanni\DiscordBotInstaller\Models\DiscordTicket;
use LucasGiovanni\DiscordBotInstaller\Commands\AbstractCommand;
use Exception;

class TicketCommand extends AbstractCommand
{
    /**
     * @var string Nome do comando
     */
    protected $name = 'ticket';
    
    /**
     * @var string Descrição do comando
     */
    protected $description = 'Gerencia tickets de suporte';
    
    /**
     * @var array Subcomandos
     */
    protected $subCommands = [
        'create' => 'Cria um novo ticket de suporte',
        'close' => 'Fecha um ticket de suporte',
        'add' => 'Adiciona um usuário a um ticket existente',
        'remove' => 'Remove um usuário de um ticket',
        'list' => 'Lista todos os tickets abertos',
        'open' => 'Reabre um ticket fechado',
        'info' => 'Mostra informações sobre um ticket'
    ];
    
    /**
     * @var TicketService
     */
    protected $ticketService;
    
    /**
     * Construtor
     *
     * @param Discord $discord
     * @param TicketService $ticketService
     */
    public function __construct(Discord $discord, TicketService $ticketService)
    {
        parent::__construct($discord);
        $this->ticketService = $ticketService;
    }
    
    /**
     * Configura as opções do comando
     *
     * @return array
     */
    public function getOptions(): array
    {
        return [
            [
                'name' => 'create',
                'description' => $this->subCommands['create'],
                'type' => Option::SUB_COMMAND,
                'options' => [
                    [
                        'name' => 'assunto',
                        'description' => 'Assunto do ticket',
                        'type' => Option::STRING,
                        'required' => true
                    ],
                    [
                        'name' => 'mensagem',
                        'description' => 'Mensagem inicial (opcional)',
                        'type' => Option::STRING,
                        'required' => false
                    ]
                ]
            ],
            [
                'name' => 'close',
                'description' => $this->subCommands['close'],
                'type' => Option::SUB_COMMAND,
                'options' => [
                    [
                        'name' => 'razao',
                        'description' => 'Razão do fechamento (opcional)',
                        'type' => Option::STRING,
                        'required' => false
                    ]
                ]
            ],
            [
                'name' => 'add',
                'description' => $this->subCommands['add'],
                'type' => Option::SUB_COMMAND,
                'options' => [
                    [
                        'name' => 'usuario',
                        'description' => 'Usuário a ser adicionado ao ticket',
                        'type' => Option::USER,
                        'required' => true
                    ]
                ]
            ],
            [
                'name' => 'remove',
                'description' => $this->subCommands['remove'],
                'type' => Option::SUB_COMMAND,
                'options' => [
                    [
                        'name' => 'usuario',
                        'description' => 'Usuário a ser removido do ticket',
                        'type' => Option::USER,
                        'required' => true
                    ]
                ]
            ],
            [
                'name' => 'list',
                'description' => $this->subCommands['list'],
                'type' => Option::SUB_COMMAND
            ],
            [
                'name' => 'open',
                'description' => $this->subCommands['open'],
                'type' => Option::SUB_COMMAND,
                'options' => [
                    [
                        'name' => 'id',
                        'description' => 'ID do ticket a ser reaberto',
                        'type' => Option::INTEGER,
                        'required' => true
                    ]
                ]
            ],
            [
                'name' => 'info',
                'description' => $this->subCommands['info'],
                'type' => Option::SUB_COMMAND,
                'options' => [
                    [
                        'name' => 'id',
                        'description' => 'ID do ticket (opcional, usa o canal atual se omitido)',
                        'type' => Option::INTEGER,
                        'required' => false
                    ]
                ]
            ]
        ];
    }
    
    /**
     * Executa o comando
     *
     * @param Message $message
     * @param array $args
     * @return void
     */
    public function handle(Message $message, array $args = []): void
    {
        // Verifica se é um subcomando válido
        if (empty($args) || !isset($args[0]) || !array_key_exists($args[0], $this->subCommands)) {
            $this->showHelp($message);
            return;
        }
        
        $subCommand = $args[0];
        $args = array_slice($args, 1);
        
        try {
            switch ($subCommand) {
                case 'create':
                    $this->createTicket($message, $args);
                    break;
                    
                case 'close':
                    $this->closeTicket($message, $args);
                    break;
                    
                case 'add':
                    $this->addUserToTicket($message, $args);
                    break;
                    
                case 'remove':
                    $this->removeUserFromTicket($message, $args);
                    break;
                    
                case 'list':
                    $this->listTickets($message);
                    break;
                    
                case 'open':
                    $this->reopenTicket($message, $args);
                    break;
                    
                case 'info':
                    $this->ticketInfo($message, $args);
                    break;
                    
                default:
                    $this->showHelp($message);
                    break;
            }
        } catch (Exception $e) {
            $this->sendErrorMessage($message, 'Ocorreu um erro ao processar o comando: ' . $e->getMessage());
        }
    }
    
    /**
     * Manipula interações de comando slash
     *
     * @param Interaction $interaction
     * @return void
     */
    public function handleInteraction(Interaction $interaction): void
    {
        $options = $interaction->data->options;
        $subCommand = $options[0]->name;
        $subOptions = isset($options[0]->options) ? $options[0]->options : [];
        
        try {
            switch ($subCommand) {
                case 'create':
                    $subject = $this->getOptionValue($subOptions, 'assunto');
                    $message = $this->getOptionValue($subOptions, 'mensagem');
                    
                    $this->handleInteractionCreateTicket($interaction, $subject, $message);
                    break;
                    
                case 'close':
                    $reason = $this->getOptionValue($subOptions, 'razao');
                    
                    $this->handleInteractionCloseTicket($interaction, $reason);
                    break;
                    
                case 'add':
                    $userId = $this->getOptionValue($subOptions, 'usuario');
                    
                    $this->handleInteractionAddUser($interaction, $userId);
                    break;
                    
                case 'remove':
                    $userId = $this->getOptionValue($subOptions, 'usuario');
                    
                    $this->handleInteractionRemoveUser($interaction, $userId);
                    break;
                    
                case 'list':
                    $this->handleInteractionListTickets($interaction);
                    break;
                    
                case 'open':
                    $ticketId = $this->getOptionValue($subOptions, 'id');
                    
                    $this->handleInteractionReopenTicket($interaction, $ticketId);
                    break;
                    
                case 'info':
                    $ticketId = $this->getOptionValue($subOptions, 'id');
                    
                    $this->handleInteractionTicketInfo($interaction, $ticketId);
                    break;
            }
        } catch (Exception $e) {
            $interaction->respondWithMessage(MessageBuilder::new()
                ->setContent('❌ Ocorreu um erro: ' . $e->getMessage())
                ->setEphemeral(true));
        }
    }
    
    /**
     * Obtém o valor de uma opção de interação
     *
     * @param array $options
     * @param string $name
     * @return mixed|null
     */
    private function getOptionValue(array $options, string $name)
    {
        foreach ($options as $option) {
            if ($option->name === $name) {
                return $option->value;
            }
        }
        
        return null;
    }
    
    /**
     * Cria um novo ticket
     *
     * @param Message $message
     * @param array $args
     * @return void
     */
    private function createTicket(Message $message, array $args): void
    {
        if (empty($args)) {
            $this->sendErrorMessage($message, 'Por favor, forneça um assunto para o ticket.');
            return;
        }
        
        $subject = $args[0];
        $initialMessage = count($args) > 1 ? implode(' ', array_slice($args, 1)) : null;
        
        $result = $this->ticketService->createTicket(
            $message->guild_id,
            $message->author->id,
            $subject,
            $initialMessage
        );
        
        if ($result === null) {
            // Verifica se já existe um ticket aberto
            $existingTicket = DiscordTicket::where('guild_id', $message->guild_id)
                                          ->where('user_id', $message->author->id)
                                          ->whereIn('status', ['open', 'pending'])
                                          ->first();
            
            if ($existingTicket) {
                $channel = $this->discord->getChannel($existingTicket->channel_id);
                $channelMention = $channel ? "<#{$channel->id}>" : "canal excluído";
                
                $this->sendErrorMessage(
                    $message,
                    "Você já possui um ticket aberto em {$channelMention}. " .
                    "Por favor, utilize esse ticket ou feche-o antes de abrir outro."
                );
            } else {
                $response = MessageBuilder::new()
                    ->setContent('Seu ticket está sendo criado, aguarde um momento...');
                
                $message->channel->sendMessage($response);
            }
        }
    }
    
    /**
     * Fecha um ticket
     *
     * @param Message $message
     * @param array $args
     * @return void
     */
    private function closeTicket(Message $message, array $args): void
    {
        $reason = empty($args) ? null : implode(' ', $args);
        
        $result = $this->ticketService->closeTicket(
            $message->channel_id,
            $message->author->id,
            $reason
        );
        
        if (!$result) {
            $this->sendErrorMessage($message, 'Este canal não é um ticket aberto.');
        }
    }
    
    /**
     * Adiciona um usuário a um ticket
     *
     * @param Message $message
     * @param array $args
     * @return void
     */
    private function addUserToTicket(Message $message, array $args): void
    {
        if (empty($args) || empty($message->mentions)) {
            $this->sendErrorMessage($message, 'Por favor, mencione o usuário a ser adicionado.');
            return;
        }
        
        $userId = $message->mentions->first()->id;
        
        $result = $this->ticketService->addUserToTicket(
            $message->channel_id,
            $userId
        );
        
        if (!$result) {
            $this->sendErrorMessage($message, 'Não foi possível adicionar o usuário ao ticket.');
        }
    }
    
    /**
     * Remove um usuário de um ticket
     *
     * @param Message $message
     * @param array $args
     * @return void
     */
    private function removeUserFromTicket(Message $message, array $args): void
    {
        if (empty($args) || empty($message->mentions)) {
            $this->sendErrorMessage($message, 'Por favor, mencione o usuário a ser removido.');
            return;
        }
        
        $userId = $message->mentions->first()->id;
        
        $result = $this->ticketService->removeUserFromTicket(
            $message->channel_id,
            $userId
        );
        
        if (!$result) {
            $this->sendErrorMessage(
                $message,
                'Não foi possível remover o usuário do ticket. O usuário mencionado pode ser o criador do ticket.'
            );
        }
    }
    
    /**
     * Lista todos os tickets abertos
     *
     * @param Message $message
     * @return void
     */
    private function listTickets(Message $message): void
    {
        $tickets = $this->ticketService->getActiveTickets($message->guild_id);
        
        if ($tickets->isEmpty()) {
            $embed = new Embed($this->discord);
            $embed->setTitle('Tickets Ativos');
            $embed->setDescription('Não há tickets abertos no momento.');
            $embed->setColor(0x3498db);
            
            $message->channel->sendMessage(MessageBuilder::new()->addEmbed($embed));
            return;
        }
        
        $embed = new Embed($this->discord);
        $embed->setTitle('Tickets Ativos');
        $embed->setDescription('Lista de todos os tickets atualmente abertos:');
        $embed->setColor(0x3498db);
        
        foreach ($tickets as $ticket) {
            $channel = $this->discord->getChannel($ticket->channel_id);
            $channelMention = $channel ? "<#{$channel->id}>" : "canal excluído";
            
            $lastActivity = $ticket->last_activity->diffForHumans();
            
            $embed->addField([
                'name' => "Ticket #{$ticket->id} - {$channelMention}",
                'value' => "**Assunto:** {$ticket->subject}\n" .
                          "**Aberto por:** <@{$ticket->user_id}>\n" .
                          "**Última atividade:** {$lastActivity}",
                'inline' => false
            ]);
        }
        
        $message->channel->sendMessage(MessageBuilder::new()->addEmbed($embed));
    }
    
    /**
     * Reabre um ticket fechado
     *
     * @param Message $message
     * @param array $args
     * @return void
     */
    private function reopenTicket(Message $message, array $args): void
    {
        if (empty($args)) {
            $this->sendErrorMessage($message, 'Por favor, forneça o ID do ticket a ser reaberto.');
            return;
        }
        
        $ticketId = (int) $args[0];
        
        $result = $this->ticketService->reopenTicket(
            $ticketId,
            $message->author->id
        );
        
        if (!$result) {
            $this->sendErrorMessage(
                $message,
                'Não foi possível reabrir o ticket. Verifique se o ID está correto e se o ticket está fechado.'
            );
        } else {
            $embed = new Embed($this->discord);
            $embed->setTitle('Ticket Reaberto');
            $embed->setDescription("O ticket #{$ticketId} foi reaberto com sucesso.");
            $embed->setColor(0x2ecc71);
            
            $message->channel->sendMessage(MessageBuilder::new()->addEmbed($embed));
        }
    }
    
    /**
     * Mostra informações sobre um ticket
     *
     * @param Message $message
     * @param array $args
     * @return void
     */
    private function ticketInfo(Message $message, array $args): void
    {
        if (empty($args)) {
            // Tenta obter informações do ticket atual
            $ticket = DiscordTicket::where('channel_id', $message->channel_id)->first();
            
            if (!$ticket) {
                $this->sendErrorMessage($message, 'Este canal não é um ticket. Forneça um ID de ticket válido.');
                return;
            }
        } else {
            $ticketId = (int) $args[0];
            $ticket = DiscordTicket::find($ticketId);
            
            if (!$ticket) {
                $this->sendErrorMessage($message, "Não foi encontrado um ticket com o ID #{$ticketId}.");
                return;
            }
        }
        
        $embed = new Embed($this->discord);
        $embed->setTitle("Informações do Ticket #{$ticket->id}");
        $embed->setColor($ticket->status === 'open' ? 0x2ecc71 : 0xe74c3c);
        
        $channel = $this->discord->getChannel($ticket->channel_id);
        $channelMention = $channel ? "<#{$channel->id}>" : "canal excluído";
        
        $embed->addField([
            'name' => 'Assunto',
            'value' => $ticket->subject,
            'inline' => true
        ]);
        
        $embed->addField([
            'name' => 'Status',
            'value' => ucfirst($ticket->status),
            'inline' => true
        ]);
        
        $embed->addField([
            'name' => 'Canal',
            'value' => $channelMention,
            'inline' => true
        ]);
        
        $embed->addField([
            'name' => 'Criado por',
            'value' => "<@{$ticket->user_id}>",
            'inline' => true
        ]);
        
        $embed->addField([
            'name' => 'Data de criação',
            'value' => $ticket->created_at->format('d/m/Y H:i:s'),
            'inline' => true
        ]);
        
        $embed->addField([
            'name' => 'Última atividade',
            'value' => $ticket->last_activity->diffForHumans(),
            'inline' => true
        ]);
        
        if ($ticket->status === 'closed') {
            $embed->addField([
                'name' => 'Fechado por',
                'value' => $ticket->closed_by ? "<@{$ticket->closed_by}>" : "Desconhecido",
                'inline' => true
            ]);
            
            $embed->addField([
                'name' => 'Data de fechamento',
                'value' => $ticket->closed_at ? $ticket->closed_at->format('d/m/Y H:i:s') : "Desconhecida",
                'inline' => true
            ]);
            
            if ($ticket->transcript_url) {
                $embed->addField([
                    'name' => 'Transcrição',
                    'value' => "[Ver transcrição]({$ticket->transcript_url})",
                    'inline' => true
                ]);
            }
        }
        
        $message->channel->sendMessage(MessageBuilder::new()->addEmbed($embed));
    }
    
    /**
     * Envia uma mensagem de erro
     *
     * @param Message $message
     * @param string $errorMessage
     * @return void
     */
    private function sendErrorMessage(Message $message, string $errorMessage): void
    {
        $embed = new Embed($this->discord);
        $embed->setTitle('Erro');
        $embed->setDescription($errorMessage);
        $embed->setColor(0xe74c3c);
        
        $message->channel->sendMessage(MessageBuilder::new()->addEmbed($embed));
    }
    
    /**
     * Mostra a ajuda do comando
     *
     * @param Message $message
     * @return void
     */
    private function showHelp(Message $message): void
    {
        $embed = new Embed($this->discord);
        $embed->setTitle('Ajuda do comando Ticket');
        $embed->setDescription('Este comando permite gerenciar tickets de suporte.');
        $embed->setColor(0x3498db);
        
        foreach ($this->subCommands as $subCommand => $description) {
            $embed->addField([
                'name' => $this->getPrefix() . $this->getName() . ' ' . $subCommand,
                'value' => $description,
                'inline' => false
            ]);
        }
        
        $message->channel->sendMessage(MessageBuilder::new()->addEmbed($embed));
    }
    
    /**
     * Manipula a interação de criar ticket
     *
     * @param Interaction $interaction
     * @param string $subject
     * @param string|null $message
     * @return void
     */
    private function handleInteractionCreateTicket(Interaction $interaction, string $subject, ?string $message = null): void
    {
        // Verifica se já existe um ticket aberto
        $existingTicket = DiscordTicket::where('guild_id', $interaction->guild_id)
                                      ->where('user_id', $interaction->user->id)
                                      ->whereIn('status', ['open', 'pending'])
                                      ->first();
        
        if ($existingTicket) {
            $channel = $this->discord->getChannel($existingTicket->channel_id);
            $channelMention = $channel ? "<#{$channel->id}>" : "canal excluído";
            
            $interaction->respondWithMessage(MessageBuilder::new()
                ->setContent("❌ Você já possui um ticket aberto em {$channelMention}. " .
                             "Por favor, utilize esse ticket ou feche-o antes de abrir outro.")
                ->setEphemeral(true));
            return;
        }
        
        // Responde imediatamente
        $interaction->respondWithMessage(MessageBuilder::new()
            ->setContent('✅ Seu ticket está sendo criado, aguarde um momento...')
            ->setEphemeral(true));
        
        // Cria o ticket
        $this->ticketService->createTicket(
            $interaction->guild_id,
            $interaction->user->id,
            $subject,
            $message
        );
    }
    
    /**
     * Manipula a interação de fechar ticket
     *
     * @param Interaction $interaction
     * @param string|null $reason
     * @return void
     */
    private function handleInteractionCloseTicket(Interaction $interaction, ?string $reason = null): void
    {
        $result = $this->ticketService->closeTicket(
            $interaction->channel_id,
            $interaction->user->id,
            $reason
        );
        
        if (!$result) {
            $interaction->respondWithMessage(MessageBuilder::new()
                ->setContent('❌ Este canal não é um ticket aberto.')
                ->setEphemeral(true));
        } else {
            $interaction->respondWithMessage(MessageBuilder::new()
                ->setContent('✅ Ticket fechado com sucesso.')
                ->setEphemeral(true));
        }
    }
    
    /**
     * Manipula a interação de adicionar usuário
     *
     * @param Interaction $interaction
     * @param string $userId
     * @return void
     */
    private function handleInteractionAddUser(Interaction $interaction, string $userId): void
    {
        $result = $this->ticketService->addUserToTicket(
            $interaction->channel_id,
            $userId
        );
        
        if (!$result) {
            $interaction->respondWithMessage(MessageBuilder::new()
                ->setContent('❌ Não foi possível adicionar o usuário ao ticket.')
                ->setEphemeral(true));
        } else {
            $interaction->respondWithMessage(MessageBuilder::new()
                ->setContent("✅ <@{$userId}> foi adicionado ao ticket.")
                ->setEphemeral(true));
        }
    }
    
    /**
     * Manipula a interação de remover usuário
     *
     * @param Interaction $interaction
     * @param string $userId
     * @return void
     */
    private function handleInteractionRemoveUser(Interaction $interaction, string $userId): void
    {
        $result = $this->ticketService->removeUserFromTicket(
            $interaction->channel_id,
            $userId
        );
        
        if (!$result) {
            $interaction->respondWithMessage(MessageBuilder::new()
                ->setContent('❌ Não foi possível remover o usuário do ticket. O usuário mencionado pode ser o criador do ticket.')
                ->setEphemeral(true));
        } else {
            $interaction->respondWithMessage(MessageBuilder::new()
                ->setContent("✅ <@{$userId}> foi removido do ticket.")
                ->setEphemeral(true));
        }
    }
    
    /**
     * Manipula a interação de listar tickets
     *
     * @param Interaction $interaction
     * @return void
     */
    private function handleInteractionListTickets(Interaction $interaction): void
    {
        $tickets = $this->ticketService->getActiveTickets($interaction->guild_id);
        
        if ($tickets->isEmpty()) {
            $embed = new Embed($this->discord);
            $embed->setTitle('Tickets Ativos');
            $embed->setDescription('Não há tickets abertos no momento.');
            $embed->setColor(0x3498db);
            
            $interaction->respondWithMessage(MessageBuilder::new()->addEmbed($embed));
            return;
        }
        
        $embed = new Embed($this->discord);
        $embed->setTitle('Tickets Ativos');
        $embed->setDescription('Lista de todos os tickets atualmente abertos:');
        $embed->setColor(0x3498db);
        
        foreach ($tickets as $ticket) {
            $channel = $this->discord->getChannel($ticket->channel_id);
            $channelMention = $channel ? "<#{$channel->id}>" : "canal excluído";
            
            $lastActivity = $ticket->last_activity->diffForHumans();
            
            $embed->addField([
                'name' => "Ticket #{$ticket->id} - {$channelMention}",
                'value' => "**Assunto:** {$ticket->subject}\n" .
                          "**Aberto por:** <@{$ticket->user_id}>\n" .
                          "**Última atividade:** {$lastActivity}",
                'inline' => false
            ]);
        }
        
        $interaction->respondWithMessage(MessageBuilder::new()->addEmbed($embed));
    }
    
    /**
     * Manipula a interação de reabrir ticket
     *
     * @param Interaction $interaction
     * @param int $ticketId
     * @return void
     */
    private function handleInteractionReopenTicket(Interaction $interaction, int $ticketId): void
    {
        $result = $this->ticketService->reopenTicket(
            $ticketId,
            $interaction->user->id
        );
        
        if (!$result) {
            $interaction->respondWithMessage(MessageBuilder::new()
                ->setContent('❌ Não foi possível reabrir o ticket. Verifique se o ID está correto e se o ticket está fechado.')
                ->setEphemeral(true));
        } else {
            $interaction->respondWithMessage(MessageBuilder::new()
                ->setContent("✅ O ticket #{$ticketId} foi reaberto com sucesso.")
                ->setEphemeral(true));
        }
    }
    
    /**
     * Manipula a interação de informações do ticket
     *
     * @param Interaction $interaction
     * @param int|null $ticketId
     * @return void
     */
    private function handleInteractionTicketInfo(Interaction $interaction, ?int $ticketId = null): void
    {
        if (!$ticketId) {
            // Tenta obter informações do ticket atual
            $ticket = DiscordTicket::where('channel_id', $interaction->channel_id)->first();
            
            if (!$ticket) {
                $interaction->respondWithMessage(MessageBuilder::new()
                    ->setContent('❌ Este canal não é um ticket. Forneça um ID de ticket válido.')
                    ->setEphemeral(true));
                return;
            }
        } else {
            $ticket = DiscordTicket::find($ticketId);
            
            if (!$ticket) {
                $interaction->respondWithMessage(MessageBuilder::new()
                    ->setContent("❌ Não foi encontrado um ticket com o ID #{$ticketId}.")
                    ->setEphemeral(true));
                return;
            }
        }
        
        $embed = new Embed($this->discord);
        $embed->setTitle("Informações do Ticket #{$ticket->id}");
        $embed->setColor($ticket->status === 'open' ? 0x2ecc71 : 0xe74c3c);
        
        $channel = $this->discord->getChannel($ticket->channel_id);
        $channelMention = $channel ? "<#{$channel->id}>" : "canal excluído";
        
        $embed->addField([
            'name' => 'Assunto',
            'value' => $ticket->subject,
            'inline' => true
        ]);
        
        $embed->addField([
            'name' => 'Status',
            'value' => ucfirst($ticket->status),
            'inline' => true
        ]);
        
        $embed->addField([
            'name' => 'Canal',
            'value' => $channelMention,
            'inline' => true
        ]);
        
        $embed->addField([
            'name' => 'Criado por',
            'value' => "<@{$ticket->user_id}>",
            'inline' => true
        ]);
        
        $embed->addField([
            'name' => 'Data de criação',
            'value' => $ticket->created_at->format('d/m/Y H:i:s'),
            'inline' => true
        ]);
        
        $embed->addField([
            'name' => 'Última atividade',
            'value' => $ticket->last_activity->diffForHumans(),
            'inline' => true
        ]);
        
        if ($ticket->status === 'closed') {
            $embed->addField([
                'name' => 'Fechado por',
                'value' => $ticket->closed_by ? "<@{$ticket->closed_by}>" : "Desconhecido",
                'inline' => true
            ]);
            
            $embed->addField([
                'name' => 'Data de fechamento',
                'value' => $ticket->closed_at ? $ticket->closed_at->format('d/m/Y H:i:s') : "Desconhecida",
                'inline' => true
            ]);
            
            if ($ticket->transcript_url) {
                $embed->addField([
                    'name' => 'Transcrição',
                    'value' => "[Ver transcrição]({$ticket->transcript_url})",
                    'inline' => true
                ]);
            }
        }
        
        $interaction->respondWithMessage(MessageBuilder::new()->addEmbed($embed));
    }
} 