<?php
/*
Plugin Name:	Tailored Tools
Description:	Adds some functionality to WordPress that you'll need.  (Version 1.5+ has different style rules. Do not upgrade without checking these.)
Version:		1.5.1
Author:			Tailored Web Services
Author URI:		http://www.tailored.com.au
*/



// Register our scripts & styles for later enqueuing
add_action('init', 'tailored_tools_register_scripts');
function tailored_tools_register_scripts() {
	// Stylesheets
	wp_register_style('ttools', plugins_url('resource/custom.css', __FILE__));
	wp_register_style('jquery-chosen', plugins_url('resource/chosen.css', __FILE__));
	
	// Javascript
	wp_deregister_script('jquery-validate');	// Assume this plugin is more up-to-date than other sources.  Might be bad mannered.
//	wp_register_script('jquery-validate', '//ajax.aspnetcdn.com/ajax/jquery.validate/1.9/jquery.validate.min.js', array('jquery'), '1.9.0', true);
	wp_register_script('jquery-validate', '//ajax.aspnetcdn.com/ajax/jquery.validate/1.11.1/jquery.validate.min.js', array('jquery'), '1.11.1', true);
	wp_register_script('jquery-chosen', plugins_url('js/chosen.jquery.min.js', __FILE__), array('jquery'), false, true);
	wp_register_script('ttools-loader', plugins_url('js/loader.js', __FILE__), array('jquery-validate','jquery-ui-datepicker', 'jquery-chosen'), false, true);
}

//	Include Helper Classes
if (!class_exists('TailoredTinyMCE'))			require( dirname(__FILE__).'/lib/tinymce.php' );
if (!class_exists('TailoredForm'))				require( dirname(__FILE__).'/lib/class.forms.php' );
if (!class_exists('TailoredTools_Shortcodes'))	require( dirname(__FILE__).'/shortcodes.php' );
if (!class_exists('TailoredTools_GoogleMaps'))	require( dirname(__FILE__).'/googlemaps.php' );

// Anti-spam Modules
if (!class_exists('Tailored_reCAPTCHA'))		require( dirname(__FILE__).'/lib/class.recaptcha.php' );
if (!class_exists('Tailored_Akismet'))			require( dirname(__FILE__).'/lib/class.akismet.php' );
if (!class_exists('Tailored_Tools_AYAH'))		require( dirname(__FILE__).'/lib/class.ayah.php' );

//	Contact Form
if (!class_exists('ContactForm'))		require( dirname(__FILE__).'/form.contact.php' );

//	Sample Form
//if (!class_exists('SampleForm')		require( dirname(__FILE__).'/form.sample.php' );







?>