<?php

use App\Http\Controllers\Setting\SettingController;
use App\Http\Controllers\Category\CategoryController;
use App\Http\Controllers\Service\ServiceController;
use App\Http\Controllers\News\NewsController;

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

// S E R V I C E =================
// GET
Route::get("/services", [ServiceController::class, "index"])->name("service");
Route::get("/service/{id}", [ServiceController::class, "findById"])->name("findOne");
// POST
Route::post("/service/add", [ServiceController::class, "add"])->name("createService");
// PATCH
Route::patch("/service/edit/{id}", [ServiceController::class, "edit"])->name("editServices");
// DELETE
Route::delete("/service/delete/{id}", [ServiceController::class, "delete"])->name("deleteServices");

// C A T E G O R Y =================
//GET
Route::get("/categories", [CategoryController::class, "index"])->name("category");
Route::get("/category/{id}", [CategoryController::class, "findById"])->name("findOne");
Route::post("/category/add", [CategoryController::class, "add"])->name("createCategory");
Route::patch("/category/edit/{id}", [CategoryController::class, "edit"])->name("editCategory");
Route::delete("/category/delete/{id}", [CategoryController::class, "delete"])->name("deleteCategory");
// S E T T I N G =================
// GET
Route::get('/setting', [SettingController::class, "getAll"])->name("setting");
Route::get("/setting/{id}", [SettingController::class, "show"])->name("findOne");
// POST
Route::post("/setting", [SettingController::class, "store"])->name("createSetting");
// PATCH
Route::patch("/setting/{id}", [SettingController::class, "update"])->name("editSetting");
// DELETE
Route::delete("/setting/{id}", [SettingController::class, "destroy"])->name("deleteSetting");
