<?php

/**
 *	Additional Shortcodes
 */


new TailoredTools_Shortcodes();


class TailoredTools_Shortcodes {

	function __construct() {
		add_shortcode('tabs', array(&$this,'shortcode_ui_tabs'));
		add_shortcode('pagecontent', array(&$this,'shortcode_pagecontent'));
		add_filter('tailored_tools_mce_buttons', array(&$this,'add_mce_buttons'));
	}
	
	
	/**
	 *	Register our buttons for TinyMCE to add our shortcode
	 */
	function add_mce_buttons($buttons) {
		array_push($buttons, array(
			'label'		=> 'Tabbed Content',
			'shortcode'	=> "[tabs]<br /><h2>Tab Heading</h2><p>Content</p><h2>Tab Heading</h2><p>Content</p>[/tabs]",
		));
		array_push($buttons, array(
			'label'		=> 'Include Content',
			'shortcode'	=> "[pagecontent id='99']",
		));
		return $buttons;
	}
	
	
	/**
	 *	Shortcode:  [tabs] for jQuery UI Tabs
	 *	Javascript does the heavy lifting
	 */
	function shortcode_ui_tabs($atts=false, $content=null) {
		// Strip start and end <p> tags to avoid broken HTML
		if (substr($content, 0, 4)=='</p>')	$content = substr($content, 4);
		if (substr($content, -3, 3)=='<p>')	$content = substr($content, 0, -3);
		$content = trim($content);
		// Apply a wrapper for each panel
		$content = str_replace('<h2>', '</div><div class="tab_panel"><h2>', $content);
		// Fix start and end <div> panels to avoid broken HTML
		if (substr($content, 0, 6)=='</div>')	$content = substr($content, 6);
		$content .= '</div>'."\n";
		// Using do_shortcode() to apply shortcodes inside the tabs
		$content = '<div class="ui_tabs">'."\n".do_shortcode($content)."\n".'</div>'."\n";
		// Add JS
		wp_enqueue_script('jquery-ui-tabs');
		// Return
		return $content;
	}
	

	/**
	 *	Shortcode: [pagecontent] to include content from another page
	 *	Used for templates & includes. Optionally include heading too.
	 *	Usage:  [pagecontent id="99" include_title="yes"]
	 */
	function shortcode_pagecontent($atts=false) {
		$atts = shortcode_atts(array(
			'id'			=> false,
			'include_title'	=> (strtoupper($atts['include_title'])=='YES') ? true : false,
		), $atts);
		if (!is_numeric($atts['id']))	return '';
		$page = get_page($atts['id']);
		if (!$page)						return '';
		ob_start();
		echo apply_filters('the_content', $page->post_content);
		return ob_get_clean();
	}

}


?>