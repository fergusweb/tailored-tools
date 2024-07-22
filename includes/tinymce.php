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
		add_action('init', array($this,'tiny_mce_init'));
	}

		
	/**
	 *	Admin Init
	 */
	function tiny_mce_init() {
		if ( !current_user_can('edit_posts') && !current_user_can('edit_pages') ) { return; }
		// Rich editor
		add_filter('mce_buttons',			array($this,'filter_mce_buttons'));
		add_filter('mce_external_plugins',	array($this,'filter_mce_external_plugins'));
		add_filter('mce_css',				array($this,'filter_mce_css'));
	}
	

	/**
	 *	Tiny MCE Filters
	 */
	function filter_mce_buttons($buttons) {
		array_push($buttons, '|', 'ttools_extras');
		return $buttons;
	}
	function filter_mce_external_plugins($plugins) {
		// Using localize to get some data into the DOM that we can use in our JS
		$data = array();
		$buttons = apply_filters('tailored_tools_mce_buttons', array());
		foreach ($buttons as $button) {
			$data[ $button['label'] ] = $button['shortcode'];
		}
		wp_localize_script( 'wp-tinymce', 'ttools_extras', $data );
		// And add our plugin
		$plugins['ttools_extras'] = $this->plugin_url.'js/tinymce.js';
		return $plugins;
	}
	function filter_mce_css($css, $sep=' ,') {
		//$css .= $sep.TAILCORE_PLUGIN_URL.'/mce_styling.css?mod='.date('mdy-Gms', filemtime(TAILCORE_PLUGIN_DIR.'/mce_styling.css'));	
		return $css;
	}
	
}

?>