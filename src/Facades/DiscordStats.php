<?php

namespace LucasGiovanni\DiscordBotInstaller\Facades;

use Illuminate\Support\Facades\Facade;
use LucasGiovanni\DiscordBotInstaller\Services\StatsService;

/**
 * @method static array getGeneralStats()
 * @method static array getCommandStats(int $days = 7)
 * @method static array getUserStats(string $guildId)
 * @method static array getEconomyStats()
 * @method static array getEventsStats()
 * @method static array getTicketStats()
 * @method static bool logCommand(string $commandName, string $userId, string $guildId)
 * @method static bool logMessage(string $userId, string $guildId, string $channelId)
 * @method static bool clearCache()
 * 
 * @see \LucasGiovanni\DiscordBotInstaller\Services\StatsService
 */
class DiscordStats extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return StatsService::class;
    }
} 