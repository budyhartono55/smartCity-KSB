<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\GenUid;

class Setting extends Model
{
    use HasFactory, GenUid;

    protected $guarded = [];
    protected $table = 'setting';
    protected $hidden = [
        'created_at',
        'updated_at'
    ];

    // relasi ke table user (one to one)
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
