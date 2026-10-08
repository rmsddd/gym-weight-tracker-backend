<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Services\CategoryService;

class CategoryController extends Controller
{
    public function __construct(protected CategoryService $service) {}

    public function index()
    {
        $this->authorize('viewAny', Category::class);
        $categories = $this->service->getAll();
        return CategoryResource::collection($categories);
    }

    public function store(StoreCategoryRequest $request)
    {
        $this->authorize('create', Category::class);
        $category = $this->service->create($request->validated());
        return new CategoryResource($category);
    }

    public function show(Category $category)
    {
        $this->authorize('view', $category);
        return new CategoryResource($category);
    }

    public function update(UpdateCategoryRequest $request, Category $category)
    {
        $this->authorize('update', $category);
        $updated = $this->service->update($category, $request->validated());
        return new CategoryResource($updated);
    }

    public function destroy(Category $category)
    {
        $this->authorize('delete', $category);
        $this->service->delete($category);
        return response()->json(null, 204);
    }
}
