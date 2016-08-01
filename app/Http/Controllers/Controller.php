<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesResources;
use Log;
use Request;

use EasyWeChat\Foundation\Application;
use App\AdminUser;

class Controller extends BaseController
{
    use AuthorizesRequests, AuthorizesResources, DispatchesJobs, ValidatesRequests;

    public $target_url = '';

    public function __construct(Request $request)
    {
        $this->requestData = $request::all();

        session_start();

        //$this->_is_login();
    }

    public function return_base_app()
    {
        $options = [
            'debug' => true,
            'app_id' => 'wxb56787ede27d5697',
            'secret' => '8c2c56b3155e8a6729c5338aaace03c4',
            'token' => 'chuanchuanchuan1q2w',
            'aes_key' => 'eHowUPpSuU7pjDHpmumt9ntnx1iTqm2jFB2vu2qsP74',

            'log' => [
                'level' => 'debug',
                'file' => '/tmp/easywechat.log'
            ],
            'oauth' => [
                'scopes' => [
                    'snsapi_base'
                ],
                'callback' => 'http://' . $_SERVER['HTTP_HOST'] . '/news/baseoauth?no='.rand(100000,999999)
          ],

            'payment' => [
                'merchant_id' => '1318327901',
                'key'         => 'DcSpPJIFVpAlx48Z9QSp33Y4xMWO4VLW',
                'cert_path'   => '/cert/apiclient_cert.pem', // XXX: 绝对路径！！！！
                'key_path'    => '/cert/apiclient_key.pem',      // XXX: 绝对路径！！！！
                // 'notify_url'         => '默认的订单回调地址',       // 你也可以在下单时单独设置来想覆盖它
            ]
        ];

        return new Application($options);
    }

    public function return_app()
    {
        $options = [
            'debug' => true,
            'app_id' => 'wxb56787ede27d5697',
            'secret' => '8c2c56b3155e8a6729c5338aaace03c4',
            'token' => 'chuanchuanchuan1q2w',
            'aes_key' => 'eHowUPpSuU7pjDHpmumt9ntnx1iTqm2jFB2vu2qsP74',

            'log' => [
                'level' => 'debug',
                'file' => '/tmp/easywechat.log'
            ],
            'oauth' => [
                'scopes' => [
                    "snsapi_userinfo"
                ],
                'callback' => 'http://' . $_SERVER['HTTP_HOST'] . '/news/oauth?no='.rand(100000,999999)
            ],

            'payment' => [
                'merchant_id' => '1318327901',
                'key'         => 'DcSpPJIFVpAlx48Z9QSp33Y4xMWO4VLW',
                'cert_path'   => '/cert/apiclient_cert.pem', // XXX: 绝对路径！！！！
                'key_path'    => '/cert/apiclient_key.pem',      // XXX: 绝对路径！！！！
                // 'notify_url'         => '默认的订单回调地址',       // 你也可以在下单时单独设置来想覆盖它
            ]
        ];

        return new Application($options);
    }

    public function output($data)
    {
        if ($data) {
            return response()->json([
                'code' => 200,
                'msg' => '操作成功',
                'data' => $data
            ]);
        }

        return response()->json([
            'code' => 100,
            'msg' => '操作失败!'
        ]);
    }

    public function success($data)
    {
        return response()->json([
            'code' => 200,
            'msg' => '操作成功',
            'data' => $data
         ]);
    }

    public  function get_userinfo($target_url = "",$type="")
    {
        $this->target_url = $target_url;
        if($type=="info"){
            Log::error($type);
            return $this->do_oauth();
        }else{
            return $this->do_base_oauth();
        }
    }

    public function do_oauth()
    {
        $app = $this->return_app();
        
        $oauth = $app->oauth;
        // 未登录
        if (!$_SESSION['wechat_user']["nickname"]) {
            $_SESSION['target_url'] = $this->target_url;

            $oauth->redirect()->send();
        }else{
            // 已经登录过
            return $_SESSION['wechat_user'];
        }
    }

    public function do_base_oauth()
    {
        $app = $this->return_base_app();

        $oauth = $app->oauth;

        // 未登录
        if (!isset($_SESSION['wechat_user'])) {

            $_SESSION['target_url'] = $this->target_url;

            $oauth->redirect()->send();
        }else{
            // 已经登录过
            return $_SESSION['wechat_user'];
        }
    }
    public function return_js_sdk()
    {
        $app = $this->return_app();

        $js = $app->js;

        $config = [
            'chooseImage',
            'previewImage',
            'uploadImage',
            'startRecord',
            'stopRecord',
            'uploadVoice',
            'playVoice',
            'onMenuShareTimeline',
            'onMenuShareAppMessage'
        ];

        return compact('js', 'config');
    }

    public function _is_login()
    {
        if(!$_SESSION['wechat_user']['id'])
        {
           // $this->get_userinfo($_SERVER['HTTP_HOST']);
        }
    }

    public function _is_admin()
    {
       return AdminUser::where(['openid' => $_SESSION['wechat_user']['id']])->first() ? true : false;
    }
}
