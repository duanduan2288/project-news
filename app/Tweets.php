<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/8/1
 * Time: 17:07
 */
namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tweets extends Model
{
	use SoftDeletes;
	protected $guarded = ['id'];
	/**
	 * 需要被转换成日期的属性。
	 *
	 * @var array
	 */
	protected $dates = ['deleted_at'];
}