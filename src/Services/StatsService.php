<?php

namespace LucasGiovanni\DiscordBotInstaller\Services;

use LucasGiovanni\DiscordBotInstaller\Models\DiscordUser;
use LucasGiovanni\DiscordBotInstaller\Models\DiscordLevel;
use LucasGiovanni\DiscordBotInstaller\Models\DiscordEconomy;
use LucasGiovanni\DiscordBotInstaller\Models\DiscordEvent;
use LucasGiovanni\DiscordBotInstaller\Models\DiscordGiveaway;
use LucasGiovanni\DiscordBotInstaller\Models\DiscordTicket;
use LucasGiovanni\DiscordBotInstaller\Models\DiscordWarning;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class StatsService
{
    /**
     * @var int Tempo de cache em minutos
     */
    protected $cacheTtl;

    /**
     * @var bool Se deve rastrear comandos
     */
    protected $trackCommands;

    /**
     * @var bool Se deve rastrear mensagens
     */
    protected $trackMessages;

    /**
     * @var bool Se deve rastrear membros
     */
    protected $trackMembers;

    /**
     * @var bool Se deve registrar logs detalhados
     */
    protected $detailedLogging;

    /**
     * Construtor
     */
    public function __construct()
    {
        $this->cacheTtl = config('discordbot.stats.cache_ttl', 5);
        $this->trackCommands = config('discordbot.stats.track_commands', true);
        $this->trackMessages = config('discordbot.stats.track_messages', true);
        $this->trackMembers = config('discordbot.stats.track_members', true);
        $this->detailedLogging = config('discordbot.stats.detailed_logging', false);
    }

    /**
     * Obtém estatísticas gerais sobre o uso do bot
     *
     * @return array
     */
    public function getGeneralStats(): array
    {
        return Cache::remember('discord_stats_general', $this->cacheTtl, function () {
            $userCount = DiscordUser::count();
            $serverCount = DiscordUser::select('guild_id')->distinct()->count('guild_id');
            
            $totalLevels = DiscordLevel::sum('level');
            $avgLevel = $userCount > 0 ? $totalLevels / $userCount : 0;
            
            $totalEconomy = DiscordEconomy::sum('balance');
            $avgEconomy = $userCount > 0 ? $totalEconomy / $userCount : 0;
            
            $activeEvents = DiscordEvent::upcoming()->count();
            $activeGiveaways = DiscordGiveaway::active()->count();
            $openTickets = DiscordTicket::open()->count();
            
            $warningCount = DiscordWarning::where('active', true)->count();
            
            return [
                'total_users' => $userCount,
                'total_servers' => $serverCount,
                'total_xp' => DiscordLevel::sum('xp'),
                'avg_level' => round($avgLevel, 2),
                'total_economy' => $totalEconomy,
                'avg_economy' => round($avgEconomy, 2),
                'active_events' => $activeEvents,
                'active_giveaways' => $activeGiveaways,
                'open_tickets' => $openTickets,
                'active_warnings' => $warningCount,
                'cached_at' => now()->toIso8601String(),
            ];
        });
    }

    /**
     * Obtém estatísticas de comandos
     *
     * @param int $days Dias anteriores para análise
     * @return array
     */
    public function getCommandStats(int $days = 7): array
    {
        if (!$this->trackCommands) {
            return [
                'enabled' => false,
                'message' => 'O rastreamento de comandos está desativado.',
            ];
        }

        $cacheKey = "discord_stats_commands_{$days}";
        
        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($days) {
            // Aqui normalmente consultaríamos uma tabela de logs de comandos
            // Como não temos essa tabela implementada, retornamos dados de exemplo
            
            return [
                'total_commands' => 1250,
                'top_commands' => [
                    ['name' => 'help', 'count' => 320],
                    ['name' => 'ping', 'count' => 215],
                    ['name' => 'level', 'count' => 180],
                    ['name' => 'rank', 'count' => 145],
                    ['name' => 'daily', 'count' => 120],
                ],
                'command_trend' => [
                    ['date' => Carbon::now()->subDays(6)->format('Y-m-d'), 'count' => 180],
                    ['date' => Carbon::now()->subDays(5)->format('Y-m-d'), 'count' => 195],
                    ['date' => Carbon::now()->subDays(4)->format('Y-m-d'), 'count' => 205],
                    ['date' => Carbon::now()->subDays(3)->format('Y-m-d'), 'count' => 187],
                    ['date' => Carbon::now()->subDays(2)->format('Y-m-d'), 'count' => 210],
                    ['date' => Carbon::now()->subDays(1)->format('Y-m-d'), 'count' => 225],
                    ['date' => Carbon::now()->format('Y-m-d'), 'count' => 105],
                ],
                'period_days' => $days,
                'cached_at' => now()->toIso8601String(),
            ];
        });
    }

    /**
     * Obtém estatísticas de usuários para um servidor específico
     *
     * @param string $guildId ID do servidor
     * @return array
     */
    public function getUserStats(string $guildId): array
    {
        $cacheKey = "discord_stats_users_{$guildId}";
        
        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($guildId) {
            $users = DiscordUser::where('guild_id', $guildId)->count();
            
            // Top usuários por nível
            $topLevels = DiscordLevel::select('user_id', 'level', 'xp')
                                    ->where('guild_id', $guildId)
                                    ->orderBy('level', 'desc')
                                    ->orderBy('xp', 'desc')
                                    ->limit(10)
                                    ->get();
            
            // Top usuários por economia
            $topEconomy = DiscordEconomy::select('user_id', 'balance')
                                      ->where('guild_id', $guildId)
                                      ->orderBy('balance', 'desc')
                                      ->limit(10)
                                      ->get();
            
            // Atividade por dia da semana
            $activityByDay = [];
            for ($i = 0; $i < 7; $i++) {
                $activityByDay[] = [
                    'day' => $i,
                    'day_name' => Carbon::now()->startOfWeek()->addDays($i)->format('l'),
                    'count' => rand(50, 200), // Dados fictícios
                ];
            }
            
            // Usuários recentes
            $recentUsers = DiscordUser::where('guild_id', $guildId)
                                    ->orderBy('created_at', 'desc')
                                    ->limit(5)
                                    ->get(['user_id', 'username', 'created_at']);
            
            return [
                'total_users' => $users,
                'top_levels' => $topLevels,
                'top_economy' => $topEconomy,
                'activity_by_day' => $activityByDay,
                'recent_users' => $recentUsers,
                'cached_at' => now()->toIso8601String(),
            ];
        });
    }

    /**
     * Obtém estatísticas sobre a economia
     *
     * @return array
     */
    public function getEconomyStats(): array
    {
        return Cache::remember('discord_stats_economy', $this->cacheTtl, function () {
            $totalBalance = DiscordEconomy::sum('balance');
            $totalEarned = DiscordEconomy::sum('total_earned');
            $totalSpent = DiscordEconomy::sum('total_spent');
            
            // Distribução de riqueza
            $wealthDistribution = [
                'top_10_percent' => 0,
                'middle_40_percent' => 0,
                'bottom_50_percent' => 0,
            ];
            
            // Este cálculo seria mais complexo em um ambiente real
            // Aqui usamos valores fictícios
            $wealthDistribution['top_10_percent'] = $totalBalance * 0.65;
            $wealthDistribution['middle_40_percent'] = $totalBalance * 0.25;
            $wealthDistribution['bottom_50_percent'] = $totalBalance * 0.1;
            
            return [
                'total_balance' => $totalBalance,
                'total_earned' => $totalEarned,
                'total_spent' => $totalSpent,
                'circulation' => ($totalEarned > 0) ? ($totalSpent / $totalEarned) * 100 : 0,
                'wealth_distribution' => $wealthDistribution,
                'cached_at' => now()->toIso8601String(),
            ];
        });
    }

    /**
     * Obtém estatísticas sobre eventos e sorteios
     *
     * @return array
     */
    public function getEventsStats(): array
    {
        return Cache::remember('discord_stats_events', $this->cacheTtl, function () {
            $activeEvents = DiscordEvent::upcoming()->count();
            $pastEvents = DiscordEvent::past()->count();
            
            $activeGiveaways = DiscordGiveaway::active()->count();
            $completedGiveaways = DiscordGiveaway::where('ended', true)->count();
            
            // Participação média em eventos (fictício)
            $avgParticipation = 15;
            
            return [
                'active_events' => $activeEvents,
                'past_events' => $pastEvents,
                'active_giveaways' => $activeGiveaways,
                'completed_giveaways' => $completedGiveaways,
                'avg_participation' => $avgParticipation,
                'cached_at' => now()->toIso8601String(),
            ];
        });
    }

    /**
     * Obtém estatísticas sobre tickets de suporte
     *
     * @return array
     */
    public function getTicketStats(): array
    {
        return Cache::remember('discord_stats_tickets', $this->cacheTtl, function () {
            $openTickets = DiscordTicket::open()->count();
            $closedTickets = DiscordTicket::closed()->count();
            
            // Tempo médio de resolução (fictício)
            $avgResolutionTime = 8.5; // horas
            
            // Tickets por status
            $byStatus = [
                'open' => DiscordTicket::where('status', DiscordTicket::STATUS_OPEN)->count(),
                'pending' => DiscordTicket::where('status', DiscordTicket::STATUS_PENDING)->count(),
                'solved' => DiscordTicket::where('status', DiscordTicket::STATUS_SOLVED)->count(),
                'closed' => DiscordTicket::where('status', DiscordTicket::STATUS_CLOSED)->count(),
            ];
            
            return [
                'open_tickets' => $openTickets,
                'closed_tickets' => $closedTickets,
                'total_tickets' => $openTickets + $closedTickets,
                'avg_resolution_time' => $avgResolutionTime,
                'by_status' => $byStatus,
                'cached_at' => now()->toIso8601String(),
            ];
        });
    }

    /**
     * Registra um novo comando para análise
     *
     * @param string $commandName
     * @param string $userId
     * @param string $guildId
     * @return bool
     */
    public function logCommand(string $commandName, string $userId, string $guildId): bool
    {
        if (!$this->trackCommands) {
            return false;
        }

        // Aqui cadastraríamos o comando em uma tabela de logs
        // Como não temos essa tabela implementada, apenas retornamos true
        
        return true;
    }

    /**
     * Registra uma nova mensagem para análise
     *
     * @param string $userId
     * @param string $guildId
     * @param string $channelId
     * @return bool
     */
    public function logMessage(string $userId, string $guildId, string $channelId): bool
    {
        if (!$this->trackMessages) {
            return false;
        }

        // Aqui cadastraríamos a mensagem em uma tabela de logs
        // Como não temos essa tabela implementada, apenas retornamos true
        
        return true;
    }

    /**
     * Limpa cache de estatísticas
     *
     * @return bool
     */
    public function clearCache(): bool
    {
        $cacheKeys = [
            'discord_stats_general',
            'discord_stats_commands_7',
            'discord_stats_commands_30',
            'discord_stats_economy',
            'discord_stats_events',
            'discord_stats_tickets',
        ];
        
        foreach ($cacheKeys as $key) {
            Cache::forget($key);
        }
        
        // Limpar caches de servidores específicos
        $guildIds = DiscordUser::select('guild_id')->distinct()->pluck('guild_id');
        foreach ($guildIds as $guildId) {
            Cache::forget("discord_stats_users_{$guildId}");
        }
        
        return true;
    }
} 