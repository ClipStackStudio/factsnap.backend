<?php

namespace App\Http\Requests;

use App\Services\NotificationRulesService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDeliverySettingsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $user = $this->user();
        $rulesService = app(NotificationRulesService::class);
        $userRules = $rulesService->getRulesForUser($user);
        $systemLimits = config('notification_rules.system_limits');
        
        return [
            'delivery_enabled' => 'boolean',
            'delivery_mode' => ['string', Rule::in($userRules['allowed_delivery_modes'])],
            'preferred_times' => 'array|max:' . min($userRules['max_times_per_day'], $systemLimits['max_preferred_times']),
            'preferred_times.*' => 'string|date_format:H:i',
            'days_of_week' => 'array',
            'days_of_week.*' => 'integer|min:0|max:6',
            'delivery_window_start' => [
                'nullable',
                'string',
                'date_format:H:i',
                'required_with:delivery_window_end',
                $userRules['supports_delivery_window'] ? '' : 'prohibited'
            ],
            'delivery_window_end' => [
                'nullable', 
                'string',
                'date_format:H:i',
                'required_with:delivery_window_start',
                'after:delivery_window_start',
                $userRules['supports_delivery_window'] ? '' : 'prohibited'
            ],
            'per_day_quota' => 'integer|min:1|max:' . $userRules['max_per_package_daily'],
            'min_interval_minutes' => 'nullable|integer|min:' . $userRules['min_interval_minutes'],
            'time_sensitive' => [
                'boolean',
                $userRules['supports_time_sensitive'] ? '' : 'prohibited'
            ],
            'quiet_hours_enabled' => 'boolean',
            'quiet_hours_start' => 'nullable|string|date_format:H:i',
            'quiet_hours_end' => 'nullable|string|date_format:H:i|after:quiet_hours_start',
            'timezone' => 'nullable|string|timezone',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $user = $this->user();
            $rulesService = app(NotificationRulesService::class);
            
            // Validate delivery settings against user rules
            $errors = $rulesService->validateDeliverySettings($user, $this->all());
            
            foreach ($errors as $error) {
                $validator->errors()->add('settings', $error);
            }

            // Validate delivery window duration
            if ($this->has('delivery_window_start') && $this->has('delivery_window_end')) {
                $rulesService = app(NotificationRulesService::class);
                $userRules = $rulesService->getRulesForUser($this->user());
                $systemLimits = config('notification_rules.system_limits');
                
                if (!$userRules['supports_delivery_window']) {
                    $validator->errors()->add('delivery_window', 'Delivery windows are only available for premium accounts.');
                } else {
                    $windowDuration = $rulesService->calculateWindowDuration(
                        $this->input('delivery_window_start'),
                        $this->input('delivery_window_end')
                    );
                    
                    if ($windowDuration > ($systemLimits['max_delivery_window_hours'] * 60)) {
                        $validator->errors()->add('delivery_window', "Delivery window cannot exceed {$systemLimits['max_delivery_window_hours']} hours.");
                    }
                    
                    if ($windowDuration < $systemLimits['min_delivery_window_minutes']) {
                        $validator->errors()->add('delivery_window', "Delivery window must be at least {$systemLimits['min_delivery_window_minutes']} minutes.");
                    }
                }
            }
            
            // Validate quiet hours permissions
            if ($this->has('quiet_hours_enabled') && !$this->input('quiet_hours_enabled')) {
                $rulesService = app(NotificationRulesService::class);
                $userRules = $rulesService->getRulesForUser($this->user());
                
                if (!$userRules['can_disable_quiet_hours']) {
                    $validator->errors()->add('quiet_hours_enabled', 'Quiet hours cannot be disabled for your account type.');
                }
            }

            // Validate required fields for delivery modes
            $deliveryMode = $this->input('delivery_mode');
            $deliveryModes = config('notification_rules.delivery_modes');
            
            if ($deliveryMode && isset($deliveryModes[$deliveryMode])) {
                $modeConfig = $deliveryModes[$deliveryMode];
                
                if ($modeConfig['requires_times'] && !$this->has('preferred_times')) {
                    $validator->errors()->add('preferred_times', 'Preferred times are required for this delivery mode.');
                }
                
                if ($modeConfig['requires_days'] && !$this->has('days_of_week')) {
                    $validator->errors()->add('days_of_week', 'Days of week are required for weekly delivery mode.');
                }
                
                if (isset($modeConfig['requires_window']) && $modeConfig['requires_window'] 
                    && (!$this->has('delivery_window_start') || !$this->has('delivery_window_end'))) {
                    $validator->errors()->add('delivery_window', 'Delivery window is required for windowed delivery mode.');
                }
            }
        });
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'preferred_times.max' => 'You can set maximum :max delivery times per day for your account type.',
            'delivery_mode.in' => 'The selected delivery mode is not allowed for your account type.',
            'per_day_quota.max' => 'Maximum :max facts per day per package allowed for your account type.',
            'min_interval_minutes.min' => 'Minimum interval must be at least :min minutes for your account type.',
            'delivery_window_end.after' => 'Delivery window end time must be after start time.',
        ];
    }
}
