<?php
/*
Plugin Name:	Tailored Tools
Description:	Adds some functionality to WordPress that you'll need.
Version:		1.9.2
Author:			Tailored Media
Author URI:		http://www.tailoredmedia.com.au
*/


//	Register our scripts & styles for later enqueuing
add_action('init', 'tailored_tools_register_scripts');
function tailored_tools_register_scripts() {
	// Stylesheets
	wp_register_style('ttools', plugins_url('resource/custom.css', __FILE__));
	wp_register_style('ttools-admin', plugins_url('resource/admin.css', __FILE__));

	// Javascript
	wp_deregister_script('jquery-validate');	// Assume this plugin is more up-to-date than other sources.  Might be bad mannered, but we know which version we're getting.
	wp_register_script('jquery-validate',	plugins_url('js/jquery.validate.js', __FILE__), array('jquery'), '1.15.1', true);
	wp_register_script('jquery-timepicker',	plugins_url('js/jquery.timepicker.js', __FILE__), array('jquery-ui-datepicker'), '1.6.3', true);
	wp_register_script('google-maps-api', '//maps.googleapis.com/maps/api/js?sensor=false&amp;libraries=places', false, false, false);
	wp_register_script('jquery-geocomplete', plugins_url('js/jquery.geocomplete.js', __FILE__), array('jquery', 'google-maps-api'), '1.7.0', true);
	wp_register_script('ttools-loader',	 plugins_url('js/loader.js', __FILE__), array('jquery'), false, true);
}


//	Include Helper Classes
if (!class_exists('TailoredTinyMCE'))			require( dirname(__FILE__).'/includes/tinymce.php' );
if (!class_exists('TailoredForm'))				require( dirname(__FILE__).'/includes/class.forms.php' );
if (!class_exists('tws_WP_List_Table'))			require( dirname(__FILE__).'/includes/class-wp-list-table.php' );

// Anti-spam Modules
if (!class_exists('Tailored_reCAPTCHA'))		require( dirname(__FILE__).'/includes/class.recaptcha.php' );
if (!class_exists('Tailored_Akismet'))			require( dirname(__FILE__).'/includes/class.akismet.php' );



//	Run after all plugins loaded
add_action('plugins_loaded', 'tailored_tools_plugins_loaded');
function tailored_tools_plugins_loaded() {
	// Include Tailored Tools modules
	if (!class_exists('TailoredTools_Shortcodes'))	require( dirname(__FILE__).'/tools/shortcodes.php' );
	if (!class_exists('ttools_mce_columns'))		require( dirname(__FILE__).'/tools/mce-columns.php' );
	if (!class_exists('TailoredTools_GoogleMaps'))	require( dirname(__FILE__).'/tools/googlemaps.php' );
	//	Helper to embed JS like Adwords Conversion Code
	if (!class_exists('ttools_embed_page_js'))		require( dirname(__FILE__).'/tools/embed-js.php' );
}

// Run after theme loaded
add_action('plugins_loaded', 'tailored_tools_after_setup_theme');
function tailored_tools_after_setup_theme() {
	//	Contact Form
	if (!class_exists('ContactForm'))				require( dirname(__FILE__).'/tools/form.contact.php' );
//	if (!class_exists('DummyForm'))					require( dirname(__FILE__).'/tools/form.dummy.php' );
}


/**
 *	Gitlab updater
 */
add_action('admin_init', function() {
	if (!class_exists('Moenus\GitLabUpdater\PluginUpdater')) require_once 'wp-gitlab-updater/plugin-updater.php';

	new Moenus\GitLabUpdater\PluginUpdater( [
		'slug'				=> 'tailored-tools', 
		'plugin_base_name'	=> 'tailored-tools/tools.php', 
		'access_token'		=> 'GSCwURSXGgHBLhH8Su8x', 	// hosting@tailored.com.au
		'gitlab_url'		=> 'https://gitlab.com',
		'repo'				=> 'tailored-wp-plugins/tailored-tools',
	] );
});







?>