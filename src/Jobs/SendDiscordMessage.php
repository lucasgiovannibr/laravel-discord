<?php

namespace LucasGiovanni\DiscordBotInstaller\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use LucasGiovanni\DiscordBotInstaller\Facades\DiscordBot;
use LucasGiovanni\DiscordBotInstaller\Services\DiscordLogger;

class SendDiscordMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * ID do canal
     *
     * @var string
     */
    protected $channelId;

    /**
     * Conteúdo da mensagem
     *
     * @var string
     */
    protected $message;

    /**
     * Embed da mensagem (opcional)
     *
     * @var array|null
     */
    protected $embed;

    /**
     * Número de tentativas
     *
     * @var int
     */
    public $tries = 3;

    /**
     * Tempo de espera entre tentativas (segundos)
     *
     * @var int
     */
    public $backoff = 60;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(string $channelId, string $message, array $embed = null)
    {
        $this->channelId = $channelId;
        $this->message = $message;
        $this->embed = $embed;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(DiscordLogger $logger)
    {
        try {
            // Enviar a mensagem usando a Facade
            DiscordBot::sendMessage($this->channelId, $this->message, $this->embed);
            
            $logger->info('Mensagem agendada enviada com sucesso', [
                'channel_id' => $this->channelId,
                'has_embed' => !empty($this->embed)
            ]);
        } catch (\Exception $e) {
            $logger->error('Erro ao enviar mensagem agendada', [
                'error' => $e->getMessage(),
                'channel_id' => $this->channelId,
                'attempts' => $this->attempts(),
                'max_tries' => $this->tries
            ]);
            
            // Se ainda há tentativas, relança a exceção para tentar novamente
            if ($this->attempts() < $this->tries) {
                throw $e;
            }
        }
    }
} 