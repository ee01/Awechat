<?php
use EasyWeChat\Kernel\Messages\Article;

class Awechat_postsync {

	public function __construct() {
		// Sync up current changes to wechat OA meterials when updating the posts
		add_action('save_post_post', array($this, 'action_save_post_post'), 10, 3);
		// Delete wechat OA meterials when deleting the posts
		add_action('before_delete_post', array($this, 'action_after_delete_post'));
	}

	public function action_save_post_post($post_ID, $post, $update) {
		$options = get_option( AWECHAT_PLUGIN_OPTIONNAME );
		if ($_POST['wechat_meterial_sync'] != 'on' && !($options['xmlrpc_sync'] && defined('XMLRPC_REQUEST')) ) return false;
		if (!$options['appid'] || !$options['appsecret']) return false;
		require_once AWECHAT_PLUGIN_DIR . 'includes/officialAccount.php';
		$app = new Awechat_oa(array(
			'app_id' => $options['appid'],
			'secret' => $options['appsecret']
		));
		if (!$this->_can_sync_post($options['appid'], $app)) return false;

		$featured_image_id = get_post_thumbnail_id( $post_ID );
		if ($featured_image_id) {
			$wechat_media_id = get_post_meta( $featured_image_id, '_wechat_media_id', true );
			$image_url = wp_get_attachment_image_url($featured_image_id , 'single-post-thumbnail' );
			$image_path = $this->_get_local_image_path($image_url);
		} else {
			$wechat_media_id = get_option( 'wechat_default_image_id_for_'.$options['appid'] );
			$image_url = $options['article_cover_image'];
			$image_path = $this->_get_local_image_path($image_url);
		}

		if (!$wechat_media_id) {
			if (!file_exists($image_path)) return false;

			$image_media = $app->material->uploadThumb($image_path);
			$wechat_media_id = $image_media['media_id'];
			if ($featured_image_id) {
				update_post_meta( $featured_image_id, '_wechat_media_id', $wechat_media_id);
			} else {
				update_option( 'wechat_default_image_id_for_'.$options['appid'], $wechat_media_id );
			}
		}

		$qrcode_image = get_option( 'wechat_qrcode_for_'.$options['appid'] );
		if (!$qrcode_image && $options['qrcode_image']) {
			$qrcode_path = $this->_get_local_image_path($options['qrcode_image']);
			$qrcode_media = $app->material->uploadImage($qrcode_path);
			$qrcode_image = $qrcode_media['url'];
		}

		require_once AWECHAT_PLUGIN_DIR . 'includes/htmlDom.php';
		$dom = new Awechat_dom($post->post_content);
		$dom->formatPost();
		$content = $dom->getHtml();
		
		$imgsrcs = $dom->getImagesSrc();
		$content = $this->replace_uploaded_img($content, $imgsrcs, $app);
		$content = $this->replace_uploaded_audio($content, $app, $post_ID, $options);
		$tail = str_replace(array(
			'{author}',
			'{avatarlink}',
			'{biography}',
			'{website}',
			'{authorpage}',
			'{qrcode}',
		), array(
			get_the_author_meta( 'display_name', $post->post_author ),
			get_avatar_url($post->post_author),
			nl2br( get_the_author_meta( 'user_description', $post->post_author ) ),
			get_the_author_meta('url', $post->post_author),
			get_author_posts_url( get_the_author_meta( 'ID' , $post->post_author)),
			$qrcode_image
		), $options['post_tail']);
		$dom_tail = new Awechat_dom($tail);
		$tail_imgsrcs = $dom_tail->getImagesSrc();
		// array_push($tail_imgsrcs, get_avatar_url($post->post_author));
		$tail = $this->replace_uploaded_img($tail, $tail_imgsrcs, $app);
		$content .= $tail;
		$uploaded_follow_tips = $this->get_follow_tips_image($app, $options);
		$uploaded_read_source = $this->get_read_source_image($app, $options);
		if ($uploaded_read_source) $content .= '<p><img src="'.$uploaded_read_source.'" /></p>';
		
		$post_data = [
			'title' => $options['title_prefix'] . $post->post_title,
			'author' => get_the_author_meta('display_name', $post->post_author),
			'show_cover' => $_POST['wechat_show_cover'] == 'on' ? 1 : 0,
			'show_cover_pic' => $_POST['wechat_show_cover'] == 'on' ? 1 : 0,
			'digest' => $post->post_excerpt ? mb_substr($post->post_excerpt, 0, 57, 'utf-8').'...' : '',
			'content' => $content,
			'thumb_media_id' => 0,
			'source_url' => get_permalink($post),
			'content_source_url' => get_permalink($post),
		];
		
		$wechat_article_id = get_post_meta( $post_ID, '_wechat_article_id', true );
		if ($wechat_article_id) $wechat_article = $app->material->get($wechat_article_id);
		$is_update_existed_material = $wechat_article_id && !array_key_exists('errcode', $wechat_article);
		if ($is_update_existed_material) {
			if (!$featured_image_id && $wechat_article['news_item'][0]['thumb_media_id']) {
				$wechat_media_id = $wechat_article['news_item'][0]['thumb_media_id'];
			}
			$post_data['thumb_media_id'] = $wechat_media_id;
			$post_data['content'] = $this->keep_tag_from_remote('iframe', $wechat_article['news_item'][0]['content'], $post_data['content']);
			$post_data['content'] = $this->keep_tag_from_remote('mpvoice', $wechat_article['news_item'][0]['content'], $post_data['content']);
			if ($uploaded_follow_tips) $post_data['content'] = '<p><img src="'.$uploaded_follow_tips.'" /></p>' . $post_data['content'];
			if (mb_strlen($post_data['content']) > 40000) $post_data['content'] = mb_substr($post_data['content'], 0, 39900, 'utf-8') . '<p>...</p><p><p style="color:orangered;font-size:12px;font-style:italic">因微信文章长度限制，请点击阅读原文继续阅读！<p>';
			$result = $app->material->updateArticle($wechat_article_id, $post_data);
			update_post_meta( $post_ID, '_wechat_article_url', $wechat_article['news_item'][0]['url'] );
		} else {
			$post_data['thumb_media_id'] = $wechat_media_id;
			if (mb_strlen($post_data['content']) > 40000) $post_data['content'] = mb_substr($post_data['content'], 0, 39900, 'utf-8') . '<p>...</p><p><p style="color:orangered;font-size:12px;font-style:italic">因微信文章长度限制，请点击阅读原文继续阅读！<p>';
			$article = new Article($post_data);
			$result = $app->material->uploadArticle($article);
			if (array_key_exists('media_id', $result))
				update_post_meta( $post_ID, '_wechat_article_id', $result['media_id'] );
		}
		
		$result['success'] = array_key_exists('errcode', $result) && $result['errcode'] !== 0 ? 0 : 1;
		$result['date'] = time();
		$wechat_sync_log = get_post_meta( $post_ID, '_wechat_sync_log', true );
		if (!is_array($wechat_sync_log)) $wechat_sync_log = array();
		array_push($wechat_sync_log, $result);
		update_post_meta( $post_ID, '_wechat_sync_log', $wechat_sync_log );

		if ($_POST['wechat_message_push'] == 'on' && $result['media_id'] && $this->_can_push_message($options['appid'], $app)) {
			if ($_POST['wechat_message_recipient'] == 'all') {
				$app->broadcasting->sendNews($result['media_id']);
			} else {
				$app->broadcasting->previewNewsByName($result['media_id'], $_POST['wechat_message_recipient']);
			}
		}
	}
	private function _can_sync_post($appid, $app) {
		$account_type = get_option( 'wechat_account_type_for_' . $appid );
		if (!$account_type) $account_type = $app->check_account_type();
		if ($account_type == $app::UNAUTH_SERVICE_ACCOUNT) {
			return false;
		}else{
			return true;
		}
	}
	private function _can_push_message($appid, $app) {
		$account_type = get_option( 'wechat_account_type_for_' . $appid );
		if (!$account_type) $account_type = $app->check_account_type();
		if ($account_type == $app::AUTH_SUBSCRIPTION_ACCOUNT || $account_type == $app::AUTH_SERVICE_ACCOUNT) {
			return true;
		}else{
			return false;
		}
	}
	private function _get_local_image_path($image_url) {
		$parsed = parse_url(trim($this->_link_urlencode($image_url)));
		if (preg_match("/\/wp-content\//i", $parsed['path'])) {
			return urldecode(get_home_path() . $parsed['path']);
		}
		$upload_dir = wp_upload_dir();
		$image_path = empty($parsed['path']) ? '' : preg_replace("/\S*\/files/i", $upload_dir['basedir'], $parsed['path']);
		return urldecode($image_path);
	}
	private function _link_urlencode($url) {
		$uri = '';
		$cs = unpack('C*', $url);
		$len = count($cs);
		for ($i=1; $i<=$len; $i++) {
		  $uri .= $cs[$i] > 127 ? '%'.strtoupper(dechex($cs[$i])) : $url{$i-1};
		}
		return $uri;
	}

	private function replace_uploaded_img($content, $imgsrcs, $app) {
		foreach ($imgsrcs as $img) {
			$url_info = parse_url($img);
			$img_url = isset($url_info['host']) ? $img : $_SERVER['HTTP_ORIGIN'] . $url_info['path'];
			$is_local_img = strstr($img_url, $_SERVER['HTTP_HOST']);
			/* Deprecated! uploadImage方法
			if ($is_local_img) {
				$img_origin_url = preg_replace("/(\/[^\/]+)-\d+x\d+(\.[^\/]+)$/i", "$1$2", $img_url);
				$img_media_id = attachment_url_to_postid($img_origin_url);
				$img_wechat_id = get_post_meta( $img_media_id, '_wechat_media_id', true );
				if (!$img_wechat_id) {
					$img_dir = $this->_get_local_image_path($img_url);
					$upload_img_result = $app->material->uploadImage($img_dir);
					update_post_meta( $img_media_id, '_wechat_media_id', $upload_img_result['media_id']);
				} else {
					// 微信接口无法通过media_id获取url
					$img_dir = $this->_get_local_image_path($img_url);
					$upload_img_result = $app->material->uploadImage($img_dir);
					update_post_meta( $img_media_id, '_wechat_media_id', $upload_img_result['media_id']);
				}
			} else {
				$img_media_id = $this->_insert_wp_media($img_url, $post_ID);
				if (!$img_media_id) continue;
				$img_media_url = wp_get_attachment_image_url($img_media_id , 'single-post-thumbnail' );
				$img_dir = $this->_get_local_image_path($img_media_url);
				$upload_img_result = $app->material->uploadImage($img_dir);
			}
			*/
			if ($is_local_img) {
				$img_dir = $this->_get_local_image_path($img_url);
				$upload_img_result = $app->material->uploadArticleImage($img_dir);
			} else {
				if (preg_match("/^https?:\/\/wximg\.ieexx\.com/i", $img_url)) {
					$upload_img_result = array();
					$upload_img_result['url'] = preg_replace("/^(https?:\/\/)wximg\.ieexx\.com/i", "$1mmbiz.qpic.cn", $img_url);
				} else {
					$wp_upload_dir = wp_upload_dir();
					$img_filename = iconv("utf-8", "gb2312//IGNORE", basename($url_info['path']));
					$img_filename = preg_match("/\.(jpg|jpeg|png|gif)$/i", $img_filename) ? $img_filename : $img_filename.'.jpg';
					$img_dir = $wp_upload_dir['basedir'] . $img_filename;
					file_put_contents($img_dir, file_get_contents($img_url));
					$upload_img_result = $app->material->uploadArticleImage($img_dir);
					unlink($img_dir);
				}
			}
			if ($upload_img_result) $content = str_replace($img, $upload_img_result['url'], $content);
		}
		return $content;
	}
	private function _insert_wp_media($mediaUrl, $post_ID = null) {
        if (strpos($mediaUrl, $_SERVER['HTTP_HOST']) > 0) return false;
        $dataGet = wp_remote_get($mediaUrl);
        if (is_wp_error($request)) return false;

        $mediaInfo = parse_url($mediaUrl);
		$mediaFileName = basename($mediaInfo['path']);
		$mediaFileName = preg_match("/\.(jpg|jpeg|png|gif|mp3|wav|mp4)$/i", $mediaFileName) ? $mediaFileName : $mediaFileName.'.jpg';
		$uploadFile = wp_upload_bits($mediaFileName, null, wp_remote_retrieve_body($dataGet));
		if (array_key_exists('error', $uploadFile) && $uploadFile['error']) return false;

        $attach_id = wp_insert_attachment(array(
            'post_title' => $mediaFileName,
            'post_mime_type' => wp_remote_retrieve_header($dataGet, 'content-type'),
        ), $uploadFile['file'], $post_ID);
        $attachment_data = wp_generate_attachment_metadata($attach_id, $uploadFile['file']);
		wp_update_attachment_metadata($attach_id, $attachment_data);
		
		return $attach_id;
	}

	private function replace_uploaded_audio($content, $app, $post_ID = null, $options) {
		preg_match_all("/https?:\/\/\S*\.(mp3|wma|wav)/i", $content, $matches);
		$has_beepress_podcast = get_post_meta($post_ID, 'enclosure', true);
		if ($has_beepress_podcast) {
			$enclosureURL = trim(explode("\n", $has_beepress_podcast, 4)[0]);
			if (preg_match("/https?:\/\/\S*\.(mp3|wma|wav)/i", $enclosureURL)) array_push($matches[0], $enclosureURL);
		}

		$origin_prefix = site_url().'/files';
		$cdn_prefix = get_option('upload_url_path');
		foreach ($matches[0] as $audio) {
			$audio_url = str_replace($cdn_prefix, $origin_prefix, $audio);
			$url_info = parse_url($audio_url);
			$audio_url = isset($url_info['host']) ? $audio_url : $_SERVER['HTTP_ORIGIN'] . $url_info['path'];
			$is_local_audio = strstr($audio_url, $_SERVER['HTTP_HOST']);
			if ($is_local_audio) {
				$audio_media_id = attachment_url_to_postid($audio_url);
				$audio_wechat_id = get_post_meta( $audio_media_id, '_wechat_media_id', true );
				if (!$audio_wechat_id) {
					$audio_dir = $this->_get_local_image_path($audio_url);
					if (filesize($audio_dir) > 31457280) continue;
					$upload_audio_result = $app->material->uploadVoice($audio_dir);
					update_post_meta( $audio_media_id, '_wechat_media_id', $upload_audio_result['media_id']);
				} else {
					$upload_audio_result = array();
					$upload_audio_result['media_id'] = $audio_wechat_id;
				}
			} else {
				$audio_media_id = $this->_insert_wp_media($audio_url, $post_ID);
				if (!$audio_media_id) continue;
				$audio_media_url = wp_get_attachment_image_url($audio_media_id , 'single-post-thumbnail' );
				$audio_dir = $this->_get_local_image_path($audio_media_url);
				if (filesize($audio_dir) > 31457280) continue;
				$upload_audio_result = $app->material->uploadVoice($audio_dir);
			}
			if ($upload_audio_result){
				// TODO: mpvoice音频标签内的voice_encode_fileid与返回的media_id不同，无法插入音频
				$audio_html = '<p><mpvoice frameborder="0" class="res_iframe js_editor_audio audio_iframe place_audio_area" src="/cgi-bin/readtemplate?t=tmpl/audio_tmpl&amp;name=Openning.mp3&amp;play_length=00:46" isaac2="1" low_size="87.88" source_size="87.9" high_size="362.13" name="Openning.mp3" play_length="46000" voice_encode_fileid="'.$upload_audio_result['media_id'].'"></mpvoice></p>';
				$audio_html = '<p><mpvoice frameborder="0" class="res_iframe js_editor_audio audio_iframe place_audio_area" src="/cgi-bin/readtemplate?t=tmpl/audio_tmpl&amp;name=Openning.mp3&amp;play_length=00:46" isaac2="1" low_size="87.88" source_size="87.9" high_size="362.13" name="Openning.mp3" play_length="46000" voice_encode_fileid="MzU1Njg0MDE5Ml8xMDAwMDAwMjU="></mpvoice></p>';
				$audio_html = '<p style="font-style:italic;color:#ddd;font-size:13px:">因微信接口限制 音频无法嵌入，请"阅读原文"播放！</p>';
				$content = str_replace($audio, $audio_html, $content);
			}
		}
		return $content;
	}
	private function get_read_source_image($app, $options) {
		$uploaded_read_source = get_option( 'wechat_read_source_image_for_'.$options['appid'] );
		if (!$uploaded_read_source) {
			$read_source_dir = $this->_get_local_image_path($options['read_source_tips_image']);
			if (!$read_source_dir) return false;
			$image_media = $app->material->uploadImage($read_source_dir);
			$uploaded_read_source = $image_media['url'];
			update_option( 'wechat_read_source_image_for_'.$options['appid'], $uploaded_read_source );
		}
		return $uploaded_read_source;
	}
	private function get_follow_tips_image($app, $options) {
		$uploaded_follow_tips = get_option( 'wechat_follow_tips_image_for_'.$options['appid'] );
		if (!$uploaded_follow_tips) {
			$follow_tips_dir = $this->_get_local_image_path($options['follow_tips_image']);
			if (!$follow_tips_dir) return false;
			$image_media = $app->material->uploadImage($follow_tips_dir);
			$uploaded_follow_tips = $image_media['url'];
			update_option( 'wechat_follow_tips_image_for_'.$options['appid'], $uploaded_follow_tips );
		}
		return $uploaded_follow_tips;
	}

	private function keep_tag_from_remote($tag, $remote_content, $local_content) {
		preg_match_all("/(.{1,60}?)(<".$tag."[^>]*>.*?<\/".$tag.">)(.{1,60})/i", $remote_content, $matches);
		if (!$matches[0]) return $local_content;
		$put_front = "";
		foreach ($matches[0] as $i => $match) {
			if ($this->_partially_match($matches[1][$i], $local_content, 'prefix') && $this->_partially_match($matches[3][$i], $local_content, 'suffix')) {
				$pattern = "/^(.*".$matches[1][$i].").*(".$matches[3][$i].".*)$/i";
				$local_content = preg_replace($pattern, "$1".$matches[2][$i]."$2", $local_content);
			} else $put_front .= $matches[2][$i];
		}
		return $put_front . $local_content;
	}
	private function _partially_match($search, $content, $position, $length = 60, $step = 20) {
		for ($i=$length; $i > 10; $i-=$step) { 
			if ($position == 'prefix') $s = substr($search, 0, $i);
			else $s = substr($search, $length-$i);
			if (substr_count($content, $s) == 1) {
				return $s;
			}
		}
		return false;
	}

	public function action_after_delete_post($post_ID) {
		$options = get_option( AWECHAT_PLUGIN_OPTIONNAME );
		if (!$options['appid'] || !$options['appsecret']) return false;
		require_once AWECHAT_PLUGIN_DIR . 'includes/officialAccount.php';
		$app = new Awechat_oa(array(
			'app_id' => $options['appid'],
			'secret' => $options['appsecret']
		));
		$wechat_article_id = get_post_meta( $post_ID, '_wechat_article_id', true );
		if ($wechat_article_id) {
			$app->material->delete($wechat_article_id);
		}
	}
}