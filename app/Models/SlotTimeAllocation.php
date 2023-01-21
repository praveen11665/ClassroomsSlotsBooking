<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SlotTimeAllocation extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'slot_time_allocation';
    protected $appends = ['combine_id'];

    protected $fillable = [
        'slot_date_allocation_id',
        'start_hr',
        'end_hr',
        'people',
        'updated_at',
        'deleted_at',
    ];

    public function getCombineIdAttribute()
    {
        return $this->start_hr . '-' . $this->end_hr;
    }
}
