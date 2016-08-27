<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/8/1
 * Time: 17:12
 */

namespace App\Http\Controllers;


use App\Answer;
use App\NewsOrder;
use App\OrderPayBack;
use App\Services\ServiceLbs;
use App\Tweets;
use App\Vote;
use Illuminate\Contracts\Logging\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;
use app\AdminUser;
use App\News;
use App\Orders;
use App\Question;

class TweetsController extends Controller
{

	protected function returnUser()
	{
		return [
			'openid' => $_SESSION['wechat_user']['id'],
			'nickname' => $_SESSION['wechat_user']['nickname'],
			'avatar' => $_SESSION['wechat_user']['avatar']
		];
	}

	public function getGeosearch(){
		$start_time = strtotime(date("Y-m-d"));
		$filter = "created_at:".$start_time.",".time();
		$sortby = "created_at:1";
		$data = $this->requestData;
		$data["filter"] = $filter;
		$data["sortby"] = $sortby;

		$service = new ServiceLbs();
		$ret = $service->geosearch($data);
		if(isset($ret["error_code"])){
			$result = ['code'=>100,'msg'=>$ret["msg"]];
		}else{
			$arr = [];
			foreach($ret["contents"] as $item){
				if(isset($item["openid"]) && !empty($item["openid"])){
					$arr[$item["openid"]] = $item;
				}else{
					$arr[] = $item;
				}

			}
			$ret["contents"] = $arr;

			$result = ['code'=>200,'result'=>$ret];
		}
		return response()->json($result);
	}

	public function postQuit(){
		$userid = $this->returnUser();

	}
	/**
	 * 添加说说
	 */
	public function postTweets(){
		$result = Tweets::create($this->requestData + $this->returnUser());
		if(null!=$result){
			//将说说存到百度lbs
			$data = $result->toArray();
			$data["tweet_id"] = $data["id"];
			$data["created_at"] = strtotime($data["created_at"]);
			$data["tags"] = isset($this->requestData["event_type"])?$this->requestData["event_type"]:"";
			unset($data["id"]);
			$service = new ServiceLbs();
			$res = $service->create($data);
			if(isset($res["id"])){
				Tweets::where(["id"=>$result["id"]])->update(["poi_id"=>$res["id"]]);
			}
			$return = ['code'=>200,'msg'=>$result];
		}else{
			$return = ['code'=>100,'result'=>"发布失败"];
		}
		return response()->json($return);
	}

	public function postDel(){
		$result = [
				'code' => 200,
				'msg' => '删除成功'
		];
		if ($this->_is_admin) {
			$where = [
					'id' => $this->requestData['id']
			];
			if (!Tweets::where($where)->update(['is_delete' => '1'])) {
				$result = [
						'code' => 100,
						'msg' => '删除失败'
				];
			}
		} else {
			$where = [
					'id' => $this->requestData['id']
			];
			$where['openid'] = $_SESSION['wechat_user']['id'];
			if (!Tweets::where($where)->update(['is_delete' => '1'])) {
				$result = [
						'code' => 100,
						'msg' => '删除失败'
				];
			}
		}
		if($result["code"]==200){
			$tweet = Tweets::find( $this->requestData['id']);
			if(null!==$tweet){
				$service = new ServiceLbs();
				$data = $service->delete($tweet->poi_id);
				if(isset($data["error_code"])){
					Tweets::where($where)->update(['is_delete' => '0']);
					$result = ['code'=>100,'msg'=>"删除失败"];
				}else{
					$result = ['code'=>200,'result'=>"删除成功"];
				}
			}
		}
		return response()->json($result);
	}
	/**
	 * 获取最后一次说说
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function getLast(){
		$user = $this->returnUser();
		$openid = $user["openid"];
		$where = [
				'is_display' => '1',
				'is_delete' => '0',
				'openid'=>$openid
		];
		$list = Tweets::select(['*'])
				->where($where)
				->orderBy("id", "desc")
				->first();

		if(null!=$list){
			$list = $list->toArray();
			return $this->output($list);
		}else{
			return $this->output("");
		}
	}
	/**
	 * 获取列表
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function getIndex(){
		$page = Request::input("page",1);
		$pagesize = 10;

		$start = ($page - 1) * $pagesize;
		$where = [
				'is_display' => '1',
				'is_delete' => '0'
		];

		$list = Tweets::select(['*'])
				->where($where)
				->orderBy("id", "desc")
				->skip($start)->take($pagesize)
				->get()
				->toArray();

		return $this->output($list);
	}

	/**
	 * 获取说说详情
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function getDetail(){

		$id = trim(Request::input("id",""));
		if(empty($id)){
			$result = ["code"=>100,"msg"=>"参数错误"];
			return response()->json($result);
		}
		//获取说说的详情
		$tweet_obj = Tweets::find($id)->toArray();
		if(null==$tweet_obj){
			$result = ["code"=>100,"msg"=>"数据不存在"];
			return response()->json($result);
		}
		$tweet = $tweet_obj->toArray();

		//查看说说下面的问问
		$question = [];
		$question_obj = Question::where(["tweet_id"=>$tweet["id"],'is_display'=>1,'is_delete'=>0])
				->orderBy("id", "desc")
				->get();
		if(!empty($question_obj)){
			$question = $question_obj->toArray();
			foreach($question as $k=>$v){
				$question[$k]['answers'] = $this->_format_question_answer($this->requestData['id']);;
			}
		}
		$tweet["questions"] = $question;

		return $this->output($tweet);
	}
	/**
	 * 获取我发布的所有内容
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function getMylist(){
		$page = Request::input("page",1);
		$pagesize = 10;

		$start = ($page - 1) * $pagesize;

		$openid =$_SESSION['wechat_user']['id'];
		$is_admin = 0;
		if (AdminUser::where('openid', $openid)->first()) {
			$is_admin = 1;
		}
		if($is_admin){
			//获取所有用户的news
			$sql = "select type,openid,avatar,nickname,content,created_at,updated_at,updated_at,is_display from news as n where is_delete=0";
			$sql .= " union all select type,openid,avatar,nickname,content,created_at,updated_at,updated_at,is_display from tweets as tw where is_delete=0";
			$sql .= " union all select type,openid,avatar,nickname,content,created_at,updated_at,updated_at,is_display from questions as qu where is_delete=0";
			$sql .= " order by created_at desc limit {$start},{$pagesize}";
		}else{
			//获取用户的news
			$sql = "select type,openid,avatar,nickname,content,created_at,updated_at,updated_at,is_display from news as n where is_delete=0 and openid='{$openid}'";
			$sql .= " union all select type,openid,avatar,nickname,content,created_at,updated_at,updated_at,is_display from tweets as tw where is_delete=0 and openid='{$openid}'";
			$sql .= " union all select type,openid,avatar,nickname,content,created_at,updated_at,updated_at,is_display from questions as qu where is_delete=0 and openid='{$openid}'";
			$sql .= " order by created_at desc limit {$start},{$pagesize}";
		}


		$data = DB::select($sql);

		return $this->output($data);
	}

	/**
	 * 格式化question,详情页数据展示
	 *
	 * @param string $q_id
	 * @return multitype:
	 */
	protected function _format_question_answer($q_id = "")
	{
		$list = [];
		$user_id = $_SESSION['wechat_user']['id'];
		if ($this->_is_admin()) {
			// 管理员用户显示全部，包括删除的回答
			$list = Answer::withTrashed()->where(['q_id' => $q_id])->orderBy('updated_at', 'desc')->get()->toArray();
		} elseif (Question::where('id', $q_id)->value('openid') == $user_id) {
			// 问问题的人，不管is_display字段，显示所有未删除的回答
			$list = Answer::where(['q_id' => $q_id])->OrderBy('updated_at', 'desc')->get()->toArray();
		} else {
			// 普通用户，仅显示所有is_display=1的，加自己的回答，加已经购买的回答
			$payIds = Orders::where(['openid' => $user_id, 'type' => 'answer', 'pay_status' => 'PAY_SUCCESS'])->lists('pay_id');
			Log::info($payIds);
			$list = Answer::where(['q_id' => $q_id, 'is_display' => 1])->orWhere(['q_id' => $q_id, 'openid' => $user_id])->orWhere(function ($query) use ($q_id, $payIds) {
				$query->where('q_id', $q_id)
						->whereIn('id', $payIds);
			})->orderBy('updated_at', 'desc')->get()->toArray();
		}
		return $this->format_list($list, 'answer');
	}

	/**
	 * 格式化news 、 answer
	 *
	 * @param unknown $list
	 * @param string $type
	 * @return multitype:
	 */
	public function format_list($list = [], $type = "news") {
		if (!$list) return [];

		foreach ($list as $k => $v) {
			$list[$k]['content_img'] = $v['content_img'] ? explode(',', $v['content_img']) : [];
			//duan
			$file_number = null!=$v["file_number"] ? \GuzzleHttp\json_decode($v["file_number"],true):[];
			$list[$k]["vedio_count"] = isset($file_number["vedio_count"])?$file_number["vedio_count"]:0;
			$list[$k]["image_count"] = isset($file_number["image_count"])?$file_number["image_count"]:0;
			$list[$k]["voice_count"] = isset($file_number["voice_count"])?$file_number["voice_count"]:0;
			$list[$k]["txt_count"] = isset($file_number["txt_count"])?$file_number["txt_count"]:0;
			$list[$k]["other_count"] = isset($file_number["other_count"])?$file_number["other_count"]:0;

			$list[$k]['qr_length'] = mb_strlen($list[$k]['qrcode_content']);
			// Log::info($list[$k]');
			// Log::info($list[$k]);

			$list[$k]['is_admin'] = $this->_is_admin();
			$list[$k]['is_me'] = ($list[$k]['openid'] == $_SESSION['wechat_user']['id'])?1:0;
			$list[$k]['confirm_status'] = intval($list[$k]['confirm_status']);
			if ($type == 'answer') {
				$list[$k]['has_delete_btn'] = 1;
				$_where = [
						'pay_id'     => $list[$k]['id'],
						'pay_status' => 'PAY_SUCCESS',
						'type'       => 'answer'
				];
				if (!$list[$k]['is_me'] || Orders::where($_where)->count() > 0) {
					$list[$k]['has_delete_btn'] = 0;
				}
			}

			//失实证明 duan
			$payback = OrderPayBack::where(['type'=>$type, 'vote_id'=>$list[$k]['id']])->first();
			if(null!=$payback){
				$list[$k]['can_payback'] = 0;
				$detail = $payback->toArray();
				if(!empty($detail["files"])){
					$detail["files"] = explode(",",$detail["files"]);
				}
				$list[$k]['payback_detail'] = $detail;

			}else{
				$list[$k]['can_payback'] = 1;
				$list[$k]['payback_detail'] = [];
			}

			$list[$k]['thumb'] = $v['thumb'] ? explode(',', $v['thumb']) : 0;
			//if (count($list[$k]['thumb_des']) == 0) $list[$k]['thumb_des'] = 0;
			$list[$k]["sum"] = count($list[$k]['thumb']) + count($list[$k]['content_img']);
			//我是否投票
			$list[$k]['vote'] = Vote::where(['openid' => $_SESSION['wechat_user']['id'] , 'type' => $type,'vote_id' => $v['id']])->pluck('vote')->toArray()[0];


			$order = $this->_check_pay($v['id'], $type);
			if(null!==$order){
				$list[$k]['pay_date'] = $order["pay_date"];
				$is_payed = 1;
			}else{
				$list[$k]['pay_date'] = "";
				$is_payed = 0;
			}

			if($type =="news" && !$v['price']) {
				$is_payed = true;
			}

			if ($v['openid'] != $_SESSION['wechat_user']['id'] && !$is_payed && !$this->_is_admin()) {
				$list[$k]['qrcode_content'] = "";
				$list[$k]['content_img'] = [];
			}
			$list[$k]['is_open'] = $is_payed;
			if ($this->_is_admin()) {
				$list[$k]['is_open'] = 1;
			}

			$list[$k] += $this->return_useful_data($v['id'], $type);
		}
		return $list;
	}

	/**
	 * 返回投票数据
	 *
	 * @param string $id
	 * @return array
	 */
	public function return_useful_data($id = "", $type = "") {
		$useful_count = Vote::where([
				'type' => $type,
				'vote' => 3,
				'vote_id' => $id
		])->count();

		$good_count = Vote::where([
				'type' => $type,
				'vote' => 2,
				'vote_id' => $id
		])->count();

		$useless_count = Vote::where([
				'type' => $type,
				'vote' => 1,
				'vote_id' => $id
		])->count();

		$is_useful = (($useful_count > 0) || ($good_count > 1)) ? 1 : 0;
		$effect_count = $useful_count + $good_count;
		return compact('useful_count', 'useless_count', 'is_useful', 'good_count', 'effect_count');
	}

	/**
	 * 判断是否支付，返回相应的内容
	 *
	 * @param unknown $id
	 * @param unknown $type
	 */
	public function _check_pay($id, $type) {
		$where = [
				'pay_id' => $id,
				'type' => $type,
				'openid' => $_SESSION['wechat_user']['id'],
				'pay_status' => 'PAY_SUCCESS'
		];

		$order = NewsOrder::where($where)->first();

		return  $order;
	}
}