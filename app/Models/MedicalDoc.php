<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="MedicalDoc",
 *     title="Medical Document",
 *     description="Medical document or PPS for a user",
 *     @OA\Property(property="doc_id", type="integer", readOnly=true, example=1),
 *     @OA\Property(property="doc_num_pps", type="string", example="PPS-123456"),
 *     @OA\Property(property="doc_end_validity", type="string", format="date", example="2026-12-31"),
 *     @OA\Property(property="doc_date_added", type="string", format="date", example="2025-01-01"),
 *     @OA\Property(property="created_at", type="string", format="date-time", readOnly=true),
 *     @OA\Property(property="updated_at", type="string", format="date-time", readOnly=true)
 * )
 */
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
