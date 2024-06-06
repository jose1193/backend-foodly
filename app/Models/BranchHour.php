<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BranchHour extends Model
{
    use HasFactory;

    use HasFactory;
    protected $fillable = ['branch_id', 'day', 'open', 'close'];

    public function branch()
    {

    return $this->belongsTo(BusinessBranch::class);
 

    }
}
