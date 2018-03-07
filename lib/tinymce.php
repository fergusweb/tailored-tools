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
		add_action('admin_print_footer_scripts', array($this,'localize_vars'));
		$plugins['ttools_extras'] = $this->plugin_url.'js/tinymce-extras.js';
		return $plugins;
	}
	function filter_mce_css($css, $sep=' ,') {
		//$css .= $sep.TAILCORE_PLUGIN_URL.'/mce_styling.css?mod='.date('mdy-Gms', filemtime(TAILCORE_PLUGIN_DIR.'/mce_styling.css'));	
		return $css;
	}
	
	/**
	 *	Localize some variables for use in tinymce.js.php
	 */
	function localize_vars() {
		?>
<script>
/* <![CDATA[ */
var ttools_tinymce_shortcuts = [
	<?php
	$buttons = apply_filters('tailored_tools_mce_buttons', array());
	foreach ($buttons as $button) {
		echo "\n".'{ text:"'.$button['label'].'", onclick: function() {editor.insertContent("'.$button['shortcode'].'");} },';
	}
	?>
];
/* ]]> */</script>
		<?php
	}
}

?>