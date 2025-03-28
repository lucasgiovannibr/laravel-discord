<?php

namespace LucasGiovanni\DiscordBotInstaller\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class DiscordLevel extends Model
{
    use HasFactory;

    /**
     * A tabela associada ao modelo.
     *
     * @var string
     */
    protected $table = 'discord_levels';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'guild_id',
        'experience',
        'level',
        'last_message_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'experience' => 'integer',
        'level' => 'integer',
        'last_message_at' => 'datetime',
    ];

    /**
     * Get the user that owns the level.
     */
    public function user()
    {
        return $this->belongsTo(DiscordUser::class, 'user_id', 'discord_id');
    }

    /**
     * Add experience to the user.
     *
     * @param int $amount Amount of experience to add
     * @return bool Whether the user leveled up
     */
    public function addExperience($amount)
    {
        $this->experience += $amount;
        
        $oldLevel = $this->level;
        $this->level = $this->calculateLevel($this->experience);
        $this->last_message_at = now();
        
        $this->save();
        
        return $oldLevel < $this->level;
    }

    /**
     * Calculate the level based on experience.
     *
     * @param int $experience
     * @return int
     */
    protected function calculateLevel($experience)
    {
        // Formula: Level = sqrt(Experience / 100)
        return (int) floor(sqrt($experience / 100));
    }

    /**
     * Get experience required for next level.
     *
     * @return int
     */
    public function experienceForNextLevel()
    {
        $nextLevel = $this->level + 1;
        return ($nextLevel * $nextLevel) * 100;
    }

    /**
     * Get experience progress to next level (0-100%).
     *
     * @return float
     */
    public function progressToNextLevel()
    {
        $currentLevelExp = ($this->level * $this->level) * 100;
        $nextLevelExp = $this->experienceForNextLevel();
        $requiredExp = $nextLevelExp - $currentLevelExp;
        $userExp = $this->experience - $currentLevelExp;
        
        return min(100, max(0, ($userExp / $requiredExp) * 100));
    }

    /**
     * Scope a query to order by highest experience.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeTopUsers($query)
    {
        return $query->orderBy('experience', 'desc');
    }

    /**
     * Adiciona experiência ao usuário e verifica se subiu de nível.
     *
     * @param int $amount
     * @return bool Se o usuário subiu de nível
     */
    public function addXp(int $amount): bool
    {
        // Adiciona a experiência
        $this->experience += $amount;
        $this->last_message_at = Carbon::now();

        // Calcula o novo nível baseado na experiência
        $newLevel = $this->calculateLevel($this->experience);
        $leveledUp = $newLevel > $this->level;

        // Atualiza o nível se necessário
        if ($leveledUp) {
            $this->level = $newLevel;
        }

        // Salva as alterações
        $this->save();

        return $leveledUp;
    }

    /**
     * Calcula a experiência necessária para o próximo nível.
     *
     * @return int
     */
    public function xpForNextLevel(): int
    {
        $nextLevel = $this->level + 1;
        return ($nextLevel * $nextLevel) * 100;
    }

    /**
     * Verifica se o usuário pode receber XP (cooldown).
     *
     * @param int $cooldown em segundos
     * @return bool
     */
    public function canGainXp(int $cooldown = 60): bool
    {
        if (!$this->last_message_at) {
            return true;
        }

        return $this->last_message_at->addSeconds($cooldown)->isPast();
    }

    /**
     * Encontra ou cria um registro para a combinação usuário/servidor.
     *
     * @param string $userId
     * @param string $serverId
     * @return self
     */
    public static function findOrCreateFor(string $userId, string $serverId): self
    {
        return self::firstOrCreate([
            'user_id' => $userId,
            'guild_id' => $serverId,
        ]);
    }

    /**
     * Busca o ranking de usuários por servidor.
     *
     * @param string $serverId
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function leaderboard(string $serverId, int $limit = 10)
    {
        return self::where('guild_id', $serverId)
            ->orderBy('experience', 'desc')
            ->limit($limit)
            ->get();
    }
} 