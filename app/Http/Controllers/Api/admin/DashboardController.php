<?php

namespace App\Http\Controllers\Api\admin;

use App\Http\Controllers\Controller;
use App\Repositories\admin\Dashboard\DashboardInterface as DashboardInterface;

class DashboardController extends Controller
{

    private $dashboardRepository;

    public function __construct(DashboardInterface $dashboardRepository)
    {
        $this->dashboardRepository = $dashboardRepository;
    }

    //M E T H O D E ======================
    // core
    public function index()
    {
        return $this->dashboardRepository->getAllEachTotalData();
    }
}