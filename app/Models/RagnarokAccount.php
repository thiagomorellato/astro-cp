<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RagnarokAccount extends Model
{
    protected $connection = 'mysql_ragnarok';
    protected $table = 'login';
    protected $primaryKey = 'account_id';
    public $timestamps = false;

    protected $fillable = [
        'userid',
        'user_pass',
        'sex',
    ];
}
