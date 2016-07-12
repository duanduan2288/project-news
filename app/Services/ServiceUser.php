<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/6/30
 * Time: 10:57
 */

namespace App\Services;


use App\BusinessPayRecord;
use App\RefundRecord;
use App\WechatUser;
use Illuminate\Support\Facades\DB;
use Log;

class ServiceUser
{
	/**
	 * 保存微信用户信息
	 * @param $user_info
	 * @return static
	 */
	public static function saveWechatUser($user_info){
		$user = WechatUser::where("openid",$user_info["id"])->first();
		if(null!==$user){
			return $user->toArray();
		}
		$original = $user_info["original"];
		$data["name"] = $user_info["name"];
		$data["nickname"] = $user_info["nickname"];
		$data["openid"] = $user_info["id"];
		$data["avatar"] = $user_info["avatar"];
		$data["email"] = $user_info["email"];
		$data["sex"] = $original["sex"];
		$data["city"] = $original["city"];
		$data["province"] = $original["province"];
		$data["country"] = $original["country"];
		$data["created"] = date("Y-m-d H:i:s");

		$wechatuser = WechatUser::create($data);

		return $wechatuser;
	}

	/**
	 * 获取昵称
	 * @return string
	 */
	public static function getNickname(){
		$openid = $_SESSION['wechat_user']['id'];
		$nickname = DB::table("wechat_user")->where("openid",$openid)->pluck("nickname");

		return null==$nickname ? "" : $nickname[0];
	}

	/**
	 * 插入企业支付表
	 * @param $param
	 * @param $vote_id
	 * @return static
	 */
	public static function insertBusinessRecord($param,$vote_id){
		$param["content"] = $param["desc"];
		$param["created"] = date("Y-m-d H:i:s");
		$param["vote_id"] = $vote_id;
		unset($param["desc"]);
		$result = BusinessPayRecord::create($param);
		return $result;
	}

	/**
	 * 修改支付状态
	 * @param $id
	 * @param $pay_status
	 * @return mixed
	 */
	public static function updateBusinessPayStatus($app,$id,$pay_status,$param,$message=""){
		$record = BusinessPayRecord::where("id",$id)->first();
		if(null!=$record){
			$record->pay_status = $pay_status;
			$record->remark = $message;
			$record->save();

			$param["status"] = $pay_status=="FAIL" ? "企业支付失败" : "企业支付成功";
			$param["error_msg"] = !empty($message)?"失败原因：".$message:"";
			$data = ServiceNews::getParamForBusiness($param);
			ServiceNews::sendMessage($app,$data);
		}
		return true;
	}

	/**
	 * 插入退款表
	 * @param $param
	 * @param $vote_id
	 * @return static
	 */
	public static function insertRefundRecord($param){
		$result = RefundRecord::create($param);
		return $result;
	}

	/**
	 * 修改退款状态
	 * @param $id
	 * @param $pay_status
	 * @return mixed
	 */
	public static function updateRefundStatus($app,$id,$refund_status,$message=""){
		$record = RefundRecord::where("id",$id)->first();
		if(null!=$record){
			$record->refund_status = $refund_status;
			$record->remark = $message;
			$record->save();

			$param['out_trade_no'] = $record->out_trade_no;
			$param["refund_amount"] = $record->refund_amount/100;
			$param["status"] = $refund_status=="FAIL" ? "退款失败" : "退款成功";
			$param["error_msg"] = !empty($message)?"失败原因：".$message:"";
			$data = ServiceNews::getParamForFail($param);
			ServiceNews::sendMessage($app,$data);
		}
		return true;
	}
}