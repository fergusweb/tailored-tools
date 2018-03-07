
(function() {
	tinymce.PluginManager.add('ttools_columns', function(editor, url) {
		editor.addButton('ttools_columns', {
			type:		'menubutton',
			tooltip:	'Content Columns',
			menu:		ttools_tinymce_columns,	// set in HTML
		});

	});
})();
