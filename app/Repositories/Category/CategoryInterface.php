<?php

namespace App\Repositories\Category;

use Illuminate\Http\Request;

interface CategoryInterface {
    // getAll
    public function getAllCategories();
    // findOne
    public function findById($id);
    // insertData
    public function createCategory($request);
    // update
    public function updateCategory($request, $id);
    // delete
    public function deleteCategory($id);
}