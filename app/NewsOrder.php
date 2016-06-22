<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class NewsOrder extends Model
{
    protected $guarded = ['id'];

    protected $table = 'orders';
}
