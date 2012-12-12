<?php

/** 
 *	Tailored Tools and AreYouAHuman integration
 */

new Tailored_Tools_AYAH();

class Tailored_Tools_AYAH {
	public		$web_service	= 'ws.areyouahuman.com';
	public		$ayah			= false; // will hold API class
	

	
	/**
	 *	Load API Object
	 */
	function load_api($publisher_key, $scoring_key) {
		if (!class_exists('AYAH'))	require( dirname(__FILE__).'/lib.ayah.php' );
		$this->ayah = new AYAH(array(
			'publisher_key'		=> $publisher_key,
			'scoring_key'		=> $scoring_key,
			'web_service_host'	=> $this->web_service,
		));
	}
	
	/**
	 *	Constructor
	 */
	function __construct() {
		add_action('ttools_form_before_submit_button', array(&$this,'insert_html_code'), 10, 1);
		add_filter('ttools_form_filter_validate', array(&$this,'filter_form_validate_error'), 10, 2);
	}
	
	/**
	 *	Checks the form options to see if we're using AYAH
	 *	Includes library if not yet present
	 */
	function check_in_use($opts) {
		if (!$opts['ayah']['use'])															return false;
		if (empty($opts['ayah']['publisher_key']) || empty($opts['ayah']['scoring_key']))	return false;
		$this->load_api($opts['ayah']['publisher_key'], $opts['ayah']['scoring_key']);
		return true;
	}
	
	
	
	/**
	 *	Called just before the [submit'] button.
	 *	Output code if appropriate
	 */
	function insert_html_code($form) {
		if (!$this->check_in_use($form->opts))	return false;
		
		// Default call embeds the script etc right here.
		// echo $this->ayah->getPublisherHTML();
		
		// Let's try this instead.
		$script_src = 'https://' . $this->web_service . "/ws/script/" . urlencode($form->opts['ayah']['publisher_key']);
		wp_enqueue_script('ayah', $script_src, false, false, true);
		echo '<div id="AYAH" class="ayah_box"></div>'."\n";
	}
	
	/**
	 *	Called as form as being validated
	 *	Return array of $errors
	 */
	function filter_form_validate_error($errors, $form) {
		if (!$this->check_in_use($form->opts))	return $errors;
		
		$score = $this->ayah->scoreResult();
		
		if ( !$score ) {	
			$errors[] = 'You did not complete the puzzle to prove that you are human.';
			$errors[] = 'Please try again!';
		}
		return $errors;
	}
	
	
}



?>