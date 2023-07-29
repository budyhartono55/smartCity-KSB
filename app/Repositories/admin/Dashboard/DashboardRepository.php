<?php

namespace App\Repositories\admin\Dashboard;

use App\Repositories\admin\Dashboard\DashboardInterface as DashboardInterface;
use App\Models\News;
use App\Models\Service;
use App\Models\User;
use App\Models\Agenda;
use App\Models\Contact;
use App\Traits\API_response;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Auth;
use App\Helpers\Helper;
use App\Models\Category;

class DashboardRepository implements DashboardInterface
{

    // Response API HANDLER
    use API_response;

    // getAll
    public function getAllEachTotalData()
    {
        try {

            function countData()
            {

                $serviceCount = Service::count();
                $userCount = User::count();
                $agendaCount = Agenda::count();
                $contactCount = Contact::count();
                $categoryCount = Category::count();
                if (Helper::isAdmin()) {
                    $newsCount = News::count();
                } else {
                    $newsCount = News::join('users', 'users.id', '=', 'news.user_id')->where('news.user_id', auth()->user()->id)->count();
                }


                $list = [
                    'jumlah_berita' => $newsCount,
                    'jumlah_layanan' => $serviceCount,
                    'jumlah_agenda' => $agendaCount,
                    'jumlah_kategori' => $categoryCount,
                    'jumlah_user' => $userCount,
                    'jumlah_kontak' => $contactCount,
                    'jumlah_category' => $categoryCount,

                ];

                return $list;
            }



            if (Helper::isAdmin()) {
                $key = "admin_AllDatasCount_" . request()->get('page', 1);
            } else {
                $key = "admin_AllDatasCount_" . request()->get('page', 1) . "_" . auth()->user()->id;
            }
            if (Redis::exists($key)) {
                $result = json_decode(Redis::get($key));
                return $this->success("List Keseluruhan Jumlah Data from (CACHE)", $result);
            };
            $count = countData();
            $res = [
                'data' => $count
            ];
            // dd($count);

            if ($res) {
                Redis::set($key, json_encode($res));
                Redis::expire($key, 60);
                return $this->success("List kesuluruhan Jumlah Data", $res);
            }
        } catch (\Exception $e) {
            return $this->error("Internal Server Error!", $e->getMessage(), $e->getCode());
        }
    }
}
