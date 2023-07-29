<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\GenUid;

class Category extends Model
{
    use HasFactory, GenUid;

    protected $guarded = [];

    //R E L A T I O N ==============
    public function news()
    {
        return $this->belongsToMany(News::class);
    }
    public function agendas()
    {
        return $this->belongsToMany(News::class);
    }

    public function gallery()
    {
        return $this->belongsToMany(Gallery::class);
    }
}
