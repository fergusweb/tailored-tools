<?php
/**
 *	Tweaks TinyMCE to let us easily add shortcut/shortcode buttons
 */

$TailoredTinyMCE = new TailoredTinyMCE();

class TailoredTinyMCE {
	
	function __construct() {
		$this->plugin_url		= plugin_dir_url(dirname(__FILE__));
		$this->plugin_dir		= trailingslashit(dirname(__FILE__));
		// Admin only:
		if (!is_admin())		return;
		add_action('init', array(&$this,'tiny_mce_init'));
	}

		
	/**
	 *	Admin Init
	 */
	function tiny_mce_init() {
		if ( !current_user_can('edit_posts') && !current_user_can('edit_pages') ) { return; }
		// Rich editor
		add_filter('mce_buttons',			array(&$this,'filter_mce_buttons'));
		add_filter('mce_external_plugins',	array(&$this,'filter_mce_external_plugins'));
		add_filter('mce_css',				array(&$this,'filter_mce_css'));
	}
	

	/**
	 *	Tiny MCE Filters
	 */
	function filter_mce_buttons($buttons) {
		array_push($buttons, '|', 'tailored_tools');
		return $buttons;
	}
	function filter_mce_external_plugins($plugins) {
		$plugins['tailored_tools'] = $this->plugin_url.'resource/tinymce.js.php';
		return $plugins;
	}
	function filter_mce_css($css, $sep=' ,') {
		//$css .= $sep.TAILCORE_PLUGIN_URL.'/mce_styling.css?mod='.date('mdy-Gms', filemtime(TAILCORE_PLUGIN_DIR.'/mce_styling.css'));	
		return $css;
	}
	
}

?>