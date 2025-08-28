<?php

declare(strict_types=1);

namespace App\Swagger;

use OpenApi\Annotations as OA;

/**
 * @OA\SecurityScheme(
 *   securityScheme="firebaseAuth",
 *   type="http",
 *   scheme="bearer",
 *   bearerFormat="JWT",
 *   description="Firebase ID Token obtained from Firebase SDK. Use in Authorization header: Bearer <firebase_id_token>"
 * )
 * 
 * @OA\SecurityScheme(
 *   securityScheme="sanctumAuth", 
 *   type="http",
 *   scheme="bearer",
 *   bearerFormat="Token",
 *   description="Sanctum Bearer Token for guest users. Get token from POST /api/auth/guest. Use in Authorization header: Bearer <sanctum_token>"
 * )
 * 
 * @OA\SecurityScheme(
 *   securityScheme="dualAuth",
 *   type="http", 
 *   scheme="bearer",
 *   description="Accepts either Firebase ID tokens OR Sanctum tokens. Firebase is tried first, then Sanctum as fallback."
 * )
 *
 * @OA\Tag(
 *   name="Authentication",
 *   description="User authentication endpoints for Firebase and Guest users"
 * )
 * 
 * @OA\Tag(
 *   name="User Packages",
 *   description="Package subscription management for authenticated users (both Firebase and Guest users). Uses unified endpoints that support dual authentication."
 * )
 * 
 * @OA\Tag(
 *   name="Packages",
 *   description="Public package browsing and information retrieval"
 * )
 * 
 * @OA\Tag(
 *   name="Categories",
 *   description="Package category browsing and filtering"
 * )
 * 
 * @OA\Schema(
 *   schema="User",
 *   type="object",
 *   required={"id","name","is_guest","is_premium","created_at","updated_at"},
 *   @OA\Property(property="id", type="string", format="uuid"),
 *   @OA\Property(property="name", type="string"),
 *   @OA\Property(property="email", type="string", nullable=true),
 *   @OA\Property(property="email_verified_at", type="string", format="date-time", nullable=true),
 *   @OA\Property(property="firebase_uid", type="string", nullable=true, description="Firebase UID for Firebase authenticated users, null for guest users"),
 *   @OA\Property(property="phone_number", type="string", nullable=true, example="+15551234567"),
 *   @OA\Property(property="phone_verified_at", type="string", format="date-time", nullable=true),
 *   @OA\Property(property="is_guest", type="boolean", description="True for guest users created via Sanctum, false for Firebase users"),
 *   @OA\Property(property="is_premium", type="boolean"),
 *   @OA\Property(property="created_at", type="string", format="date-time"),
 *   @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
final class OpenApi
{
}
