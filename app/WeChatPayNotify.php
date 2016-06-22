<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class WeChatPayNotify extends Model
{
    protected $table = 'wechat_pay_notify';

    protected $guarded = ['id'];
}
