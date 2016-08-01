<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/8/1 0001
 * Time: 下午 10:29
 */

namespace App\Services;

use EasyWeChat\Core\Exceptions\HttpException;
use EasyWeChat\Support\Collection;
use EasyWeChat\Core\Http;

abstract class AbstractLbs
{
	/**
	 * Http instance.
	 *
	 * @var \EasyWeChat\Core\Http
	 */
	protected $http;

	/**
	 * The request token.
	 *
	 * @var \EasyWeChat\Core\AccessToken
	 */

	const GET = 'get';
	const POST = 'post';
	const JSON = 'json';


	/**
	 * Return the http instance.
	 *
	 * @return \EasyWeChat\Core\Http
	 */
	public function getHttp()
	{
		if (is_null($this->http)) {
			$this->http = new Http();
		}

		return $this->http;
	}

	/**
	 * Set the http instance.
	 *
	 * @param \EasyWeChat\Core\Http $http
	 *
	 * @return $this
	 */
	public function setHttp(Http $http)
	{
		$this->http = $http;

		return $this;
	}


	/**
	 * Parse JSON from response and check error.
	 *
	 * @param string $method
	 * @param array  $args
	 *
	 * @return \EasyWeChat\Support\Collection
	 */
	public function parseJSON($method, array $args)
	{
		$http = $this->getHttp();

		$contents = $http->parseJSON(call_user_func_array([$http, $method], $args));

		$this->checkAndThrow($contents);

		return new Collection($contents);
	}

	/**
	 * Check the array data errors, and Throw exception when the contents contains error.
	 *
	 * @param array $contents
	 *
	 * @throws \EasyWeChat\Core\Exceptions\HttpException
	 */
	protected function checkAndThrow(array $contents)
	{
		if (isset($contents['errcode']) && 0 !== $contents['errcode']) {
			if (empty($contents['errmsg'])) {
				$contents['errmsg'] = 'Unknown';
			}

			throw new HttpException($contents['errmsg'], $contents['errcode']);
		}
	}
}