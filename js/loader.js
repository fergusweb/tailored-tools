
/**
 *	Autoloader to validate any form with class 'validate'
 */
jQuery(document).ready(function($){
	if ($('form.validate').length < 1)		return; 
	$('form.validate').each(function(i) {
		$(this).validate();
	});
});

/**
 *	Autoload datepicker fields - assumes ui-datepicker loaded
 */
jQuery(function($) {
	if ($('form .datepicker').length < 1)	return;
	$('form input.datepicker, form p.datepicker input').datepicker({
		dateFormat:		'dd-mm-yy',
		changeMonth:	true,
		changeYear:		true
	});
	// Date of birth fields
	var d = new Date();
	var range = (d.getFullYear()-60)+':'+(d.getFullYear()+1);
	$('form .dob input.hasDatepicker').datepicker('option', 'yearRange', range);
});

/**
 *	Autoload Chosen script select fields with more than 3 options
 */
jQuery(function($) {
	if ($('form p label select').length < 1)	return;
	$('form p label select').each(function(i) {
		if ($(this).find('option').length > 3) {
			$(this).chosen();
		}
	});
});


/**
 *	Launch jQuery UI Tabs for .ui_tabs sections
 */
var tab_counter = 0;
jQuery(document).ready(function($) {
	if ($('.ui_tabs').size() < 1) { return; }
	$('.ui_tabs').each(function(i, tabset) {
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

