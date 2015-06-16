<?php
/*
Plugin Name:	Tailored Tools
Description:	Adds some functionality to WordPress that you'll need.  (Version 1.5+ has different style rules. Do not upgrade without checking these.)
Version:		1.8.1
Author:			Tailored Web Services
Author URI:		http://www.tailored.com.au
*/



//	Register our scripts & styles for later enqueuing
add_action('init', 'tailored_tools_register_scripts');
function tailored_tools_register_scripts() {
	// Stylesheets
	wp_register_style('ttools', plugins_url('resource/custom.css', __FILE__));
//	wp_register_style('jquery-select2',		'//cdnjs.cloudflare.com/ajax/libs/select2/4.0.0/css/select2.min.css');
	// Javascript
	wp_deregister_script('jquery-validate');	// Assume this plugin is more up-to-date than other sources.  Might be bad mannered.
	wp_register_script('jquery-validate',	'//ajax.aspnetcdn.com/ajax/jquery.validate/1.13.1/jquery.validate.min.js', array('jquery'), '1.13.1', true);
//	wp_register_script('jquery-select2',	'//cdnjs.cloudflare.com/ajax/libs/select2/4.0.0/js/select2.min.js', array('jquery'), '4.0.0', true);
	wp_register_script('jquery-timepicker',	'//cdnjs.cloudflare.com/ajax/libs/jquery-ui-timepicker-addon/1.4.5/jquery-ui-timepicker-addon.js', array('jquery-ui-datepicker'), '1.4.5', true);
	wp_register_script('ttools-loader',	 plugins_url('js/loader.js', __FILE__), array('jquery-validate', 'jquery-timepicker'), false, true);
}


//	Include Helper Classes
if (!class_exists('TailoredTinyMCE'))			require( dirname(__FILE__).'/lib/tinymce.php' );
if (!class_exists('TailoredForm'))				require( dirname(__FILE__).'/lib/class.forms.php' );
if (!class_exists('tws_WP_List_Table'))			require( dirname(__FILE__).'/lib/class-wp-list-table.php' );

// Anti-spam Modules
if (!class_exists('Tailored_reCAPTCHA'))		require( dirname(__FILE__).'/lib/class.recaptcha.php' );
if (!class_exists('Tailored_Akismet'))			require( dirname(__FILE__).'/lib/class.akismet.php' );
//if (!class_exists('Tailored_Tools_AYAH'))		require( dirname(__FILE__).'/lib/class.ayah.php' );



//	Run after all plugins loaded
add_action('plugins_loaded', 'tailored_tools_plugins_loaded', 11);
function tailored_tools_plugins_loaded() {
	// Include Tailored Tools modules
	if (!class_exists('TailoredTools_Shortcodes'))	require( dirname(__FILE__).'/shortcodes.php' );
	if (!class_exists('TailoredTools_GoogleMaps'))	require( dirname(__FILE__).'/googlemaps.php' );
	//	Contact Form
	if (!class_exists('ContactForm'))				require( dirname(__FILE__).'/form.contact.php' );
//	if (!class_exists('SampleForm'))				require( dirname(__FILE__).'/form.sample.php' );
	//	Helper to embed JS like Adwords Conversion Code
	if (!class_exists('ttools_embed_page_js'))		require( dirname(__FILE__).'/embed-js.php' );
}









?>