<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/8/1 0001
 * Time: 下午 9:01
 */

namespace App\Services;


class ServiceLbs extends AbstractLbs
{
	const AK = 'Gs0gpTACzqpxHgowoL8GEz5qa9dkUG83';
	const GEOTABLE_ID = '147846';
	const API_CREATE_GEO = 'http://api.map.baidu.com/geodata/v3/geotable/create';
	const API_COLUMN_GEO = 'http://api.map.baidu.com/geodata/v3/column/create';
	const API_CREATE_POI = 'http://api.map.baidu.com/geodata/v3/poi/create';
	const API_POI_LIST   = 'http://api.map.baidu.com/geodata/v3/poi/list';//GET
	const API_POI_DEL   = 'http://api.map.baidu.com/geodata/v3/poi/list';//GET
	const API_POI_GET   = 'http://api.map.baidu.com/geodata/v3/poi/detail';//GET
	const API_POI_UPDATE   = 'http://api.map.baidu.com/geodata/v3/poi/update';//GET


	/**
	 * 创建table
	 * @param $name
	 * @param int $geotype
	 * @param int $is_published
	 * @return \EasyWeChat\Support\Collection
	 */
	public function createGeo($name,$geotype = 1,$is_published = 1){

		$param = [
			'name' => $name,
			'geotype' => $geotype,
			'is_published'=>$is_published,
			'ak' => self::AK
		];

		return $this->parseJSON('json', [self::API_CREATE_GEO, $param]);
	}

	/**
	 * 自定义字段
	 * @param $name
	 * @param $key
	 * @param $type
	 * @param int $is_index_field
	 * @param int $max_length
	 * @return \EasyWeChat\Support\Collection
	 */
	public function createColumn($name,$key,$type,$is_index_field = 0,$max_length=2048){

		$param = [
				'name' => $name,
				'key' => $key,
				'is_index_field'=>$is_index_field,
				'type'=>$type,
				'max_length'=>$max_length,
				'ak' => self::AK
		];

		return $this->parseJSON('json', [self::API_COLUMN_GEO, $param]);
	}

	/**
	 * Get POI by ID.
	 *
	 * @param int $poiId
	 *
	 * @return \EasyWeChat\Support\Collection
	 */
	public function get($poiId)
	{
		return $this->parseJSON('GET', [self::API_POI_GET, ['ak'=>self::AK,'id' => $poiId,'geotable_id'=>147841]]);
	}

	/**
	 * List POI.
	 *
	 * @param int $offset
	 * @param int $limit
	 *
	 * @return \EasyWeChat\Support\Collection
	 */
	public function lists($offset = 0, $limit = 10)
	{
		$params = [
			'begin' => $offset,
			'limit' => $limit,
		];

		return $this->parseJSON('json', [self::API_LIST, $params]);
	}

	/**
	 * Create a POI.
	 *
	 * @param array $data ['coord_type'=>3,'latitude=>'','longitude'=>'',$column=>'']
	 *
	 * @return bool
	 */
	public function create(array $data)
	{
		$data = array_merge($data, ['geotable_id' => self::GEOTABLE_ID,'ak'=>self::AK,'coord_type'=>3]);

		return $this->parseJSON('post', [self::API_CREATE_POI, $data]);
	}

	/**
	 * Update a POI.
	 *
	 * @param int   $poiId
	 * @param array $data
	 *
	 * @return bool
	 */
	public function update($poiId, array $data)
	{
		$data = array_merge($data, ['id' => $poiId,'geotable_id' => self::GEOTABLE_ID,'ak'=>self::AK]);

		$params = [
			'business' => ['base_info' => $data],
		];

		return $this->parseJSON('json', [self::API_POI_UPDATE, $params]);
	}

	/**
	 * Delete a POI.
	 *
	 * @param int $poiId
	 *
	 * @return bool
	 */
	public function delete($poiId)
	{
		$params = ['id' => $poiId,'geotable_id' => self::GEOTABLE_ID,'ak'=>self::AK];

		return $this->parseJSON('json', [self::API_POI_DEL, $params]);
	}


	function sign()
	{
		//API控制台申请得到的ak（此处ak值仅供验证参考使用）
		$ak = 'Gs0gpTACzqpxHgowoL8GEz5qa9dkUG83';

		//应用类型为for server, 请求校验方式为sn校验方式时，系统会自动生成sk，可以在应用配置-设置中选择Security Key显示进行查看（此处sk值仅供验证参考使用）
		$sk = 'sHyXoMgCg0fSuaZvo2bFnrxv74Gzsl0m';

		//以Geocoding服务为例，地理编码的请求url，参数待填
		$url = "http://api.map.baidu.com/geocoder/v2/?address=%s&output=%s&ak=%s&sn=%s";

		//get请求uri前缀
		$uri = '/geocoder/v2/';

		//地理编码的请求中address参数
		$address = '百度大厦';

		//地理编码的请求output参数
		$output = 'json';

		//构造请求串数组
		$querystring_arrays = array (
				'address' => $address,
				'output' => $output,
				'ak' => $ak
		);

		//调用sn计算函数，默认get请求
		$sn = $this->caculateAKSN($ak, $sk, $uri, $querystring_arrays);

		//请求参数中有中文、特殊字符等需要进行urlencode，确保请求串与sn对应
		$target = sprintf($url, urlencode($address), $output, $ak, $sn);

		//输出计算得到的sn
		echo "sn: $sn \n";

		//输出完整请求的url（仅供参考验证，故不能正常访问服务）
		echo "url: $target \n";


	}

	public function caculateAKSN($ak, $sk, $url, $querystring_arrays, $method = 'GET')
	{
		if ($method === 'POST'){
			ksort($querystring_arrays);
		}
		$querystring = http_build_query($querystring_arrays);
		return md5(urlencode($url.'?'.$querystring.$sk));
	}
}