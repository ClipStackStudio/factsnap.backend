<?php

namespace App\Http\Controllers;

/**
 * @OA\Info(
 *     title="Factsnap API",
 *     version="1.0.0",
 *     description="API for managing educational categories, packages, and facts.
 *
 * # Authentication
 * 
 * This API uses **Firebase Authentication** supporting both regular and guest users:
 * 
 * ## Regular Users (Phone Authentication)
 * - Use Firebase phone verification to authenticate
 * - Send Firebase ID token in `Authorization: Bearer <firebase_id_token>` header
 * - Access to 'free', 'loggedIn', and 'premium' packages (if premium)
 * 
 * ## Guest Users (Anonymous Authentication)  
 * - Use Firebase anonymous authentication (no phone required)
 * - Send Firebase ID token in `Authorization: Bearer <firebase_id_token>` header
 * - Access to 'free' and 'loggedIn' packages ('premium' if created as premium guest)
 * 
 * ## Package Access Levels
 * - **free**: No authentication required
 * - **loggedIn**: Requires Firebase authentication (phone OR anonymous)
 * - **premium**: Requires premium user status",
 *     @OA\Contact(
 *         email="support@factsnap.com"
 *     )
 * )
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
 */
class ApiDocumentation
{
    // This class is used only for API documentation annotations
}
