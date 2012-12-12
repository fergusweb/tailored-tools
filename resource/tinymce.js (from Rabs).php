<?php
// Load as Javascript
header('Content-Type: application/javascript');
// Prepare our DB connections & actions/filters
require_once('../../../../wp-load.php');

?>
//alert('DEBUG: Tailored Tools MCE JS loaded');
(function() {
	tinymce.create('tinymce.plugins.tailored_tools', {
		/**
		 * @param {tinymce.Editor} ed Editor instance that the plugin is initialized in.
		 * @param {string} url Absolute URL to where the plugin is located.
		 */
		init : function(ed, url) {
		},

		/**
		 * @param {String} n Name of the control to create.
		 * @param {tinymce.ControlManager} cm Control manager to use inorder to create new control.
		 * @return {tinymce.ui.Control} New control instance or null if no control was created.
		 */
		createControl : function(n, cm) {
			switch (n) {
			  case 'tailored_tools':
				var c = cm.createSplitButton('TailoredTools', {
					title : 'Tailored Tools',
					image : '<?php echo $TailoredTinyMCE->plugin_url; ?>resource/mce-icon.gif',
					onclick : function() {
						c.showMenu();
					}
				});
				
				c.onRenderMenu.add(function(c, m) {
					m.add({title: 'Tailored Tools', 'class' : 'mceMenuItemTitle'}).setDisabled(1);
					<?php
					$buttons = apply_filters('tailored_tools_mce_buttons', array());
					foreach ($buttons as $button) {
						//echo '<pre>'; print_r($button); echo '</pre>';
						echo "\n".'m.add({title: "'.$button['label'].'", onclick: function() {	mce_tailtools_InsertTag("'.$button['shortcode'].'");		}});';
					}
					?>
					
				});
				
				// Return the new splitbutton instance
				return c;
			}
			return null;
		},

		/**
		 * @return {Object} Name/value array containing information about the plugin.
		 */
		getInfo : function() {
			return {
				longname : "Tailored Tools Addon Button",
				author : 'Tailored Web Services',
				authorurl : 'http://www.tailored.com.au/',
				infourl : 'http://www.tailored.com.au/',
				version : "1.1"
			};
		}
	});

	// Register plugin
	tinymce.PluginManager.add('tailored_tools', tinymce.plugins.tailored_tools);
})();



function mce_tailtools_InsertTag(tag) {
  if ( typeof tinyMCE != 'undefined' && ( ed = tinyMCE.activeEditor ) && !ed.isHidden() ) {
	ed.focus();
	if (tinymce.isIE)	ed.selection.moveToBookmark(tinymce.EditorManager.activeEditor.windowManager.bookmark);		
	ed.execCommand('mceInsertContent', false, tag);
  }
}


