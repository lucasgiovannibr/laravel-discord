<?php

namespace LucasGiovanni\DiscordBotInstaller\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class DiscordGiveaway extends Model
{
    /**
     * A tabela associada ao modelo.
     *
     * @var string
     */
    protected $table = 'discord_giveaways';

    /**
     * Os atributos que são atribuíveis em massa.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'guild_id',
        'channel_id',
        'message_id',
        'creator_id',
        'prize',
        'description',
        'winners_count',
        'ends_at',
        'ended',
        'winners',
    ];

    /**
     * Os atributos que devem ser convertidos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'winners_count' => 'integer',
        'ended' => 'boolean',
        'ends_at' => 'datetime',
        'winners' => 'array',
    ];

    /**
     * Verifica se o sorteio já terminou
     *
     * @return bool
     */
    public function hasEnded(): bool
    {
        return $this->ended || $this->ends_at->isPast();
    }

    /**
     * Verifica se o sorteio deve terminar agora
     * 
     * @return bool
     */
    public function shouldEnd(): bool
    {
        return !$this->ended && $this->ends_at->isPast();
    }

    /**
     * Retorna o tempo restante formatado
     *
     * @return string
     */
    public function getTimeLeftFormatted(): string
    {
        if ($this->hasEnded()) {
            return 'Encerrado';
        }

        $now = Carbon::now();
        $diff = $now->diff($this->ends_at);

        if ($diff->days > 0) {
            return $diff->days . 'd ' . $diff->h . 'h ' . $diff->i . 'm restantes';
        } elseif ($diff->h > 0) {
            return $diff->h . 'h ' . $diff->i . 'm ' . $diff->s . 's restantes';
        } elseif ($diff->i > 0) {
            return $diff->i . 'm ' . $diff->s . 's restantes';
        } else {
            return $diff->s . 's restantes';
        }
    }

    /**
     * Finaliza o sorteio e seleciona os vencedores
     *
     * @param array $participants IDs dos participantes
     * @return array Array com os IDs dos vencedores
     */
    public function end(array $participants): array
    {
        if ($this->ended) {
            return $this->winners ?? [];
        }

        // Remove duplicatas e embaralha os participantes
        $participants = array_unique($participants);
        shuffle($participants);

        // Se não houver participantes suficientes, todos vencem
        $winnersCount = min($this->winners_count, count($participants));
        
        // Seleciona os vencedores
        $winners = array_slice($participants, 0, $winnersCount);
        
        // Atualiza o modelo
        $this->winners = $winners;
        $this->ended = true;
        $this->save();
        
        return $winners;
    }

    /**
     * Realiza um novo sorteio entre os participantes
     *
     * @param array $participants IDs dos participantes
     * @return string ID do novo vencedor
     */
    public function reroll(array $participants): string
    {
        if (!$this->ended || empty($participants)) {
            return '';
        }

        // Remove os vencedores anteriores da lista de participantes
        $participants = array_diff($participants, $this->winners ?? []);
        
        if (empty($participants)) {
            return '';
        }

        // Embaralha e escolhe um novo vencedor
        shuffle($participants);
        $newWinner = $participants[0];
        
        // Adiciona à lista de vencedores
        $winners = $this->winners ?? [];
        $winners[] = $newWinner;
        $this->winners = $winners;
        $this->save();
        
        return $newWinner;
    }

    /**
     * Scope para sorteios não terminados
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('ended', false);
    }

    /**
     * Scope para sorteios que devem terminar agora
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeEnding($query)
    {
        return $query->where('ended', false)
                     ->where('ends_at', '<=', Carbon::now());
    }

    /**
     * Scope para sorteios em um servidor específico
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $guildId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInGuild($query, string $guildId)
    {
        return $query->where('guild_id', $guildId);
    }
} 