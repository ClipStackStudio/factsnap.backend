<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Config;
use Carbon\Carbon;

class NotificationRulesService
{
    /**
     * Get notification rules for a user type
     */
    public function getRulesForUserType(string $userType): array
    {
        return Config::get("notification_rules.user_types.{$userType}", []);
    }

    /**
     * Get user type based on user model
     */
    public function getUserType(User $user): string
    {
        if ($user->is_guest) {
            return 'guest';
        }
        
        if ($user->is_premium) {
            return 'premium';
        }
        
        return 'logged_in';
    }

    /**
     * Get effective rules for a specific user
     */
    public function getRulesForUser(User $user): array
    {
        $userType = $this->getUserType($user);
        return $this->getRulesForUserType($userType);
    }

    /**
     * Validate delivery settings against user's rules
     */
    public function validateDeliverySettings(User $user, array $settings): array
    {
        $rules = $this->getRulesForUser($user);
        $systemLimits = Config::get('notification_rules.system_limits');
        $deliveryModes = Config::get('notification_rules.delivery_modes');
        $errors = [];

        // Validate delivery mode
        if (isset($settings['delivery_mode'])) {
            if (!in_array($settings['delivery_mode'], $rules['allowed_delivery_modes'])) {
                $errors[] = "Delivery mode '{$settings['delivery_mode']}' is not allowed for your account type.";
            }

            // Validate mode-specific requirements
            $mode = $settings['delivery_mode'];
            if (isset($deliveryModes[$mode])) {
                $modeConfig = $deliveryModes[$mode];
                
                if ($modeConfig['requires_times'] && empty($settings['preferred_times'])) {
                    $errors[] = "Preferred times are required for {$mode} delivery mode.";
                }
                
                if ($modeConfig['requires_days'] && empty($settings['days_of_week'])) {
                    $errors[] = "Days of week are required for {$mode} delivery mode.";
                }
                
                if (isset($modeConfig['requires_window']) && $modeConfig['requires_window']) {
                    if (empty($settings['delivery_window_start']) || empty($settings['delivery_window_end'])) {
                        $errors[] = "Delivery window is required for windowed delivery mode.";
                    }
                }
            }
        }

        // Validate number of preferred times
        if (isset($settings['preferred_times']) && is_array($settings['preferred_times'])) {
            $timesCount = count($settings['preferred_times']);
            
            if ($timesCount > $rules['max_times_per_day']) {
                $errors[] = "You can set maximum {$rules['max_times_per_day']} delivery times per day.";
            }

            if ($timesCount > $systemLimits['max_preferred_times']) {
                $errors[] = "System limit: maximum {$systemLimits['max_preferred_times']} preferred times allowed.";
            }

            // Validate time intervals
            $intervals = $this->calculateTimeIntervals($settings['preferred_times']);
            foreach ($intervals as $interval) {
                if ($interval < $rules['min_interval_minutes']) {
                    $errors[] = "Minimum time between notifications must be {$rules['min_interval_minutes']} minutes.";
                    break;
                }
            }
        }

        // Validate per-package daily limit
        if (isset($settings['per_day_quota'])) {
            if ($settings['per_day_quota'] > $rules['max_per_package_daily']) {
                $errors[] = "Maximum {$rules['max_per_package_daily']} facts per day per package allowed.";
            }
        }

        // Validate delivery window
        if (isset($settings['delivery_window_start']) || isset($settings['delivery_window_end'])) {
            if (!$rules['supports_delivery_window']) {
                $errors[] = "Delivery windows are only available for premium accounts.";
            } else {
                // Validate window duration
                if (isset($settings['delivery_window_start']) && isset($settings['delivery_window_end'])) {
                    $windowDuration = $this->calculateWindowDuration(
                        $settings['delivery_window_start'], 
                        $settings['delivery_window_end']
                    );
                    
                    if ($windowDuration > ($systemLimits['max_delivery_window_hours'] * 60)) {
                        $errors[] = "Delivery window cannot exceed {$systemLimits['max_delivery_window_hours']} hours.";
                    }
                    
                    if ($windowDuration < $systemLimits['min_delivery_window_minutes']) {
                        $errors[] = "Delivery window must be at least {$systemLimits['min_delivery_window_minutes']} minutes.";
                    }
                }
            }
        }

        // Validate time-sensitive notifications
        if (isset($settings['time_sensitive']) && $settings['time_sensitive']) {
            if (!$rules['supports_time_sensitive']) {
                $errors[] = "Time-sensitive notifications are only available for premium accounts.";
            }
        }

        // Validate quiet hours modification
        if (isset($settings['quiet_hours_enabled']) && $settings['quiet_hours_enabled'] === false) {
            if (!$rules['can_disable_quiet_hours']) {
                $errors[] = "Quiet hours cannot be disabled for your account type.";
            }
        }

        return $errors;
    }

    /**
     * Calculate intervals between preferred times
     */
    private function calculateTimeIntervals(array $times): array
    {
        if (count($times) < 2) {
            return [];
        }

        sort($times);
        $intervals = [];
        
        for ($i = 1; $i < count($times); $i++) {
            $time1 = Carbon::createFromFormat('H:i', $times[$i - 1]);
            $time2 = Carbon::createFromFormat('H:i', $times[$i]);
            
            $interval = $time2->diffInMinutes($time1);
            $intervals[] = $interval;
        }

        return $intervals;
    }

    /**
     * Check if user can schedule more notifications today
     */
    public function canScheduleMore(User $user): bool
    {
        $rules = $this->getRulesForUser($user);
        $today = Carbon::today();
        
        $deliveredToday = $user->deliveredFacts()
            ->whereDate('delivered_at', $today)
            ->where('status', 'delivered')
            ->count();

        return $deliveredToday < $rules['max_daily_facts'];
    }

    /**
     * Get remaining daily quota for user
     */
    public function getRemainingDailyQuota(User $user): int
    {
        $rules = $this->getRulesForUser($user);
        $today = Carbon::today();
        
        $deliveredToday = $user->deliveredFacts()
            ->whereDate('delivered_at', $today)
            ->where('status', 'delivered')
            ->count();

        return max(0, $rules['max_daily_facts'] - $deliveredToday);
    }

    /**
     * Check if enough time has passed since last notification
     */
    public function hasMinIntervalPassed(User $user): bool
    {
        $rules = $this->getRulesForUser($user);
        
        $lastDelivery = $user->deliveredFacts()
            ->where('status', 'delivered')
            ->latest('delivered_at')
            ->first();

        if (!$lastDelivery) {
            return true;
        }

        $minutesSinceLastDelivery = Carbon::now()->diffInMinutes($lastDelivery->delivered_at);
        return $minutesSinceLastDelivery >= $rules['min_interval_minutes'];
    }

    /**
     * Get default settings for user type
     */
    public function getDefaultSettings(User $user): array
    {
        $userType = $this->getUserType($user);
        $defaults = Config::get("notification_rules.defaults.{$userType}", []);
        $rules = $this->getRulesForUser($user);
        
        return array_merge($defaults, [
            'quiet_hours_start' => $rules['default_quiet_hours']['start'],
            'quiet_hours_end' => $rules['default_quiet_hours']['end'],
            'quiet_hours_enabled' => true,
            'delivery_enabled' => true,
            'per_day_quota' => 1,
            'min_interval_minutes' => $rules['min_interval_minutes'],
        ]);
    }

    /**
     * Check if current time is within quiet hours for user
     */
    public function isWithinQuietHours(User $user): bool
    {
        if (!$user->quiet_hours_enabled) {
            return false;
        }

        $now = Carbon::now($user->timezone ?? 'UTC');
        $quietStart = Carbon::createFromFormat('H:i', $user->quiet_hours_start ?? '22:00', $user->timezone);
        $quietEnd = Carbon::createFromFormat('H:i', $user->quiet_hours_end ?? '08:00', $user->timezone);

        // Handle quiet hours that span midnight
        if ($quietStart->greaterThan($quietEnd)) {
            return $now->greaterThanOrEqualTo($quietStart) || $now->lessThanOrEqualTo($quietEnd);
        }

        return $now->between($quietStart, $quietEnd);
    }

    /**
     * Get next allowed delivery time outside quiet hours
     */
    public function getNextAllowedTime(User $user, Carbon $desiredTime): Carbon
    {
        if (!$this->isWithinQuietHours($user)) {
            return $desiredTime;
        }

        $quietEnd = Carbon::createFromFormat('H:i', $user->quiet_hours_end ?? '08:00', $user->timezone);
        
        // If desired time is today and quiet hours end today, schedule for quiet end time
        if ($desiredTime->isSameDay($quietEnd)) {
            return $desiredTime->setTimeFrom($quietEnd);
        }

        // Otherwise, schedule for tomorrow at quiet end time
        return $desiredTime->addDay()->setTimeFrom($quietEnd);
    }

    /**
     * Apply jitter to prevent clustering of notifications
     */
    public function applyJitter(Carbon $scheduledTime): Carbon
    {
        $maxJitter = Config::get('notification_rules.system_limits.jitter_range_minutes', 5);
        $jitter = rand(-$maxJitter, $maxJitter);
        
        return $scheduledTime->addMinutes($jitter);
    }

    /**
     * Calculate window duration in minutes
     */
    public function calculateWindowDuration(string $start, string $end): int
    {
        $startTime = Carbon::createFromFormat('H:i', $start);
        $endTime = Carbon::createFromFormat('H:i', $end);
        
        if ($endTime->lessThan($startTime)) {
            $endTime->addDay(); // Handle overnight windows
        }
        
        return $startTime->diffInMinutes($endTime);
    }

    /**
     * Check if user can schedule more deliveries for a specific package today
     */
    public function canScheduleMoreForPackage(User $user, string $packageId): bool
    {
        $rules = $this->getRulesForUser($user);
        $today = Carbon::today();
        
        $deliveredToday = $user->deliveredFacts()
            ->where('package_id', $packageId)
            ->whereDate('delivered_at', $today)
            ->where('status', 'delivered')
            ->count();

        return $deliveredToday < $rules['max_per_package_daily'];
    }

    /**
     * Get retry configuration for failed deliveries
     */
    public function getRetryConfig(): array
    {
        return [
            'max_attempts' => Config::get('notification_rules.system_limits.max_retry_attempts', 3),
            'backoff_multiplier' => Config::get('notification_rules.system_limits.backoff_multiplier', 2),
        ];
    }

    /**
     * Calculate backoff delay for retries
     */
    public function calculateBackoffDelay(int $attemptNumber): int
    {
        $config = $this->getRetryConfig();
        $baseDelay = 60; // 1 minute base delay
        
        return $baseDelay * pow($config['backoff_multiplier'], $attemptNumber - 1);
    }

    /**
     * Validate time format according to config
     */
    public function validateTimeFormat(string $time): bool
    {
        $format = Config::get('notification_rules.validation.time_format', 'H:i');
        $parsed = Carbon::createFromFormat($format, $time);
        
        return $parsed && $parsed->format($format) === $time;
    }

    /**
     * Validate days of week according to config
     */
    public function validateDaysOfWeek(array $days): bool
    {
        $validDays = Config::get('notification_rules.validation.valid_days_of_week', [0, 1, 2, 3, 4, 5, 6]);
        
        foreach ($days as $day) {
            if (!in_array($day, $validDays)) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Check if device token is required
     */
    public function requiresDeviceToken(): bool
    {
        return Config::get('notification_rules.validation.require_device_token', true);
    }

    /**
     * Get maximum retry attempts for failed deliveries
     */
    public function getMaxRetryAttempts(): int
    {
        return Config::get('notification_rules.system_limits.max_retry_attempts', 3);
    }

    /**
     * Calculate random time within delivery window
     */
    public function calculateRandomWindowTime(string $windowStart, string $windowEnd, Carbon $baseDate): Carbon
    {
        [$startHour, $startMinute] = explode(':', $windowStart);
        [$endHour, $endMinute] = explode(':', $windowEnd);
        
        $windowStartTime = $baseDate->copy()->setTime((int)$startHour, (int)$startMinute, 0);
        $windowEndTime = $baseDate->copy()->setTime((int)$endHour, (int)$endMinute, 0);
        
        // Handle overnight windows
        if ($windowEndTime->lessThan($windowStartTime)) {
            $windowEndTime->addDay();
        }
        
        $windowDurationMinutes = $windowStartTime->diffInMinutes($windowEndTime);
        $randomOffset = rand(0, $windowDurationMinutes);
        
        return $windowStartTime->addMinutes($randomOffset);
    }

    /**
     * Check if user supports time-sensitive notifications
     */
    public function supportsTimeSensitive(User $user): bool
    {
        $rules = $this->getRulesForUser($user);
        return $rules['supports_time_sensitive'] ?? false;
    }
}
