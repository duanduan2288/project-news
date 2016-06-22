<?php
namespace App\Http\Controllers;

use App\Services\ServiceNews;
use EasyWeChat\Payment\Order;

use App\NewsOrder;

use App\WeChatPayNotify;
use Log;


class OrderController extends Controller
{

    public function postPay()
    {
        $app = $this->return_app();

        $payment = $app->payment;

        $order = $this->return_order_info();

        $result = $payment->prepare($order);

        $prepareId = $result->prepay_id;

        $json = $payment->configForPayment($prepareId);

        $data = json_decode($json,true);

        return $this->output($data);

    }

    public function postNotify()
    {
        $this->requestData = (array) simplexml_load_string(file_get_contents("php://input"), 'SimpleXMLElement', LIBXML_NOCDATA);

        file_put_contents('/tmp/pay.php', "<?php\nreturn " . var_export($this->requestData, true) . ";\n?>", FILE_APPEND);

        $app = $this->return_app();

        $response = $app->payment->handleNotify(function ($notify, $successful) use($app)
        {
            NewsOrder::where('out_trade_no', $notify->out_trade_no)->update([
                'pay_status' => 'PAY_SUCCESS'
            ]);

            WeChatPayNotify::create(json_decode($notify,true));
            //发送消息给管理员
            $param = ServiceNews::getParamForPay($notify->out_trade_no);
            ServiceNews::sendMessage($app,$param);
            return true;
        });

        return $response;
    }

    public function return_order_info()
    {
      Log::info("return_order_info");
      Log::info($this->requestData);
        $attributes = [
            'body' => '打开',
            'detail' => '打开',
            'out_trade_no' => $this->return_order_id(),
            'total_fee' => floatval($this->requestData['total_fee']) ? floatval($this->requestData['total_fee']) * 100 : 1,
            'notify_url' => 'http://' . $_SERVER['HTTP_HOST'] . '/order/notify',
            'trade_type' => 'JSAPI',
            'openid' =>  isset($_SESSION['wechat_user']['id']) ? $_SESSION['wechat_user']['id'] : ''
        ];

        $order_info['type'] = $this->requestData['type'];

        $order_info['pay_id'] = $this->requestData['id'];

        $order_info['nickname'] = $_SESSION['wechat_user']['nickname'];

        $order_info['avatar'] = $_SESSION['wechat_user']['avatar'];

        NewsOrder::create($attributes + $order_info);

        return new Order($attributes);
    }

    /**
     * 生成唯一订单号
     *
     * @return string
     */
    protected function return_order_id()
    {
        $orderMaxId = NewsOrder::count();

        $l = strlen($orderMaxId);

        $rand = '';

        for ($i = 0; $i < 5 - $l; $i++)
        {
            $rand .= mt_rand(0, 9);
        }

        return $rand . $orderMaxId . date('nsd');
    }
}
