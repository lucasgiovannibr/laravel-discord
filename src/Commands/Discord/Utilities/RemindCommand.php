<?php

namespace LucasGiovanni\DiscordBotInstaller\Commands\Discord\Utilities;

use Discord\Discord;
use Discord\Parts\Channel\Message;
use LucasGiovanni\DiscordBotInstaller\Services\DiscordLogger;
use LucasGiovanni\DiscordBotInstaller\Models\DiscordReminder;
use Carbon\Carbon;
use Carbon\CarbonInterval;
use DateTimeZone;

class RemindCommand
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
     * Manipula o comando
     */
    public function handle(Message $message, array $args = []): void
    {
        // Se não houver argumentos, exibe ajuda
        if (empty($args)) {
            $this->showHelp($message);
            return;
        }
        
        // Tenta extrair um tempo válido dos argumentos
        try {
            // Obtém o tempo e a mensagem
            list($timeStr, $reminderText) = $this->parseTimeAndMessage($args);
            
            // Se não houver mensagem
            if (empty($reminderText)) {
                $message->channel->sendMessage('⚠️ Você precisa especificar uma mensagem para o lembrete.');
                return;
            }
            
            // Converte o tempo para um Carbon
            $remindAt = $this->parseTime($timeStr);
            
            // Se a data for inválida ou no passado
            if (!$remindAt || $remindAt->isPast()) {
                $message->channel->sendMessage('⚠️ A data fornecida é inválida ou está no passado. Use formatos como "30m", "2h", "1d", etc.');
                return;
            }
            
            // Criar o lembrete no banco de dados
            $reminder = new DiscordReminder([
                'user_id' => $message->author->id,
                'channel_id' => $message->channel->id,
                'server_id' => $message->guild->id ?? null,
                'message' => $reminderText,
                'remind_at' => $remindAt,
            ]);
            
            $reminder->save();
            
            // Enviar confirmação
            $formattedTime = $remindAt->format('d/m/Y H:i:s');
            $diffForHumans = $remindAt->diffForHumans();
            
            $message->channel->sendMessage("⏰ Lembrete definido para {$formattedTime} ({$diffForHumans})");
            
            $this->logger->info('Lembrete criado', [
                'user' => $message->author->username,
                'time' => $remindAt->toIso8601String(),
                'channel' => $message->channel->name ?? 'DM'
            ]);
            
        } catch (\Exception $e) {
            $message->channel->sendMessage('❌ Erro ao criar lembrete: ' . $e->getMessage());
            $this->logger->error('Erro ao processar comando remind', [
                'error' => $e->getMessage(),
                'args' => $args
            ]);
        }
    }
    
    /**
     * Exibe a ajuda do comando
     */
    protected function showHelp(Message $message): void
    {
        $prefix = config('discordbot.command_prefix', '!');
        
        $helpText = "**⏰ Comando Lembrete**\n\n";
        $helpText .= "Use `{$prefix}remind [tempo] [mensagem]` para definir um lembrete.\n\n";
        $helpText .= "**Formatos de tempo válidos:**\n";
        $helpText .= "- Minutos: `30m`, `45min`\n";
        $helpText .= "- Horas: `2h`, `1hour`\n";
        $helpText .= "- Dias: `1d`, `2day`\n";
        $helpText .= "- Combinações: `1d 2h 30m`, `1day 6hours`\n";
        $helpText .= "- Data/hora específica: `2023-12-31 23:59`, `amanhã 15:00`\n\n";
        $helpText .= "**Exemplos:**\n";
        $helpText .= "`{$prefix}remind 30m Verificar o forno`\n";
        $helpText .= "`{$prefix}remind 1d Dar seguimento ao e-mail`\n";
        $helpText .= "`{$prefix}remind 26/12 12:00 Almoço de Natal`";
        
        $message->channel->sendMessage($helpText);
    }
    
    /**
     * Analisa os argumentos para extrair o tempo e a mensagem
     */
    protected function parseTimeAndMessage(array $args): array
    {
        $timeStr = '';
        $messageWords = [];
        
        $isTimeArg = true;
        
        foreach ($args as $arg) {
            // Verifica se ainda estamos processando a parte do tempo
            if ($isTimeArg) {
                // Se contém números, provavelmente é parte do tempo
                if (preg_match('/[0-9]/', $arg)) {
                    $timeStr .= " {$arg}";
                    continue;
                }
                
                // Se é uma palavra de tempo comum (min, hour, day, etc)
                if (preg_match('/^(min|minute|h|hour|d|day|week|month|tomorrow|today|amanhã|hoje)s?$/i', $arg)) {
                    $timeStr .= " {$arg}";
                    continue;
                }
                
                // Se chegou aqui, provavelmente estamos começando a mensagem
                $isTimeArg = false;
            }
            
            // Adicionar à mensagem
            $messageWords[] = $arg;
        }
        
        // Se ainda não começamos a mensagem
        if ($isTimeArg) {
            // O último argumento temporal é provavelmente parte da mensagem
            $timeParts = explode(' ', trim($timeStr));
            if (count($timeParts) > 0) {
                $messageWords[] = array_pop($timeParts);
                $timeStr = implode(' ', $timeParts);
            }
        }
        
        return [trim($timeStr), implode(' ', $messageWords)];
    }
    
    /**
     * Tenta analisar uma string de tempo em diversos formatos
     */
    protected function parseTime(string $timeStr): ?Carbon
    {
        $now = Carbon::now();
        
        // Tentar formato direto: 30m, 1h, 2d, etc.
        if (preg_match('/^(\d+)([mhd])$/', $timeStr, $matches)) {
            $value = (int) $matches[1];
            $unit = $matches[2];
            
            switch ($unit) {
                case 'm':
                    return $now->copy()->addMinutes($value);
                case 'h':
                    return $now->copy()->addHours($value);
                case 'd':
                    return $now->copy()->addDays($value);
            }
        }
        
        // Tentar formato mais complexo
        try {
            // Primeira tentativa: tratar como uma string relativa simples
            $interval = CarbonInterval::fromString($timeStr);
            if ($interval) {
                return $now->copy()->add($interval);
            }
        } catch (\Exception $e) {
            // Ignorar e tentar outros formatos
        }
        
        // Tentar formatos de data/hora comuns
        try {
            // Tentar formato de data e hora
            return Carbon::parse($timeStr);
        } catch (\Exception $e) {
            // Não foi possível analisar a data
            return null;
        }
    }
    
    /**
     * Retorna informações sobre o comando para o sistema de ajuda
     */
    public function getHelp(): array
    {
        return [
            'usage' => '[tempo] [mensagem]',
            'examples' => [
                '30m Verificar o forno',
                '1d Dar seguimento ao e-mail',
                '26/12 12:00 Almoço de Natal',
            ],
            'notes' => "Você pode usar formatos como 30m (30 minutos), 2h (2 horas), 1d (1 dia), ou datas específicas."
        ];
    }
} 