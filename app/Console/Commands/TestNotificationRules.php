<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\NotificationRulesService;
use Illuminate\Console\Command;

class TestNotificationRules extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:notification-rules 
                           {--user-type=guest : Test specific user type (guest, logged_in, premium)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test notification rules implementation for different user types';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->info('Testing Notification Rules Implementation');
        $this->line('===========================================');
        
        $rulesService = app(NotificationRulesService::class);
        $userType = $this->option('user-type');
        
        if (in_array($userType, ['guest', 'logged_in', 'premium'])) {
            $this->testUserType($userType, $rulesService);
        } else {
            $this->testAllUserTypes($rulesService);
        }
        
        $this->testSystemLimits($rulesService);
        $this->testValidationMethods($rulesService);
        
        $this->info("\n✅ All notification rules tests completed!");
    }
    
    private function testAllUserTypes(NotificationRulesService $rulesService): void
    {
        $userTypes = ['guest', 'logged_in', 'premium'];
        
        foreach ($userTypes as $type) {
            $this->testUserType($type, $rulesService);
        }
    }
    
    private function testUserType(string $userType, NotificationRulesService $rulesService): void
    {
        $this->line("\n📋 Testing {$userType} user rules:");
        
        // Create mock user
        $user = new User();
        $user->is_guest = $userType === 'guest';
        $user->is_premium = $userType === 'premium';
        $user->timezone = 'UTC';
        $user->quiet_hours_enabled = true;
        $user->quiet_hours_start = '22:00';
        $user->quiet_hours_end = '08:00';
        
        $rules = $rulesService->getRulesForUser($user);
        $this->displayUserRules($rules);
        
        $this->testValidations($user, $rulesService, $userType);
    }
    
    private function displayUserRules(array $rules): void
    {
        $this->line("  Max daily facts: {$rules['max_daily_facts']}");
        $this->line("  Min interval: {$rules['min_interval_minutes']} minutes");
        $this->line("  Max times per day: {$rules['max_times_per_day']}");
        $this->line("  Max per package daily: {$rules['max_per_package_daily']}");
        $this->line("  Allowed modes: " . implode(', ', $rules['allowed_delivery_modes']));
        $this->line("  Can disable quiet hours: " . ($rules['can_disable_quiet_hours'] ? 'Yes' : 'No'));
        $this->line("  Supports delivery window: " . ($rules['supports_delivery_window'] ?? false ? 'Yes' : 'No'));
        $this->line("  Supports time-sensitive: " . ($rules['supports_time_sensitive'] ?? false ? 'Yes' : 'No'));
    }
    
    private function testValidations(User $user, NotificationRulesService $rulesService, string $userType): void
    {
        $this->line("\n  🧪 Testing validations:");
        
        // Test valid settings
        $validSettings = [
            'delivery_mode' => 'daily',
            'preferred_times' => ['10:00'],
            'per_day_quota' => 1,
        ];
        
        $errors = $rulesService->validateDeliverySettings($user, $validSettings);
        $this->line("    ✅ Valid settings: " . (empty($errors) ? 'PASS' : 'FAIL'));
        
        // Test invalid delivery mode
        $invalidMode = [
            'delivery_mode' => 'windowed',
            'preferred_times' => ['10:00'],
        ];
        
        $errors = $rulesService->validateDeliverySettings($user, $invalidMode);
        $expectedError = !in_array('windowed', $rulesService->getRulesForUser($user)['allowed_delivery_modes']);
        $hasError = !empty($errors);
        $this->line("    " . ($expectedError === $hasError ? '✅' : '❌') . " Invalid mode validation: " . ($expectedError === $hasError ? 'PASS' : 'FAIL'));
        
        // Test too many times
        $rules = $rulesService->getRulesForUser($user);
        $tooManyTimes = [
            'delivery_mode' => 'times_per_day',
            'preferred_times' => array_fill(0, $rules['max_times_per_day'] + 1, '10:00'),
        ];
        
        $errors = $rulesService->validateDeliverySettings($user, $tooManyTimes);
        $this->line("    " . (!empty($errors) ? '✅' : '❌') . " Too many times validation: " . (!empty($errors) ? 'PASS' : 'FAIL'));
        
        // Test windowed delivery for non-premium
        if ($userType !== 'premium') {
            $windowedSettings = [
                'delivery_mode' => 'windowed',
                'delivery_window_start' => '09:00',
                'delivery_window_end' => '17:00',
            ];
            
            $errors = $rulesService->validateDeliverySettings($user, $windowedSettings);
            $this->line("    " . (!empty($errors) ? '✅' : '❌') . " Windowed delivery restriction: " . (!empty($errors) ? 'PASS' : 'FAIL'));
        }
        
        // Test quiet hours disable for guest
        if ($userType === 'guest') {
            $noQuietHours = ['quiet_hours_enabled' => false];
            $errors = $rulesService->validateDeliverySettings($user, $noQuietHours);
            $this->line("    " . (!empty($errors) ? '✅' : '❌') . " Quiet hours disable restriction: " . (!empty($errors) ? 'PASS' : 'FAIL'));
        }
    }
    
    private function testSystemLimits(NotificationRulesService $rulesService): void
    {
        $this->line("\n🔧 Testing system limits:");
        
        $config = $rulesService->getRetryConfig();
        $this->line("  Max retry attempts: {$config['max_attempts']}");
        $this->line("  Backoff multiplier: {$config['backoff_multiplier']}");
        
        // Test backoff calculation
        for ($i = 1; $i <= 3; $i++) {
            $delay = $rulesService->calculateBackoffDelay($i);
            $this->line("  Attempt {$i} delay: {$delay} seconds");
        }
        
        // Test window duration calculation
        $duration = $rulesService->calculateWindowDuration('09:00', '17:00');
        $this->line("  Window duration (9AM-5PM): {$duration} minutes");
        $expected = 8 * 60; // 8 hours
        $this->line("    " . ($duration === $expected ? '✅' : '❌') . " Window calculation: " . ($duration === $expected ? 'PASS' : 'FAIL'));
    }
    
    private function testValidationMethods(NotificationRulesService $rulesService): void
    {
        $this->line("\n🧪 Testing validation methods:");
        
        // Test time format validation
        $validTime = $rulesService->validateTimeFormat('14:30');
        $invalidTime = $rulesService->validateTimeFormat('25:90');
        $this->line("  ✅ Valid time format: " . ($validTime ? 'PASS' : 'FAIL'));
        $this->line("  ✅ Invalid time format: " . (!$invalidTime ? 'PASS' : 'FAIL'));
        
        // Test days validation
        $validDays = $rulesService->validateDaysOfWeek([1, 2, 3, 4, 5]);
        $invalidDays = $rulesService->validateDaysOfWeek([8, 9]);
        $this->line("  ✅ Valid days: " . ($validDays ? 'PASS' : 'FAIL'));
        $this->line("  ✅ Invalid days: " . (!$invalidDays ? 'PASS' : 'FAIL'));
        
        // Test device token requirement
        $requiresToken = $rulesService->requiresDeviceToken();
        $this->line("  📱 Requires device token: " . ($requiresToken ? 'Yes' : 'No'));
    }
}
