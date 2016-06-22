<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/6/6
 * Time: 15:52
 */

namespace App;


use Illuminate\Database\Eloquent\Model;

class UploadFile extends Model
{
	public $timestamps = false;
	protected $fillable = ["guid", "filetype","openid","filename","created","original_filename"];
	protected $table = 'upload_file';
}