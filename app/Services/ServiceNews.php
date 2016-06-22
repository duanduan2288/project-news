<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/6/15
 * Time: 9:13
 */

namespace App\Services;

use App\Answer;
use App\News;
use App\NewsOrder;

class ServiceNews
{
	const paytemplateId = "wsOF_eD0FCir6lwOUSXNH34feNcf7zhchnki7_LjA2w";
	const sstemplateId = "WTv50uF95B6iH38DqVQY98lQqlN2YIUO6WMxyJrlTd0";
	const adminopenid = "o4utmv0Nli7y29QJmYvorFWr_FH4";
	/**
	 * 将失实证明提交到微信公众号
	 * @param $app
	 * @param $param
	 * @return bool
	 */
	public static function sendMessage($app,$param){
		try{
			$notice = $app->notice;
			$openid = $param["openid"];

			//$userId = "o4utmv4dHrVKo8Z6eYL9sZ6gGF_Y";
			$templateId = $param["templateId"];
			$url = $param["url"];
			$data = $param["senddata"];

			$message= $notice->to($openid)->url($url)->template($templateId)->andData($data)->send();

			return $message->msgid;

		}catch (\Exception $e){

			return $e->getMessage();
		}
	}

	/**
	 * 获取拼接好的失实信息
	 * @param $param
	 * @return array
	 */
	public static function getParamForMessage($param){

		$content = "失实描述：{$param["description"]}";
		if(!empty($param["files"])){
			$content.=" 文件：{$param['files']}";
		}
		$senddata = array(
				"first"    => "收到1条来自付费用户的内容失实证明！",
				"keyword1" => $_SESSION['wechat_user']['nickname'],
				"keyword2" => date("Y-m-d H:i:s"),
				"remark"   => $content,
		);
		$url = $_SERVER["HTTP_HOST"]."/information_detail.html?id={$param['vote_id']}";
		$data = [
				"openid"		=>	self::adminopenid,
				"templateId" 	=> 	self::sstemplateId,
				"url"			=>	$url,
				'senddata'		=>	$senddata
		];

		return $data;
	}

	/**
	 * 支付
	 * @param $param
	 * @return array
	 */
	public static function getParamForPay($out_trade_no){
		$order = NewsOrder::where(["out_trade_no"=>$out_trade_no])->first();
		if(null==$order){
			return false;
		}
		$param = $order->toArray();
		if("news"==$param["type"]){
			$keyword1 = "现场";
			$url = $_SERVER["HTTP_HOST"]."/information_detail.html?id={$param['pay_id']}";
			$order_info = News::select(["content","updated_at"])->where(["id"=>$param['pay_id']])->first();
			if(null==$order_info){
				return false;
			}
		}else{
			$keyword1 = "问问";
			$order_info = Answer::select(["content","updated_at","q_id"])->where(["id"=>$param['pay_id']])->first();
			if(null==$order_info){
				return false;
			}
			$url = $_SERVER["HTTP_HOST"]."/question_detail.html?id={$order_info['q_id']}";
		}
		$senddata = array(
				"first"    => "收到1条用户支付提醒",
				"keyword1" => $keyword1,
				"keyword2" => $param['nickname'],
				"keyword3" => $param["total_fee"],
				"keyword4" => $param["updated_at"],
				"keyword5" => $param["out_trade_no"],
				"remark"   => $order_info["content"],
		);
		$data = [
				"openid"		=>	self::adminopenid,
				"templateId" 	=> 	self::paytemplateId,
				"url"			=>	$url,
				'senddata'		=>	$senddata
		];
		return $data;
	}
}
