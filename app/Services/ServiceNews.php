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
use Illuminate\Support\Facades\Log;

class ServiceNews
{
	const paytemplateId = "wsOF_eD0FCir6lwOUSXNH34feNcf7zhchnki7_LjA2w";
	const sstemplateId = "WTv50uF95B6iH38DqVQY98lQqlN2YIUO6WMxyJrlTd0";
	const adminopenid = "o4utmv0Nli7y29QJmYvorFWr_FH4";
	const answertemplateId = "8T44Ohxf1enWTH-mDOW54AxuPZfgsjmHTVHG3XaWdts";
	const appraisetemplateId = "swWxi8REN7P4ZscFo5dGhos7ac1wdBaS0ssXfXAQG-c";
	const failNoticetemplateId = "oRuJIsp16S8yCqo7V7yO6gcFaIat_rYB_KZR17CDO-o";//退款或企业支付失败通知
	/**
	 * 发送模板消息
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
	 * 企业支付发送消息
	 * @param $param
	 * @return array|bool
	 */
	public static function getParamForBusiness($param){
		$url = $_SERVER["HTTP_HOST"]."/information_list.html";

		$first = $param["desc"].$param['status'].$param["error_msg"];
		$senddata = array(
				"first"    => [$first, '#FF0000'],
				"keyword1" => $param["partner_trade_no"],
				"keyword2" => date("Y-m-d H:i:s"),
				"keyword3" => $param["type"],
				"keyword4" => $param["amount"]/100,
				"remark"   => $param["desc"]
		);

		$data = [
				"openid"		=>	self::adminopenid,
				"templateId" 	=> 	self::failNoticetemplateId,
				"url"			=>	$url,
				'senddata'		=>	$senddata
		];

		return $data;
	}
	/**
	 * 退款失败发送消息
	 * @param $param
	 * @return array|bool
	 */
	public static function getParamForFail($param){
		$order = NewsOrder::where(["out_trade_no"=>$param["out_trade_no"]])->first();
		if(null==$order){
			return false;
		}
		$order = $order->toArray();
		if("news"==$order["type"]){
			$keyword3 = "现场";
			$url = $_SERVER["HTTP_HOST"]."/information_detail.html?id={$order['pay_id']}";
			$order_info = News::select(["content","created_at","nickname"])->where(["id"=>$order['pay_id']])->first();
			if(null==$order_info){
				return false;
			}
		}else{
			$keyword3 = "问问";
			$order_info = Answer::select(["content","created_at","q_id","nickname"])->where(["id"=>$order['pay_id']])->first();
			if(null==$order_info){
				return false;
			}
			$url = $_SERVER["HTTP_HOST"]."/question_detail.html?id={$order_info['q_id']}";
		}

		$param['from_nickname'] = ServiceUser::getNickname();

		$first = "{$param['from_nickname']}对{$order_info['nickname']}{$order_info['created_at']}发布的信息{$param['status']} ".$param["error_msg"];
		$senddata = array(
				"first"    => [$first, '#FF0000'],
				"keyword1" => $param["out_trade_no"],
				"keyword2" => date("Y-m-d H:i:s"),
				"keyword3" => $keyword3,
				"keyword4" => $param["refund_amount"],
				"remark"   => mb_substr($order_info["content"],0,30)
		);

		$data = [
				"openid"		=>	self::adminopenid,
				"templateId" 	=> 	self::failNoticetemplateId,
				"url"			=>	$url,
				'senddata'		=>	$senddata
		];

		return $data;
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
				"keyword1" => ServiceUser::getNickname(),
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
			$order_info = News::select(["content","updated_at","nickname",])->where(["id"=>$param['pay_id']])->first();
			if(null==$order_info){
				return false;
			}
		}else{
			$keyword1 = "问问";
			$order_info = Answer::select(["nickname","content","updated_at","q_id"])->where(["id"=>$param['pay_id']])->first();
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
				"remark"   => $order_info["nickname"]."：".$order_info["content"],
		);
		$data = [
				"openid"		=>	self::adminopenid,
				"templateId" 	=> 	self::paytemplateId,
				"url"			=>	$url,
				'senddata'		=>	$senddata
		];
		return $data;
	}

	/**
	 * 回答提示
	 * @param $param
	 * @return array
	 */
	public static function getParamForAnswer($param){
		$nickname = ServiceUser::getNickname();
		$senddata = array(
				"first"    => "丫丫即时通知，{$nickname}已经回答了你的丫丫现场提问了!赶紧看一下",

				"keyword1" => $param["nickname"],
				"keyword2" => $param["created_at"],
				"remark"   => $param["content"],
		);
		$url = $_SERVER["HTTP_HOST"]."/question_detail.html?id={$param['vote_id']}";
		$data = [
				"openid"		=>	$param["openid"],
				"templateId" 	=> 	self::answertemplateId,
				"url"			=>	$url,
				'senddata'		=>	$senddata
		];

		return $data;
	}

	/**
	 * 评价内容发送信息
	 * @param $param
	 * @return array
	 */
	public static function getParamForVote($param){
		$vote = intval($param['vote']);
		$vote_content = config("services.vote_status.{$vote}");
		$nickname = ServiceUser::getNickname();

		if($param["type"]=="answer"){
			$first = "丫丫即时通知，{$nickname}看了您{$param["created_at"]}提交的回答，评价了{$vote_content}！";
			$url = $_SERVER["HTTP_HOST"]."/question_detail.html?id={$param['vote_id']}";
		}else{
			$first = "丫丫即时通知，{$nickname}看了您{$param["created_at"]}提交的现场{$param["event_type"]}信息，评价了{$vote_content}！";
			$url = $_SERVER["HTTP_HOST"]."/information_detail.html?id={$param['vote_id']}";
		}
		$senddata = array(
				"first"    => $first,
				"keyword1" => time().$param["vote_id"],
				"keyword2" => date('Y-m-d'),
				"remark"   => mb_substr($param["content"],0,20, 'utf-8')."..."
		);

		$data = [
				"openid"		=>	$param["openid"],
				"templateId" 	=> 	self::appraisetemplateId,
				"url"			=>	$url,
				'senddata'		=>	$senddata
		];

		return $data;
	}
}
