<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MedicalDoc extends Model
{
    /** @use HasFactory<\Database\Factories\MedicalDocFactory> */
    use HasFactory;

    protected $table = 'medical_docs';
    protected $primaryKey = 'doc_id';

    protected $fillable = [
        'doc_num_pps',
        'doc_end_validity',
        'doc_date_added',
    ];

    protected $casts = [
        'doc_end_validity' => 'date',
        'doc_date_added' => 'date',
    ];
}
