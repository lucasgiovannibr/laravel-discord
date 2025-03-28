<?php

namespace LucasGiovanni\DiscordBotInstaller\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class DiscordEvent extends Model
{
    /**
     * A tabela associada ao modelo.
     *
     * @var string
     */
    protected $table = 'discord_events';

    /**
     * Os atributos que são atribuíveis em massa.
     *
     * @var array
     */
    protected $fillable = [
        'guild_id',
        'channel_id',
        'message_id',
        'title',
        'description',
        'location',
        'start_date',
        'end_date',
        'type',
        'created_by',
        'max_participants',
        'is_recurring',
        'recurrence_pattern',
        'recurrence_end_date',
        'parent_event_id',
        'color',
        'image_url',
        'voice_channel_id',
        'reminder_sent',
        'requires_approval',
        'is_approved',
        'approved_by',
        'metadata',
    ];

    /**
     * Os atributos que devem ser convertidos.
     *
     * @var array
     */
    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'recurrence_end_date' => 'datetime',
        'is_recurring' => 'boolean',
        'reminder_sent' => 'boolean',
        'requires_approval' => 'boolean',
        'is_approved' => 'boolean',
        'metadata' => 'array',
    ];

    /**
     * Retorna o usuário do Discord que criou este evento.
     *
     * @return BelongsTo
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(DiscordUser::class, 'creator_id', 'user_id');
    }

    /**
     * Retorna os participantes do evento.
     *
     * @return HasMany
     */
    public function participants(): HasMany
    {
        return $this->hasMany(DiscordEventParticipant::class, 'event_id');
    }

    /**
     * Verifica se o evento já ocorreu
     *
     * @return bool
     */
    public function hasPassed(): bool
    {
        return $this->start_date->isPast();
    }

    /**
     * Verifica se o evento está ocorrendo agora
     *
     * @return bool
     */
    public function isHappening(): bool
    {
        return $this->start_date->isPast() && ($this->end_date === null || !$this->end_date->isPast());
    }

    /**
     * Retorna o tempo restante até o início do evento
     *
     * @return string
     */
    public function timeUntilStart(): string
    {
        if ($this->hasPassed()) {
            return 'Evento já iniciado';
        }

        return $this->start_date->diffForHumans();
    }

    /**
     * Verifica se o evento tem limite de participantes
     *
     * @return bool
     */
    public function hasLimit(): bool
    {
        return $this->max_participants > 0;
    }

    /**
     * Verifica se o evento está cheio
     *
     * @return bool
     */
    public function isFull(): bool
    {
        if (!$this->hasLimit()) {
            return false;
        }

        return $this->participants()->count() >= $this->max_participants;
    }

    /**
     * Número de lugares disponíveis
     *
     * @return int|null
     */
    public function availableSpots(): ?int
    {
        if (!$this->hasLimit()) {
            return null;
        }

        $current = $this->participants()->count();
        return max(0, $this->max_participants - $current);
    }

    /**
     * Adiciona um participante ao evento
     *
     * @param string $userId
     * @param string $status
     * @param string|null $note
     * @return bool
     */
    public function addParticipant(string $userId, ?string $note = null): bool
    {
        if ($this->hasPassed() || ($this->hasLimit() && $this->isFull())) {
            return false;
        }

        // Verifica se o usuário já está participando
        $existing = DiscordEventParticipant::where('event_id', $this->id)
                                          ->where('user_id', $userId)
                                          ->first();
        
        if ($existing) {
            return true; // Já está participando
        }

        // Adiciona o participante
        DiscordEventParticipant::create([
            'event_id' => $this->id,
            'user_id' => $userId,
            'joined_at' => Carbon::now(),
            'note' => $note
        ]);

        return true;
    }

    /**
     * Remove um participante do evento
     *
     * @param string $userId
     * @return bool
     */
    public function removeParticipant(string $userId): bool
    {
        return DiscordEventParticipant::where('event_id', $this->id)
                                      ->where('user_id', $userId)
                                      ->delete() > 0;
    }

    /**
     * Verifica se um usuário é participante do evento
     *
     * @param string $userId
     * @return bool
     */
    public function isParticipant(string $userId): bool
    {
        return DiscordEventParticipant::where('event_id', $this->id)
                                      ->where('user_id', $userId)
                                      ->exists();
    }

    /**
     * Busca eventos que precisam de lembrete nos próximos minutos
     *
     * @param int $minutes
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function findNeedingReminder(int $minutes)
    {
        $targetTime = Carbon::now()->addMinutes($minutes);

        return self::where('start_time', '>', Carbon::now())
                  ->where('start_time', '<=', $targetTime)
                  ->get();
    }

    /**
     * Scope para eventos futuros
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeUpcoming($query)
    {
        return $query->where('start_time', '>', Carbon::now());
    }

    /**
     * Scope para eventos passados
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePast($query)
    {
        return $query->where('start_time', '<=', Carbon::now());
    }

    /**
     * Scope para eventos em um servidor específico
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