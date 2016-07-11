<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class WechatUser extends Model
{
     public $timestamps = false;
     protected $guarded = ['id'];
     protected $table = 'wechat_user';
     protected $fillable = [
                             "openid",
                             "nickname",
                             "name",
                             "avatar",
                             "email",
                             "sex",
                             "city",
                             "province",
                             "country",
                             "access_token",
                             "refresh_token",
                             "created"
                         ];
}
