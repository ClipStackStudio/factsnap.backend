<?php

declare(strict_types=1);

namespace App\Swagger;

use OpenApi\Annotations as OA;

/**
 * @OA\SecurityScheme(
 *   securityScheme="bearerAuth",
 *   type="http",
 *   scheme="bearer",
 *   bearerFormat="JWT",
 *   description="Use a Firebase ID token from the client SDK in the Authorization header. Example: Authorization: Bearer <idToken>"
 * )
 *
 * @OA\Tag(
 *   name="Auth",
 *   description="Authentication using Firebase Phone Auth."
 * )
 * 
 * @OA\Schema(
 *   schema="User",
 *   type="object",
 *   required={"id","name","firebase_uid","is_guest","is_premium","created_at","updated_at"},
 *   @OA\Property(property="id", type="string", format="uuid"),
 *   @OA\Property(property="name", type="string"),
 *   @OA\Property(property="email", type="string", nullable=true),
 *   @OA\Property(property="email_verified_at", type="string", format="date-time", nullable=true),
 *   @OA\Property(property="firebase_uid", type="string"),
 *   @OA\Property(property="phone_number", type="string", nullable=true, example="+15551234567"),
 *   @OA\Property(property="phone_verified_at", type="string", format="date-time", nullable=true),
 *   @OA\Property(property="is_guest", type="boolean"),
 *   @OA\Property(property="is_premium", type="boolean"),
 *   @OA\Property(property="created_at", type="string", format="date-time"),
 *   @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
final class OpenApi
{
}
