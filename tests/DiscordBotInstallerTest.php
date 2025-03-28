<?php

namespace LucasGiovanni\DiscordBotInstaller\Tests;

use Orchestra\Testbench\TestCase;
use LucasGiovanni\DiscordBotInstaller\DiscordBotServiceProvider;
use LucasGiovanni\DiscordBotInstaller\Services\DiscordLogger;

class DiscordBotInstallerTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [
            DiscordBotServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        // Configurar ambiente para testes
        $app['config']->set('discordbot.token', 'test_token');
        $app['config']->set('discordbot.command_prefix', '!');
    }

    /** @test */
    public function verifica_se_config_foi_carregada_corretamente()
    {
        $this->assertEquals('test_token', config('discordbot.token'));
        $this->assertEquals('!', config('discordbot.command_prefix'));
    }

    /** @test */
    public function verifica_se_logger_funciona_corretamente()
    {
        $logFile = sys_get_temp_dir() . '/discordbot_test.log';
        
        // Criar um logger
        $logger = new DiscordLogger($logFile, 'info', true);
        
        // Registrar algumas mensagens
        $logger->info('Teste de info');
        $logger->error('Teste de erro', ['data' => 'exemplo']);
        
        // Verificar se o arquivo de log foi criado
        $this->assertFileExists($logFile);
        
        // Ler o conteúdo do arquivo
        $content = file_get_contents($logFile);
        
        // Verificar se as mensagens estão presentes
        $this->assertStringContainsString('[INFO] Teste de info', $content);
        $this->assertStringContainsString('[ERROR] Teste de erro', $content);
        $this->assertStringContainsString('"data":"exemplo"', $content);
        
        // Limpar o arquivo de teste
        @unlink($logFile);
    }

    /** @test */
    public function verifica_se_artisan_commands_estao_registrados()
    {
        $this->artisan('bot:install --help')
             ->expectsOutput('Instala e configura o bot Discord para o Laravel')
             ->assertExitCode(0);
        
        $this->artisan('bot:run --help')
             ->expectsOutput('Inicia o bot Discord configurado')
             ->assertExitCode(0);
    }
} 