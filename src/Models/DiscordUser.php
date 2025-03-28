<?php

namespace LucasGiovanni\DiscordBotInstaller\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DiscordUser extends Model
{
    use HasFactory;

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'discord_id';

    /**
     * The "type" of the primary key ID.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'discord_id',
        'username',
        'discriminator',
        'avatar',
        'bot',
        'last_seen_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'bot' => 'boolean',
        'last_seen_at' => 'datetime',
    ];

    /**
     * Get the user's experience levels across all guilds.
     */
    public function levels()
    {
        return $this->hasMany(DiscordLevel::class, 'user_id', 'discord_id');
    }

    /**
     * Get the user's warnings.
     */
    public function warnings()
    {
        return $this->hasMany(DiscordWarning::class, 'user_id', 'discord_id');
    }

    /**
     * Get the user's reminders.
     */
    public function reminders()
    {
        return $this->hasMany(DiscordReminder::class, 'user_id', 'discord_id');
    }

    /**
     * Get moderator actions performed by this user.
     */
    public function moderatorActions()
    {
        return $this->hasMany(DiscordWarning::class, 'moderator_id', 'discord_id');
    }

    /**
     * Update the last seen timestamp.
     *
     * @return bool
     */
    public function updateLastSeen()
    {
        return $this->update(['last_seen_at' => now()]);
    }
} 