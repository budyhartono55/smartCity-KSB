<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\GenUid;

class Gallery extends Model
{
    use HasFactory, GenUid;

    protected $guarded = [];

    //R E L A T I O N ==============
    public function category()
    {
        return $this->belongsTo(category::class);
    }
}
