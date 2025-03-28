<?php

namespace LucasGiovanni\DiscordBotInstaller\Services;

use Psr\Log\LoggerInterface;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Formatter\LineFormatter;
use Illuminate\Support\Facades\Log;

class DiscordLogger implements LoggerInterface
{
    /**
     * O logger do Monolog
     *
     * @var Logger
     */
    protected Logger $logger;

    /**
     * O nível de log definido
     *
     * @var string
     */
    protected string $level;

    /**
     * Se deve enviar logs para o canal do Discord
     *
     * @var bool
     */
    protected bool $logToDiscord;

    /**
     * O ID do canal para logs do Discord, se habilitado
     *
     * @var string|null
     */
    protected ?string $discordChannelId;

    /**
     * Cria uma nova instância do logger
     *
     * @param string $logFile Caminho para o arquivo de log
     * @param string $level Nível de log (debug, info, warning, error)
     * @param bool $enabled Se o logging está habilitado
     * @return void
     */
    public function __construct(string $logFile = null, string $level = 'info', bool $enabled = true)
    {
        $this->level = $level;
        $this->logger = new Logger('discord-bot');
        
        if ($enabled) {
            if ($logFile === null) {
                $logFile = storage_path('logs/discordbot.log');
            }
            
            // Formatar a saída do log
            $dateFormat = 'Y-m-d H:i:s';
            $output = "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n";
            $formatter = new LineFormatter($output, $dateFormat);
            
            // Configurar handler de rotação diária com limite de 7 dias
            $handler = new RotatingFileHandler($logFile, 7, $this->getMonologLevel($level));
            $handler->setFormatter($formatter);
            
            $this->logger->pushHandler($handler);
        }
        
        // Configurar log para o canal do Discord se habilitado
        $this->logToDiscord = config('discordbot.logging.discord_channel', false);
        $this->discordChannelId = config('discordbot.logging.discord_channel');
    }

    /**
     * Converte o nível de log em constante do Monolog
     *
     * @param string $level
     * @return int
     */
    protected function getMonologLevel(string $level): int
    {
        $level = strtolower($level);
        
        return match($level) {
            'debug' => Logger::DEBUG,
            'info' => Logger::INFO,
            'notice' => Logger::NOTICE,
            'warning' => Logger::WARNING,
            'error' => Logger::ERROR,
            'critical' => Logger::CRITICAL,
            'alert' => Logger::ALERT,
            'emergency' => Logger::EMERGENCY,
            default => Logger::INFO,
        };
    }

    /**
     * Log de nível de sistema (emergência)
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function emergency($message, array $context = []): void
    {
        $this->log('emergency', $message, $context);
    }

    /**
     * Log de alerta
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function alert($message, array $context = []): void
    {
        $this->log('alert', $message, $context);
    }

    /**
     * Log crítico
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function critical($message, array $context = []): void
    {
        $this->log('critical', $message, $context);
    }

    /**
     * Log de erro
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function error($message, array $context = []): void
    {
        $this->log('error', $message, $context);
    }

    /**
     * Log de aviso
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function warning($message, array $context = []): void
    {
        $this->log('warning', $message, $context);
    }

    /**
     * Alias para warning
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function warn($message, array $context = []): void
    {
        $this->warning($message, $context);
    }

    /**
     * Log de notificação
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function notice($message, array $context = []): void
    {
        $this->log('notice', $message, $context);
    }

    /**
     * Log informacional
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function info($message, array $context = []): void
    {
        $this->log('info', $message, $context);
    }

    /**
     * Log de debug
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function debug($message, array $context = []): void
    {
        $this->log('debug', $message, $context);
    }

    /**
     * Registra um log com o nível especificado
     *
     * @param mixed $level
     * @param string $message
     * @param array $context
     * @return void
     */
    public function log($level, $message, array $context = []): void
    {
        $this->logger->log($level, $message, $context);
        
        // Registrar também no Log do Laravel
        Log::channel('daily')->log($level, "DiscordBot: $message", $context);
        
        // Enviar para canal do Discord se configurado e for erro ou superior
        if ($this->logToDiscord && $this->discordChannelId && in_array($level, ['error', 'critical', 'alert', 'emergency'])) {
            $this->sendToDiscordChannel($level, $message, $context);
        }
    }

    /**
     * Envia uma mensagem de log para o canal do Discord configurado
     *
     * @param string $level
     * @param string $message
     * @param array $context
     * @return void
     */
    protected function sendToDiscordChannel(string $level, string $message, array $context = []): void
    {
        // Evita loop infinito
        if (strpos($message, 'Discord log') !== false) {
            return;
        }
        
        // Se o serviço do bot for acessível, enviar mensagem
        if (app()->has('discord-bot')) {
            $discordBot = app('discord-bot');
            
            // Formatação da cor com base no nível
            $colors = [
                'emergency' => 0xFF0000, // Vermelho
                'alert' => 0xFF0000,     // Vermelho
                'critical' => 0xFF0000,  // Vermelho
                'error' => 0xFF6600,     // Laranja
            ];
            
            $color = $colors[$level] ?? 0xFF6600;
            
            // Criar um embed com o log
            $embed = [
                'title' => 'Log do Discord Bot: ' . strtoupper($level),
                'description' => $message,
                'color' => $color,
                'footer' => 'Discord Bot Logger',
                'timestamp' => date('c'),
            ];
            
            // Adicionar contexto se houver
            if (!empty($context)) {
                $contextText = json_encode($context, JSON_PRETTY_PRINT);
                $embed['description'] .= "\n\n```json\n$contextText\n```";
            }
            
            try {
                $discordBot->sendMessage($this->discordChannelId, '', $embed);
            } catch (\Exception $e) {
                // Log silencioso para evitar loops
            }
        }
    }
} 