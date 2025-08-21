<?php

namespace App\Http\Controllers;

use App\Models\Package;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PackageController extends Controller
{
    use ApiErrorResponses;
    /**
     * Display all packages with their category.
     *
     * @OA\Get(
     *     path="/packages",
     *     summary="Get all packages with their category",
     *     tags={"Packages"},
     *     @OA\Response(
     *         response=200,
     *         description="Packages retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", 
     *                 @OA\Items(
     *                     allOf={
     *                         @OA\Schema(ref="#/components/schemas/Package"),
     *                         @OA\Schema(
     *                             @OA\Property(property="category", ref="#/components/schemas/Category")
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
        $packages = Package::with('category')->get();

        return response()->json([
            'success' => true,
            'data' => $packages,
        ]);
    }

    /**
     * Display a specific package with its category and facts.
     *
     * @OA\Get(
     *     path="/packages/{id}",
     *     summary="Get a specific package with its category and facts",
     *     tags={"Packages"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid"),
     *         description="Package UUID"
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Package retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", 
     *                 allOf={
     *                     @OA\Schema(ref="#/components/schemas/Package"),
     *                     @OA\Schema(
     *                         @OA\Property(property="category", ref="#/components/schemas/Category"),
     *                         @OA\Property(property="facts", type="array", @OA\Items(ref="#/components/schemas/Fact"))
     *                     )
     *                 }
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Package not found",
     *         @OA\JsonContent(ref="#/components/schemas/ApiError")
     *     )
     * )
     * @param string $id
     * @return JsonResponse
     */
    public function show(string $id): JsonResponse
    {
        $cacheKey = "package_{$id}_with_facts";
        
        $package = cache()->remember($cacheKey, 3600, function () use ($id) {
            return Package::with(['category', 'facts' => function ($query) {
                $query->limit(2);
            }])->find($id);
        });

        if (!$package) {
            return $this->notFoundResponse('package', $id);
        }

        return response()->json([
            'success' => true,
            'data' => $package,
        ]);
    }

    /**
     * Get packages filtered by access level.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function byAccessLevel(Request $request): JsonResponse
    {
        $accessLevel = $request->query('access_level', 'free');

        $packages = Package::with('category')
            ->where('access_level', $accessLevel)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $packages,
            'filter' => [
                'access_level' => $accessLevel,
            ],
        ]);
    }

    /**
     * Get all facts for a specific package.
     *
     * @param string $id
     * @return JsonResponse
     */
    public function facts(string $id): JsonResponse
    {
        $package = Package::with(['category', 'facts'])->find($id);

        if (!$package) {
            return $this->notFoundResponse('package', $id, 'Cannot retrieve facts for non-existent package.');
        }

        return response()->json([
            'success' => true,
            'data' => [
                'package' => $package->only(['id', 'name', 'description', 'icon_url', 'access_level']),
                'category' => $package->category,
                'facts' => $package->facts,
                'total_facts' => $package->facts->count(),
            ],
        ]);
    }

    /**
     * Get sample facts for a specific package.
     *
     * @param string $id
     * @return JsonResponse
     */
    public function sampleFacts(string $id): JsonResponse
    {
        $package = Package::with('category')->find($id);

        if (!$package) {
            return $this->notFoundResponse('package', $id, 'Cannot retrieve sample facts for non-existent package.');
        }

        $sampleFacts = $package->sampleFacts; // Uses the accessor we created

        return response()->json([
            'success' => true,
            'data' => [
                'package' => $package->only(['id', 'name', 'description', 'icon_url', 'access_level']),
                'category' => $package->category,
                'sample_facts' => $sampleFacts,
            ],
        ]);
    }
}
