<?php
/*
Plugin Name: A Wechat Official Accout Connection
Plugin URI: http://IT.eexx.me/
Description: 微信连接：同步文章到微信公众账号的素材库。
Author: Esone
Version: 2.0
Author URI: http://IT.eexx.me
*/

define( 'AWECHAT_PLUGIN_DIR', dirname( __FILE__ ) . '/' );
define( 'AWECHAT_PLUGIN_URL', plugins_url() . '/' . plugin_basename(dirname(__FILE__)) );
define( 'AWECHAT_PLUGIN_OPTIONNAME', 'Awechat_setting' );

define( 'AWECHAT_DEFAULT_ARTICLE_IMAGE', AWECHAT_PLUGIN_URL.'/assets/default_acticle_image.png' );
define( 'AWECHAT_DEFAULT_FOLLOW_IMAGE', AWECHAT_PLUGIN_URL.'/assets/follow_tips.gif' );
define( 'AWECHAT_DEFAULT_READSOURCE_IMAGE', AWECHAT_PLUGIN_URL.'/assets/read_source.gif' );

// Activation Limitation
register_activation_hook( __FILE__, 'Awechat_active' );

// Localization
add_action('plugins_loaded', 'Awechat_load_languages_file');
// Add settings link on plugin page
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'Awechat_plugin_settings_link' );
add_action( 'wp_ajax_Awechat_reset', 'Awechat_reset' );

// Sync up post to Wechat OA meterials
require AWECHAT_PLUGIN_DIR . 'includes/postsync.php';
new Awechat_postsync();

// Settings
require AWECHAT_PLUGIN_DIR . 'includes/setting.php';
new Awechat_setting();


function Awechat_active () {
	if ( version_compare(PHP_VERSION, '7.0', '<') ) {
		deactivate_plugins( plugin_basename( __FILE__ ) );
		wp_die( 'This plugin requires PHP 7.0 or higher!' );
	}
	// Do activate Stuff now.
	Awechat_setting::Activation();
}

function Awechat_load_languages_file(){
	load_plugin_textdomain( 'Awechat', false, plugin_basename(dirname(__FILE__)) . '/languages/' );
}

function Awechat_plugin_settings_link($links) {
	$settings_link = '<a id="Awechat_reset_button" href="' . admin_url('admin-ajax.php') . '?action=Awechat_reset">'.__('Reset','Awechat').'</a>';
	$settings_link .= '<script>jQuery("#Awechat_reset_button").click(function(){
		if (!confirm("'.__('Reset to default setting?','Awechat').'")) return false;
		jQuery.get($(this).attr("href"), function(json){
			json = eval("("+json+")");
			if (!json.err) {
				alert("'.__('Reset Successfully!','Awechat').'");
			}
			console.log(json);
		})
		return false;
	})</script>';
	array_unshift($links, $settings_link);
	return $links;
}
function Awechat_reset() {
	delete_option( AWECHAT_PLUGIN_OPTIONNAME );
	Awechat_setting::Activation();
	echo json_encode(array(
		'err' => 0
	));
	wp_die();
}
