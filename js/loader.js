

/**
 *	Event Tracking with Google Analytics
 *	Can put this function in your theme's custom.js if you want to apply Analytics Event Tracking to your forms
 *
jQuery(document).ready(function($){
	// Check on Google Analytics
	if (!window._gat || !window._gat._getTracker)	return;
	// Contact form
	$('form.contact').submit(function(e) {
		if ( !$(this).hasClass('validate') || ($().validate && $(this).valid()) ) {
			_trackEvent('forms', 'submit', 'Contact form was used')
		}
	});
});


/**
 *	Autoloader validation on any form.validate elements
 */
jQuery(document).ready(function($){
	if ($('form.validate').length < 1)		return; 
	if (!$().validate)						return;
	$('form.validate').each(function(i) {
		$(this).validate();
	});
});


/**
 *	Autoload datepicker fields
 */
jQuery(function($) {
	if ($('form .datepicker').length < 1)	return;
	if (!$().datepicker)					return;
	$('form input.datepicker, form p.datepicker input').datepicker({
		dateFormat:		'dd/mm/yy',
		changeMonth:	true,
		changeYear:		true
	});
});

/**
 *	Autoload timepicker fields
 */
jQuery(function($) {
	if ($('form .timepicker').length < 1)	return;
	if (!$().timepicker)					return;
	$('form input.timepicker, form p.timepicker input').timepicker({
		timeFormat:		'h:mm tt',
		stepMinute:		15,
		hourMin:		8,
		hourMax:		17
	});
});

/**
 *	Autoload datetimepicker fields
 */
jQuery(function($) {
	if ($('form .datetimepicker').length < 1)	return;
	if (!$().datetimepicker)					return;
	$('form input.datetimepicker, form p.datetimepicker input').datetimepicker({
		changeMonth:	true,
		changeYear:		true,
		dateFormat:		'dd/mm/yy',
		timeFormat:		'h:mm tt',
		stepMinute:		15,
		hourMin:		8,
		hourMax:		17
	});
});

/**
 *	If neccessary, can modify time options in another script to remove the hour restrictions.
 *	Like so:
 *
jQuery(document).ready(function($){
	$('form input.datetimepicker, form input.timepicker').datetimepicker('option', 'hourMin', 0);
	$('form input.datetimepicker, form input.timepicker').datetimepicker('option', 'hourMax', 24);
});


/**
 *	Fix for Date Of Birth fields.  Changes the Year range.
 *	Applies to both datepicker and timepicker fields.
 */
jQuery(function($) {
	if ($('form .dob input.hasDatepicker').length < 1)	return;
	var d = new Date();
	var range = (d.getFullYear()-80)+':'+(d.getFullYear()+1);
	$('form .dob input.hasDatepicker').datepicker('option', 'yearRange', range);
});


/**
 *	Autoload Chosen script select fields with more than 3 options
 */
jQuery(function($) {
	if ($('form p label select').length < 1)	return;
	if (!$().chosen)							return;
	$('form p label select').each(function(i) {
		if ($(this).parent().parent().hasClass('nochosen'))	return true;
		if ($(this).find('option').length > 3) {
			$(this).chosen();
		}
	});
});


/**
 *	Launch jQuery UI Tabs for .ui_tabs sections
 *	Revised to handle different HTML format, to handle <h2 id="something">
 */
var tab_counter = 0;
jQuery(document).ready(function($) {
	if ($('.ui_tabs').size() < 1)		return;
	if (!$().tabs)						return;
	$('.ui_tabs').each(function(i, tabset) {
		var ul = $( document.createElement('ul') );
		$(tabset).find('.tab_panel').each(function() {
			tab_counter++;
			tab_id = 'tab-'+tab_counter;
			label = $(this).prev('h2').text();
			hid = $(this).prev('h2').attr('id');
			if (typeof hid !== 'undefined' && hid !== false) {
				tab_id = hid;
			}
			$(this).prev('h2').prependTo( $(this) ).removeAttr('id');
			if (label == '')	label = 'TAB_TITLE_MISSING';
			$(ul).append('<li><a href="#' + tab_id + '"><span>' + label + '</span></a></li>');
			$(this).attr( 'id', tab_id );
			// Now set up triggers for <a href="#something"> matching our tab_ids
			var $this = $(this);
			$("a[href$='#"+tab_id+"']").click(function(e) {
				hash = $(this).attr('href').substr($(this).attr('href').indexOf('#')+1);
				$('.ui-tabs a[href=#'+hash+']').click();
				$('html,body').animate({ scrollTop:$('.ui-tabs a[href=#'+tab_id+']').offset().top-30 },500);
				e.preventDefault;
				return false;
			});
		});
		$(ul).prependTo( $(tabset) );
		$(tabset).tabs();
	});
});


