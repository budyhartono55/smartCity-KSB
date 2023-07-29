<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Repositories\Category\CategoryInterface as CategoryInterface;


class CategoryController extends Controller
{

    private $categoryRepository;

    public function __construct(CategoryInterface $categoryRepository)
    {
        $this->categoryRepository = $categoryRepository;
    }

    //M E T H O D E ======================
    // core
    public function index()
    {
        return $this->categoryRepository->getAllCategories();
    }

    //findOne
    public function findById($id)
    {
        return $this->categoryRepository->findById($id);
    }

    // create
    public function add(Request $request)
    {
        return $this->categoryRepository->createCategory($request);
    }

    // update
    public function edit(Request $request, $id)
    {
        return $this->categoryRepository->updateCategory($request, $id);
    }

    // delete
    public function delete($id)
    {
        return $this->categoryRepository->deleteCategory($id);
    }
}