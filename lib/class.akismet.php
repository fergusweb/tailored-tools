<?php

/** 
 *	Designed to make it easier to call Akismet API for anti-spam
 */

new Tailored_Akismet();

class Tailored_Akismet {
	private $class_version	= '0.1';	// Version of this class
	private	$api_version	= '1.1';	// Version of Akismet
	private	$user_agent		= false;
	public	$api_url		= 'rest.akismet.com';

	/**
	 *	Constructor
	 */
	function __construct() {
		global $wp_version;
		$this->user_agent = 'WordPress/'.$wp_version.' | Akismet/'.$this->class_version;
		
		add_filter('ttools_map_akismet_fields', array(&$this,'map_form_fields'), 9, 2);
		add_filter('ttools_form_filter_validate', array(&$this,'filter_form_validate_error'), 10, 2);
	}
	
	/**
	 *	Checks the form options to see if we're using Akismet
	 *	Includes library if not yet present
	 */
	function check_in_use($opts) {
		if (!$opts['akismet']['use'])							return false;
		if (empty($opts['akismet']['api_key']))					return false;
		if (!$this->check_key( $opts['akismet']['api_key'] ))	return false;	// Invalid API Key
		return true;
	}
	
	/**
	 *	Called as form as being validated
	 *	Return array of $errors
	 */
	function filter_form_validate_error($errors, $form) {
		if (!$this->check_in_use($form->opts))	return $errors;
		// Expecting array of (name=>, email=>, message=>) back.
		$values = apply_filters( 'ttools_map_akismet_fields', array(), $form );
		
		$is_spam = $this->check_form( $form->opts['akismet']['api_key'], $values['name'], $values['email'], $values['message'] );
		
		if ($is_spam) {
			$errors[] = 'Your message has tripped our spam filter.';
			$errors[] = 'You can try again later, or you can try re-wording it.';
		}
		
		return $errors;
	}
	
	/**
	 *	I made this a filter to so we can map to different fields if necessary
	 */
	function map_form_fields($form=false) {
		if (!is_array($fields))	$fields = array();
		$fields = array(
			'name'		=> $_POST['cust_name'],
			'email'		=> $_POST['cust_email'],
			'message'	=> $_POST['cust_message'],
		);
		return $fields;
	}
	
	
	/**
	 *	Validate our API key against Akismet
	 *	Using transients so we don't remote-call EVERY time.
	 */
	function check_key($api_key=false) {
		$key = 'akismet_api_key_valid_'.$api_key;
		$valid = get_transient($key);
		if (!is_string($valid)) {
			$is_valid = $this->remote_check_key($api_key);
			set_transient($key, (($is_valid) ? 'valid' : 'invalid'), 60*60*12);	// Good for 12 hours.
			$valid = get_transient($key);
		}
		return ($valid == 'valid') ? true : false;
	}
	function remote_check_key($api_key) {
		$akismet_url = 'http://'.$this->api_url.'/'.$this->api_version.'/verify-key';
		$http_args = array(
			'body'			=> 'key='.$api_key.'&blog='.urlencode(site_url()),
			'httpversion'	=> '1.0',
			'timeout'		=> 10
		);
//		echo '<p>Checking key: '.$akismet_url.'</p><pre>'; print_r($http_args); echo '</pre>';
		$response = wp_remote_post( $akismet_url, $http_args );
		if (is_wp_error( $response )) {
			wp_die( '<pre>'.print_r($response,true).'</pre>' );
		}
		$body = wp_remote_retrieve_body($response);
		if (trim($body) == 'valid') {
			return true;
		} else {
			$this->error_message = 'Akismet API Key is Invalid!';
			return false;
		}
	}
	
	
	/**
	 *	Check form details against Akismet.
	 *	Return TRUE if spam, and FALSE if not-spam.
	 */
	function check_form($api_key, $name='', $email='', $message='', $url='') {
		$akismet_url = 'http://'.$api_key.'.'.$this->api_url.'/'.$this->api_version.'/comment-check';
		$form = wp_parse_args($form, array(
			'blog'			=> urlencode(site_url()),
			'user_ip'		=> $_SERVER['REMOTE_ADDR'],
			'user_agent'	=> $_SERVER['HTTP_USER_AGENT'],
			'referrer'		=> $_SERVER['HTTP_REFERER'],
			'comment_type'	=> 'custom_form',
			// These are the form _POST values
			'name'		=> $name,
			'email'		=> $email,
			'url'		=> $url,
			'message'	=> $message,
		));
//echo '<p>Checking form with Akismet:</p><pre>'; print_r($form); echo '</pre>';
		$http_args = array(
			'httpversion'	=> '1.0',
			'timeout'		=> 10,
			'body'			=> http_build_query(array(
				'blog'			=> $form['blog'],
				'user_ip'		=> $form['user_ip'],
				'user_agent'	=> $form['user_agent'],
				'referrer'		=> $form['referrer'],
				'comment_type'	=> $form['comment_type'],
				'comment_author'		=> $form['name'],
				'comment_author_email'	=> $form['email'],
				'comment_author_url'	=> $form['url'],
				'comment_content'		=> $form['message'],
			)),
		);
		$response = wp_remote_post( $akismet_url, $http_args );
		$body = wp_remote_retrieve_body($response);
		return ($body == 'true') ? true : false;
	}
}



?>