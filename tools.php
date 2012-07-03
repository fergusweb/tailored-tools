<?php
/*
Plugin Name:	Tailored Tools
Description:	Adds some functionality to WordPress that you'll need.
Version:		1.3.3
Author:			Tailored Web Services
Author URI:		http://www.tailored.com.au
*/




//	Include Helper Classes
if (!class_exists('TailoredTinyMCE'))			require( dirname(__FILE__).'/lib/tinymce.php' );
if (!class_exists('TailoredForm'))				require( dirname(__FILE__).'/lib/class.forms.php' );
if (!class_exists('TailoredTools_Shortcodes'))	require( dirname(__FILE__).'/shortcodes.php' );

// Anti-spam Modules
if (!class_exists('Tailored_reCAPTCHA'))		require( dirname(__FILE__).'/lib/class.recaptcha.php' );
if (!class_exists('Tailored_Akismet'))			require( dirname(__FILE__).'/lib/class.akismet.php' );
if (!class_exists('Tailored_Tools_AYAH'))		require( dirname(__FILE__).'/lib/class.ayah.php' );

//	Contact Form
if (!class_exists('ContactForm'))		require( dirname(__FILE__).'/form.contact.php' );

//	Sample Form
//if (!class_exists('SampleForm')		require( dirname(__FILE__).'/form.sample.php' );





?>