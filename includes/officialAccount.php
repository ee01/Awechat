<?php
use EasyWeChat\Factory;
use EasyWeChat\OfficialAccount\Application as officialAccount;

require_once AWECHAT_PLUGIN_DIR . 'vendor/autoload.php';

class Awechat_oa extends officialAccount {
	var $account_type;
	var $account_type_map;
	var $account_type_name;
	const UNAUTH_SUBSCRIPTION_ACCOUNT = 'unauthorized_subscription_account';
	const AUTH_SUBSCRIPTION_ACCOUNT = 'authorized_subscription_account';
	const UNAUTH_SERVICE_ACCOUNT = 'unauthorized_service_account';
	const AUTH_SERVICE_ACCOUNT = 'authorized_service_account';

	public function __construct($config) {
		parent::__construct($config);
		$this->account_type_map = array(
			self::UNAUTH_SUBSCRIPTION_ACCOUNT => __('Unauth Subscription Account', 'Awechat'),
			self::AUTH_SUBSCRIPTION_ACCOUNT => __('Auth Subscription Account', 'Awechat'),
			self::UNAUTH_SERVICE_ACCOUNT => __('Unauth Service Account', 'Awechat'),
			self::AUTH_SERVICE_ACCOUNT => __('Auth Service Account', 'Awechat')
		);
	}

	public function check_account_type() {
		if ($this->account_type) return $this->account_type;
		$this->account_type = self::AUTH_SERVICE_ACCOUNT;

		$stats = $this->material->stats();
		if (array_key_exists('errcode', $stats) && $stats['errcode'] == 40164) {
			// not in whitelist
			return $this->account_type = false;
		}
		if (array_key_exists('errcode', $stats) && $stats['errcode'] == 48001) {
			return $this->account_type = self::UNAUTH_SERVICE_ACCOUNT;
		}

		$menu = $this->menu->list();
		if (array_key_exists('errcode', $menu) && $menu['errcode'] == 48001) {
			return $this->account_type = self::UNAUTH_SUBSCRIPTION_ACCOUNT;
		}

		$shortUrl = $app->url->shorten('https://eexx.me');
		if (array_key_exists('errcode', $shortUrl) && $shortUrl['errcode'] == 48001) {
			return $this->account_type = self::AUTH_SUBSCRIPTION_ACCOUNT;
		}
	}

	public function get_account_type_name() {
		return $this->account_type_name = $this->account_type_map[ $this->account_type ];
	}
}