(function () {
	tinymce.PluginManager.add('ttools_extras', function (editor, url) {
		editor.addButton('ttools_extras', {
			type: 'menubutton',
			tooltip: 'Tailored Tools Extras',
			menu: Object.keys(ttools_extras).map(function (key) {
				return {
					text: key,
					onclick: function () {
						editor.insertContent(ttools_extras[key]);
					}
				};
			}),
		});

	});
})();
