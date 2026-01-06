<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Member extends Model
{
    /** @use HasFactory<\Database\Factories\MemberFactory> */
    use HasFactory;

    protected $table = 'members';
    protected $primaryKey = 'adh_id';

    protected $fillable = [
        'adh_license',
        'adh_end_validity',
        'adh_date_added',
    ];

    protected $casts = [
        'adh_end_validity' => 'date',
        'adh_date_added' => 'date',
    ];
}
