<?php

namespace LucasGiovanni\DiscordBotInstaller\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use LucasGiovanni\DiscordBotInstaller\Models\DiscordWarning;
use LucasGiovanni\DiscordBotInstaller\Services\DiscordLogger;

class CleanupExpiredWarningsCommand extends Command
{
    /**
     * Nome do comando
     *
     * @var string
     */
    protected $signature = 'bot:cleanup-warnings {--dry-run : Executar em modo de simulação sem fazer alterações reais}';

    /**
     * Descrição do comando
     *
     * @var string
     */
    protected $description = 'Limpa advertências temporárias expiradas';
    
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
        $isDryRun = $this->option('dry-run');
        
        if ($isDryRun) {
            $this->info('Executando em modo de simulação (dry-run)...');
        } else {
            $this->info('Iniciando limpeza de advertências expiradas...');
        }
        
        try {
            // Buscar advertências ativas temporárias que já expiraram
            $expiredWarnings = DiscordWarning::where('active', true)
                ->whereNotNull('expires_at')
                ->where('expires_at', '<', Carbon::now())
                ->get();
                
            $count = $expiredWarnings->count();
            
            if ($count == 0) {
                $this->info('Nenhuma advertência expirada para limpar.');
                return Command::SUCCESS;
            }
            
            $this->info("Encontradas {$count} advertências expiradas.");
            
            // Se for simulação, apenas mostrar informações
            if ($isDryRun) {
                foreach ($expiredWarnings as $warning) {
                    $this->line(" - Advertência #{$warning->id}: Usuário {$warning->user_id}, Tipo: {$warning->type}, Expiração: {$warning->expires_at}");
                }
                
                $this->info("Total de {$count} advertências seriam desativadas.");
                return Command::SUCCESS;
            }
            
            // Desativar advertências em lote
            $updated = DiscordWarning::where('active', true)
                ->whereNotNull('expires_at')
                ->where('expires_at', '<', Carbon::now())
                ->update(['active' => false]);
                
            $this->info("Desativadas {$updated} advertências expiradas com sucesso.");
            
            $this->logger->info('Advertências expiradas limpas', [
                'count' => $updated
            ]);
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Erro ao limpar advertências: ' . $e->getMessage());
            
            $this->logger->error('Erro ao limpar advertências expiradas', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return Command::FAILURE;
        }
    }
} 