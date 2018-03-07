<?php
// Load as Javascript
header('Content-Type: application/javascript');

// Prepare our DB connections & actions/filters
// This is a bit awkward, but allows for some non-standard file paths
$path = explode('wp-content', __FILE__)[0];
$wpload = array(
	$path.'wp-load.php',
	$path.'wordpress/wp-load.php',
);
foreach ($wpload as $wpload_file) {
	if (is_file($wpload_file)) {
//		echo 'Requiring file: '.$wpload_file.PHP_EOL.PHP_EOL;
		require_once($wpload_file);
		break;
	}
}

?>
//alert('DEBUG: Tailored Tools MCE JS loaded');
(function() {
	tinymce.PluginManager.add('ttools_extras', function(editor, url) {
		editor.addButton('ttools_extras', {
			type:		'menubutton',
			tooltip:	'Tailored Tools Extras',
			menu:		[
				<?php
				$buttons = apply_filters('tailored_tools_mce_buttons', array());
				foreach ($buttons as $button) {
					echo "\n".'{ text:"'.$button['label'].'", onclick: function() {editor.insertContent("'.$button['shortcode'].'");} },';
				}
				?>
			],
		});

	});
})();
