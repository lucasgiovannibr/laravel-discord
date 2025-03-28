<?php

namespace LucasGiovanni\DiscordBotInstaller;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Config;
use Illuminate\Console\Scheduling\Schedule;
use LucasGiovanni\DiscordBotInstaller\Commands\InstallBotCommand;
use LucasGiovanni\DiscordBotInstaller\Commands\RunBotCommand;
use LucasGiovanni\DiscordBotInstaller\Services\DiscordBotService;
use LucasGiovanni\DiscordBotInstaller\Services\DiscordLogger;
use LucasGiovanni\DiscordBotInstaller\Console\Commands\PublishModuleCommand;
use LucasGiovanni\DiscordBotInstaller\Console\Commands\CreateCommandCommand;
use LucasGiovanni\DiscordBotInstaller\Console\Commands\CreateSlashCommandCommand;
use LucasGiovanni\DiscordBotInstaller\Console\Commands\ProcessRemindersCommand;
use LucasGiovanni\DiscordBotInstaller\Console\Commands\CleanupExpiredWarningsCommand;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

class DiscordBotServiceProvider extends ServiceProvider
{
    /**
     * Array com os comandos do pacote.
     *
     * @var array
     */
    protected $commands = [
        InstallBotCommand::class,
        RunBotCommand::class,
        PublishModuleCommand::class,
        CreateCommandCommand::class,
        CreateSlashCommandCommand::class,
        ProcessRemindersCommand::class,
        CleanupExpiredWarningsCommand::class,
    ];

    /**
     * Array com as migrações do pacote.
     *
     * @var array
     */
    protected $migrations = [
        'create_discord_users_table',
        'create_discord_levels_table',
        'create_discord_warnings_table',
        'create_discord_reminders_table',
    ];

    /**
     * Register services.
     */
    public function register(): void
    {
        // Mescla configurações
        $this->mergeConfigFrom(__DIR__ . '/../config/discordbot.php', 'discordbot');

        // Registra o serviço principal do bot
        $this->app->singleton('discord-bot', function ($app) {
            return new DiscordBotService(
                $app->make(DiscordLogger::class),
                $app->make('cache.store')
            );
        });

        // Registra o serviço de logging
        $this->app->singleton(DiscordLogger::class, function () {
            return new DiscordLogger(
                storage_path('logs/discordbot.log'),
                config('discordbot.logging.level', 'info')
            );
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Publica o arquivo de configuração
        $this->publishes([
            __DIR__ . '/../config/discordbot.php' => config_path('discordbot.php'),
        ], 'discordbot-config');

        // Publica as migrações
        $this->publishMigrations();

        // Registra os comandos
        if ($this->app->runningInConsole()) {
            $this->commands($this->commands);
        }

        // Carrega views
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'discordbot');

        // Publica os assets
        $this->publishes([
            __DIR__ . '/../public' => public_path('vendor/discordbot'),
        ], 'discordbot-assets');

        // Descobrir módulos automaticamente
        if (config('discordbot.modules.auto_discover', true)) {
            $this->discoverModules();
        }

        // Registrar tarefas agendadas
        $this->registerScheduledTasks();
    }

    /**
     * Publica as migrações do pacote.
     */
    protected function publishMigrations(): void
    {
        $path = __DIR__ . '/../database/migrations/';
        
        // Garante que a pasta de migrações exista
        if (!File::exists($path)) {
            return;
        }

        // Registra as migrações
        $this->loadMigrationsFrom($path);

        // Publica migrações com timestamps
        $migrations = [];
        foreach ($this->migrations as $migration) {
            $migrations[$path . $migration . '.php.stub'] = database_path('migrations/' . date('Y_m_d_His') . '_' . $migration . '.php');
        }

        $this->publishes($migrations, 'discordbot-migrations');
    }

    /**
     * Registra tarefas agendadas.
     */
    protected function registerScheduledTasks(): void
    {
        if (!$this->app->runningInConsole()) {
            return;
        }

        $this->app->booted(function () {
            $schedule = $this->app->make(\Illuminate\Console\Scheduling\Schedule::class);
            
            // Processa lembretes a cada minuto
            $schedule->command('bot:process-reminders')
                    ->everyMinute()
                    ->withoutOverlapping();

            // Limpa advertências expiradas diariamente
            $schedule->command('bot:cleanup-warnings')
                    ->daily()
                    ->withoutOverlapping();
        });
    }

    /**
     * Descobrir e registrar módulos automaticamente.
     */
    protected function discoverModules(): void
    {
        $modulesPath = config('discordbot.modules.directory', app_path('DiscordModules'));
        
        if (!File::exists($modulesPath)) {
            return;
        }

        $modules = File::directories($modulesPath);
        
        foreach ($modules as $modulePath) {
            $moduleName = basename($modulePath);
            $providerClass = "App\\DiscordModules\\{$moduleName}\\{$moduleName}ServiceProvider";
            
            // Verifica se o módulo está ativo
            $isActive = config("discordbot.modules.active.{$moduleName}", false);
            
            if (class_exists($providerClass) && $isActive) {
                $this->app->register($providerClass);
            }
        }
    }
} 