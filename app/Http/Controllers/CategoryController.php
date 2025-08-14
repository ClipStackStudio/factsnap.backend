<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * Display all categories only (without packages).
     *
     * @OA\Get(
     *     path="/categories-only",
     *     summary="Get all categories without packages",
     *     tags={"Categories"},
     *     @OA\Response(
     *         response=200,
     *         description="Categories retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Category")),
     *             @OA\Property(property="debug", type="object",
     *                 @OA\Property(property="response_time_ms", type="number"),
     *                 @OA\Property(property="from_cache", type="boolean"),
     *                 @OA\Property(property="count", type="integer")
     *             )
     *         )
     *     )
     * )
     * @return JsonResponse
     */
    public function allCategories(): JsonResponse
    {        
        
        $categories = cache()->remember('categories_only', 3600, function () {
            return Category::select('id', 'name', 'icon_url', 'created_at', 'updated_at')->get();
        });
        
        return response()->json([
            'success' => true,
            'data' => $categories
        ]);
    }

    /**
     * Display all categories with their packages.
     *
     * @OA\Get(
     *     path="/categories",
     *     summary="Get all categories with their packages",
     *     tags={"Categories"},
     *     @OA\Response(
     *         response=200,
     *         description="Categories with packages retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="details", type="object",
     *                 @OA\Property(property="total", type="integer", description="Total number of categories")
     *             ),
     *             @OA\Property(property="data", type="array", 
     *                 @OA\Items(
     *                     allOf={
     *                         @OA\Schema(ref="#/components/schemas/Category"),
     *                         @OA\Schema(
     *                             @OA\Property(property="packages", type="array", @OA\Items(ref="#/components/schemas/Package"))
     *                         )
     *                     }
     *                 )
     *             )
     *         )
     *     )
     * )
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $cacheKey = 'categories_with_packages';
        $fromCache = cache()->has($cacheKey);
        
        $categories = cache()->remember($cacheKey, 3600, function () {
            $categories = Category::with('packages')->get();
            return $categories;
        });
        
        return response()->json([
            'success' => true,
            'details' => [
                'total' => $categories->count(),
            ],
            'data' => $categories
        ]);
    }

    /**
     * Display a specific category with its packages.
     *
     * @OA\Get(
     *     path="/categories/{id}",
     *     summary="Get a specific category with its packages",
     *     tags={"Categories"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid"),
     *         description="Category UUID"
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Category retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", 
     *                 allOf={
     *                     @OA\Schema(ref="#/components/schemas/Category"),
     *                     @OA\Schema(
     *                         @OA\Property(property="packages", type="array", @OA\Items(ref="#/components/schemas/Package"))
     *                     )
     *                 }
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Category not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Category not found")
     *         )
     *     )
     * )
     * @param string $id
     * @return JsonResponse
     */
    public function show(string $id): JsonResponse
    {
        $category = Category::with('packages')->find($id);

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $category,
        ]);
    }

    /**
     * Get packages for a specific category.
     *
     * @OA\Get(
     *     path="/categories/{id}/packages",
     *     summary="Get all packages for a specific category",
     *     tags={"Categories"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid"),
     *         description="Category UUID"
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Packages retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="category", type="object",
     *                     @OA\Property(property="id", type="string", format="uuid"),
     *                     @OA\Property(property="name", type="string"),
     *                     @OA\Property(property="icon_url", type="string")
     *                 ),
     *                 @OA\Property(property="packages", type="array", @OA\Items(ref="#/components/schemas/Package"))
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Category not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Category not found")
     *         )
     *     )
     * )
     * @param string $id
     * @return JsonResponse
     */
    public function packages(string $id): JsonResponse
    {
        $category = Category::with('packages')->find($id);

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found',
            ], 404);
        }

        $packages = $category->packages;

        return response()->json([
            'success' => true,
            'data' => [
                'category' => $category->only(['id', 'name', 'icon_url']),
                'packages' => $packages,
            ],
        ]);
    }

    /**
     * Get categories with packages filtered by access level.
     *
     * @OA\Get(
     *     path="/categories/filter/access-level",
     *     summary="Get categories filtered by package access level",
     *     tags={"Categories"},
     *     @OA\Parameter(
     *         name="access_level",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="string", enum={"free", "premium"}, default="free"),
     *         description="Filter packages by access level"
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Filtered categories retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", 
     *                 @OA\Items(
     *                     allOf={
     *                         @OA\Schema(ref="#/components/schemas/Category"),
     *                         @OA\Schema(
     *                             @OA\Property(property="packages", type="array", @OA\Items(ref="#/components/schemas/Package"))
     *                         )
     *                     }
     *                 )
     *             ),
     *             @OA\Property(property="filter", type="object",
     *                 @OA\Property(property="access_level", type="string", example="free")
     *             )
     *         )
     *     )
     * )
     * @param Request $request
     * @return JsonResponse
     */
    public function byAccessLevel(Request $request): JsonResponse
    {
        $accessLevel = $request->query('access_level', 'free');

        $categories = Category::with([
            'packages' => function ($query) use ($accessLevel) {
                $query->where('access_level', $accessLevel);
            }
        ])->get();

        // Filter out categories that have no packages with the specified access level
        $categories = $categories->filter(function ($category) {
            return $category->packages->isNotEmpty();
        });

        return response()->json([
            'success' => true,
            'data' => $categories->values(), // Reset array keys
            'filter' => [
                'access_level' => $accessLevel,
            ],
        ]);
    }

    /**
     * Clear the categories cache (useful when data is updated).
     *
     * @OA\Post(
     *     path="/categories/clear-cache",
     *     summary="Clear categories cache",
     *     tags={"Categories"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Cache cleared successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="All categories cache cleared successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     * @return JsonResponse
     */
    public function clearCache(): JsonResponse
    {
        cache()->forget('categories_with_packages');
        cache()->forget('categories_only');
        cache()->forget('categories_fast');

        return response()->json([
            'success' => true,
            'message' => 'All categories cache cleared successfully',
        ]);
    }

}
