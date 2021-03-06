<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Service
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party Service such
    | as Stripe, Mailgun, Mandrill, and others. This file provides a sane
    | default location for this type of information, allowing packages
    | to have a conventional place to find your various credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
    ],

    'ses' => [
        'key' => env('SES_KEY'),
        'secret' => env('SES_SECRET'),
        'region' => 'us-east-1',
    ],

    'sparkpost' => [
        'secret' => env('SPARKPOST_SECRET'),
    ],

    'stripe' => [
        'model' => App\User::class,
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
    ],
    'aliyun'=>[
        'Bucket'=>'chbu2',
        'Oss'=>'http://oss-cn-hangzhou.aliyuncs.com/',
        'OutOss'=>'http://chbu2.oss-cn-hangzhou.aliyuncs.com/',
        'OssInternal' => 'http://oss-cn-hangzhou-internal.aliyuncs.com',
        'ImgOss'=>'http://chbu2.img-cn-hangzhou.aliyuncs.com/',
        'AccessKeyId'=>'niXWRQ0UBTvIHOmt',
        'AccessKeySecret'=>'AikuUvefNt2UVEtcEP1nE9p3Oxttmt',
        'PipelineId'=>'059ee436260b42d480d08e1aabb7f776',
        'TemplateId'=>'bf7338b18203e43027f97d361ed500e2',
        'Voice_TemplateId'=>'89d2fc317ff261d488ed3d6db671abcc',
        'Location'=>'oss-cn-hangzhou',
        'SubmitTypes'=>['mp4','mov','mp3','m4a','qt','amr'],
        'SubmitVedioTypes'=>['mp4','mov','qt','flv',],
        'SubmitTxtTypes'=>['txt','xls','xlsx','pdf','doc','docx'],
        'SubmitAudioTypes'=>['mp3','mov','amr','mpga'],
        'SubmitImgTypes'=>['mdi','mmr','npx','pbm','pct','pcx','pgm','pic','png','pnm','ppm','psd','ras','rgb','rlc','sgi','sid','svg','svgz','tga','tif','tiff','jpe','jpeg','jpg','ktx','uvg','uvi','uvvg','uvvi','wbmp','wdp','webp','xbm','xif','xpm','xwd']
    ],
    'confirm_status'=>[
        "0"=>"未鉴定",
        "1"=>"鉴定中",
        "2"=>"很赞",
        "3"=>"有效",
        "4"=>"不确定",
        "5"=>"部分不太确定",
        "6"=>"扯淡"
    ],
    "vote_status"=>[
        "1"=>"完全扯淡",
        "2"=>"凑合",
        "3"=>"要的就是它"
    ]
];
