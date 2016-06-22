<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class OrderPayBack extends Model
{
   protected $guarded = ['id'];
   protected $table = 'order_pay_back';
}
