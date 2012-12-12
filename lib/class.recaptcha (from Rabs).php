<?php

/** 
 *	Seperate our CAPTCHA functionality from the TTools plugin
 *	Integrate via hooks, filters and actions
 */

new Tailored_reCAPTCHA();

class Tailored_reCAPTCHA {

	/**
	 *	Constructor
	 */
	function __construct() {
		add_action('ttools_form_before_submit_button', array(&$this,'insert_html_code'), 10, 1);
		add_filter('ttools_form_filter_validate', array(&$this,'filter_form_validate_error'), 10, 2);
	}
	
	/**
	 *	Checks the form options to see if we're using reCAPTCHA
	 *	Includes library if not yet present
	 */
	function check_in_use($opts) {
		if (!$opts['recaptcha']['use'])														return false;
		if (empty($opts['recaptcha']['public']) || empty($opts['recaptcha']['private']))	return false;
		if (!function_exists('recaptcha_get_html'))		require( dirname(__FILE__).'/recaptchalib.php' );
		return true;
	}
	
	/**
	 *	Called just before the [submit'] button.
	 *	Output code if appropriate
	 */
	function insert_html_code($form) {
		if (!$this->check_in_use($form->opts))	return false;
		echo '<div class="recaptcha">'.recaptcha_get_html($form->opts['recaptcha']['public'])."</div>\n";
	}
	
	/**
	 *	Called as form as being validated
	 *	Return array of $errors
	 */
	function filter_form_validate_error($errors, $form) {
		if (!$this->check_in_use($form->opts))	return $errors;
		$response = recaptcha_check_answer(
			$form->opts['recaptcha']['private'],	$_SERVER["REMOTE_ADDR"], 
			$_POST["recaptcha_challenge_field"],	$_POST["recaptcha_response_field"]
		);
		if (!$response->is_valid) {
			if (!is_array($errors))	$errors = array();
			$errors[] = 'The reCAPTCHA (to stop spam) wasn\'t entered correctly.'; 
			$errors[] = 'You need to re-type the words you see in the box.';
		}
		return $errors;
	}
	
}



?>