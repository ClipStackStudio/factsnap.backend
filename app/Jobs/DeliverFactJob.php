<?php

namespace App\Jobs;

use App\Models\DeliveredFact;
use App\Models\Fact;
use App\Models\Package;
use App\Models\User;
use App\Services\AppleNotificationService;
use App\Services\NotificationRulesService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DeliverFactJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries;
    public $timeout = 60;

    public function __construct(
        public User $user,
        public Package $package
    ) {
        $rulesService = app(NotificationRulesService::class);
        $this->tries = $rulesService->getMaxRetryAttempts();
    }

    public function handle(): void
    {
        $rulesService = app(NotificationRulesService::class);
        
        // Check if user still has push notifications enabled
        if (!$this->user->push_notifications_enabled || !$this->user->apns_device_token) {
            Log::info("Push notifications disabled for user {$this->user->id}");
            return;
        }

        // Check notification rules - daily quota and interval limits
        if (!$rulesService->canScheduleMore($this->user)) {
            Log::info("Daily quota reached for user {$this->user->id}");
            return;
        }

        if (!$rulesService->hasMinIntervalPassed($this->user)) {
            Log::info("Minimum interval not met for user {$this->user->id}");
            return;
        }

        // Check if within quiet hours
        if ($rulesService->isWithinQuietHours($this->user)) {
            Log::info("Within quiet hours for user {$this->user->id}, rescheduling");
            $this->rescheduleJob();
            return;
        }

        // Check if user is still subscribed to the package
        $subscription = $this->user->packages()->where('packages.id', $this->package->id)->first();
        if (!$subscription || !$subscription->pivot->delivery_enabled) {
            Log::info("User {$this->user->id} no longer subscribed to package {$this->package->id}");
            return;
        }

        // Check per-package daily quota
        if (!$rulesService->canScheduleMoreForPackage($this->user, $this->package->id)) {
            Log::info("Per-package daily quota reached for user {$this->user->id}, package {$this->package->id}");
            return;
        }

        // Get next fact to deliver
        $fact = $this->selectNextFact($subscription);
        if (!$fact) {
            Log::info("No more facts available for user {$this->user->id} in package {$this->package->id}");
            return;
        }

        try {
            // Record the delivery attempt
            $deliveredFact = DeliveredFact::create([
                'user_id' => $this->user->id,
                'package_id' => $this->package->id,
                'fact_id' => $fact->id,
                'scheduled_at' => now(),
                'status' => 'delivering',
                'channel' => 'push',
                'attempts' => $this->attempts + 1,
            ]);

            // Send push notification
            $notificationService = app(AppleNotificationService::class);
            $rulesService = app(NotificationRulesService::class);
            
            $options = [
                'time_sensitive' => $subscription->pivot->time_sensitive && $rulesService->supportsTimeSensitive($this->user),
                'collapse_id' => "fact_{$this->package->id}",
            ];
            
            $success = $notificationService->sendFactNotification(
                $this->user->apns_device_token,
                $fact,
                $this->package,
                $options
            );

            if ($success) {
                $deliveredFact->update([
                    'delivered_at' => now(),
                    'status' => 'delivered',
                ]);

                // Update subscription last delivered info
                $this->user->packages()->updateExistingPivot($this->package->id, [
                    'last_delivered_at' => now(),
                    'last_selected_fact_id' => $fact->id,
                ]);

                Log::info("Fact delivered to user {$this->user->id}: {$fact->title}");
            } else {
                $deliveredFact->update(['status' => 'failed']);
                throw new \Exception('Push notification failed');
            }

            // Schedule next delivery
            $this->scheduleNextDelivery();

        } catch (\Exception $e) {
            Log::error("Failed to deliver fact to user {$this->user->id}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Select the next fact to deliver, avoiding recent duplicates
     */
    private function selectNextFact($subscription): ?Fact
    {
        $deliveredFactIds = DeliveredFact::where('user_id', $this->user->id)
            ->where('package_id', $this->package->id)
            ->where('status', 'delivered')
            ->pluck('fact_id');

        // Try to get an undelivered fact first
        $fact = Fact::where('package_id', $this->package->id)
            ->whereNotIn('id', $deliveredFactIds)
            ->inRandomOrder()
            ->first();

        // If no undelivered facts, get a fact that hasn't been delivered recently
        if (!$fact && $deliveredFactIds->isNotEmpty()) {
            $recentlyDelivered = DeliveredFact::where('user_id', $this->user->id)
                ->where('package_id', $this->package->id)
                ->where('status', 'delivered')
                ->where('delivered_at', '>=', Carbon::now()->subDays(30)) // Avoid facts from last 30 days
                ->pluck('fact_id');

            $fact = Fact::where('package_id', $this->package->id)
                ->whereNotIn('id', $recentlyDelivered)
                ->inRandomOrder()
                ->first();
        }

        // Last resort: any fact from the package
        if (!$fact) {
            $fact = Fact::where('package_id', $this->package->id)
                ->inRandomOrder()
                ->first();
        }

        return $fact;
    }

    /**
     * Schedule the next delivery based on subscription settings
     */
    private function scheduleNextDelivery(): void
    {
        $subscription = $this->user->packages()->where('packages.id', $this->package->id)->first();
        if (!$subscription) return;

        $pivot = $subscription->pivot;
        $nextDelivery = $this->calculateNextDeliveryTime($pivot);

        if ($nextDelivery) {
            // Apply jitter to prevent clustering
            $rulesService = app(NotificationRulesService::class);
            $nextDelivery = $rulesService->applyJitter($nextDelivery);

            // Ensure it's not within quiet hours
            $nextDelivery = $rulesService->getNextAllowedTime($this->user, $nextDelivery);

            // Update the next delivery time
            $this->user->packages()->updateExistingPivot($this->package->id, [
                'next_delivery_at' => $nextDelivery
            ]);

            // Schedule the next job
            DeliverFactJob::dispatch($this->user, $this->package)->delay($nextDelivery);
        }
    }

    /**
     * Calculate next delivery time based on delivery mode and settings
     */
    private function calculateNextDeliveryTime($pivot): ?Carbon
    {
        $mode = $pivot->delivery_mode;
        $preferredTimes = $pivot->preferred_times ?? ['10:00'];
        $daysOfWeek = $pivot->days_of_week;
        $userTimezone = $this->user->timezone ?? 'UTC';

        $now = Carbon::now($userTimezone);

        switch ($mode) {
            case 'daily':
                return $this->calculateDailyDelivery($preferredTimes, $now);

            case 'weekly':
                return $this->calculateWeeklyDelivery($preferredTimes, $daysOfWeek, $now);

            case 'times_per_day':
                return $this->calculateTimesPerDayDelivery($preferredTimes, $now, $pivot);

            case 'windowed':
                return $this->calculateWindowedDelivery($pivot, $now);

            default:
                return null;
        }
    }

    /**
     * Calculate next daily delivery
     */
    private function calculateDailyDelivery(array $times, Carbon $now): Carbon
    {
        $time = $times[0]; // Use first time for daily mode
        $next = $now->copy()->addDay();
        
        [$hour, $minute] = explode(':', $time);
        $next->setTime((int)$hour, (int)$minute, 0);
        
        return $next->utc();
    }

    /**
     * Calculate next weekly delivery
     */
    private function calculateWeeklyDelivery(array $times, ?array $daysOfWeek, Carbon $now): Carbon
    {
        $time = $times[0];
        $days = $daysOfWeek ?? [1, 2, 3, 4, 5]; // Default to weekdays
        
        $next = $now->copy()->addDay();
        
        // Find next valid day
        while (!in_array($next->dayOfWeek, $days)) {
            $next->addDay();
        }
        
        [$hour, $minute] = explode(':', $time);
        $next->setTime((int)$hour, (int)$minute, 0);
        
        return $next->utc();
    }

    /**
     * Calculate next delivery for times per day mode
     */
    private function calculateTimesPerDayDelivery(array $times, Carbon $now, $pivot): Carbon
    {
        sort($times);
        
        // Find next time today or tomorrow
        foreach ($times as $time) {
            [$hour, $minute] = explode(':', $time);
            $candidate = $now->copy()->setTime((int)$hour, (int)$minute, 0);
            
            if ($candidate->greaterThan($now)) {
                // Check minimum interval
                if ($pivot->min_interval_minutes) {
                    $lastDelivery = $pivot->last_delivered_at ? Carbon::parse($pivot->last_delivered_at) : null;
                    if ($lastDelivery && $candidate->diffInMinutes($lastDelivery) < $pivot->min_interval_minutes) {
                        continue;
                    }
                }
                return $candidate->utc();
            }
        }
        
        // No more times today, schedule for first time tomorrow
        $firstTime = $times[0];
        [$hour, $minute] = explode(':', $firstTime);
        $next = $now->copy()->addDay()->setTime((int)$hour, (int)$minute, 0);
        
        return $next->utc();
    }

    /**
     * Calculate windowed delivery
     */
    private function calculateWindowedDelivery($pivot, Carbon $now): Carbon
    {
        $windowStart = $pivot->delivery_window_start;
        $windowEnd = $pivot->delivery_window_end;
        
        if (!$windowStart || !$windowEnd) {
            return $this->calculateDailyDelivery(['10:00'], $now);
        }
        
        $rulesService = app(NotificationRulesService::class);
        $nextDeliveryDate = $now->copy()->addDay();
        
        return $rulesService->calculateRandomWindowTime($windowStart, $windowEnd, $nextDeliveryDate)->utc();
    }

    /**
     * Reschedule job for when quiet hours end
     */
    private function rescheduleJob(): void
    {
        $rulesService = app(NotificationRulesService::class);
        $nextAllowedTime = $rulesService->getNextAllowedTime($this->user, Carbon::now());
        
        DeliverFactJob::dispatch($this->user, $this->package)->delay($nextAllowedTime);
    }

    /**
     * Handle job failure with exponential backoff
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("DeliverFactJob failed permanently for user {$this->user->id}, package {$this->package->id}: " . $exception->getMessage());
        
        // Record final failure in delivered_facts
        DeliveredFact::create([
            'user_id' => $this->user->id,
            'package_id' => $this->package->id,
            'fact_id' => null,
            'scheduled_at' => now(),
            'status' => 'failed_permanently',
            'channel' => 'push',
            'attempts' => $this->attempts(),
            'meta' => [
                'error' => $exception->getMessage(),
                'final_failure' => true,
            ],
        ]);
    }

    /**
     * Calculate retry delay with exponential backoff
     */
    public function backoff(): array
    {
        $rulesService = app(NotificationRulesService::class);
        $delays = [];
        
        for ($attempt = 1; $attempt <= $this->tries; $attempt++) {
            $delays[] = $rulesService->calculateBackoffDelay($attempt);
        }
        
        return $delays;
    }
}
