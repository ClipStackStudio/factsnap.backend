<?php

namespace App\Http\Controllers;

/**
 * @OA\Info(
 *     title="Factsnap API",
 *     version="1.0.0",
 *     description="API for managing educational categories, packages, and facts",
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
