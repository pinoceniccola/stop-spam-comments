<?php
/*
Plugin Name: Stop Spam Comments
Description: Dead simple and super lightweight anti-spambot plugin. No captcha, tricky questions or any other user interaction required at all.
Plugin URI: https://github.com/pinoceniccola/stop-spam-comments/archive/master.zip
Version: 0.1
Author: Pino Ceniccola
Author URI: http://pino.ceniccola.it
License: GPLv2 
*/

add_action('init','p_ssc_init');

function p_ssc_init(){
	// activate only for not logged in users
	if (!is_user_logged_in()) {
		// config the comment form
		add_filter('comment_form_field_comment','p_ssc_config');
		// process the comment
		add_filter('preprocess_comment','p_ssc_process');
		// add a notice and a key for users with no js support
		add_action('comment_form','p_ssc_notice');
	}
}	

function p_ssc_process($commentdata) {
	
	// if this is a trackback or pingback return
	if ($commentdata['comment_type'] != '') return $commentdata;
		
	global $post;
	$key = COOKIEHASH.'_'.dechex((int) $post->ID);	
	
	// if comment has key field return
	if ($_POST['ssc_key']==$key)  { return $commentdata; }
	
	// else if the key is in the comment content (accessibility, for users with no js support)
	elseif (strpos($commentdata['comment_content'], $key) !== false) {
		$commentdata['comment_content'] = str_replace($key,'',$commentdata['comment_content']);
		return $commentdata;
	}
	
	// no key = comment is spam
	else {
		$commentdata['comment_approved'] = 'spam';
		wp_insert_comment($commentdata);
		// alternative: redirect spambots to their own home! 
		//wp_redirect('http://127.0.0.1', 301 ); exit;
		wp_die('Notice: It seems you have Javascript disabled in your Browser. In order to submit a comment to this post, please copy the code below the form and paste it along with your comment.');
	}
}

function p_ssc_config($field){
	global $post;
	$key = COOKIEHASH.'_'.dechex((int) $post->ID);
	$field=str_replace('<textarea','<textarea onfocus="if(!this._s==true){var _i=document.createElement(\'input\');_i.setAttribute(\'type\',\'hidden\');_i.setAttribute(\'name\',\'ssc_key\');_i.setAttribute(\'value\',\''.$key.'\');var _p=this.parentNode;_p.insertBefore(_i,this);this._s=true;}"',$field);
	return $field;
}

function p_ssc_notice($id) {
	$key = COOKIEHASH.'_'.dechex((int) $id);	
	echo '<noscript><p class="ssc_notice">Notice: It seems you have Javascript disabled in your Browser. In order to submit a comment to this post, please copy this code and paste it along with your comment: <strong>'.$key.'</strong></p></noscript>';
}
