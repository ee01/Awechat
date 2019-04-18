<?php
use EasyWeChat\Factory;

class Awechat_base {
	var $wechat;
	var $account_type;
	const UNAUTH_SUBSCRIPTION_ACCOUNT = 'unauthorized_subscription_account';
	const AUTH_SUBSCRIPTION_ACCOUNT = 'authorized_subscription_account';
	const UNAUTH_SERVICE_ACCOUNT = 'unauthorized_service_account';
	const AUTH_SERVICE_ACCOUNT = 'authorized_service_account';

	public function __construct() {
		$config = [
			// 敏捷加油站
			'app_id' => 'wx8d528d8d23f1acc7',
			'secret' => '89a43064ac0929fd918787c5a5cbbaf7',
			// ScrumMaster
			// 'app_id' => 'wx3e5dc0a4295fac29',
			// 'secret' => 'b68b32531aa7cb92f72fd26ef434c957',
		
			// 指定 API 调用返回结果的类型：array(default)/collection/object/raw/自定义类名
			'response_type' => 'array',
		
			//...
		];
		$this->wechat = Factory::officialAccount($config);
	}

	public function check_account_type() {
		if ($this->account_type) return $this->account_type;
		$this->account_type = self::AUTH_SERVICE_ACCOUNT;

		$stats = $this->wechat->material->stats();
		if (array_key_exists('errcode', $stats) && $stats['errcode'] == 40164) {
			// not in whitelist
			return $this->account_type = false;
		}
		if (array_key_exists('errcode', $stats) && $stats['errcode'] == 48001) {
			return $this->account_type = self::UNAUTH_SERVICE_ACCOUNT;
		}

		$menu = $this->wechat->menu->list();
		if (array_key_exists('errcode', $menu) && $menu['errcode'] == 48001) {
			return $this->account_type = self::UNAUTH_SUBSCRIPTION_ACCOUNT;
		}

		$shortUrl = $app->url->shorten('https://eexx.me');
		if (array_key_exists('errcode', $shortUrl) && $shortUrl['errcode'] == 48001) {
			return $this->account_type = self::AUTH_SUBSCRIPTION_ACCOUNT;
		}
	}
}