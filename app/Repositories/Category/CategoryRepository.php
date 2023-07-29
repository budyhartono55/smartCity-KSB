<?php
namespace App\Repositories\Category;

use App\Repositories\Category\CategoryInterface as CategoryInterface;
use App\Models\Category;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\CategoryResource;
use Exception;
use Illuminate\Http\Request;
use App\Traits\API_response;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\CategoryRequest;
use Illuminate\Support\Facades\Redis;
use App\Helpers\RedisHelper;


class CategoryRepository implements CategoryInterface{
    
    // Response API HANDLER
    use API_response;

    protected $category;

	public function __construct(Category $category)
	{
        $this->category = $category;  
    }

    // getAll
    public function getAllCategories()
    {
        try {

            $key = "AllCategories_" .request()->get('page', 1);
            if(Redis::exists($key)) {
                $result = json_decode(Redis::get($key));
                return $this->success("List Keseluruhan Kategori from (CACHE)", $result);
            };

            $category = Category::latest('created_at')->paginate(12);
            if($category){
                Redis::set($key, json_encode($category));
                Redis::expire($key, 60); //Cache for 60 seconds

                return $this->success("List keseluruhan Kategori", $category);
            };

            //=========================
            // NO-REDIS
            // $kategori = Category::paginate(3);
            // return $this->success(" List kesuluruhan kategori", $kategori);
        } catch(\Exception $e) {
            return $this->error("Internal Server Error", $e->getMessage());
        }
    }

    // findOne
    public function findById($id)
    {
        try {
            if (Redis::exists('category_' . $id)) {
                $result = json_decode(Redis::get('category_' . $id));
                return $this->success("Detail Kategori dengan ID = ($id) from (CACHE)", $result);
            }

            $category = Category::find($id);
            if ($category) {
                Redis::set('category_' . $id, json_encode($category));
                Redis::expire('category_' . $id, 60); // Cache for 1 minute
                return $this->success("Detail Kategori", $category);
            } else {
                return $this->error("Not Found", "Kategori dengan ID = ($id) tidak ditemukan!", 404);
            }
            
            //=====================
            //NO-REDIS
            // $kategori = Category::find($id); 
            // // Check the user
            // if(!$kategori) return $this->error("Kategori dengan ID = ($id) tidak ditemukan!", 404);

            // return $this->success("Detail kategori", $kategori);
        } catch(\Exception $e) {
            return $this->error("Internal Server Error", $e->getMessage());
        }
    }

    // create
    public function createCategory($request)
    {
        $validator = Validator::make($request->all(), [
            'category_title' => 'required',
         ],
         [
            'category_title.required' => 'Mohon isikan category_title',
         ]
        );

        //check if validation fails
        if ($validator->fails()) {
            return $this->error("Upps, Validation Failed!", $validator->errors(), 400);
        }
        
        try {
            //redisCheck
            $key = 'AllCategories'.request()->get("page");
            if (Redis::exists($key)) {
                Redis::del($key);
            }

            $data = Category::create($request->all());
            
            if ($data){
                RedisHelper::deleteKeysCategory();
                return $this->success("Kategori Berhasil ditambahkan!", $data);
            }
        } catch(\Exception $e) {
            return $this->error("Internal Server Error", $e->getMessage(), 500);
        }
    }

    // update
    public function updateCategory($request, $id)
    {
            $validator = Validator::make($request->all(), [
                'category_title' => 'required',
            ],
            [
                'category_title.required' => 'Mohon isikan category_title',
            ]
        );

        //check if validation fails
        if ($validator->fails()) {
            return $this->error("Upps, Validation Failed!", $validator->errors(), 400);
        }
        
        try {
            // search
            $kategori = Category::find($id);
            
            // check
            if (!$kategori) {
                return $this->error("Not Found", "Kategori dengan ID = ($id) tidak ditemukan!", 404);
            }else{
            // approved
            $kategori['category_title'] = $request->category_title;
             
            //save 
              $update = $kategori->save();
              if ($update) {
                RedisHelper::deleteKeysCategory();
                return $this->success("Kategori Berhasil diperharui!", $kategori);
            }
         }
        } catch(\Exception $e) {
            return $this->error("Internal Server Error", $e->getMessage());
        }
    }

    // delete
    public function deleteCategory($id)
    {
        try {
            // search
            $kategori = Category::find($id);
            if (!$kategori) {
                return $this->error("Not Found", "Kategori dengan ID = ($id) tidak ditemukan!", 404);
            }
            // approved
            $del = $kategori->delete();
            if ($del) {
            RedisHelper::deleteKeysCategory();
            return $this->success("Kategori dengan ID = ($id) Berhasil dihapus!", "COMPLETED");
            }
        }catch(\Exception $e){
            return $this->error("Internal Server Error", $e->getMessage());
        }

      
    }
}