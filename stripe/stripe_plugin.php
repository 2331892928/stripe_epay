<?php
/**
 * auther: AMEN
 * url: https://www.ymypay.cn
 * date:2023/7/6
 */

class stripe_plugin
{
	static public $info = [
		'name'        => 'stripe', //支付插件英文名称，需和目录名称一致，不能有重复
		'showname'    => 'stripe支付', //支付插件显示名称
		'author'      => 'AMEN', //支付插件作者
		'link'        => 'https://www.ymypay.cn/', //支付插件作者链接
		'types'       => ['alipay','wxpay'], //支付插件支持的支付方式，可选的有alipay,qqpay,wxpay,bank
		'inputs' => [ //支付插件要求传入的参数以及参数显示名称，可选的有appid,appkey,appsecret,appurl,appmchid
            'appid' => [
                'name' => '产品id',
                'type' => 'input',
                'note' => '产品id',
            ],
			'appkey' => [
				'name' => '私钥',
				'type' => 'input',
				'note' => '真实模式私钥，一般为：sk_live_xxxxxx，在stripe的后台首页即可看见',
			],
            'appurl' => [
                'name' => '镜像站',
                'type' => 'input',
                'note' => '镜像站，格式：https://xx，为空则是https://checkout.stripe.com',
            ],
            'appsecret' => [
                'name' => '汇率',
                'type' => 'input',
                'note' => '汇率，如：1.08',
            ]
		],
		'select' => null,
		'note' => '<p>此插件为stripe支付，stripe官网：https://stripe.com<p>'
            .
            '<p>镜像站：默认空为官方链接，由于stripe国外，防止用户无法访问(不是你机器访问，是用户访问，用户需要访问链接前往支付地址)，你可以反代或镜像站：https://checkout.stripe.com  也可以自定义域;便不用反代，见：https://stripe.com/docs/payments/checkout/custom-domains</p>'
            .
            '<p>产品ID：在stripe后台添加产品，产品价格为：0.01，货币随意，一次性，只能添加一种价格并设置默认价格。添加完成后进入产品即可看见产品id</p>'
            .
            '<p>汇率：默认1.08。易只支持人民币，所以需要转换货币汇率。这里输入1人民币=多少你的产品货币。比如你的产品货币是HKD，那么1人民币=1.08港币，这里填1.08</p>'
            .
            '更多使用教程：https://blog.ymypay.cn/index.php/2023/07/06/ypay_stripe/'
        ,
		'bindwxmp' => false, //是否支持绑定微信公众号
		'bindwxa' => false, //是否支持绑定微信小程序
	];

	static public function submit(){
		global $siteurl, $channel, $order, $sitename, $conf;

        try {
            $code_url = self::addOrder();
            if ($code_url['code'] != 200){
                return ['type'=>'error','msg'=>$code_url['msg']];
            }
        }catch (Error $e){
            return ['type'=>'error','msg'=>$e->getMessage()];
        }
        return ['type'=>'jump','url'=>$code_url['msg']];


	}

	static public function mapi(){
		global $siteurl, $channel, $order, $device, $mdevice;
        try {
            $code_url = self::addOrder();
            if ($code_url['code'] != 200){
                return ['type'=>'error','msg'=>$code_url['msg']];
            }
        }catch (Error $e){
            return ['type'=>'error','msg'=>$e->getMessage()];
        }
        return ['type'=>'jump','url'=>$code_url['msg']];
	}

	static private function make_sign($param, $key){
		ksort($param);
		$signstr = '';
	
		foreach($param as $k => $v){
			if($k != "sign" && $v!=''){
				$signstr .= $k.'='.$v.'&';
			}
		}
		$signstr .= 'key='.$key;
		$sign = strtoupper(md5($signstr));
		return $sign;
	}
//汇率接口
    private function getExchangeRate($from,$to)
    {
        //获得页面代码
        $data = get_curl("https://api.it120.cc/gooking/forex/rate?fromCode='.$from.'&toCode=".$to);
        $a = json_decode($data,true);
        if (!$a){
            return false;
        } else {
            return $a;
        }
    }
    static private function check($id){
        require_once(PAY_ROOT."inc/common.php");

        try{
            $output = $Stripe_Class->checkout->sessions->retrieve(
                $id
            );
            return ['code'=>200,'msg'=>$output];
        }catch (Error $e){
            return ['code'=>100,'msg'=>$e->getMessage()];
        }

    }
	//通用创建订单
	static private function addOrder(){
		global $channel, $order, $ordername, $conf, $clientip, $siteurl, $DB;
        require_once(PAY_ROOT."inc/common.php");
//        $data = (new stripe_plugin)->getExchangeRate($from,$stripe_config['appsecret']);
//        if ($data===false){
//            return ['type'=>'error','msg'=>'获取汇率错误'];
//        }
//        $hl = $data['rate'];
        $hl = $stripe_config['appsecret'];
        if ($hl == NULL){
            $hl = "1.08";
        }
        // 先计算一人民币需要多少数量
        $num = round($hl / 0.01);
        // 再计算order人民币数量
        $num = $num * $order['money'];
        $pay_type = $order['typename'];
        if ($pay_type=='alipay'){
            $pay_type = 'alipay';
        } elseif($pay_type=='wxpay'){
            $pay_type = 'wechat_pay';
        } else{
            return ['code'=>100,'msg'=>'支付方式错误，无此支付方式：'.$pay_type];
        }

        $output = $Stripe_Class->checkout->sessions->create([
            'locale'=>'auto',
            'success_url' => $siteurl.'pay/return/'.TRADE_NO.'/',
            'cancel_url' => $order['return_url'],
            'payment_method_types' => [$pay_type],  // 选择支付方式
            'payment_method_options'=>[
                'wechat_pay'=>['client'=>'web']
            ],
            'line_items' => [
                [
                    'price' => $stripe_config['appid'],
                    'quantity' => $num,
                ],
            ],
            'mode' => 'payment',
        ]);
        if (!$output){
            return ['code'=>100,'msg'=>'请求stripe支付失败'];
        }
        // 是否有自定义域，有则自定义，否则如果有镜像
        $code_url = $output['url'];
        if ($stripe_config['appurl']!=NULL){ //如果有镜像
            $th_count = 1;
            $code_url = str_ireplace("https://checkout.stripe.com",$stripe_config['appurl'],$output['url'],$th_count);
        }

        // 将id附加到写到数据库
        $trade_no = $order['trade_no'];
        $api_tn = $output['id'];
        $DB->exec("update pre_order set api_trade_no='$api_tn' where trade_no='$trade_no'");
        return ['code'=>200,'msg'=>$code_url];
	}


	//异步回调
	static public function notify(){
        global $channel, $DB, $order;
        // 处理stripe与易支付直接联系后跳回对接的return_url
        $output = self::check($order['api_trade_no']);
        if ($output['code']!=200){
            return ['type'=>'html','data'=>'FAIL'];
        }
        if ($output['msg']['status']=='complete'){
            // 完成订单
            processNotify($order, $output['msg']['id']);
            return ['type'=>'html','data'=>'SUCCESS'];
        } else {
            return ['type'=>'html','data'=>'FAIL'];
        }
	}

	//同步回调
	static public function return(){
        global $channel, $DB, $order;
        // 处理stripe与易支付直接联系后跳回对接的return_url
        $output = self::check($order['api_trade_no']);
        if ($output['code']!=200){
            return ['type'=>'page','page'=>'error'];
        }
        if ($output['msg']['status']=='expired'){ // 订单超时
            return ['type'=>'page','page'=>'error'];
        }
        if ($output['msg']['status']=='complete'){
            // 完成订单
            processReturn($order, $output['msg']['id']);
        }
		return ['type'=>'page','page'=>'return'];
	}
}