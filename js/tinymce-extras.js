
(function() {
	tinymce.PluginManager.add('ttools_extras', function(editor, url) {
		editor.addButton('ttools_extras', {
			type:		'menubutton',
			tooltip:	'Tailored Tools Extras',
			menu:		ttools_tinymce_shortcuts,	// Set in HTML code
		});

	});
})();
