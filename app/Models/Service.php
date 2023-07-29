<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\GenUid;

class Service extends Model
{
    use HasFactory, GenUid;

    protected $guarded = [];
    protected $dates = ['created_at'];
}
