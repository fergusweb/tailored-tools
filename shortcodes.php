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
			'shortcode'	=> '[pagecontent id=\"99\"]',
		));
		array_push($buttons, array(
			'label'		=> 'Google Map',
			'shortcode'	=> '[googlemap address=\"1 Cavill Ave, Surfers Paradise, QLD\" width=\"500\" height=\"400\" zoom=\"16\"]',
		));
		
		return $buttons;
	}
	
	
	/**
	 *	Shortcode:  [tabs] for jQuery UI Tabs
	 *	Javascript does the heavy lifting
	 *	Revised this to use SmartDOMDocument instead of str_replace, to allow for <h2 id="something">
	 */
	function shortcode_ui_tabs($atts=false, $content=null) {
		if (!class_exists('SmartDOMDocument'))	require_once('lib/class.smartdomdocument.php');
		// Strip start and end <p> tags to avoid broken HTML
		if (substr($content, 0, 4)=='</p>')	$content = substr($content, 4);
		if (substr($content, -3, 3)=='<p>')	$content = substr($content, 0, -3);
		$content = trim($content);
		
		$dom = new SmartDOMDocument();
		$dom->loadHTML( $content );
		// Loop H2 and wrap
		$nodes = $dom->getElementsByTagName('h2');
		foreach ($nodes as $i => $h) {
			$div = $dom->createElement('div');
			$div->setAttribute('class', 'tab_panel');
			while ($h->nextSibling && $h->nextSibling->localName != 'h2') {
				$div->appendChild( $h->nextSibling );
			}
			if ($h->nextSibling) {
				$h->parentNode->insertBefore($div, $h->nextSibling);
			} else {
				$h->parentNode->appendChild($div);
			}
		}
		
		$output = $dom->saveHTMLExact();
		$output = '<div class="ui_tabs">'."\n".do_shortcode($output)."\n".'</div>'."\n";
		// Try this to remove empty paragraphs
		$output = str_replace(array('<p></p>', '<p>&nbsp;</p>'), '', $output);
		// Fix some strange HTML
		$output = str_replace('<p><form', '<form', $output);
		$output = str_replace('</form></p>', '</form>', $output);
		$output = str_replace("</form>\n</p>", '</form>', $output);
		
		wp_enqueue_script('jquery-ui-tabs');
		return $output;
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