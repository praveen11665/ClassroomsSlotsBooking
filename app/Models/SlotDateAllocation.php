<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SlotDateAllocation extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'slot_date_allocation';

    protected $fillable = [
        'classroom_id',
        'date',
        'updated_at',
        'deleted_at',
    ];

    /**
     * This function used for get relationship data for classroom.
     *
     * @return object
     * @author Karthick
     * @date 01/21/2023
     */
    public function classRoom()
    {
        return $this->belongsTo(ClassRoom::class);
    }


    /**
     * This function used for get relationship data for time allocation
     *
     * @return object
     * @author Karthick
     * @date 01/21/2023
     */
    public function timeAllocation()
    {
        return $this->hasMany(SlotTimeAllocation::class);
    }
}
