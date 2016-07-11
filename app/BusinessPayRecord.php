<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class BusinessPayRecord extends Model
{
     public $timestamps = false;
     protected $guarded = ['id'];
     protected $table = 'business_pay_record';
     protected $fillable = [
                             "partner_trade_no",
                             "openid",
                             "check_name",
                             "amount",
                             "content",
                             "spbill_create_ip",
                             "pay_status",
                             "vote_id",
                             "created"
                         ];
}
