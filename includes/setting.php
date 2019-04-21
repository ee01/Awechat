<?php

class Awechat_setting {
	static public $option_name = AWECHAT_PLUGIN_OPTIONNAME;

	public function __construct() {
        add_action( 'admin_init', array( $this, 'settings_init' ));
        add_action( 'admin_menu', array( $this, 'add_plugin_page' ));
        add_action( 'update_option_'.self::$option_name, array( $this, 'admin_setting_action' ), 10, 3);
        add_action( 'admin_menu', array( $this, 'create_wechat_box' ));
		add_action( 'manage_post_posts_columns', array( $this, 'posts_add_column' ) );
		add_action( 'manage_post_posts_custom_column', array( $this, 'posts_render_column' ), 10, 2 );
	}

	static function Activation() {
		$options = get_option( self::$option_name );
		if ($options) return false;
		$options = array(
			'article_cover_image' => AWECHAT_DEFAULT_ARTICLE_IMAGE,
			'follow_tips_image' => AWECHAT_DEFAULT_FOLLOW_IMAGE,
			'read_source_tips_image' => AWECHAT_DEFAULT_READSOURCE_IMAGE,
			'post_tail' => '
<section class="" style="box-sizing: border-box;margin-top:30px;"><section class="" style="box-sizing: border-box;"><section class="" style="display: inline-block; vertical-align: top; width: 40%; box-sizing: border-box;"><section class="" style="box-sizing: border-box;" powered-by="xiumi.us"><section class="" style="margin: 0.5em 0px; box-sizing: border-box;"><section style="border-top: 1px dotted rgb(24, 23, 21); box-sizing: border-box;" class=""></section></section></section></section><section class="" style="display: inline-block; vertical-align: top; width: 20%; box-sizing: border-box;"><section class="" style="box-sizing: border-box;" powered-by="xiumi.us"><section class="" style="box-sizing: border-box;"><section class="" style="text-align: center; color: rgb(24, 23, 21); font-size: 12px; box-sizing: border-box;"><p style="margin: 0px; padding: 0px; box-sizing: border-box;">END</p></section></section></section></section><section class="" style="display: inline-block; vertical-align: top; width: 40%; box-sizing: border-box;"><section class="" style="box-sizing: border-box;" powered-by="xiumi.us"><section class="" style="margin: 0.5em 0px; box-sizing: border-box;"><section style="border-top: 1px dotted rgb(24, 23, 21); box-sizing: border-box;" class=""></section></section></section></section></section></section>
<section class="Aposttail" style="padding: 0 10px;max-width: 640px;margin-top: 20px;">
	<section class="Aposttail-avatar" style="margin-left:50%;margin-bottom: -50px;">
		<p style="display:inline-block;margin-left:-51px;padding: 3px;background-color: #009ACD;border-radius: 50%;"><img src="{avatarlink}" style="display:block;width:102px;border-radius:50%;box-shadow:none;border:none;" /></p>
	</section>
	<section class="Aposttail-container" style="border: 2px solid #009ACD; border-radius: 10px;padding: 50px 10px 10px;text-align:center;">
		<section class="Aposttail-biography" style="display:inline-block;vertical-align:top;padding:0 50px;">
			<h3 class="author" style="margin: 10px 0; text-align: center;font-family: Comic Sans MS,Microsoft Yahei; font-size: 1.5em;font-weight: bold;color:#009ACD;">{author}</h3>
			<p style="margin: 10px 0; text-align: center;font: 1em Comic Sans MS,Microsoft Yahei;">{biography}</p>
		</section>
		<section class="Aposttail-qrcode" style="display:inline-block;vertical-align:top;width: 300px;">
			<p><img src="{qrcode}" alt="微信二维码" style="width: 80%;display: inline;"></p>
			<p>长按二维码关注</p>
		</section>
	</section>
</section>',
		);
		update_option( self::$option_name, $options );
	}
	
    public function settings_init(){
		register_setting( self::$option_name, self::$option_name );
	}

    public function add_plugin_page(){
        // This page will be under "Settings"
        $page_title=__('WeChat Settings', 'Awechat');
        $menu_title=__('WeChat Settings', 'Awechat');
        $capability='manage_options';
        $menu_slug=AWECHAT_PLUGIN_OPTIONNAME;
        
        add_options_page(
        	$page_title,
        	$menu_title,
        	$capability,
        	$menu_slug,
        	array( $this, 'create_admin_page' )
        );
    }
    public function create_admin_page(){
        // Set class property
		$options = get_option( self::$option_name );
		if ($options['appid'] && $options['appsecret']) {
			require_once AWECHAT_PLUGIN_DIR . 'includes/officialAccount.php';
			$app = new Awechat_oa(array(
				'app_id' => $options['appid'],
				'secret' => $options['appsecret']
			));
			$account_type = $app->check_account_type();
			$account_type_name = $app->get_account_type_name();
			if ($account_type != get_option( 'wechat_account_type_for_' . $options['appid'] )) {
				update_option( 'wechat_account_type_for_'.$options['appid'], $account_type );
			}
		}
		$interface_url = $options['token']!=''?home_url().'/?'.$options['token']:'none';
		wp_enqueue_media();
		wp_register_script('Awechat-custom-upload', AWECHAT_PLUGIN_URL.'/assets/media_upload.js', array('jquery','media-upload','thickbox'),"2.0");
		wp_enqueue_script('Awechat-custom-upload');
	?>
		<div class="wrap">
			<h2><?php _e('A WeChat Official Accout Connection','Awechat')?></h2>
			<form action="options.php" method="POST">
				<?php settings_fields( self::$option_name );?>
				<hr>
				<h2><?php _e('Account Settings','Awechat')?></h2>
				<table class="form-table">
					<tr valign="top">
						<th scope="row"><label>AppID</label></th>
						<td>
							<input type="text"
								size="30"
								name="<?php echo self::$option_name ;?>[appid]"
								value="<?php echo $options['appid'];?>"
								class="regular-text"/>
							<p class="description"><?php echo $account_type_name; ?></p>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row"><label>AppSecret</label></th>
						<td>
							<input type="text"
								size="30"
								name="<?php echo self::$option_name ;?>[appsecret]"
								value="<?php echo $options['appsecret'];?>"
								class="regular-text"/>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row"><label>Token</label></th>
						<td>
							<input type="text"
								size="30"
								name="<?php echo self::$option_name ;?>[token]"
								value="<?php echo $options['token'];?>"
								class="regular-text"/>
							<p class="description">
								<?php _e('Access verification for your WeChat public platform. Only Latin letter, number, dash and underscore. 30 character limited.','Awechat')?>
							</p>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row"><label>URL</label></th>
						<td>
							<h4><?php echo $interface_url;?></h4>
							<p class="description">
							<?php _e('First input a TOKEN above and save the settings, then &quot;Copy&quot; and &quot;Bind&quot; this URL to WeChat Platform.','Awechat')?>
						</p>
						</td>
					</tr>
				</table>
				<h2><?php _e('Sync Up Settings','Awechat')?></h2>
				<table class="form-table">
					<tr valign="top">
						<th scope="row"><label><?php _e('Preview User Wechat ID','Awechat')?></label></th>
						<td>
							<input type="text"
								size="30"
								name="<?php echo self::$option_name ;?>[preview_wechat_id]"
								value="<?php echo $options['preview_wechat_id'];?>"
								class="regular-text"/>
							<p class="description">
								<?php _e('It will push a preview message only to this wechat ID when saving posts.','Awechat')?>
							</p>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row"><label><?php _e('Title Prefix','Awechat')?></label></th>
						<td>
							<input type="text"
								size="30"
								name="<?php echo self::$option_name ;?>[title_prefix]"
								value="<?php echo $options['title_prefix'];?>"
								class="regular-text"/>
							<p class="description">
								<?php _e('Add to Wechat OA article title automatically.','Awechat')?>
							</p>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row"><label><?php _e('Enable sync from XML-RPC','Awechat')?></label></th>
						<td>
							<input type="checkbox"
								name="<?php echo self::$option_name ;?>[xmlrpc_sync]"
								value="1"
								<?php echo $options['xmlrpc_sync']?'checked':'';?>
								class="regular-checkbox"/>
							<p class="description">
								<?php _e('Enable all sync when using destop app to write article.','Awechat')?>
							</p>
						</td>
					</tr>
					<!-- Cover Image-->
					<tr valign="top">
						<th scope="row">
							<label><?php _e('Default Cover', 'Awechat'); ?></label>
						</th>
						<td>
						<div class="preview-box large">
							<img src="<?php echo $options['article_cover_image']; ?>" style="max-width:500px" />
						</div>
						<input type="hidden"
								value="<?php echo $options['article_cover_image']; ?>"
								name="<?php echo self::$option_name; ?>[article_cover_image]"
								rel="img-input" class="img-input large-text"/>
						<button class='media_upload_button button'>
							<?php _e('Upload', 'Awechat'); ?>
						</button>
						<button class='media_delete_button button'>
							<?php _e('Delete', 'Awechat'); ?>
						</button>
						</td>
					</tr>
					<!-- Follow Image-->
					<tr valign="top">
						<th scope="row">
							<label><?php _e('Follow Tips', 'Awechat'); ?></label>
						</th>
						<td>
						<div class="preview-box large">
							<img src="<?php echo $options['follow_tips_image']; ?>" style="max-width:500px" />
						</div>
						<input type="hidden"
								value="<?php echo $options['follow_tips_image']; ?>"
								name="<?php echo self::$option_name; ?>[follow_tips_image]"
								rel="img-input" class="img-input large-text"/>
						<button class='media_upload_button button'>
							<?php _e('Upload', 'Awechat'); ?>
						</button>
						<button class='media_delete_button button'>
							<?php _e('Delete', 'Awechat'); ?>
						</button>
						</td>
					</tr>
					<!-- Read Source Image-->
					<tr valign="top">
						<th scope="row">
							<label><?php _e('Read Source Tips', 'Awechat'); ?></label>
						</th>
						<td>
						<div class="preview-box large">
							<img src="<?php echo $options['read_source_tips_image']; ?>" style="max-width:500px" />
						</div>
						<input type="hidden"
								value="<?php echo $options['read_source_tips_image']; ?>"
								name="<?php echo self::$option_name; ?>[read_source_tips_image]"
								rel="img-input" class="img-input large-text"/>
						<button class='media_upload_button button'>
							<?php _e('Upload', 'Awechat'); ?>
						</button>
						<button class='media_delete_button button'>
							<?php _e('Delete', 'Awechat'); ?>
						</button>
						</td>
					</tr>
					<!-- QRCode Image-->
					<tr valign="top">
						<th scope="row">
							<label><?php _e('QRCode', 'Awechat'); ?></label>
						</th>
						<td>
						<div class="preview-box large">
							<img src="<?php echo $options['qrcode_image']; ?>" style="max-width:500px" />
						</div>
						<input type="hidden"
								value="<?php echo $options['qrcode_image']; ?>"
								name="<?php echo self::$option_name; ?>[qrcode_image]"
								rel="img-input" class="img-input large-text"/>
						<button class='media_upload_button button'>
							<?php _e('Upload', 'Awechat'); ?>
						</button>
						<button class='media_delete_button button'>
							<?php _e('Delete', 'Awechat'); ?>
						</button>
						</td>
					</tr>
					<!-- Post tail -->
					<tr valign="top">
						<th scope="row"><label><?php _e('Post tail', 'Awechat'); ?></label></th>
						<td>
						<textarea name="<?php echo self::$option_name; ?>[post_tail]" cols="80" rows="10"><?php echo $options['post_tail']; ?></textarea>
							<p class="description">
								以上HTML将会在每篇文章末尾展示。可以运用以下标签变量：<br />
								{author} : 文章作者名<br />
								{avatar} : 文章作者头像（输出img）<br />
								{biography} : 文章作者个人传记（在个人资料中设置）<br />
								{website} : 文章作者个人网站（在个人资料中设置）<br />
								{authorpage} : 文章作者所有文章页面URL<br />
          				  		{qrcode} : 需要关注的公众号二维码图片地址
							</p>
						</td>
					</tr>
				</table>

				<?php submit_button(); ?>
			</form>
		</div>
	<?php
	}
	public function admin_setting_action($old_value, $value, $option) {
		if ($value['article_cover_image'] != $old_value['article_cover_image']) {
			delete_option( 'wechat_default_image_id_for_'.$old_value['appid'] );
		}
		if ($value['follow_tips_image'] != $old_value['follow_tips_image']) {
			delete_option( 'wechat_follow_tips_image_for_'.$old_value['appid'] );
		}
		if ($value['read_source_tips_image'] != $old_value['read_source_tips_image']) {
			delete_option( 'wechat_read_source_image_for_'.$old_value['appid'] );
		}
		if ($value['qrcode_image'] != $old_value['qrcode_image']) {
			delete_option( 'wechat_qrcode_for_'.$old_value['appid'] );
		}
	}
	
	public function create_wechat_box() {
        // $post_types = array_keys(get_post_types());
        add_meta_box('Awechat-meta-box', __('Wechat OA Sync', 'Awechat'), [$this, 'wechat_meta_box'], 'post', 'side', 'high');
	}
    public function wechat_meta_box() {
		$options = get_option( self::$option_name );
    ?>
        <p>
            <input type="checkbox" name="wechat_meterial_sync" id="wechat_meterial_sync" <?php if ($options['appid']&&$options['appsecret']) echo 'checked';else echo 'disabled'; ?>/><label for="wechat_meterial_sync"><?php _e('Sync Meterial', 'Awechat') ?></label>
            <a href="/wp-admin/options-general.php?page=<?php echo AWECHAT_PLUGIN_OPTIONNAME ?>" style="float: right;"><span aria-hidden="true"><?php _e('Help', 'Awechat') ?></span></a>
        </p>
		<section id="for_wechat_meterial_sync">
			<p>
				<input type="checkbox" name="wechat_show_cover" id="wechat_show_cover"/><label for="wechat_show_cover"><?php _e('Show cover in content', 'Awechat') ?></label>
			</p>
			<hr />
		</section>
        <p>
            <input type="checkbox" name="wechat_message_push" id="wechat_message_push" <?php if (!$options['appid']||!$options['appsecret']||!self::_can_push_message($options['appid'], $options['appsecret'])) echo 'disabled'?>/><label for="wechat_message_push"><?php _e('Push Message', 'Awechat') ?>: </label>
			<select name="wechat_message_recipient">
				<option value="all"><?php _e('All', 'Awechat') ?></option>
				<?php if ($options['preview_wechat_id']) { ?><option value="<?php echo $options['preview_wechat_id'] ?>"><?php echo $options['preview_wechat_id'] ?></option><?php } ?>
			</select>
        </p>
		<script>
			jQuery('#wechat_meterial_sync').change(function(){
				if ($(this).is(':checked')) jQuery('#for_wechat_meterial_sync').show();
				else jQuery('#for_wechat_meterial_sync').hide();
			}).change();
		</script>
    <?php
	}
	public function _can_push_message($appid, $appsecret) {
		require_once AWECHAT_PLUGIN_DIR . 'includes/officialAccount.php';
		$AUTH_SUBSCRIPTION_ACCOUNT = Awechat_oa::AUTH_SUBSCRIPTION_ACCOUNT;
		$AUTH_SERVICE_ACCOUNT = Awechat_oa::AUTH_SERVICE_ACCOUNT;
		$account_type = get_option( 'wechat_account_type_for_' . $appid );
		if (!$account_type) {
			$app = new Awechat_oa(array(
				'app_id' => $appid,
				'secret' => $appsecret
			));
			$account_type = $app->check_account_type();
			$AUTH_SUBSCRIPTION_ACCOUNT = $app::AUTH_SUBSCRIPTION_ACCOUNT;
			$AUTH_SERVICE_ACCOUNT = $app::AUTH_SERVICE_ACCOUNT;
		}
		if ($account_type == $AUTH_SUBSCRIPTION_ACCOUN || $account_type == $AUTH_SERVICE_ACCOUNT) {
			return true;
		}else{
			return false;
		}
	}

	public function posts_add_column($post_columns) {
		$post_columns['Awechat'] = __( 'Wechat Sync', 'Awechat' );
		return $post_columns;
	}
	public function posts_render_column($column_name, $post_ID) {
		if ( $column_name == 'Awechat' ) {
			date_default_timezone_set('PRC');
			$wechat_article_id = get_post_meta( $post_ID, '_wechat_article_id', true );
			// if (!$wechat_article_id) return false;
			$wechat_article_url = get_post_meta( $post_ID, '_wechat_article_url', true );
			if (!$wechat_article_url) $wechat_article_url = 'javascript:;';
			$wechat_sync_log = get_post_meta( $post_ID, '_wechat_sync_log', true );
			$icon_titles = array();
			if (is_array($wechat_sync_log) && count($wechat_sync_log) > 0) {
				$is_sync_successful = !!$wechat_sync_log[ count($wechat_sync_log)-1 ]['success'];
				if (!$is_sync_successful) $icon_bg_position = 'background-position:24px 0;';
				for ($i=count($wechat_sync_log)-1; $i >= 0; $i--) { 
					if (count($wechat_sync_log) - $i >= 5) {array_push($icon_titles, '...'); break;}
					$is_sync_successful = !!$wechat_sync_log[$i]['success'];
					array_push($icon_titles, date('Y-m-d H:i:s', $wechat_sync_log[$i]['date']) . ': ' . ($is_sync_successful ? __('Sync Successfully!', 'Awechat') :  __('Sync Failed!', 'Awechat').$wechat_sync_log[$i]['errcode'].'-'.$wechat_sync_log[$i]['errmsg']) );
				}
			}
			if (!$wechat_article_id) $icon_bg_position .= 'filter:grayscale(100%);';
			echo '<a href="' . $wechat_article_url . '" title="' . join("\n",$icon_titles) . '" style="display:inline-block;margin:10px 5px;width:24px;height:25px;background-size:48px 25px;background-image:url(' . AWECHAT_PLUGIN_URL.'/assets/wx_icon.png' . ');' . $icon_bg_position . '" target="_blank" /></a>';
		}
	}
}
