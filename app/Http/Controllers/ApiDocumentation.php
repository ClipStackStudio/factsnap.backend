<?php

namespace App\Http\Controllers;

/**
 * @OA\Info(
 *     title="Factsnap API",
 *     version="1.0.0",
 *     description="API for managing educational categories, packages, and facts with comprehensive notification system supporting user type-based delivery rules, Apple Push Notifications, and intelligent fact scheduling."
 * )
 *
 * @OA\Server(
 *     url="/api",
 *     description="Factsnap API Server"
 * )
 * @OA\SecurityScheme(
 *     securityScheme="sanctum",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 * @OA\Schema(
 *     schema="ApiResponse",
 *     @OA\Property(property="success", type="boolean", description="Request success status"),
 *     @OA\Property(property="message", type="string", description="Response message"),
 *     @OA\Property(property="data", type="object", description="Response data")
 * )
 * @OA\Schema(
 *     schema="ApiError",
 *     @OA\Property(property="code", type="integer", description="Error code for programmatic handling", example=1001),
 *     @OA\Property(property="message", type="string", description="User-friendly error message", example="The requested category could not be found."),
 *     @OA\Property(property="details", type="string", description="Technical details and context for developers", example="Category with UUID '123e4567-e89b-12d3-a456-426614174000' does not exist in the database.")
 * )
 * @OA\Schema(
 *     schema="ValidationError",
 *     @OA\Property(property="code", type="integer", example=2001, description="Error code for programmatic handling"),
 *     @OA\Property(property="message", type="string", example="The provided data is invalid.", description="User-friendly error message"),
 *     @OA\Property(property="details", type="string", description="Technical details and context for developers"),
 *     @OA\Property(property="errors", type="object", description="Field-specific validation errors")
 * )
 * @OA\Schema(
 *     schema="Category",
 *     @OA\Property(property="id", type="string", format="uuid", description="Category UUID"),
 *     @OA\Property(property="name", type="string", description="Category name"),
 *     @OA\Property(property="icon_url", type="string", description="Category icon URL"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 * @OA\Schema(
 *     schema="Package",
 *     @OA\Property(property="id", type="string", format="uuid", description="Package UUID"),
 *     @OA\Property(property="category_id", type="string", format="uuid", description="Category UUID"),
 *     @OA\Property(property="name", type="string", description="Package name"),
 *     @OA\Property(property="description", type="string", description="Package description"),
 *     @OA\Property(property="icon_url", type="string", description="Package icon URL"),
 *     @OA\Property(property="access_level", type="string", enum={"free", "premium"}, description="Access level"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 * @OA\Schema(
 *     schema="Fact",
 *     @OA\Property(property="id", type="string", format="uuid", description="Fact UUID"),
 *     @OA\Property(property="package_id", type="string", format="uuid", description="Package UUID"),
 *     @OA\Property(property="content", type="string", description="Fact content"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 * @OA\Schema(
 *     schema="DeliverySettings",
 *     @OA\Property(property="delivery_enabled", type="boolean", description="Whether delivery is enabled for this package"),
 *     @OA\Property(property="delivery_mode", type="string", enum={"daily", "weekly", "times_per_day", "windowed"}, description="Delivery mode"),
 *     @OA\Property(property="preferred_times", type="array", @OA\Items(type="string", format="time"), description="Preferred delivery times in HH:MM format"),
 *     @OA\Property(property="days_of_week", type="array", @OA\Items(type="integer", minimum=0, maximum=6), description="Days of week (0=Sunday, 6=Saturday)"),
 *     @OA\Property(property="delivery_window_start", type="string", format="time", description="Window start time (premium only)"),
 *     @OA\Property(property="delivery_window_end", type="string", format="time", description="Window end time (premium only)"),
 *     @OA\Property(property="per_day_quota", type="integer", minimum=1, description="Maximum facts per day from this package"),
 *     @OA\Property(property="min_interval_minutes", type="integer", minimum=1, description="Minimum minutes between notifications"),
 *     @OA\Property(property="time_sensitive", type="boolean", description="Enable time-sensitive notifications (premium only)"),
 *     @OA\Property(property="next_delivery_at", type="string", format="date-time", description="Next scheduled delivery time")
 * )
 * @OA\Schema(
 *     schema="NotificationRules",
 *     @OA\Property(property="user_type", type="string", enum={"guest", "logged_in", "premium"}, description="User account type"),
 *     @OA\Property(property="max_daily_facts", type="integer", description="Maximum facts per day"),
 *     @OA\Property(property="min_interval_minutes", type="integer", description="Minimum minutes between notifications"),
 *     @OA\Property(property="max_times_per_day", type="integer", description="Maximum delivery times per day"),
 *     @OA\Property(property="max_per_package_daily", type="integer", description="Maximum facts per package per day"),
 *     @OA\Property(property="allowed_delivery_modes", type="array", @OA\Items(type="string"), description="Allowed delivery modes"),
 *     @OA\Property(property="can_disable_quiet_hours", type="boolean", description="Can disable quiet hours"),
 *     @OA\Property(property="supports_delivery_window", type="boolean", description="Supports delivery windows"),
 *     @OA\Property(property="supports_time_sensitive", type="boolean", description="Supports time-sensitive notifications"),
 *     @OA\Property(property="default_quiet_hours", type="object", 
 *         @OA\Property(property="start", type="string", format="time"),
 *         @OA\Property(property="end", type="string", format="time")
 *     )
 * )
 * @OA\Schema(
 *     schema="DeliveredFact",
 *     @OA\Property(property="id", type="string", format="uuid", description="Delivery UUID"),
 *     @OA\Property(property="user_id", type="string", format="uuid", description="User UUID"),
 *     @OA\Property(property="package_id", type="string", format="uuid", description="Package UUID"),
 *     @OA\Property(property="fact_id", type="string", format="uuid", description="Fact UUID"),
 *     @OA\Property(property="scheduled_at", type="string", format="date-time", description="When delivery was scheduled"),
 *     @OA\Property(property="delivered_at", type="string", format="date-time", description="When fact was delivered"),
 *     @OA\Property(property="seen_at", type="string", format="date-time", description="When user marked as seen"),
 *     @OA\Property(property="status", type="string", enum={"scheduled", "delivering", "delivered", "failed", "failed_permanently"}, description="Delivery status"),
 *     @OA\Property(property="channel", type="string", enum={"push", "email", "sms"}, description="Delivery channel"),
 *     @OA\Property(property="attempts", type="integer", description="Number of delivery attempts"),
 *     @OA\Property(property="meta", type="object", description="Additional delivery metadata"),
 *     @OA\Property(property="fact", ref="#/components/schemas/Fact"),
 *     @OA\Property(property="package", ref="#/components/schemas/Package")
 * )
 * @OA\Schema(
 *     schema="PushNotificationPreferences",
 *     @OA\Property(property="push_notifications_enabled", type="boolean", description="Enable push notifications"),
 *     @OA\Property(property="timezone", type="string", description="User timezone (IANA format)"),
 *     @OA\Property(property="quiet_hours_enabled", type="boolean", description="Enable quiet hours"),
 *     @OA\Property(property="quiet_hours_start", type="string", format="time", description="Quiet hours start time"),
 *     @OA\Property(property="quiet_hours_end", type="string", format="time", description="Quiet hours end time")
 * )
 * @OA\Schema(
 *     schema="UserPackageSubscription",
 *     @OA\Property(property="package_id", type="string", format="uuid", description="Package UUID"),
 *     @OA\Property(property="package_name", type="string", description="Package name"),
 *     @OA\Property(property="description", type="string", description="Package description"),
 *     @OA\Property(property="icon_url", type="string", description="Package icon URL"),
 *     @OA\Property(property="access_level", type="string", enum={"free", "loggedIn", "premium"}, description="Required access level"),
 *     @OA\Property(property="subscribed_at", type="string", format="date-time", description="Subscription date"),
 *     @OA\Property(property="category", type="object",
 *         @OA\Property(property="id", type="string", format="uuid"),
 *         @OA\Property(property="name", type="string")
 *     ),
 *     @OA\Property(property="delivery_settings", ref="#/components/schemas/DeliverySettings")
 * )
 */
class ApiDocumentation
{
    // This class is used only for API documentation annotations
}
