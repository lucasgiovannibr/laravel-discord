<?php

namespace LucasGiovanni\DiscordBotInstaller\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiscordEventParticipant extends Model
{
    /**
     * A tabela associada ao modelo.
     *
     * @var string
     */
    protected $table = 'discord_event_participants';

    /**
     * Os atributos que são atribuíveis em massa.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'event_id',
        'user_id',
        'joined_at',
        'note',
        'will_attend',
    ];

    /**
     * Os atributos que devem ser convertidos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'joined_at' => 'datetime',
        'will_attend' => 'boolean',
    ];

    /**
     * Retorna o evento associado.
     *
     * @return BelongsTo
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(DiscordEvent::class, 'event_id');
    }

    /**
     * Retorna o usuário associado.
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(DiscordUser::class, 'user_id', 'user_id');
    }

    /**
     * Confirma a presença no evento
     *
     * @return bool
     */
    public function confirm(): bool
    {
        $this->will_attend = true;
        return $this->save();
    }

    /**
     * Confirma a ausência no evento
     *
     * @return bool
     */
    public function decline(): bool
    {
        $this->will_attend = false;
        return $this->save();
    }

    /**
     * Atualiza a nota do participante
     *
     * @param string $note
     * @return bool
     */
    public function updateNote(string $note): bool
    {
        $this->note = $note;
        return $this->save();
    }

    /**
     * Scope para participantes confirmados
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeConfirmed($query)
    {
        return $query->where('will_attend', true);
    }

    /**
     * Scope para participantes que recusaram
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDeclined($query)
    {
        return $query->where('will_attend', false);
    }

    /**
     * Scope para participantes que não responderam
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePending($query)
    {
        return $query->whereNull('will_attend');
    }

    /**
     * Scope para participantes de um evento específico
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $eventId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOfEvent($query, int $eventId)
    {
        return $query->where('event_id', $eventId);
    }

    /**
     * Scope para participantes que são um usuário específico
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $userId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOfUser($query, string $userId)
    {
        return $query->where('user_id', $userId);
    }
} 