<?php

namespace LucasGiovanni\DiscordBotInstaller\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class InstallBotCommand extends Command
{
    protected $signature = 'bot:install';
    protected $description = 'Instala e configura o bot Discord para o Laravel';

    public function handle()
    {
        $this->info('Iniciando instalação do Bot Discord...');

        // 1. Verificar e publicar configuração
        $this->publishConfiguration();

        // 2. Verificar e atualizar .env
        $this->updateEnvironmentFile();

        // 3. Verificar e criar diretório de logs
        $this->createLogDirectory();

        $this->info('✅ Bot Discord instalado com sucesso!');
        $this->info('Para executar o bot, use o comando: php artisan bot:run');

        return Command::SUCCESS;
    }

    protected function publishConfiguration()
    {
        $this->info('Publicando arquivo de configuração...');
        
        $configPath = config_path('discordbot.php');
        
        if (File::exists($configPath)) {
            if (!$this->confirm('O arquivo de configuração já existe. Deseja sobrescrevê-lo?', false)) {
                $this->info('Publicação do arquivo de configuração ignorada.');
                return;
            }
        }
        
        $this->call('vendor:publish', [
            '--provider' => 'LucasGiovanni\\DiscordBotInstaller\\DiscordBotServiceProvider',
            '--tag' => 'discordbot-config',
            '--force' => true,
        ]);
        
        $this->info('✅ Arquivo de configuração publicado com sucesso.');
    }

    protected function updateEnvironmentFile()
    {
        $this->info('Atualizando arquivo .env...');
        
        $envPath = base_path('.env');
        
        // Verificar se o arquivo .env existe
        if (!File::exists($envPath)) {
            // Se não existir, tentar copiar de .env.example
            if (File::exists(base_path('.env.example'))) {
                File::copy(base_path('.env.example'), $envPath);
                $this->info('Arquivo .env criado a partir de .env.example');
            } else {
                // Criar um novo arquivo .env
                File::put($envPath, "APP_NAME=Laravel\nAPP_ENV=local\nAPP_DEBUG=true\n");
                $this->info('Novo arquivo .env criado');
            }
        }
        
        // Ler o conteúdo atual do .env
        $content = File::get($envPath);
        
        // Variáveis a serem adicionadas/atualizadas
        $vars = [
            'DISCORD_BOT_TOKEN' => '',
            'DISCORD_COMMAND_PREFIX' => '!',
            'DISCORD_BOT_ACTIVITY' => 'Laravel Discord Bot',
            'DISCORD_LOG_LEVEL' => 'info'
        ];
        
        // Adicionar variáveis se não existirem
        $updated = false;
        foreach ($vars as $key => $defaultValue) {
            if (!preg_match("/^{$key}=/m", $content)) {
                $value = $this->ask("Digite o valor para {$key}", $defaultValue);
                $content .= "\n{$key}={$value}";
                $updated = true;
            }
        }
        
        // Solicitar o token do bot se estiver vazio
        if (!preg_match("/^DISCORD_BOT_TOKEN=.+/m", $content)) {
            $token = $this->secret('Digite o token do seu bot Discord (obrigatório)');
            if (!empty($token)) {
                $content = preg_replace("/^DISCORD_BOT_TOKEN=.*$/m", "DISCORD_BOT_TOKEN={$token}", $content);
                $updated = true;
            } else {
                $this->warn('⚠️ Token do bot não fornecido. Configure o DISCORD_BOT_TOKEN no arquivo .env antes de executar o bot.');
            }
        }
        
        if ($updated) {
            File::put($envPath, $content);
            $this->info('✅ Arquivo .env atualizado com sucesso.');
        } else {
            $this->info('O arquivo .env já está configurado.');
        }
    }

    protected function createLogDirectory()
    {
        $logDir = storage_path('logs');
        
        if (!File::exists($logDir)) {
            File::makeDirectory($logDir, 0755, true);
            $this->info('✅ Diretório de logs criado com sucesso.');
        }
    }
} 