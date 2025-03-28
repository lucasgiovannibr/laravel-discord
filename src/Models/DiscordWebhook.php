<?php

namespace LucasGiovanni\DiscordBotInstaller\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Carbon\Carbon;

class DiscordWebhook extends Model
{
    /**
     * A tabela associada ao modelo.
     *
     * @var string
     */
    protected $table = 'discord_webhooks';

    /**
     * Os atributos que são atribuíveis em massa.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'guild_id',
        'channel_id',
        'name',
        'description',
        'token',
        'secret',
        'created_by',
        'expires_at',
        'rate_limit',
        'is_disabled',
    ];

    /**
     * Os atributos que devem ser convertidos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'expires_at' => 'datetime',
        'rate_limit' => 'integer',
        'is_disabled' => 'boolean',
    ];

    /**
     * Os atributos que devem ser escondidos para serialização.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'secret',
    ];

    /**
     * Retorna o usuário que criou este webhook.
     *
     * @return BelongsTo
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(DiscordUser::class, 'created_by', 'user_id');
    }

    /**
     * Gera um novo token para o webhook
     *
     * @return string
     */
    public function regenerateToken(): string
    {
        $this->token = Str::random(64);
        $this->save();
        
        return $this->token;
    }

    /**
     * Gera um novo segredo para o webhook
     *
     * @return string
     */
    public function regenerateSecret(): string
    {
        $this->secret = Str::random(32);
        $this->save();
        
        return $this->secret;
    }

    /**
     * Verifica se o webhook expirou
     *
     * @return bool
     */
    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    /**
     * Verifica se o webhook está ativo
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return !$this->is_disabled && !$this->isExpired();
    }

    /**
     * Estende a validade do webhook por X dias
     *
     * @param int $days
     * @return bool
     */
    public function extend(int $days): bool
    {
        if ($this->expires_at === null) {
            $this->expires_at = Carbon::now()->addDays($days);
        } else {
            $this->expires_at = $this->expires_at->addDays($days);
        }
        
        return $this->save();
    }

    /**
     * Desativa o webhook
     *
     * @return bool
     */
    public function disable(): bool
    {
        $this->is_disabled = true;
        return $this->save();
    }

    /**
     * Ativa o webhook
     *
     * @return bool
     */
    public function enable(): bool
    {
        $this->is_disabled = false;
        return $this->save();
    }

    /**
     * Valida uma assinatura HMAC para o webhook
     *
     * @param string $payload
     * @param string $signature
     * @return bool
     */
    public function validateSignature(string $payload, string $signature): bool
    {
        if (empty($this->secret)) {
            return false;
        }
        
        $expectedSignature = hash_hmac('sha256', $payload, $this->secret);
        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Scope para webhooks ativos
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_disabled', false)
                     ->where(function ($q) {
                         $q->whereNull('expires_at')
                           ->orWhere('expires_at', '>', Carbon::now());
                     });
    }

    /**
     * Scope para webhooks em um servidor específico
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