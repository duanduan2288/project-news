<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/8/1 0001
 * Time: 下午 10:13
 */

namespace App\Services;


use EasyWeChat\Core\Http;

class ServiceHttp
{
	protected $http = null;

	public function __construct()
	{
		if (is_null($this->http)) {
			$this->http = new Http();
		}
	}

	public  function post($url,$data){
		$this->http->post($url,$data);
	}
}