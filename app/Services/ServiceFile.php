<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/6/10 0010
 * Time: 下午 1:11
 */

namespace App\Services;

include_once base_path("vendor").'/aliyunopenapi/aliyun-php-sdk-core/Config.php';
use Mts\Request\V20140618 as Mts;
class ServiceFile
{
	/**
	 * 获取视频时长
	 * @param $filepath
	 * @return bool
	 */
	public static function getDuration($filepath){
		$info = shell_exec("/usr/local/ffmpeg/bin/ffprobe -v quiet -print_format json -show_format -show_streams {$filepath} 2>&1");
		if(null!=$info && !empty($info)){
			$info = json_decode($info,true);
			$duration = $info["duration"];
			if($duration<=600){
				return true;
			}
		}
		return false;
	}


	/**
	 * 添加转码任务
	 * @param $file_name
	 * @param $guid
	 * @return bool|string
	 */
	public static function submitJob($file_name,$guid){
		try{
			$Outoss = config('services.aliyun.OutOss');
			$AccessKeyId = config('services.aliyun.AccessKeyId');
			$AccessKeySecret = config('services.aliyun.AccessKeySecret');
			$Bucket = config('services.aliyun.Bucket');
			$iClientProfile = \DefaultProfile::getProfile("cn-hangzhou", $AccessKeyId, $AccessKeySecret);
			$client = new \DefaultAcsClient($iClientProfile);
			$input = [
				"Bucket"=>$Bucket,
				"Location"=>"oss-cn-hangzhou",
				"Object"=>urlencode($file_name)
			];
			$OutputObject = $guid;
			$outputs = [
				[
					"OutputObject"=>urlencode($OutputObject),
					"Location"=>"oss-cn-hangzhou",
					"TemplateId"=>config('services.aliyun.TemplateId')
				]
			];
			$request = new Mts\SubmitJobsRequest();
			$request->SetPipelineId(config('services.aliyun.PipelineId'));
			$request->setInput(json_encode($input));
			$request->setOutputBucket($Bucket);
			$request->setOutputs(json_encode($outputs));
			$client->getAcsResponse($request);
			return $Outoss.$guid.".m3u8";
		}catch (\Exception $e){
			return false;
		}
	}

	/**
	 * 截图作业
	 * @param $file_name
	 * @param $guid
	 * @return bool|string
	 */
	public static function submitSnapshotJob($file_name,$guid){
		try{
			$ImgOss = config('services.aliyun.ImgOss');
			$AccessKeyId = config('services.aliyun.AccessKeyId');
			$AccessKeySecret = config('services.aliyun.AccessKeySecret');
			$Bucket = config('services.aliyun.Bucket');
			$iClientProfile = \DefaultProfile::getProfile("cn-hangzhou", $AccessKeyId, $AccessKeySecret);
			$client = new \DefaultAcsClient($iClientProfile);
			$input = [
					"Bucket"=>$Bucket,
					"Location"=>"oss-cn-hangzhou",
					"Object"=>urlencode($file_name)
			];
			$OutputObject = $guid.".jpg";
			$outputs = [
					"OutputFile"=>[
							"Object"=>urlencode($OutputObject),
							"Location"=>"oss-cn-hangzhou",
							"Bucket"=>$Bucket
					],
					"Time"=> "5"
			];
			$request = new Mts\SubmitSnapshotJobRequest();
			$request->SetPipelineId(config('services.aliyun.PipelineId'));
			$request->setInput(json_encode($input));
			$request->setSnapshotConfig(json_encode($outputs));
			$client->getAcsResponse($request);
			return $ImgOss.$OutputObject."!small";
		}catch (\Exception $e){
			return "";
		}
	}
}