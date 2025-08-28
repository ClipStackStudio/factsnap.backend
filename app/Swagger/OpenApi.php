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
 *   description="Firebase ID Token obtained from Firebase SDK (phone or anonymous authentication). Use in Authorization header: Bearer <firebase_id_token>"
 * )
 *
 * @OA\Tag(
 *   name="Authentication",
 *   description="Firebase authentication endpoints for regular and guest users"
 * )
 * 
 * @OA\Tag(
 *   name="User Packages",
 *   description="Package subscription management for Firebase authenticated users (both regular and guest users)."
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
