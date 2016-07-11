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
	public static function updateBusinessPayStatus($id,$pay_status,$message=""){
		$flag = BusinessPayRecord::where("id",$id)->update(["pay_status"=>$pay_status,"renark"=>$message]);
		return $flag;
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
	public static function updateRefundStatus($id,$refund_status,$message=""){
		$flag = RefundRecord::where("id",$id)->update(["refund_status"=>$refund_status,"renark"=>$message]);
		return $flag;
	}
}