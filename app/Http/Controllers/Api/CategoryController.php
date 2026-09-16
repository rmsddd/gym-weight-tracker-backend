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
        $categories = $this->service->getAll();
        return CategoryResource::collection($categories);
    }

    public function store(StoreCategoryRequest $request)
    {
        $category = $this->service->create($request->validated());
        return new CategoryResource($category);
    }

    public function show(Category $category)
    {
        return new CategoryResource($category);
    }

    public function update(UpdateCategoryRequest $request, Category $category)
    {
        $updated = $this->service->update($category, $request->validated());
        return new CategoryResource($updated);
    }

    public function destroy(Category $category)
    {
        $this->service->delete($category);
        return response()->json(null, 204);
    }
}
