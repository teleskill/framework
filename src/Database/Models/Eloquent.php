<?php

namespace Teleskill\Framework\Database\Models;

use Illuminate\Database\Eloquent\Model As EloquentModel;
use Teleskill\Framework\Database\DB;

class Eloquent extends EloquentModel
{
    public static function boot() {
        DB::boot();

        parent::boot();
    }
}