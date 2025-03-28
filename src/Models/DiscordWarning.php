<?php

namespace LucasGiovanni\DiscordBotInstaller\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class DiscordWarning extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'guild_id',
        'moderator_id',
        'reason',
        'expires_at',
        'is_active',
        'is_temporary',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
        'is_temporary' => 'boolean',
    ];

    /**
     * Scope a query to only include active warnings.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include expired warnings.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeExpired($query)
    {
        return $query->where('is_active', true)
                     ->where('is_temporary', true)
                     ->where('expires_at', '<=', Carbon::now());
    }

    /**
     * Get the Discord user associated with the warning.
     */
    public function user()
    {
        return $this->belongsTo(DiscordUser::class, 'user_id', 'discord_id');
    }

    /**
     * Get the moderator who issued the warning.
     */
    public function moderator()
    {
        return $this->belongsTo(DiscordUser::class, 'moderator_id', 'discord_id');
    }

    /**
     * Deactivate this warning.
     *
     * @return bool
     */
    public function deactivate()
    {
        return $this->update(['is_active' => false]);
    }

    /**
     * Check if the warning is expired.
     *
     * @return bool
     */
    public function isExpired()
    {
        if (!$this->is_temporary) {
            return false;
        }

        return $this->is_active && $this->expires_at->isPast();
    }
} 