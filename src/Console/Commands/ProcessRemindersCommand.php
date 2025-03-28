<?php

namespace LucasGiovanni\DiscordBotInstaller\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use LucasGiovanni\DiscordBotInstaller\Models\DiscordReminder;
use LucasGiovanni\DiscordBotInstaller\Facades\DiscordBot;
use LucasGiovanni\DiscordBotInstaller\Services\DiscordLogger;

class ProcessRemindersCommand extends Command
{
    /**
     * Nome do comando
     *
     * @var string
     */
    protected $signature = 'bot:process-reminders';

    /**
     * Descrição do comando
     *
     * @var string
     */
    protected $description = 'Processa e envia lembretes agendados';
    
    /**
     * Logger
     */
    protected $logger;

    /**
     * Construct
     */
    public function __construct(DiscordLogger $logger)
    {
        parent::__construct();
        $this->logger = $logger;
    }

    /**
     * Execução do comando
     */
    public function handle()
    {
        $this->info('Processando lembretes...');
        
        try {
            // Buscar lembretes pendentes
            $dueReminders = DiscordReminder::where('sent', false)
                ->where('remind_at', '<=', Carbon::now())
                ->get();
                
            $count = $dueReminders->count();
            
            if ($count == 0) {
                $this->info('Nenhum lembrete para processar.');
                return Command::SUCCESS;
            }
            
            $this->info("Encontrados {$count} lembretes para processar.");
            
            $processed = 0;
            
            foreach ($dueReminders as $reminder) {
                try {
                    // Preparar o embed
                    $embed = [
                        'title' => '⏰ Lembrete',
                        'description' => $reminder->message,
                        'color' => 0x3498DB, // Azul
                        'timestamp' => date('c'),
                        'footer' => [
                            'text' => 'Lembrete definido em ' . $reminder->created_at->format('d/m/Y H:i:s')
                        ]
                    ];
                    
                    // Enviar o lembrete com menção ao usuário
                    DiscordBot::sendMessage(
                        $reminder->channel_id,
                        "🔔 <@{$reminder->user_id}>, aqui está seu lembrete!",
                        $embed
                    );
                    
                    // Marcar como enviado
                    $reminder->sent = true;
                    $reminder->save();
                    
                    $processed++;
                } catch (\Exception $e) {
                    $this->error("Erro ao processar lembrete #{$reminder->id}: " . $e->getMessage());
                    
                    $this->logger->error("Erro ao processar lembrete", [
                        'id' => $reminder->id,
                        'error' => $e->getMessage(),
                        'user_id' => $reminder->user_id,
                        'channel_id' => $reminder->channel_id
                    ]);
                }
            }
            
            $this->info("Processados {$processed} lembretes com sucesso.");
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Erro ao processar lembretes: ' . $e->getMessage());
            
            $this->logger->error('Erro ao processar lembretes', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return Command::FAILURE;
        }
    }
} 