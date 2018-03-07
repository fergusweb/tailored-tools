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

(function() {
	tinymce.PluginManager.add('ttools_columns', function(editor, url) {
		editor.addButton('ttools_columns', {
			type:		'menubutton',
			tooltip:	'Content Columns',
			menu:		[
				<?php
				$buttons = apply_filters('tailored_tools_mce_columns', array());
				foreach ($buttons as $button) {
					echo "\n".'{ text:"'.$button['label'].'", onclick: function() {editor.insertContent("'.$button['shortcode'].'");} },';
				}
				?>
			],
		});

	});
})();
