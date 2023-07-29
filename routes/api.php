<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\ServiceController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\PilarController;
use App\Http\Controllers\Api\NewsController;
use App\Http\Controllers\Api\GalleryController;
use App\Http\Controllers\Api\AgendaController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\admin\DashboardController;
use App\Http\Controllers\Api\RegulasiController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\InfrastrukturController;
use App\Http\Controllers\Api\SliderController;
use App\Http\Controllers\Api\ApplicationController;
use App\Http\Controllers\Api\StatisticController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::group(
    ['middleware' => ['XssSanitizer']],
    function () {
        // register and login to get token
        Route::post('register', [AuthController::class, "register"]);
        Route::post('login', [AuthController::class, "login"]);

        Route::post('forgotPassword', [AuthController::class, "forgot_password"]);
        Route::post('resetPassword', [AuthController::class, "reset_password"]);
    }
);

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });
Route::middleware(['auth:sanctum', 'XssSanitizer:true', 'LogApiResponse'])->group(function () {
    Route::get('cekLogin', [AuthController::class, "cekLogin"]);

    Route::post("/logout", [AuthController::class, "logout"])->name("logout");
    Route::post("/changePassword", [AuthController::class, "change_password"])->name("change_password");
    // =======================================================================================================
    // U S E R
    Route::group(
        ['middleware' => ['isAdmin']],
        function () {
            // GET
            Route::get("/user", [UserController::class, "getAll"])->name("getAllUser");
            Route::get("/user/trash", [UserController::class, "getAllTrash"])->name("getAllTrashUser");
            Route::get("/user/{id}", [UserController::class, "getById"])->name("getByIdUser");
            Route::get("/user/restore", [UserController::class, "restore"])->name("getrestoreUser");
            Route::get("/user/restore/{id}", [UserController::class, "restoreById"])->name("getrestoreByIdUser");

            // POST
            Route::post("/user", [UserController::class, "save"])->name("createUser");

            // DELETE SEMENTARA
            Route::delete("/user/{id}", [UserController::class, "deleteSementara"])->name("deleteUserSementara");
            // DELETE PERMANENT
            Route::delete("/user/permanent/{id}", [UserController::class, "deletePermanent"])->name("deleteUserPermanent");
            // RESET
            Route::patch("/user/reset/{id}", [UserController::class, "resetPassword"])->name("resetPassword");
        }
    );
    // U S E R
    // Update User
    Route::patch("/user/{id}", [UserController::class, "update"])->name("editUser");
    //change Password
    Route::patch("/user/password/{id}", [UserController::class, "changePassword"])->name("changePassword");


    // S E R V I C E 
    // GET
    Route::get("/services", [ServiceController::class, "index"])->name("service");
    Route::get("/service/{id}", [ServiceController::class, "findById"])->name("findOne");
    Route::get("/service/search/{keyword}", [ServiceController::class, "getAllByKeyword"])->name("findByKeyword");
    // // POST
    Route::post("/service", [ServiceController::class, "add"])->name("createService");
    // // PATCH
    Route::patch("/service/{id}", [ServiceController::class, "edit"])->name("editServices");
    // // DELETE
    Route::delete("/service/{id}", [ServiceController::class, "delete"])->name("deleteServices");

    // =======================================================================================================
    // S T A T I S T I C
    // GET
    Route::get("/statistics", [StatisticController::class, "index"])->name("statistic");
    Route::get("/statistic/{id}", [StatisticController::class, "findById"])->name("findOne");
    Route::get("/statistic/search/{keyword}", [StatisticController::class, "getAllByKeyword"])->name("findByKeyword");
    // POST
    Route::post("/statistic", [StatisticController::class, "add"])->name("createStatistic");
    // PATCH
    Route::patch("/statistic/{id}", [StatisticController::class, "edit"])->name("editStatistics");
    // DELETE
    Route::delete("/statistic/{id}", [StatisticController::class, "delete"])->name("deleteStatistics");


    // =======================================================================================================
    // C A T E G O R Y 
    // //GET
    Route::get("/categories", [CategoryController::class, "index"])->name("category");
    Route::get("/category/{id}", [CategoryController::class, "findById"])->name("findOne");
    // POST
    Route::post("/category", [CategoryController::class, "add"])->name("createCategory");
    // PATCH
    Route::patch("/category/{id}", [CategoryController::class, "edit"])->name("editCategory");
    // DELETE
    Route::delete("/category/{id}", [CategoryController::class, "delete"])->name("deleteCategory");

    // =======================================================================================================
    // G A L L E R Y
    Route::get("/galleries", [GalleryController::class, "index"])->name("gallery");
    Route::get("/gallery/{id}", [GalleryController::class, "findById"])->name("findOne");
    // POST
    Route::post("/gallery", [GalleryController::class, "add"])->name("GalleryCategory");
    Route::patch("/gallery/{id}", [GalleryController::class, "edit"])->name("editGallery");
    // DELETE
    Route::delete("/gallery/{id}", [GalleryController::class, "delete"])->name("deleteGallery");

    // =======================================================================================================
    // S E T T I N G 
    Route::get("/setting", [SettingController::class, "getAll"])->name("setting");
    Route::get("/setting/{id}", [SettingController::class, "getById"])->name("findOne");
    // POST
    Route::post("/setting", [SettingController::class, "save"])->name("createSetting");
    // PATCH
    Route::patch("/setting/{id}", [SettingController::class, "update"])->name("editSetting");
    // DELETE
    Route::delete("/setting/{id}", [SettingController::class, "delete"])->name("deleteSetting");

    // =======================================================================================================
    // P I L A R 
    // GET
    Route::get("/pilars", [PilarController::class, "index"])->name("pilar");
    Route::get("/pilarsIncludeApp", [PilarController::class, "includeApplications"])->name("pilarsIncludeApp");
    Route::get("/pilar/{id}", [PilarController::class, "findById"])->name("findOne");
    // POST
    Route::post("/pilar", [PilarController::class, "add"])->name("PilarCategory");
    Route::patch("/pilar/{id}", [PilarController::class, "edit"])->name("editPilar");
    // DELETE
    Route::delete("/pilar/{id}", [PilarController::class, "delete"])->name("deletePilar");

    // =======================================================================================================
    // A P P L I C A T I O N 
    // GET
    Route::get("/applications", [ApplicationController::class, "index"])->name("application");
    Route::get("/application/{id}", [ApplicationController::class, "findById"])->name("findOne");
    // POST
    Route::post("/application", [ApplicationController::class, "add"])->name("ApplicationCategory");
    Route::patch("/application/{id}", [ApplicationController::class, "edit"])->name("editApplication");
    // DELETE
    Route::delete("/application/{id}", [ApplicationController::class, "delete"])->name("deleteApplication");

    // =======================================================================================================
    // C O M M E N T
    // GET
    Route::get('/comment', [CommentController::class, "getAll"])->name("comment");
    Route::get("/comment/{id}", [CommentController::class, "getById"])->name("findOne");
    // POST
    Route::post("/comment", [CommentController::class, "save"])->name("createComment");
    // PATCH
    Route::patch("/comment/{id}", [CommentController::class, "update"])->name("editComment");
    // DELETE
    Route::delete("/comment/{id}", [CommentController::class, "delete"])->name("deleteComment");

    // =======================================================================================================
    // A G E N D A
    Route::get("/agendas", [AgendaController::class, "index"])->name("agendas");
    Route::get("/agenda/{id}", [AgendaController::class, "findById"])->name("findOne");
    Route::get("/agenda/category/{id}", [AgendaController::class, "getByCategoryId"])->name("findByCategoryId");
    Route::get("/agenda/search/{keyword}", [AgendaController::class, "getAllByKeyword"])->name("findByKeyword");
    Route::get("/agenda/read/{slug}", [AgendaController::class, "findBySlug"])->name("findSlug");
    // POST
    Route::post("/agenda", [AgendaController::class, "add"])->name("AgendaCategory");
    Route::patch("/agenda/{id}", [AgendaController::class, "edit"])->name("editAgenda");
    // DELETE
    Route::delete("/agenda/{id}", [AgendaController::class, "delete"])->name("deleteAgenda");

    // =======================================================================================================
    // N E W S
    // GET
    Route::get('/news', [NewsController::class, "getAll"])->name("news");
    Route::get("/news/{id}", [NewsController::class, "getById"])->name("findOne");
    Route::get("/news/category/{id}", [NewsController::class, "getByCategory"])->name("findByCategory");
    Route::get("/news/filter/{filter?}", [NewsController::class, "getAllBy"])->name("getAllBy");
    Route::get("/news/search/{keyword?}", [NewsController::class, "search"])->name("search");
    Route::get("/news/read/{slug?}", [NewsController::class, "read"])->name("read");
    // POST
    Route::post("/news", [NewsController::class, "save"])->name("createNews");
    // PATCH
    Route::patch("/news/{id}", [NewsController::class, "update"])->name("editNews");
    // DELETE
    Route::delete("/news/{id}", [NewsController::class, "delete"])->name("deleteNews");

    // C O N T A C T
    // GET
    Route::get('/contact', [ContactController::class, "getAll"])->name("contact");
    Route::get("/contact/{id}", [ContactController::class, "getById"])->name("findOne");
    // POST
    Route::post("/contact", [ContactController::class, "save"])->name("createContact");
    // PATCH
    Route::patch("/contact/{id}", [ContactController::class, "update"])->name("editContact");
    // DELETE
    Route::delete("/contact/{id}", [ContactController::class, "delete"])->name("deleteContact");

    // R E G U L A S I
    // GET
    Route::get('/regulasi', [RegulasiController::class, "getAll"])->name("regulasi");
    Route::get("/regulasi/{id}", [RegulasiController::class, "getById"])->name("findOneRegulasi");
    // POST
    Route::post("/regulasi", [RegulasiController::class, "save"])->name("createRegulasi");
    // PATCH
    Route::patch("/regulasi/{id}", [RegulasiController::class, "update"])->name("editRegulasi");
    // DELETE
    Route::delete("/regulasi/{id}", [RegulasiController::class, "delete"])->name("deleteRegulasi");

    // P R O F I L E
    // GET
    Route::get('/profile', [ProfileController::class, "getAll"])->name("profile");
    Route::get("/profile/{id}", [ProfileController::class, "getById"])->name("findOneProfile");
    // POST
    Route::post("/profile", [ProfileController::class, "save"])->name("createProfile");
    // PATCH
    Route::patch("/profile/{id}", [ProfileController::class, "update"])->name("editProfile");
    // DELETE
    Route::delete("/profile/{id}", [ProfileController::class, "delete"])->name("deleteProfile");

    // I N F R A S T R U K T U R
    // GET
    Route::get('/infrastruktur', [InfrastrukturController::class, "getAll"])->name("infrastruktur");
    Route::get("/infrastruktur/{id}", [InfrastrukturController::class, "getById"])->name("findOneInfrastruktur");
    // POST
    Route::post("/infrastruktur", [InfrastrukturController::class, "save"])->name("createInfrastruktur");
    // PATCH
    Route::patch("/infrastruktur/{id}", [InfrastrukturController::class, "update"])->name("editInfrastruktur");
    // DELETE
    Route::delete("/infrastruktur/{id}", [InfrastrukturController::class, "delete"])->name("deleteInfrastruktur");

    // S L I D E R
    // GET
    Route::get('/slider', [SliderController::class, "getAll"])->name("slider");
    Route::get("/slider/{id}", [SliderController::class, "getById"])->name("findOneSlider");
    // POST
    Route::post("/slider", [SliderController::class, "save"])->name("createSlider");
    // PATCH
    Route::patch("/slider/{id}", [SliderController::class, "update"])->name("editSlider");
    // DELETE
    Route::delete("/slider/{id}", [SliderController::class, "delete"])->name("deleteSlider");

    // ADMIN
    Route::get("/admin/datasCount", [DashboardController::class, "index"])->name("dashboard");
});

// PUBLIC
Route::group(['middleware' => ['LogApiResponse', 'XssSanitizer']], function () {
    // =======================================================================================================
    // S E R V I C E 
    // GET
    Route::get("/public/services", [ServiceController::class, "index"])->name("service");
    Route::get("/public/service/{id}", [ServiceController::class, "findById"])->name("findOne");
    Route::get("/public/service/search/{keyword}", [ServiceController::class, "getAllByKeyword"])->name("findByKeyword");

    // =======================================================================================================
    // C A T E G O R Y 
    //GET
    Route::get("/public/categories", [CategoryController::class, "index"])->name("category");
    Route::get("/public/category/{id}", [CategoryController::class, "findById"])->name("findOne");

    // =======================================================================================================
    // P I L A R 
    // GET
    Route::get("/public/pilars", [PilarController::class, "index"])->name("pilar");
    Route::get("/public/pilar/{id}", [PilarController::class, "findById"])->name("findOne");
    Route::get("/public/pilarsIncludeApp", [PilarController::class, "includeApplications"])->name("pilarsIncludeApp");

    // =======================================================================================================
    // A P P L I C A T I O N
    // GET
    Route::get("/public/applications", [ApplicationController::class, "index"])->name("pilar");
    Route::get("/public/application/{id}", [ApplicationController::class, "findById"])->name("findOne");

    // =======================================================================================================
    // A G E N D A
    Route::get("/public/agendas", [AgendaController::class, "index"])->name("agendas");
    Route::get("/public/agenda/{id}", [AgendaController::class, "findById"])->name("findOne");
    Route::get("/public/agenda/category/{id}", [AgendaController::class, "getByCategoryId"])->name("findByCategoryId");
    Route::get("/public/agenda/search/{keyword}", [AgendaController::class, "getAllByKeyword"])->name("findByKeyword");
    Route::get("/public/agenda/read/{slug}", [AgendaController::class, "findBySlug"])->name("findSlug");

    // =======================================================================================================
    // N E W S
    // GET
    Route::get('/public/news', [NewsController::class, "getAll"])->name("news");
    Route::get("/public/news/{id}", [NewsController::class, "getById"])->name("findOne");
    Route::get("/public/news/category/{id}", [NewsController::class, "getByCategory"])->name("findByCategory");
    Route::get("/public/news/filter/{filter?}", [NewsController::class, "getAllBy"])->name("getAllBy");
    Route::get("/public/news/search/{keyword?}", [NewsController::class, "search"])->name("search");
    Route::get("/public/news/read/{slug?}", [NewsController::class, "read"])->name("read");

    // =======================================================================================================
    // C O M M E N T
    // GET
    Route::get('/public/comment', [CommentController::class, "getAll"])->name("comment");
    Route::get("/public/comment/{id}", [CommentController::class, "getById"])->name("findOne");

    // =======================================================================================================
    // S E T T I N G 
    Route::get("/public/setting", [SettingController::class, "getAll"])->name("setting");
    Route::get("/public/setting/{id}", [SettingController::class, "getById"])->name("findOne");

    // =======================================================================================================
    // G A L L E R Y
    Route::get("/public/galleries", [GalleryController::class, "index"])->name("gallery");
    Route::get("/public/gallery/{id}", [GalleryController::class, "findById"])->name("findOne");

    // C O N T A C T
    // GET
    Route::get('/public/contact', [ContactController::class, "getAll"])->name("contact");
    Route::get("/public/contact/{id}", [ContactController::class, "getById"])->name("findOne");

    // R E G U L A S I 
    // GET
    Route::get('/public/regulasi', [RegulasiController::class, "getAll"])->name("regulasi");
    Route::get("/public/regulasi/{id}", [RegulasiController::class, "getById"])->name("findOne");

    // I N F R A S T R U K T U R
    // GET
    Route::get('/public/infrastruktur', [InfrastrukturController::class, "getAll"])->name("infrastruktur");
    Route::get("/public/infrastruktur/{id}", [InfrastrukturController::class, "getById"])->name("findOne");

    // P R O F I L E
    // GET
    Route::get('/public/profile', [ProfileController::class, "getAll"])->name("profile");
    Route::get("/public/profile/{id}", [ProfileController::class, "getById"])->name("findOne");

    // S L I D E R
    // GET
    Route::get('/public/slider', [SliderController::class, "getAll"])->name("slider");
    Route::get("/public/slider/{id}", [SliderController::class, "getById"])->name("findOne");
});
