
/**
 *	Autoloader to validate any form with class 'validate'
 */
jQuery(document).ready(function($){
	if ($('form.validate').size() < 1) { return; }
	$('form.validate').each(function(i) {
		$(this).validate();
	});
});

/**
 *	Autoload datepicker fields - assumes ui-datepicker loaded
 */
jQuery(function($) {
	if ($('form .datepicker').size() < 1) { return; }
	$('form input.datepicker, form p.datepicker input').datepicker({
		dateFormat: 'dd-mm-yy'
	});
});

/**
 *	Launch jQuery UI Tabs for .ui_tabs sections
 */
var tab_counter = 0;
jQuery(document).ready(function($) {
	if ($('.format_text .ui_tabs').size() < 1) { return; }
	$('.format_text .ui_tabs').each(function(i, tabset) {
		var ul = $( document.createElement('ul') );
		//var ul = $('ul');
		$(tabset).find('.tab_panel').each(function() {
			tab_counter++;
			label = $(this).find('h2:first').text();
			if (label == '')	label = 'TAB_TITLE_MISSING';
			$(ul).append('<li><a href="#tab-' + tab_counter + '"><span>' + label + '</span></a></li>');
			$(this).attr( 'id', 'tab-'+tab_counter );
		});
		$(ul).prependTo( $(tabset) );
		$(tabset).tabs();
	});
});

