<?php

class Awechat_notification {

	public function __construct() {
		// Send message to fans when publishing
		add_action('publish_post', array($this, 'action_publish_post'), 10, 2);
	}

	public function action_publish_post($post_ID, $post) {
		echo 'action_publish_post';
	}
}