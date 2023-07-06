<?php
require_once(PAY_ROOT.'inc/stripe/init.php');
$stripe_config = [
	'appid' => $channel['appid'],

	'appkey' => $channel['appkey'],

	'appurl' => $channel['appurl'],

	'appsecret' => $channel['appsecret'],

];
$Stripe_Class = new \Stripe\StripeClient($stripe_config['appkey']);
