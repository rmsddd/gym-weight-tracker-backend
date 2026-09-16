<?php

namespace App\Services;

use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;

class CategoryService
{
    public function getAll(): Collection
    {
        return Category::all();
    }

    public function findById(int $id): Category
    {
        return Category::findOrFail($id);
    }

    public function create(array $data): Category
    {
        return Category::create($data);
    }

    public function update(Category $category, array $data): Category
    {
        $category->update($data);
        return $category->fresh();
    }

    public function delete(Category $category): void
    {
        $category->delete();
    }
}
