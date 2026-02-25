<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Example Controller
 * 
 * This controller demonstrates how to use the ApiResponse trait
 * and implement consistent API responses.
 */
class ExampleController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $data = [
            ['id' => 1, 'name' => 'Example 1'],
            ['id' => 2, 'name' => 'Example 2'],
        ];

        return $this->successResponse($data, 'Data retrieved successfully');
    }

    /**
     * Store a newly created resource.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        // Simulate resource creation
        $data = [
            'id' => 1,
            'name' => $request->input('name'),
            'created_at' => now(),
        ];

        return $this->successResponse($data, 'Resource created successfully', 201);
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        // Simulate finding a resource
        $data = ['id' => $id, 'name' => 'Example ' . $id];

        return $this->successResponse($data, 'Resource retrieved successfully');
    }

    /**
     * Update the specified resource.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        // Simulate resource update
        $data = [
            'id' => $id,
            'name' => $request->input('name'),
            'updated_at' => now(),
        ];

        return $this->successResponse($data, 'Resource updated successfully');
    }

    /**
     * Remove the specified resource.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        // Simulate resource deletion
        return $this->successResponse(null, 'Resource deleted successfully');
    }

    /**
     * Example of error response.
     *
     * @return JsonResponse
     */
    public function error(): JsonResponse
    {
        return $this->errorResponse('Something went wrong', 500);
    }

    /**
     * Example of paginated response.
     *
     * @return JsonResponse
     */
    public function paginated(): JsonResponse
    {
        // This would normally use Laravel's paginator
        // $users = User::paginate(15);
        // return $this->paginatedResponse($users, 'Users retrieved');
        
        return $this->successResponse([], 'Check the controller for pagination example');
    }
}
