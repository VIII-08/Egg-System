<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChickenStockLog extends Model
{
    protected $fillable = [ 'user_id', 'adjustment_type', 'quantity', 'reason', 'notes', ];
}
