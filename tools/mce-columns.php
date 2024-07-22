<?php
/**
 *	If current theme is Genesis, then add some shortcodes to the editor for .one-half etc columns
 */

new ttools_mce_columns();

class ttools_mce_columns {
	
	function __construct() {
		// Only offer this if using a Genesis theme
		$theme = wp_get_theme();
		if (!in_array('genesis', array( strtolower($theme->get('Name')), strtolower($theme->get('Template')) )))	return false;

		// Set these
		$this->plugin_url		= plugin_dir_url(dirname(__FILE__));
		$this->plugin_dir		= trailingslashit(dirname(__FILE__));
		
		// Tiny MCE Filters
		add_filter('mce_buttons',			array($this,'filter_mce_buttons'));
		add_filter('mce_external_plugins',	array($this,'filter_mce_external_plugins'));
		add_filter('tailored_tools_mce_columns', array($this,'mce_shortcodes'));
		
		// Admin CSS
		add_action('admin_enqueue_scripts', array($this,'admin_enqueue'));
	}
	
	function admin_enqueue() {
		wp_enqueue_style('ttools-admin');
	}
	
	/**
	 *	Tiny MCE Filters
	 */
	function filter_mce_buttons($buttons) {
		array_push($buttons, '|', 'ttools_columns');
		return $buttons;
	}
	function filter_mce_external_plugins($plugins) {
		// Using localize to get some data into the DOM that we can use in our JS
		$data = array();
		$buttons = apply_filters('tailored_tools_mce_columns', array());
		foreach ($buttons as $button) {
			$data[ $button['label'] ] = $button['shortcode'];
		}
		wp_localize_script( 'wp-tinymce', 'ttools_columns', $data );
		// And add our plugin
		$plugins['ttools_columns'] = plugins_url('../js/tinymce-columns.js', __FILE__);
		return $plugins;
	}
	
	/**
	 *	Shortcodes to insert
	 */
	function mce_shortcodes($buttons) {
		if (!is_array($buttons))	$buttons = array();
		
		array_push($buttons, array(
			'label'		=> 'Two Columns 1:1',
			'shortcode'	=> addslashes('<div class="one-half first"><p>First Column</p></div><div class="one-half"><p>Second Column</p></div>'),
		));
		
		array_push($buttons, array(
			'label'		=> 'Two Columns 1:2',
			'shortcode'	=> addslashes('<div class="one-third first"><p>First Column</p></div><div class="two-thirds"><p>Second Column</p></div>'),
		));
		array_push($buttons, array(
			'label'		=> 'Two Columns 2:1',
			'shortcode'	=> addslashes('<div class="two-thirds first"><p>First Column</p></div><div class="one-third"><p>Second Column</p></div>'),
		));
		array_push($buttons, array(
			'label'		=> 'Three Columns 1:1:1',
			'shortcode'	=> addslashes('<div class="one-third first"><p>First Column</p></div><div class="one-third"><p>Second Column</p></div><div class="one-third"><p>Third Column</p></div>'),
		));
		
		array_push($buttons, array(
			'label'		=> 'Two Columns 1:3',
			'shortcode'	=> addslashes('<div class="one-fourth first"><p>One Fourth</p></div><div class="three-fourths"><p>Three Fourths</p></div>'),
		));
		array_push($buttons, array(
			'label'		=> 'Two Columns 3:1',
			'shortcode'	=> addslashes('<div class="three-fourths first"><p>Three Fourths</p></div><div class="one-fourth"><p>One Fourth</p></div>'),
		));
		array_push($buttons, array(
			'label'		=> 'Three Columns 1:2:1',
			'shortcode'	=> addslashes('<div class="one-fourth first"><p>One Fourth</p></div><div class="one-half"><p>One Half</p></div><div class="one-fourth"><p>One Fourth</p></div>'),
		));
		array_push($buttons, array(
			'label'		=> 'Four Columns 1:1:1:1',
			'shortcode'	=> addslashes('<div class="one-fourth first"><p>First Column</p></div><div class="one-fourth"><p>Second Column</p></div><div class="one-fourth"><p>Third Column</p></div><div class="one-fourth"><p>Fourth Column</p></div>'),
		));
		
		return $buttons;
	}
	
	
	
}




?>