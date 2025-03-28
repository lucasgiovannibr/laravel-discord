<?php

namespace LucasGiovanni\DiscordBotInstaller\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiscordEconomy extends Model
{
    /**
     * A tabela associada ao modelo.
     *
     * @var string
     */
    protected $table = 'discord_economy';

    /**
     * Os atributos que são atribuíveis em massa.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'guild_id',
        'balance',
        'total_earned',
        'total_spent',
        'last_daily',
        'streak',
    ];

    /**
     * Os atributos que devem ser convertidos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'balance' => 'integer',
        'total_earned' => 'integer',
        'total_spent' => 'integer',
        'streak' => 'integer',
        'last_daily' => 'datetime',
    ];

    /**
     * Retorna o usuário do Discord associado.
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(DiscordUser::class, 'user_id', 'user_id');
    }

    /**
     * Adiciona um montante ao saldo do usuário.
     *
     * @param int $amount
     * @return bool
     */
    public function addBalance(int $amount): bool
    {
        if ($amount <= 0) {
            return false;
        }

        $this->balance += $amount;
        $this->total_earned += $amount;
        return $this->save();
    }

    /**
     * Remove um montante do saldo do usuário.
     *
     * @param int $amount
     * @return bool
     */
    public function subtractBalance(int $amount): bool
    {
        if ($amount <= 0 || $this->balance < $amount) {
            return false;
        }

        $this->balance -= $amount;
        $this->total_spent += $amount;
        return $this->save();
    }

    /**
     * Verifica se o usuário pode receber a recompensa diária
     *
     * @return bool
     */
    public function canClaimDaily(): bool
    {
        if ($this->last_daily === null) {
            return true;
        }

        return $this->last_daily->diffInHours(now()) >= 20;
    }

    /**
     * Resgata a recompensa diária
     *
     * @param int $baseAmount
     * @return array
     */
    public function claimDaily(int $baseAmount = 100): array
    {
        if (!$this->canClaimDaily()) {
            return [
                'success' => false,
                'message' => 'Você já resgatou sua recompensa diária!',
                'next_claim' => $this->last_daily->addHours(24)->diffForHumans(),
            ];
        }

        $lastDaily = $this->last_daily;
        $streakBroken = false;

        // Verifica se a sequência foi quebrada (mais de 48h desde o último resgate)
        if ($lastDaily !== null && $lastDaily->diffInHours(now()) >= 48) {
            $this->streak = 0;
            $streakBroken = true;
        }

        // Aumenta a sequência
        $this->streak += 1;
        
        // Calcula o bônus pela sequência (5% por dia, máximo de 50%)
        $streakBonus = min(0.5, $this->streak * 0.05);
        $totalAmount = (int) ($baseAmount * (1 + $streakBonus));
        
        // Adiciona o montante ao saldo
        $this->addBalance($totalAmount);
        
        // Atualiza a data do último resgate
        $this->last_daily = now();
        $this->save();
        
        return [
            'success' => true,
            'amount' => $totalAmount,
            'streak' => $this->streak,
            'streak_broken' => $streakBroken,
            'next_claim' => now()->addHours(24)->diffForHumans(),
        ];
    }

    /**
     * Transfere fundos para outro usuário
     *
     * @param DiscordEconomy $recipient
     * @param int $amount
     * @return bool
     */
    public function transferTo(DiscordEconomy $recipient, int $amount): bool
    {
        if ($amount <= 0 || $this->balance < $amount) {
            return false;
        }

        // Inicia uma transação para garantir que ambas as operações sejam realizadas ou nenhuma
        \DB::beginTransaction();

        try {
            $this->subtractBalance($amount);
            $recipient->addBalance($amount);
            
            \DB::commit();
            return true;
        } catch (\Exception $e) {
            \DB::rollBack();
            return false;
        }
    }

    /**
     * Scope para buscar a economia de um usuário em um servidor específico
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $userId
     * @param string $guildId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOfUserInGuild($query, string $userId, string $guildId)
    {
        return $query->where('user_id', $userId)->where('guild_id', $guildId);
    }
} 