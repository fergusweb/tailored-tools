(function () {
	tinymce.PluginManager.add('ttools_columns', function (editor, url) {
		editor.addButton('ttools_columns', {
			type: 'menubutton',
			tooltip: 'Content Columns',
			menu: Object.keys(ttools_columns).map(function (key) {
				return {
					text: key,
					onclick: function () {
						editor.insertContent(ttools_columns[key]);
					}
				};
			}),
		});

	});
})();
