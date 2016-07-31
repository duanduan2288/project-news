<?php
namespace App\Http\Controllers;
set_time_limit(0);
use App\News;
use App\Services\ServiceFile;
use App\Services\ServiceNews;
use App\Services\ServiceUpload;
use App\Services\ServiceUser;
use App\Vote;
use App\WechatUser;
use EasyWeChat\Message\Text;
use EasyWeChat\Support\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\View;
use phpDocumentor\Reflection\DocBlock\Tags\Var_;
use Requests;
use App\Question;
use App\Answer;
use App\NewsOrder;
use App\OrderPayBack;
use App\AdminUser;
use App\Orders;
use Log;

// use App\Http\Controllers\WxtestClass;
class NewsController extends Controller
{
	public function getLogin()
	{
		if (isset($_SESSION['wechat_user']['id'])) {
			$user = WechatUser::where("openid", $_SESSION['wechat_user']['id'])->first();
			if (null != $user) {
				$user_info = $user->toArray();
				$user_info["id"] = $user_info["openid"];
				$_SESSION['wechat_user'] = $user_info;

				return redirect($_SERVER['HTTP_REFERER'] ?: "/");
			} else {
				$this->get_userinfo($_SERVER['HTTP_REFERER'], "info");
			}
		} else {
			$this->get_userinfo($_SERVER['HTTP_REFERER']);
		}
	}

	public function getUser()
	{
		$return_info = "";
		if (isset($_SESSION['wechat_user']['id'])) {
			$user = WechatUser::where("openid", $_SESSION['wechat_user']['id'])->first();
			if (null != $user) {
				$user_info = $user->toArray();
				$user_info["id"] = $user_info["openid"];
				$_SESSION['wechat_user'] = $user_info;
				$return_info = $user_info;
			}
		}

		return $this->output($return_info);
	}

	public function getSearch()
	{
		$key = $this->requestData['key'];

		if ($this->requestData['type'] == 'news') {
			$list = News::where('content', 'like', "%{$key}%")->get()->toArray();
		} elseif ($this->requestData['type'] == 'question') {
			$list = Question::where('content', 'like', "%{$key}%")->get()->toArray();
		}

		return $this->success($this->format_list($list, $this->requestData['type']));
	}

	/**
	 * 信息列表 news   question
	 *
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function getIndex()
	{
		if (Request::input('page')) {
			$pagesize = 10;

			$start = (Request::input('page') - 1) * $pagesize;

			$openid = $_SESSION['wechat_user']['id'];
			$is_admin = 0;
			if (AdminUser::where('openid', $_SESSION['wechat_user']['id'])->first()) {
				$is_admin = 1;
			}
			if ($this->requestData['type'] == 'news') {
				if ($is_admin) {
					// 管理员能看所有的，is_dete表示鉴定汇总页面
					if ($this->requestData['is_dete']) {
						$list = News::withTrashed()->where('confirm_status', '1')->skip($start)->take($pagesize)
							->orderBy("id", "desc")
							->get()
							->toArray();
					} else {
						$list = News::withTrashed()->skip($start)->take($pagesize)
							->orderBy("id", "desc")
							->get()
							->toArray();
					}
				} else {
					// 对所有用户都可见的，未隐藏，且未被用户删除的
					$where = [
						'type' => 'news',
						'openid' => $openid,
						'pay_status' => 'PAY_SUCCESS'
					];
					// 用户已经购买的物品
					$mustShow = Orders::where($where)->lists('pay_id');
					$where = [
						'is_display' => '1',
						'is_delete' => '0'
					];

					$list = News::where($where)
						->orWhereIn('id', $mustShow)
						->orderBy("id", "desc")
						->skip($start)->take($pagesize)
						->get()
						->toArray();
				}

				return $this->output($this->format_list($list));
			} elseif ($this->requestData['type'] == 'question') {
				if ($is_admin) {
					$list = Question::withTrashed()->skip($start)->take($pagesize)
						->orderBy("id", "desc")
						->get()
						->toArray();
				} else {
					$list = Question::where('is_display', '1')->skip($start)->take($pagesize)
						->orderBy("id", "desc")
						->get()
						->toArray();
				}
				foreach ($list as $k => $v) {
					$list[ $k ]['answer_count'] = Answer::where(['q_id' => $v['id']])->count();
					$useful_count = Vote::where([
						'type' => 'answer',
						'vote' => 3,
						'vote_id' => $v['id']
					])->count();

					$good_count = Vote::where([
						'type' => 'answer',
						'vote' => 2,
						'vote_id' => $v['id']
					])->count();
					$list[ $k ]['effect_count'] = $useful_count + $good_count;
					$list[ $k ]['is_admin'] = $is_admin;
				}


				return $this->output($list);
			}
		}
	}

	/**
	 * 内容详情页
	 */
	public function getContent()
	{
		if ($this->requestData['type'] == 'news') {
			if ($this->_is_admin()) {
				$content = News::withTrashed()->where(['id' => $this->requestData['id']])->first();
			} else {
				$content = News::where(['id' => $this->requestData['id']])->first();
			}
			//duan
			if (null !== $content) {
				$content = $content->toArray();
			} else {
				$content = [];
			}
			$user_id = $_SESSION['wechat_user']['id'];
			// 如果是自己的内容，就会直接显示出来
			if (strcmp($content['openid'], $user_id) == 0) {
				$content['is_me'] = 1;
			} else {
				$content['is_me'] = 0;
			}
			$where = [
				'openid' => $user_id,
				'vote_id' => $this->requestData['id'],
				'type' => 'news'
			];
			$vote = Vote::where($where)->get()->first();
			if ($vote) {
				$content['vote_data_value'] = $vote->vote;
			} else {
				$content['vote_data_value'] = 0;
			}

			// Log::info($content);

			return $this->output($this->format_list([
				$content
			]));
		} elseif ($this->requestData['type'] == 'question') {
			$content = Question::where(['id' => $this->requestData['id']])->first();
			if ($content) {
				$content = $content->toArray();
				$content['is_me'] = $_SESSION['wechat_user']['id'] == $content['openid'] ? 1 : 0;
				$content['answer'] = $this->_format_question_answer($this->requestData['id']);
				$content['has_delete_btn'] = 1;
				// 不是自己发布的，或者是自己发布的但是有人回答了
				if (!$content['is_me'] || Answer::where(['q_id' => $this->requestData['id']])->count() > 0) {
					$content['has_delete_btn'] = 0;
				}
			} else {
				$content = [];
			}

			return $this->output($content);
		}
	}

	/**
	 * 内容添加
	 */
	public function postAdd()
	{
		$this->requestData['price'] = ($this->requestData['price'] <= 10000) ? $this->requestData['price'] : 10000;//modify by duan

		$this->requestData['total_fee'] = $this->requestData['price'];

		if ($this->requestData['type'] == 'news') {
			$this->requestData['file_number'] = json_encode($this->requestData['file_number']);

			$voice_data = isset($this->requestData['voice_data'])?$this->requestData['voice_data']:[];
			$this->requestData["voice_data"] = json_encode($voice_data);

			$result = News::create($this->requestData + $this->returnUser());
		} elseif ($this->requestData['type'] == 'question') {
			$result = Question::create($this->requestData + $this->returnUser());
		}

		return $this->output($result);
	}

	public function postVote()
	{
		$where = [
			'openid' => $_SESSION['wechat_user']['id'],
			'vote_id' => $this->requestData['id'],
			'type' => $this->requestData['type']
		];
		//查看是否已经投过票
		if (Vote::where($where)->get()->toArray()) {
			$result = [
				'code' => 123,
				'msg' => '无法修改投票！'
			];

			return response()->json($result);
		}
		// 判断是否自己给自己投票
		$news_id = [
			'id' => $this->requestData['id']
		];
		if ($this->requestData['type'] == 'news') {
			$model = News::where($news_id)->first()->toArray();
			$order = Orders::where(['type' => 'news', 'pay_id' => $this->requestData['id'], 'pay_status' => 'PAY_SUCCESS'])->first();
			$price = $model["price"];
		} else {
			$model = Answer::where($news_id)->first()->toArray();
			$order = Orders::where(['type' => 'answer', 'pay_id' => $this->requestData['id'], 'pay_status' => 'PAY_SUCCESS'])->first();
			$vote_question = Question::where(['id' => $this->requestData['q_id']])->first()->toArray();
			$price = $vote_question["price"];
		}
		$openid = $model['openid'];
		if (strcmp($openid, $where['openid']) == 0) {
			$result = [
				'code' => 101,
				'msg' => '自己不能给自己投票'
			];

			return response()->json($result);
		}
		if ($price != 0 && !$order) {
			$result = [
				'code' => '100',
				'msg' => '未购买对应内容，无法进行评价'
			];

			return response()->json($result);
		}
		$vote_select = intval($this->requestData['vote']);
		// 小丫觉得很赞，用户评价完全扯淡
		if ($model['confirm_status'] == 2 && $vote_select == 1) {
			$result = [
				'code' => 100,
				'msg' => '此信息平台鉴定很赞，不能评价为扯淡。'
			];

			return response()->json($result);
		}
		//生成投票结果
		$this->requestData['vote_id'] = $this->requestData['id'];
		$create_data = $this->requestData;

		if (isset($create_data["q_id"])) unset($create_data["q_id"]);

		$voteresult = Vote::create($this->returnUser() + $create_data);
		$result = ['code' => 200];

		$nickName = $this->returnUser()['nickname'];
		$amount = $price * 100;

		$vote_nickname = $model['nickname'];
		$amount1 = 0;
		$amount2 = 0;
		switch ($vote_select) {
			// 用户评价为还行
			case 2:
				if ($model['confirm_status'] == 0) {
					// 付给提供者
					$amount1 = 0.5 * $amount;
					// 退款给购买者
					$amount2 = 0.5 * $amount;
				} else {
					// 付给提供者
					$amount1 = 0.5 * $amount;
					// 退款给购买者
					$amount2 = 0.45 * $amount;
				}
				break;
			// 用户评价为很赞
			case 3:
				if ($model['confirm_status'] == 0) {
					// 付给提供者
					$amount1 = $amount;
					// 退款给购买者
					$amount2 = 0;
				} else {
					// 付给提供者
					$amount1 = 0.95 * $amount;
					// 退款给购买者
					$amount2 = 0;
				}
				break;
			// 用户评价为扯淡
			case 1:
				switch ($model['confirm_status']) {
					case 0://未鉴定
						$amount1 = 0;
						$amount2 = $amount;
						break;
					case 1://鉴定中
						$amount1 = 0.05 * $amount;
						$amount2 = 0.50 * $amount;
						break;
					case 6://扯淡
						$amount1 = 0;
						$amount2 = 0.50 * $amount;
						break;
					case 3://有效
						$amount1 = 0.15 * $amount;
						$amount2 = 0.50 * $amount;
						break;
					case 4://不确定
						$amount1 = 0.05 * $amount;
						$amount2 = 0.50 * $amount;
						break;
					case 5://部分不太确定
						$amount1 = 0.05 * $amount;
						$amount2 = 0.50 * $amount;
						break;
				}
				break;
		}
		$amount1 = floor($amount1);
		if ($amount1 >= 100) {

			$app = $this->return_app();
			$merchantPay = $app->merchant_pay;
			$content = mb_substr($model["content"], 0, 4);
			$create_time = $model["created_at"];
			$type = $this->requestData['type'] == "news" ? "现场" : "问问";
			$merchantPayData = [
				'partner_trade_no' => str_random(16), //随机字符串作为订单号，跟红包和支付一个概念。
				'openid' => $openid, //收款人的openid
				// 'openid' => "o4utmv8LN_g8YyvX1CVV2qxD-3bI", //收款人的openid
				'check_name' => 'NO_CHECK',  //文档中有三种校验实名的方法 NO_CHECK OPTION_CHECK FORCE_CHECK
				'amount' => $amount1,  //单位为分
				'desc' => $nickName . "购买了您于{$create_time}发布的（{$type}）{$content}...",
				'spbill_create_ip' => $_SERVER['REMOTE_ADDR'], //发起交易的IP地址
			];
			$send_data = $merchantPayData;
			$send_data["type"] = $type;
			$send_data["desc"] = $nickName . "购买了{$vote_nickname}于{$create_time}发布的（{$type}）{$content}...";
			//保存支付记录
			$businessresult = ServiceUser::insertBusinessRecord($merchantPayData, $voteresult["id"]);
			try {
				$pay_result = $merchantPay->send($merchantPayData);
				if ($pay_result['result_code'] == 'FAIL') {
					$result['msg'] = $pay_result['err_code_des'];

					ServiceUser::updateBusinessPayStatus($app,$businessresult["id"], "FAIL",$send_data, $result['msg']);

				} else {

					$result['msg'] = "评价成功，相应款项已经支付给提供者。";
					ServiceUser::updateBusinessPayStatus($app,$businessresult["id"], "SUCCESS",$send_data);
				}

				Log::info($pay_result);
			} catch (\Exception $e) {
				$result['msg'] = "相应款项支付给提供者失败。";
				ServiceUser::updateBusinessPayStatus($app,$businessresult["id"], "FAIL",$send_data, $e->getMessage());
				Log::info($result);
			}
		}

		$amount2 = floor($amount2);
		if ($amount2 > 0) {
			// 需要退款时，要先获取原订单的id号，同时自己生成一个退款单号，同时要先查询是否已经发起过退款操作
			$out_trade_no = Orders::where(['openid'=>$where["openid"],'type' => $this->requestData['type'], 'pay_id' => $this->requestData['id'], 'pay_status' => 'PAY_SUCCESS'])->first();

			if ($out_trade_no) {
				$openid_pay = $out_trade_no->openid;
				$out_trade_no = $out_trade_no->toArray()['out_trade_no'];
				$out_refund_no = str_random(32);
				$param = [
					"out_trade_no" => $out_trade_no,
					"out_refund_no" => $out_refund_no,
					"amount" => $amount,
					"refund_amount" => $amount2,
					"openid" => $openid_pay,
					"vote_id" => $voteresult["id"],
					"created" => date("Y-m-d H:i:s")
				];
				$recordresult = ServiceUser::insertRefundRecord($param);
				try {
					$app = $this->return_app();
					$payment = $app->payment;

					$payment->queryRefund($out_trade_no);
					$_result = $payment->refund($out_trade_no, $out_refund_no, $amount, $amount2);
					Log::info($_result);

					if ($_result['result_code'] != 'SUCCESS') {
						$result['code'] = 100;
						$result['msg'] = "退款失败了。";
						ServiceUser::updateRefundStatus($app,$recordresult["id"], "FAIL", $_result['err_code_des']);
					} else {
						ServiceUser::updateRefundStatus($app,$recordresult["id"], "SUCCESS");
					}
				} catch (\Exception $e) {
					$result['code'] = 100;
					$result['msg'] = "退款失败了。";
					ServiceUser::updateRefundStatus($app,$recordresult["id"], "FAIL", $e->getMessage());
				}

			} else {
				$result = [
					'code' => '100',
					'msg' => '未查询到支付订单。'
				];
			}
		}
		if ($result['code'] == 200) {
			//评价成功给用户发送消息
			$this->requestData["openid"] = $openid;
			$this->requestData["event_type"] = isset($model["event_type"]) ? $model["event_type"] : "";
			$this->requestData["created_at"] = $model["created_at"];
			$this->requestData["content"] = $model["content"];

			$param = ServiceNews::getParamForVote($this->requestData);
			ServiceNews::sendMessage($this->return_app(), $param);

			$result['msg'] = '评价成功。 ';
			$amount1 = $amount1 / 100;
			$amount2 = $amount2 / 100;
			if ($amount1 > 0) $result['msg'] .= $amount1 . " 元支付给信主[" . $vote_nickname . ']。';
			if ($amount2 > 0) $result['msg'] .= $amount2 . " 元退款到您的原支付渠道。";
		}

		return response()->json($result);
	}

	/**
	 * 添加问题的回答
	 *
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function postAnswer()
	{
		$this->requestData['total_fee'] = ($this->requestData['total_fee'] <= 10000) ? $this->requestData['total_fee'] : 10000;
		$this->requestData['file_number'] = json_encode($this->requestData['file_number']);

		$voice_data = isset($this->requestData['voice_data'])?$this->requestData['voice_data']:[];
		$this->requestData["voice_data"] = json_encode($voice_data);

		$result = Answer::create($this->requestData + $this->returnUser());
		//duan
		if ($result) {

			$question = Question::find($this->requestData["q_id"]);
			if (null !== $question && $question->openid != $this->returnUser()["openid"]) {
				$param["nickname"] = $question->nickname;
				$param["vote_id"] = $this->requestData["q_id"];
				$param["created_at"] = date("Y-m-d", strtotime($question->created_at));
				$param["openid"] = $question->openid;
				$param["content"] = mb_substr($this->requestData["cotnent"], 0, 20, 'utf-8') . "...";

				$data = ServiceNews::getParamForAnswer($param);
				ServiceNews::sendMessage($this->return_app(), $data);
			}
			//duan
		}

		return $this->output($result);
	}

	/**
	 * 上传文件，图片
	 *
	 * @return \Illuminate\Http\JsonResponse
	 * 上传到阿里云 duan
	 */
	public function postUpload()
	{
		$file = Request::file('file');
		if ($file->isValid()) {

			$guid = uniqid() . time();
			$ext2 = $ext = strtolower($file->guessExtension());
			if (isset($_FILES["file"]['name'])) {
				$ext2 = strtolower(pathinfo($_FILES["file"]['name'], PATHINFO_EXTENSION));
			}

			$ext = ($ext != $ext2 && $ext2 != "") ? $ext2 : $ext;
			$file_name = $guid . '.' . $ext;

			$outossurl = config('services.aliyun.OutOss');
			$Bucket = config('services.aliyun.Bucket');
			$SubmitTypes = config('services.aliyun.SubmitTypes');
			$SubmitImgTypes = config('services.aliyun.SubmitImgTypes');
			$ImgOss = config('services.aliyun.ImgOss');
			$SubmitVedioTypes = config('services.aliyun.SubmitVedioTypes');
			$SubmitTxtTypes = config('services.aliyun.SubmitTxtTypes');
			$SubmitAudioTypes = config('services.aliyun.SubmitAudioTypes');

			//上传到阿里云
			$oss = ServiceUpload::boot();
			// 设置 Bucket
			$oss = $oss->setBucket($Bucket);
			// 两个参数：资源名称、文件路径
			$oss->multiuploadFile($file_name, $file->getPathname());

			$imageurl = "";
			$submitJob_type = "";
			$url = $outossurl . $file_name;
			switch ($ext) {
				case in_array($ext, $SubmitImgTypes):
					$url = $ImgOss . $file_name;
					$imageurl = $ImgOss . $file_name . "!small";
					$file_type = "image";
					break;
				case in_array($ext, $SubmitVedioTypes):
					$imageurl = ServiceFile::submitSnapshotJob($file_name, $guid);
					if ("" == $imageurl) $imageurl = "/img/vedio.png";
					$file_type = "vedio";
					break;
				case in_array($ext, $SubmitTxtTypes):
					$imageurl = "/img/txt.png";
					$file_type = "txt";
					break;
				case in_array($ext, $SubmitAudioTypes):
					$imageurl = "/img/voice.png";
					$file_type = "voice";
					$submitJob_type = "voice";
					break;
				default:
					$imageurl = "/img/other.png";
					$file_type = "other";
					break;
			}
			//转码作业
			if (in_array($ext, $SubmitTypes)) {
				//判断时长
				$flag = ServiceFile::getDuration($file->getPathname());
				if ($flag) {
					$url = ServiceFile::submitJob($file_name, $guid,$submitJob_type);
				}
			}

			return $this->output(['url' => $url, 'imgurl' => $imageurl, 'file_type' => $file_type]);
		} else {

			return $this->output("");
		}
	}
	//modify end duan
	/**
	 * 人工鉴定
	 *
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function postUpdate()
	{
		if ($this->requestData['type'] == 'news') {
			$result = News::where(['id' => $this->requestData['id']])->update(['confirm_status' => $this->requestData['confirm_status']]);
		} elseif ($this->requestData['type'] == 'question') {
			$result = Question::create($this->requestData + $this->returnUser());
		} elseif ($this->requestData['type'] == 'answer') {
			$result = Answer::where(['id' => $this->requestData['id']])->update(['confirm_status' => $this->requestData['confirm_status']]);
		}

		return $this->output($result);
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
			// Log::info($list);
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
	public function format_list($list = [], $type = "news")
	{
		if (!$list) return [];

		foreach ($list as $k => $v) {
			$list[ $k ]['content_img'] = $v['content_img'] ? explode(',', $v['content_img']) : [];
			//duan
			$file_number = null != $v["file_number"] ? \GuzzleHttp\json_decode($v["file_number"], true) : [];
			$list[ $k ]["vedio_count"] = isset($file_number["vedio_count"]) ? $file_number["vedio_count"] : 0;
			$list[ $k ]["image_count"] = isset($file_number["image_count"]) ? $file_number["image_count"] : 0;
			$list[ $k ]["voice_count"] = isset($file_number["voice_count"]) ? $file_number["voice_count"] : 0;
			$list[ $k ]["txt_count"] = isset($file_number["txt_count"]) ? $file_number["txt_count"] : 0;
			$list[ $k ]["other_count"] = isset($file_number["other_count"]) ? $file_number["other_count"] : 0;

			$list[ $k ]['qr_length'] = mb_strlen($list[ $k ]['qrcode_content']);

			$list[ $k ]["voice_data"] = null != $v["voice_data"] ? \GuzzleHttp\json_decode($v["voice_data"], true) : [];
			// Log::info($list[$k]');
			// Log::info($list[$k]);

			$list[ $k ]['is_admin'] = $this->_is_admin();
			$list[ $k ]['is_me'] = ($list[ $k ]['openid'] == $_SESSION['wechat_user']['id']) ? 1 : 0;
			$list[ $k ]['confirm_status'] = intval($list[ $k ]['confirm_status']);
			if ($type == 'answer') {
				$list[ $k ]['has_delete_btn'] = 1;
				$_where = [
					'pay_id' => $list[ $k ]['id'],
					'pay_status' => 'PAY_SUCCESS',
					'type' => 'answer'
				];
				if (!$list[ $k ]['is_me'] || Orders::where($_where)->count() > 0) {
					$list[ $k ]['has_delete_btn'] = 0;
				}
			}

			// $list[$k]['video_count'] = 2;
			//失实证明 duan
			$payback = OrderPayBack::where(['type' => $type, 'vote_id' => $list[ $k ]['id']])->first();
			if (null != $payback) {
				$list[ $k ]['can_payback'] = 0;
				$detail = $payback->toArray();
				if (!empty($detail["files"])) {
					$detail["files"] = explode(",", $detail["files"]);
				}
				$list[ $k ]['payback_detail'] = $detail;

			} else {
				$list[ $k ]['can_payback'] = 1;
				$list[ $k ]['payback_detail'] = [];
			}

			$list[ $k ]['thumb'] = $v['thumb'] ? explode(',', $v['thumb']) : 0;
			//if (count($list[$k]['thumb_des']) == 0) $list[$k]['thumb_des'] = 0;
			$list[ $k ]["sum"] = count($list[ $k ]['thumb']) + count($list[ $k ]['content_img']);
			//我是否投票
			$list[ $k ]['vote'] = Vote::where(['openid' => $_SESSION['wechat_user']['id'], 'type' => $type, 'vote_id' => $v['id']])->pluck('vote')->toArray()[0];


			$order = $this->_check_pay($v['id'], $type);
			if (null !== $order) {
				$list[ $k ]['pay_date'] = $order["pay_date"];
				$is_payed = 1;
			} else {
				$list[ $k ]['pay_date'] = "";
				$is_payed = 0;
			}

			if ($type == "news" && !$v['price']) {
				$is_payed = true;
			}

			if ($v['openid'] != $_SESSION['wechat_user']['id'] && !$is_payed && !$this->_is_admin()) {
				$list[ $k ]['qrcode_content'] = "";
				$list[ $k ]['content_img'] = [];
			}
			$list[ $k ]['is_open'] = $is_payed;
			if ($this->_is_admin()) {
				$list[ $k ]['is_open'] = 1;
			}

			$list[ $k ] += $this->return_useful_data($v['id'], $type);
		}

		return $list;
	}

	/**
	 * 判断是否支付，返回相应的内容
	 *
	 * @param unknown $id
	 * @param unknown $type
	 */
	public function _check_pay($id, $type)
	{
		$where = [
			'pay_id' => $id,
			'type' => $type,
			'openid' => $_SESSION['wechat_user']['id'],
			'pay_status' => 'PAY_SUCCESS'
		];

		$order = NewsOrder::where($where)->first();

		return $order;
	}

	/**
	 * 返回投票数据
	 *
	 * @param string $id
	 * @return array
	 */
	public function return_useful_data($id = "", $type = "")
	{
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

	protected function returnUser()
	{
		return [
			'openid' => $_SESSION['wechat_user']['id'],
			'nickname' => $_SESSION['wechat_user']['nickname'],
			'avatar' => $_SESSION['wechat_user']['avatar']
		];
	}

	/**
	 * 微信授权统一回调地址
	 *
	 * @return Ambigous
	 */
	public function getOauth()
	{
		/***如果oauth_code失效就重新进行授权 modify by duan**/
		try {
			$app = $this->return_app();

			$oauth = $app->oauth;

			$user = $oauth->user();
			$userinfo = $user->toArray();
			ServiceUser::saveWechatUser($userinfo);
			$_SESSION['wechat_user'] = $userinfo;

			$targetUrl = empty($_SESSION['target_url']) ? "http://" . $_SERVER['HTTP_HOST'] . "/information_list.html" : $_SESSION['target_url'];

			return redirect($targetUrl);
		} catch (\Exception $e) {
			$this->do_oauth();

			$targetUrl = empty($_SESSION['target_url']) ? "http://" . $_SERVER['HTTP_HOST'] . "/information_list.html" : $_SESSION['target_url'];

			return redirect($targetUrl);
		}
	}

	/**
	 * 微信授权统一回调地址
	 *
	 * @return Ambigous
	 */
	public function getBaseoauth()
	{
		/***如果oauth_code失效就重新进行授权 modify by duan**/
		try {
			$app = $this->return_base_app();

			$oauth = $app->oauth;

			$user = $oauth->user();
			$user_info = $user->toArray();

			$wechat_user = WechatUser::where("openid", $user_info['id'])->first();

			if (null != $wechat_user) {
				$wechat_user = $wechat_user->toArray();
				$wechat_user["id"] = $wechat_user["openid"];
				$_SESSION['wechat_user'] = $wechat_user;
			} else {
				$_SESSION['wechat_user'] = $user_info;
			}
			$targetUrl = empty($_SESSION['target_url']) ? "http://" . $_SERVER['HTTP_HOST'] . "/information_list.html" : $_SESSION['target_url'];

			return redirect($targetUrl);
		} catch (\Exception $e) {
			$this->do_base_oauth();

			$targetUrl = empty($_SESSION['target_url']) ? "http://" . $_SERVER['HTTP_HOST'] . "/information_list.html" : $_SESSION['target_url'];

			return redirect($targetUrl);
		}
	}

	public function postOrderpayback()
	{
		$where = [
			'vote_id' => $this->requestData['vote_id'],
			'type' => $this->requestData['type'],
			'openid' => $_SESSION['wechat_user']['id'],
			'nickname' => $_SESSION['wechat_user']['nickname'],//duan
		];
		$params = [
			'files' => $this->requestData['files'],
			'description' => $this->requestData['description']
		];
		$vote = OrderPayBack::where($where)->get()->first();
		$result = 0;
		if ($vote) {
			$result = $vote->update($params);
		} else {
			$result = OrderPayBack::create($where + $params);
		}
		if ($result) {
			//拼接发给管理员的消息并发送消息给管理员 add by duan
			$param = ServiceNews::getParamForMessage(array_merge($params, $where));
			$messageid = ServiceNews::sendMessage($this->return_app(), $param);

			$result = [
				'code' => 200,
				'msg' => '申请成功，请等待审核！'
			];
		} else {
			$result = [
				'code' => 100,
				'msg' => '程序开了个小差...'
			];
		}

		return response()->json($result);
	}

	public function postDelete()
	{
		$id = $this->requestData['id'];
		$openid = $_SESSION['wechat_user']['id'];
		$value = $this->requestData['value'];
		if ($this->_is_admin()) {
			$result = [
				'code' => 200,
				'msg' => ''
			];
			if ($value) {
				$tmp = null;
				if ($this->requestData['type'] == 'news') {
					$tmp = News::withTrashed()->where('id', $id);
				} else if ($this->requestData['type'] == 'answer') {
					$tmp = Answer::withTrashed()->where('id', $id);
				} else if ($this->requestData['type'] == 'question') {
					$tmp = Question::withTrashed()->where('id', $id);
				}
				Log::info($tmp);
				$tmp = $tmp->first();
				if ($tmp) {
					if ($tmp->restore() || $tmp->trashed()) {
						$result['msg'] = '删除信息';
					}
				}
			} else {
				$tmp = null;
				if ($this->requestData['type'] == 'news') {
					$tmp = News::withTrashed()->where('id', $id);
				} else if ($this->requestData['type'] == 'answer') {
					$tmp = Answer::withTrashed()->where('id', $id);
				} else if ($this->requestData['type'] == 'question') {
					$tmp = Question::withTrashed()->where('id', $id);
				}
				$tmp = $tmp->first();
				Log::info($tmp);
				if ($tmp) {
					if ($tmp->delete() || $tmp->trashed()) {
						$result['msg'] = '恢复信息';
					}
				}
			}
			if (!$result['msg']) {
				$result = [
					'code' => 100,
					'msg' => '操作失败'
				];
			}

			return response()->json($result);
		} else {
			// 用户发起删除问题操作
			if ($this->requestData['type'] == 'question') {
				$question = Question::where(['id' => $id, 'openid' => $openid])->first();
				if ($question) {
					Log::info($id);
					$a = Answer::where(['q_id' => $id])->count();
					if ($a > 0) {
						// 提示无法删除
						$result = [
							'code' => 100,
							'msg' => '问题已有人参与回答，不能删除！'
						];

						return response()->json($result);
					} else {
						// 直接删除
						$question->delete();
						$result = [
							'code' => 200,
							'msg' => '删除成功'
						];

						return response()->json($result);
					}
				}
			} else if ($this->requestData['type'] == 'answer') {
				$answer = Answer::where(['id' => $id, 'openid' => $openid])->first();
				if ($answer) {
					$pay_count = Orders::where(['pay_id' => $id, 'type' => 'answer', 'pay_status' => 'PAY_SUCCESS'])->count();
					if ($pay_count > 0) {
						$result = [
							'code' => 100,
							'msg' => '回答已经有人支付，无法删除！'
						];

						return response()->json($result);
					} else {
						// 直接删除该回答
						$answer->delete();
						$result = [
							'code' => 200,
							'msg' => '删除成功'
						];

						return response()->json($result);
					}
				}
			}
		}
		$result = [
			'code' => 100,
			'msg' => '操作失败'
		];

		return response()->json($result);
	}

	public function postFakedelete()
	{
		$id = $this->requestData['id'];
		$openid = $_SESSION['wechat_user']['id'];
		$result = [
			'code' => 200,
			'msg' => '隐藏信息'
		];
		if ($this->requestData['is_display'] == '0') {
			$result = [
				'code' => 201,
				'msg' => '不再隐藏'
			];
		}
		if ($this->requestData['type'] == 'news') {
			if ($this->_is_admin()) {
				$count = News::withTrashed()->where('id', $id)->update(['is_display' => $this->requestData['is_display']]);
			} else {
				$result = [
					'code' => 100,
					'msg' => '更新失败'
				];
			}
		} else if ($this->requestData['type'] == 'answer') {
			Log::info('update answer ' . $id . ' with is_display : ' . $this->requestData['is_display']);
			Answer::withTrashed()->where('id', $id)->update(['is_display' => $this->requestData['is_display']]);
		} else if ($this->requestData['type'] == 'question') {
			Log::info('update question ' . $id . ' with is_display : ' . $this->requestData['is_display']);
			Question::withTrashed()->where('id', $id)->update(['is_display' => $this->requestData['is_display']]);
		}

		return response()->json($result);
	}

	public function postDeletenew()
	{
		$result = [
			'code' => 200,
			'msg' => '删除成功'
		];
		if ($this->_is_admin) {
			$where = [
				'id' => $this->requestData['id']
			];
			if (!News::where($where)->update(['is_delete' => '1'])) {
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
			if (!News::where($where)->update(['is_delete' => '1'])) {
				$result = [
					'code' => 100,
					'msg' => '删除失败'
				];
			}
		}

		return response()->json($result);
	}

	/**
	 * 分享到朋友/圈
	 *
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function postShare()
	{
		$url = $this->requestData['url'];

		$app = $this->return_app();
		$js = $app->js;
		$js->setUrl($url);
		$apis = array(
					'onMenuShareAppMessage', 
					'onMenuShareTimeline',
					'startRecord',
					'stopRecord',
					'onVoiceRecordEnd',
					'playVoice',
					'pauseVoice',
					'stopVoice',
					'onVoicePlayEnd',
					'uploadVoice',
					'downloadVoice',
					'translateVoice'
					);
		$result = $js->config($apis, $debug = false, $beta = false, false);

		return response()->json($result);
	}

	/**
	 * 上传到阿里云
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function postWechatmedia(){
		$media_id = $this->requestData['media_id'];
		$app = $this->return_app();
		$temporary = $app->material_temporary;

		$file_name = $temporary->download($media_id, public_path("uploadchch"));

		$outossurl = config('services.aliyun.OutOss');
		$Bucket = config('services.aliyun.Bucket');
		try{
			//上传到阿里云
			$oss = ServiceUpload::boot();
			// 设置 Bucket
			$oss = $oss->setBucket($Bucket);
			// 两个参数：资源名称、文件内容
			$oss->multiuploadFile($file_name, public_path("uploadchch")."/".$file_name);

			//转码作业
			//$guid = uniqid().time();
			//$url = ServiceFile::submitJob($file_name,$guid,"voice");
			$url = $outossurl.$file_name;
			$result = ["code"=>200,"media_url"=>$url];
		}catch (\Exception $e){
			$result = ["code"=>100,"msg"=>$e->getMessage()];
		}

		return response()->json($result);
	}
}
