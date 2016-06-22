<?php
namespace App\Http\Controllers;

use Log;

/**
 * @author moyang
 * @date 2016-5-18 11:47:25
 */

class WxtestClass {

    protected $values = array();

    const CURL_PROXY_HOST = "101.200.149.42";//"10.152.18.220";
    const CURL_PROXY_PORT = 0;//8080;

    // key文件地址
    const SSLCERT_PATH = '/cert/apiclient_cert.pem';
    const SSLKEY_PATH = '/cert/apiclient_key.pem';

    const APPID = 'wxb56787ede27d5697';
    const MCHID = '1318327901';
    const KEY = 'DcSpPJIFVpAlx48Z9QSp33Y4xMWO4VLW';
    const APPSECRET = '8c2c56b3155e8a6729c5338aaace03c4';
    const URL = "https://api.mch.weixin.qq.com/mmpaymkttransfers/promotion/transfers";

        public function actionIndex($openid) {

        $this->values['mch_appid']    =   self::APPID; // 公众号appid
        $this->values['mchid']        =   self::MCHID; // 微信支付分配的商户号
        $this->values['nonce_str']    =   $this->getNonceStr(); // 随机字符串
        $this->values['partner_trade_no'] =   self::MCHID.date('YmdHis'); // 商户订单号
        $this->values['openid']       =   $openid; // 用户openid,公众号授权获取
        $this->values['check_name']   =   'NO_CHECK'; // 校验用户姓名选项
        $this->values['amount']       =   1; // 金额
        $this->values['desc']         =   'test'; // 描述
        $this->values['spbill_create_ip']   =   $_SERVER['REMOTE_ADDR']; // ip

        $this->SetSign();
        Log::info($this->values);
        $xml = $this->ToXml();
//        var_dump($xml);die;
        $result = $this->postXmlCurl($xml, self::URL, true);
        Log::info($result);
        return result;
    }



   /**
    *
    * 产生随机字符串，不长于32位
    * @param int $length
    * @return 产生的随机字符串
    */
   private function getNonceStr($length = 32)
   {
           $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
           $str ="";
           for ( $i = 0; $i < $length; $i++ )  {
                   $str .= substr($chars, mt_rand(0, strlen($chars)-1), 1);
           }
           return $str;
   }

   /**
    * 设置签名，详见签名生成算法
    * @param string $value
    **/
    private function SetSign()
    {
            $sign = $this->MakeSign();
            $this->values['sign'] = $sign;
            return $sign;
    }

    /**
     * 生成签名
     * @return 签名，本函数不覆盖sign成员变量，如要设置签名需要调用SetSign方法赋值
     */
    private function MakeSign()
    {
           //签名步骤一：按字典序排序参数
           ksort($this->values);
           $string = $this->ToUrlParams();
           //签名步骤二：在string后加入KEY
           $string = $string . "&key=".self::KEY;
           //签名步骤三：MD5加密
           $string = md5($string);
           //签名步骤四：所有字符转为大写
           $result = strtoupper($string);
           return $result;
    }

    /**
     * 格式化参数格式化成url参数
     */
    private function ToUrlParams()
    {
        $buff = "";
        foreach ($this->values as $k => $v)
        {
                if($k != "sign" && $v != "" && !is_array($v)){
                        $buff .= $k . "=" . $v . "&";
                }
        }

        $buff = trim($buff, "&");
        return $buff;
    }

   /**
    * 输出xml字符
    * @throws WxPayException
    **/
    private function ToXml()
    {

        $xml = "<xml>";
        foreach ($this->values as $key=>$val)
        {
            if (is_numeric($val)){
                    $xml.="<".$key.">".$val."</".$key.">";
            }else{
                    $xml.="<".$key."><![CDATA[".$val."]]></".$key.">";
            }
        }
        $xml.="</xml>";
        return $xml;
    }

    /**
    * 以post方式提交xml到对应的接口url
    *
    * @param string $xml  需要post的xml数据
    * @param string $url  url
    * @param bool $useCert 是否需要证书，默认不需要
    * @param int $second   url执行超时时间，默认30s
    * @throws WxPayException
    */
    private function postXmlCurl($xml, $url, $useCert = false, $second = 30)
    {
           $ch = curl_init();
           //设置超时
           curl_setopt($ch, CURLOPT_TIMEOUT, $second);

           //如果有配置代理这里就设置代理
           if(self::CURL_PROXY_HOST != "0.0.0.0"
                   && self::CURL_PROXY_PORT != 0){
                   curl_setopt($ch,CURLOPT_PROXY, self::CURL_PROXY_HOST);
                   curl_setopt($ch,CURLOPT_PROXYPORT, self::CURL_PROXY_PORT);
           }
           curl_setopt($ch,CURLOPT_URL, $url);
           curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,TRUE);
           curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,2);//严格校验
           //设置header
           curl_setopt($ch, CURLOPT_HEADER, FALSE);
           //要求结果为字符串且输出到屏幕上
           curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

           if($useCert == true){
                   //设置证书
                   //使用证书：cert 与 key 分别属于两个.pem文件
                   curl_setopt($ch,CURLOPT_SSLCERTTYPE,'PEM');
                   curl_setopt($ch,CURLOPT_SSLCERT, self::SSLCERT_PATH);
                   curl_setopt($ch,CURLOPT_SSLKEYTYPE,'PEM');
                   curl_setopt($ch,CURLOPT_SSLKEY, self::SSLKEY_PATH);
           }
           //post提交方式
           curl_setopt($ch, CURLOPT_POST, TRUE);
           curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
           //运行curl
           $data = curl_exec($ch);
           //返回结果
           if($data){
                   curl_close($ch);
                   return $data;
           } else {
                   $error = curl_errno($ch);
                   curl_close($ch);
                   throw new Exception("curl出错，错误码:$error");
           }
    }
}
