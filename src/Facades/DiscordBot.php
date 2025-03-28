<?php

namespace LucasGiovanni\DiscordBotInstaller\Facades;

use Illuminate\Support\Facades\Facade;
use LucasGiovanni\DiscordBotInstaller\Services\DiscordBotService;

/**
 * @method static \Discord\Discord getClient()
 * @method static \Discord\Discord initializeClient()
 * @method static bool sendMessage(string $channelId, string $message, array $embed = null)
 * @method static bool registerSlashCommand(string $name, string $description, array $options = [])
 * @method static void scheduleMessage(string $channelId, string $message, \Carbon\Carbon $sendAt, array $embed = null)
 * @method static array getGuilds()
 * @method static array getChannels(string $guildId)
 * @method static array getMembers(string $guildId)
 * @method static array getRoles(string $guildId)
 * @method static int getUserLevel(string $userId, string $guildId)
 * @method static array getLeaderboard(string $guildId, int $limit = 10)
 * @method static array getWarnings(string $userId, string $guildId)
 * @method static bool addUserExperience(string $userId, string $guildId, int $amount)
 * @method static bool checkLevelRoles(string $userId, string $guildId)
 * @method static bool createReminder(string $userId, string $channelId, string $message, \Carbon\Carbon $remindAt)
 * 
 * @see \LucasGiovanni\DiscordBotInstaller\Services\DiscordBotService
 */
class DiscordBot extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'discord-bot';
    }
} 