<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RefundRecord extends Model
{
     public $timestamps = false;
     protected $guarded = ['id'];
    protected $table = 'refund_record';
     protected $fillable = [
                             "out_trade_no",
                             "out_refund_no",
                             "amount",
                             "refund_amount",
                             "refund_status",
                             "nickname",
                             "openid",
                             "vote_id",
                             "created"
                         ];
}
