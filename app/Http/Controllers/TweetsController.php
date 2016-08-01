<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/8/1
 * Time: 17:12
 */

namespace App\Http\Controllers;


use App\Tweets;
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

	public function postTweets(){
		$result = Tweets::create($this->requestData + $this->returnUser());
		$this->output($result);
	}

	/**
	 * 获取我发布的所有内容
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function getMylist(){
		//if (Request::input('page')) {
			$page = Request::input("page",1);
			$pagesize = 10;

			$start = ($page - 1) * $pagesize;

			$openid = 'o4utmvwt85XSNdP3SFKsJDXI8jas';
				//$_SESSION['wechat_user']['id'];

			//获取用户的news
			$sql = "select openid,avatar,nickname,content,created_at,updated_at,updated_at,is_display,position from news as n where is_delete=0 and openid='{$openid}'";
			$sql .= " union all select openid,avatar,nickname,content,created_at,updated_at,updated_at,is_display,position from tweets as tw where is_delete=0 and openid='{$openid}'";
			$sql .= " union all select openid,avatar,nickname,content,created_at,updated_at,updated_at,is_display,position from questions as qu where is_delete=0 and openid='{$openid}'";
			$sql .= " order by created_at desc limit {$start},{$pagesize}";

			$data = DB::select($sql);
			var_dump($data);die;
			$list = News::where($where)
				->orderBy("id", "desc")
				->skip($start)->take($pagesize)
				->get()
				->toArray();


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
		//}
	}
}