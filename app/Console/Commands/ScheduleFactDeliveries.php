<?php

namespace App\Console\Commands;

use App\Jobs\DeliverFactJob;
use App\Models\User;
use App\Services\NotificationRulesService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ScheduleFactDeliveries extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'facts:schedule 
                           {--dry-run : Show what would be scheduled without actually scheduling}
                           {--user= : Schedule for specific user ID only}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Schedule fact deliveries based on user subscription settings and notification rules';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->info('Scheduling fact deliveries...');
        
        $rulesService = app(NotificationRulesService::class);
        $dryRun = $this->option('dry-run');
        $specificUserId = $this->option('user');
        
        $scheduledCount = 0;
        $skippedCount = 0;
        
        // Get users with active subscriptions
        $query = User::whereHas('packages', function ($query) {
            $query->where('delivery_enabled', true)
                  ->where(function ($subQuery) {
                      $subQuery->whereNull('next_delivery_at')
                               ->orWhere('next_delivery_at', '<=', Carbon::now());
                  });
        })->where('push_notifications_enabled', true)
          ->whereNotNull('apns_device_token');
        
        if ($specificUserId) {
            $query->where('id', $specificUserId);
        }
        
        $users = $query->with(['packages' => function ($query) {
            $query->where('delivery_enabled', true)
                  ->where(function ($subQuery) {
                      $subQuery->whereNull('next_delivery_at')
                               ->orWhere('next_delivery_at', '<=', Carbon::now());
                  });
        }])->get();
        
        foreach ($users as $user) {
            // Check user-level constraints
            if (!$rulesService->canScheduleMore($user)) {
                $this->warn("User {$user->id} has reached daily quota");
                $skippedCount++;
                continue;
            }
            
            if (!$rulesService->hasMinIntervalPassed($user)) {
                $this->warn("User {$user->id} hasn't met minimum interval");
                $skippedCount++;
                continue;
            }
            
            if ($rulesService->isWithinQuietHours($user)) {
                $this->warn("User {$user->id} is within quiet hours");
                $skippedCount++;
                continue;
            }
            
            foreach ($user->packages as $package) {
                $this->info("Processing user {$user->id}, package {$package->id}");
                
                if ($dryRun) {
                    $this->line("  [DRY RUN] Would schedule delivery for user {$user->id}, package {$package->id}");
                } else {
                    // Dispatch the job immediately
                    DeliverFactJob::dispatch($user, $package);
                    $this->line("  ✓ Scheduled delivery for user {$user->id}, package {$package->id}");
                }
                
                $scheduledCount++;
            }
        }
        
        $this->info("Scheduling completed:");
        $this->line("  - Scheduled: {$scheduledCount}");
        $this->line("  - Skipped: {$skippedCount}");
        
        if ($dryRun) {
            $this->warn("This was a dry run. No actual jobs were scheduled.");
        }
    }
}
