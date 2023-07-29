<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\GenUid;

class Statistic extends Model
{
    use HasFactory, GenUid;

    protected $guarded = [];
    protected $table = 'statistics';
    protected $dates = ['created_at'];
}
