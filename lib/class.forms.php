<?php

/**
 *	Form Helper Class
 *	Specific forms can extend this
 */


abstract class TailoredForm {
	public		$form_action	= false;
	public		$form_class		= false;
	public		$form_enctype	= false;			// If uploads, change to: 'multipart/form-data';
	public	$error, $success	= false;
	public		$debug			= false;
	// Which anti-spam modules are available?
	public		$avail_recaptcha= false;
	public		$avail_akismet	= false;
	public		$avail_ayah		= false;
	public		$check_bad_words= true;				// Turn this on child-classes to enable check.
	// Customise these in child-class
	public		$nonce			= 'tailored-tools';
	public		$admin_menu		= 'index.php';		// parent-hook for add_menu_item
	public		$form_name		= false;
	public		$questions		= false;			// Will be an array of questions.  See docs for sample.
	public		$option_key		= 'ttools_option_key';
	public		$shortcode		= 'FormShortcode';
	public		$log_type		= false;			// False to disable logging, or post-type
	public		$submit_key		= 'submit_form';	// submit button key - for processing.
	public		$submit_label	= 'Submit Form';
	
	
	/**
	 *	Constructor
	 */
	abstract function __construct();
	
	
	/**
	 *	Option helper functions
	 */
	function load_options() {
		$this->opts = get_option($this->option_key);
		if (!$this->opts)	$this->default_options();
		
		if (!$this->avail_recaptcha)	$this->opts['recaptcha']['use'] = false;
		if (!$this->avail_akismet)		$this->opts['akismet']['use'] = false;
		if (!$this->avail_ayah)			$this->opts['ayah']['use'] = false;
		
		// Load akismet API key if one not already specified
		if (empty($this->opts['akismet']['api_key']))	$this->opts['akismet']['api_key'] = get_option('wordpress_api_key');
		
		return $this->opts;
	}
	function save_options($options = false) {
		if (!$options) { $options = $this->opts; }
		update_option($this->option_key, $options);
	}
	abstract function default_options();


	
	/**
	 *	Init - call from extended class
	 */
	function init() {
		// Prepare
		$this->error = array();
		$this->files = array();
		$this->load_options();
		if (!$this->form_action)	$this->form_action = esc_url($_SERVER['REQUEST_URI']);
		if (is_admin()) {
			add_action('admin_menu', array(&$this,'admin_menu'), 11);
			add_action('load-dashboard_page_'.$this->option_key, array(&$this,'output_csv_logs'));
			return;
		}
		// Actions
		add_action('wp_enqueue_scripts', array(&$this,'enqueue_scripts'));
		add_action('template_redirect', array(&$this,'process_form'));
		add_shortcode($this->shortcode, array(&$this,'handle_shortcode'));
		add_filter('ttools_form_filter_email_headers', array(&$this,'filter_headers'), 10, 2);
		// In case we need to tie-in with a particular form
		add_action('wp_print_footer_scripts ', array(&$this,'print_footer_scripts'));
		// TinyMCE Button
		add_filter('tailored_tools_mce_buttons', array(&$this,'add_mce_button'));
		// Build bad-words array
		add_filter('ttools_form_bad_words_to_check', array(&$this,'filter_bad_words_to_check'), 10, 2);
	}

	
	
	/**
	 *	Enqueue scripts & styles
	 */
	function enqueue_scripts() {
		wp_enqueue_script('ttools-loader');
		wp_enqueue_style('jquery-chosen');
		wp_enqueue_style('ttools');
	}
	
	// Over-ride if you need in special cases.
	function print_footer_scripts() {
	}
	
	

	
	/**
	 *	Register our button for TinyMCE to add our shortcode
	 */
	function add_mce_button($buttons) {
		return $buttons;
	}
	
	
	/**
	 *	Log form submission as a custom post-type
	 */
	function log_form($data=false) {
		if (!$this->log_type || !data || empty($data))		return false;
		$insertID = wp_insert_post(array(
			'post_title'	=> '',
			'post_content'	=> serialize($data),
			'post_status'	=> 'private',
			'post_type'		=> $this->log_type,
		));
		return $insertID;
	}
	
	
	/**
	 *	Filter to generate email headers
	 */
	function filter_headers($headers=false, $form=false) {
		// Only run for specific form
		if ($this->form_name !== $form->form_name)	return $headers;
		// By default, we send "from" the WordPress blog & admin email.
		// Over-ride this function to send from customer details.
		$headers = array(
			'From: '.get_bloginfo('name').' <'.get_bloginfo('admin_email').'>',
			'Reply-To: '.get_bloginfo('name').' <'.get_bloginfo('admin_email').'>',
			'Return-Path: '.get_bloginfo('name').' <'.get_bloginfo('admin_email').'>',
		);
		return $headers;
	}
	
	
	/**
	 *	Process upload & prepare attachments
	 */
	function process_upload() {
		$dir = wp_upload_dir();
		foreach ($this->questions as $key => $q) {
			if ($q['type'] != 'file')		continue;
			$upload = $dir['basedir'].'/'.$_FILES[$key]['name'];
			if (!move_uploaded_file($_FILES[$key]['tmp_name'], $upload))	continue;
			$this->files[] = $upload;
		}
	}
	
	/**
	 *	Build message string from $questions array
	 */
	function build_message($formdata=false) {
		$message = '';
		foreach ($this->questions as $key => $q) {
			$message .= $this->build_message_line($formdata, $key, $q);
		}
		return $message;
	}
	
	function build_message_line($formdata, $key, $q) {
		$nl = " \r\n";
		// Fieldset
		if ($q['type'] == 'fieldset') {
			$string = $q['label'].$nl;
			foreach ($q['questions'] as $kk => $qq) {
				$string .= $this->build_message_line($formdata, $kk, $qq);
			}
			return $string;
		}
		// Separators
		if ($q['type'] == 'sep') {
			return $nl;
		}
		// Question
		$value = $formdata[$key];
		if (is_array($formdata[$key]))		$value = $nl.' - '.implode($nl.' - ', $formdata[$key]);
		if ($q['type'] == 'textarea')		$value = $nl.$formdata[$key];
		return $q['label'].': '.$value.$nl.$nl;
	}
	
	
	/**
	 *	Process form submission
	 */
	function process_form() {
		// Are we processing?
		if (empty($_POST) || !isset($_POST[$this->submit_key]))	return;
		// Validate the form
		if (!$this->validate_form())	return;
		// Handle file uploads
		if (!empty($_FILES))			$this->process_upload();

		// Prepare form data array
		$formdata = $this->process_form_prepare_data();
		
		// Prepare email message
		$message = $this->build_message($formdata);
		
		// Prepare email headers - how do we know which?
		$headers = apply_filters('ttools_form_filter_email_headers', false, $this);
		if (!empty($this->opts['email']['bcc']))	$headers[] = 'BCC: '.$this->opts['email']['bcc'];
		
		// Debugging
		if ($this->debug) {
			echo '<pre>Form Data - '; print_r($formdata); echo '</pre>';
			echo '<p>Headers:<br />'; foreach($headers as $h) echo htmlentities($h).'<br>'; echo '</p>';
			echo '<p>---Message---<br />'.nl2br($message).'</p>'."\n";
			if (!empty($this->files))	echo '<p>Attachments:<br> - '.implode('<br> - ',$this->files).'</p>';
		}
		
		// Log Data
		$this->log_form($formdata);
		// Send email
		$this->was_mail_sent = wp_mail($this->opts['email']['to'], $this->opts['email']['subject'], $message, $headers, $this->files);
		// Delete any uploaded attachments
		foreach ($this->files as $file) {
			unlink($file);
		}
		// Handle redirection or response
		$this->redirect_or_response();
	}
	
	function process_form_prepare_data() {
		// Fields to ignore
		$ignore_fields = array( $this->submit_key, 'recaptcha_challenge_field', 'recaptcha_response_field' );
		$ignore_fields = apply_filters('ttools_form_filter_ignore_fields', $ignore_fields, $this);
		// Prepare data from $_POST
		$formdata = array();
		foreach ($_POST as $key => $val) {
			if (in_array($key, $ignore_fields)) { continue; }
			$formdata[$key] = stripslashes_deep($val);
		}
		$formdata['Viewing'] = get_permalink();
		return $formdata;
	}
	
	function redirect_or_response() {
		// Handle redirection/response
		if (!$this->was_mail_sent) {
			$this->error[] = nl2br($this->opts['failure']['message']);
		} else {
			$this->success[] = nl2br($this->opts['success']['message']);
			if (!empty($this->opts['success']['redirect'])) {
				wp_redirect($this->opts['success']['redirect']);
				exit;
			}
		}
	}
	
	
	
	/**
	 *	Validate form submission
	 */
	function validate_form() {
		// Required Fields
		foreach ($this->questions as $key => $q) {
			if ($q['type'] == 'fieldset') {
				foreach ($q['questions'] as $kk => $qq) {
					$this->validate_question($kk, $qq);
				}
			} else {
				$this->validate_question($key, $q);
			}
		}
		// Filter, so modules can apply validation per-form
		$this->error = apply_filters('ttools_form_filter_validate', $this->error, $this);
		// Now check for bad words?
		$this->validate_bad_words();
		// Return true or false
		return (empty($this->error)) ? true : false;
	}
	
	function validate_question($key, $q) {
		if (!$q['required'])		return;
		if ($q['type'] != 'file') {
			if (!isset($_POST[$key]) || empty($_POST[$key]))								$this->error[] = $q['error'];
			if ($q['type'] == 'email' && !empty($_POST[$key]) && !is_email($_POST[$key]))	$this->error[] = '<em>'.$_POST[$key].'</em> does not look like an email address';
		} else
		if ($q['type'] == 'file') {
			if (!isset($_FILES[$key]) || $_FILES[$key]['error'] != '0')	$this->error[] = $q['error'];
		}
		
	}
	
	function validate_bad_words() {
		// Only run if flag enabled.
		if (!$this->check_bad_words)	return;
		// Fetch our array of bad words
		$bad_words = apply_filters('ttools_form_bad_words_to_check', false, $this);
		// Build a string of the entire form contents
		$merged = '';	 foreach ($_POST as $key => $val) 	{	$merged .= $val.' '; 	}
		// Check each of our bad words against the merged string.
		foreach ($bad_words as $badword) {
			if (stripos($merged, $badword)) {
				$this->error[] = 'Your message has tripped our spam filter.  Please double check your message, and avoid suspect words like "viagra".';
				break;
			}
		}
	}
	
	
	
	/**
	 *	Array of bad-words to check for.  If the form contains a bad word, we reject it as spam.
	 *	Fetch with:		$badwords = apply_filters('ttools_form_bad_words_to_check', false, $this);
	 */
	function filter_bad_words_to_check($badwords=false, $form=false) {
		if ($this->form_name != $form->form_name)	return $badwords;
		if (!is_array($badwords))					$badwords = array();
		// Add words to existing array
		$badwords = array_merge($badwords, array(
			'ambien', 'cialis', 'buycialis', 'hydrocodone', 'viagraonline', 'cialisonline', 'phentermine', 'viagrabuy', 'percocet', 'tramadol',
			'propecia', 'xenical', 'meridia', 'levitra', 'vicodin', 'viagra', 'valium', 'porno', 'xanax', 'href=', // 'sex', 'soma'
		));
		$badwords = array_unique($badwords);
		return $badwords;
	}
	
	
	
	
	
	
	/**
	 *	Shortcode Handler
	 */
	function handle_shortcode($atts=false) {
		// This allows for a class-override via the shortcode
		$atts = shortcode_atts(array(
			'class'	=> '',
		), $atts);
		if (!empty($atts['class']))	$this->form_class .= ' '.$atts['class'];
		// Now buffer then output form HTML
		ob_start();
		$this->html();
		return ob_get_clean();
	}
	
	
	/**
	 *	Draw Form HTML
	 */
	function draw_form() {	$this->html(); }
	function form_html() {	$this->html(); }
	function html() {
		// Form Feedback
		if (!empty($this->error))	echo '<p class="error"><strong>Errors:</strong><br /> &bull; '.implode("<br /> &bull; ",$this->error)."</p>\n";
		if (!empty($this->success))	echo '<p class="success">'.implode("<br />",$this->success)."</p>\n";
		// Set encoding type
		foreach ($this->questions as $q) {
			if ($q['type'] == 'file')		$this->form_enctype = 'multipart/form-data';
		}
		$enctype = (!$this->form_enctype) ? '' : ' enctype="'.$this->form_enctype.'"';
		// Draw form
		do_action('ttools_form_before_form', $this);
		echo '<form action="'.$this->form_action.'" method="post" class="tws '.$this->form_class.'"'.$enctype.'>'."\n";
		do_action('ttools_form_before_questions', $this);
		foreach ($this->questions as $key => $q) {
			
			if ($q['type'] == 'fieldset') {
				$this->draw_fieldset($key, $q);
				continue;
			}
			// Draw the field element/wrapper
			$this->draw_element($key, $q);
		}
		do_action('ttools_form_before_submit_button', $this);
		// Submit button
		echo '<input type="hidden" name="'.$this->submit_key.'" value="" />'."\n";
		echo '<p class="submit"><input type="submit" name="'.$this->submit_key.'" value="'.$this->submit_label.'" /></p>'."\n";
		do_action('ttools_form_after_submit_button', $this);
		echo '</form>'."\n";
		do_action('ttools_form_after_form', $this);
	}
	
	function draw_fieldset($id, $fieldset) {
		$fid = (!is_numeric($id)) ? ' id="'.$id.'"' : '';
		echo '<fieldset'.$fid.'>'."\n";
		if (!empty($fieldset['label']))	echo "\t".'<legend><span>'.$fieldset['label'].'</span></legend>'."\n";
		foreach ($fieldset['questions'] as $key => $q) {
			$this->draw_element($key, $q);
		}
		echo '</fieldset>'."\n";
	}
	
	function draw_element($key, $q) {
		// Separator line?
		if ($q['type'] == 'sep') { echo '<p class="sep">&nbsp;</p>'."\n"; return; }
		// Heading line
		if ($q['type'] == 'heading') { echo '<p class="heading">'.nl2br($q['label']).'</p>'."\n"; return; }
		// Text Note
		if ($q['type'] == 'note') { echo '<p class="note '.$q['class'].'">'.nl2br($q['label']).'</p>'."\n"; return; }
		// Prepare default value
		if (!isset($_POST[$key]) && !empty($q['default']))	$_POST[$key] = $q['default'];
		// Prepare element class
		if (!is_array($q['class']))		$q['class'] = array($q['class']);
		if (in_array($q['type'], array('radio', 'checkbox')))	$q['class'][] = 'radio';
		foreach ($q['class'] as $k => $c) { if (empty($c)) unset($q['class'][$k]); }
		$q['class'] = (empty($q['class'])) ? '' : ' class="'.implode(' ',$q['class']).'"';
		// Draw appropriate element
		switch ($q['type']) {
			case 'file':			$this->draw_fileupload($key, $q);		break;
			case 'select':			$this->draw_select($key, $q);			break;
			case 'country':			$this->draw_country_select($key, $q);	break;
			case 'radio':			$this->draw_radio($key, $q);			break;
			case 'checkbox':		$this->draw_radio($key, $q);			break;
			case 'textarea':		$this->draw_textarea($key, $q);			break;
			case 'date':			$this->draw_datepicker($key, $q);		break;
			case 'time':			$this->draw_timepicker($key, $q);		break;
			case 'datetime':		$this->draw_datetimepicker($key, $q);	break;
			case 'number':			$this->draw_number_range($key, $q);		break;
			case 'range':			$this->draw_number_range($key, $q);		break;
			case 'hidden':			$this->draw_hidden_input($key, $q);		break;
			default:				$this->draw_input($key, $q);			break;
		}
	}
	
	/**
	 *	Form Element Helpers
	 */
	function draw_input($key, $q) {
		// Allowed inputs
		$allowed_types = array( 'color', 'date', 'datetime', 'datetime-local', 'email', 'month', 'number', 'range', 'search', 'tel', 'time', 'url', 'week' );
		if (!in_array($q['type'], $allowed_types))	$q['type'] = 'text';
		// Either Email or Text
//		if ($q['type'] != 'email')	$q['type'] = 'text';
		// Element class
		$class = array('txt');
		if ($q['type']=='email')	$class[] = 'email';
		if ($q['required'])			$class[] = 'required';
		// Element Attributes
		$attrs = 'type="'.$q['type'].'" name="'.$key.'" id="'.$key.'" class="'.implode(' ',$class).'"';
		if (!empty($q['placeholder']))	$attrs .= ' placeholder="'.esc_attr($q['placeholder']).'"';
		// Draw Element
		echo '<p'.$q['class'].'><label><span>'.$q['label'].'</span>'."\n";
		echo "\t".'<input '.$attrs.' value="'.esc_attr($_POST[$key]).'" /></label></p>'."\n";
	}
	
	function draw_textarea($key, $q) {
		// Element Class
		$class = array('txt');
		if ($q['required'])			$class[] = 'required';
		// Element Attributes
		$attrs = 'name="'.$key.'" id="'.$key.'" class="'.implode(' ',$class).'"';
		if (!empty($q['placeholder']))	$attrs .= ' placeholder="'.esc_attr($q['placeholder']).'"';
		// Draw Element
		echo '<p'.$q['class'].'><label><span>'.$q['label'].'</span>'."\n";
		echo "\t".'<textarea '.$attrs.'>'.esc_textarea($_POST[$key]).'</textarea></label></p>'."\n";
	}
	
	function draw_hidden_input($key, $q) {
		if (!isset($q['value']))	$q['value'] = '';
		if (isset($_POST[$key]))	$q['value'] = $_POST[$key];
		echo '<input type="hidden" name="'.$key.'" value="'.$q['value'].'" />'."\n";
	}
	
	function draw_select($key, $q) {
		// Is this an associative array?
		$is_assoc = array_keys($q['options']) !== range(0, count($q['options']) - 1);
		// Draw Element
		echo '<p'.$q['class'].'><label><span>'.$q['label'].'</span>'."\n";
		echo "\t".'<select name="'.$key.'" id="'.$key.'" class="txt">'."\n";
		foreach ($q['options'] as $val => $opt) {

			if (!$is_assoc)	$val = $opt;
			$sel = ($_POST[$key] == $val) ? ' selected="selected"' : '';
			echo "\t\t".'<option value="'.$val.'"'.$sel.'>'.$opt.'</option>'."\n";
		}
		echo "\t".'</select></label></p>'."\n";
	}
	
	function draw_radio($key, $q) {
		// Set options
		if ($q['type'] != 'checkbox')	$q['type'] = 'radio';
		$name = ($q['type'] == 'checkbox') ? $key.'[]' : $key;
		if ($q['label'])	$q['label'] = '<span class="label">'.$q['label'].'</span>';
		// Is this an associative array?
		$is_assoc = array_keys($q['options']) !== range(0, count($q['options']) - 1);
		// Draw Element
		echo '<p'.$q['class'].'>'.$q['label']."\n";
		foreach ($q['options'] as $val => $opt) {
			if (!$is_assoc)	$val = $opt;
			$sel = ($_POST[$key] == $val || @in_array($val, $_POST[$key])) ? ' checked="checked"' : '';
			echo "\t".'<label><input type="'.$q['type'].'" name="'.$name.'" value="'.$val.'"'.$sel.' /> '.$opt.'</label>'."\n";
		}
		echo '</p>'."\n";
	}
	
	function draw_fileupload($key, $q) {
		echo '<p'.$q['class'].'><label><span>'.$q['label'].'</span>'."\n";
		echo "\t".'<input type="'.$q['type'].'" name="'.$key.'" id="'.$key.'" /></label></p>'."\n";
	}
	
	function draw_datepicker($key, $q) {
		echo '<p'.$q['class'].'><label><span>'.$q['label'].'</span>'."\n";
		echo "\t".'<input type="text" name="'.$key.'" id="'.$key.'" class="txt datepicker" value="'.esc_attr($_POST[$key]).'" /></label></p>'."\n";
	}
	
	function draw_timepicker($key, $q) {
		echo '<p'.$q['class'].'><label><span>'.$q['label'].'</span>'."\n";
		echo "\t".'<input type="text" name="'.$key.'" id="'.$key.'" class="txt timepicker" value="'.esc_attr($_POST[$key]).'" /></label></p>'."\n";
		wp_enqueue_script('jquery-timepicker');
	}
	
	function draw_datetimepicker($key, $q) {
		echo '<p'.$q['class'].'><label><span>'.$q['label'].'</span>'."\n";
		echo "\t".'<input type="text" name="'.$key.'" id="'.$key.'" class="txt datetimepicker" value="'.esc_attr($_POST[$key]).'" /></label></p>'."\n";
		wp_enqueue_script('jquery-timepicker');
	}
	
	function draw_number_range($key, $q) {
		$class = array('txt', 'number');
		if ($q['required'])		$class[] = 'required';
		$class = ' class="'.implode(' ',$class).'"';
		$min = (!empty($q['min'])) ? ' min="'.$q['min'].'"' : '';
		$min = (!empty($q['max'])) ? ' max="'.$q['max'].'"' : '';
		$step = (!empty($q['step'])) ? ' step="'.$q['step'].'"' : '';
		echo '<p'.$q['class'].'><label><span>'.$q['label'].'</span>'."\n";
		echo "\t".'<input type="'.$q['type'].'"'.$class.'  name="'.$key.'" value="'.esc_attr($_POST[$key]).'" id="'.$key.'"'.$min.$max.$step.' /></label></p>'."\n";
		
	}
	
	
	function draw_country_select($key, $q) {
		if (!function_exists('tt_country_array'))	require( plugin_dir_path(__FILE__).'countries.php' );
		// Draw Element
		echo '<p'.$q['class'].'><label><span>'.$q['label'].'</span>'."\n";
		echo "\t".'<select name="'.$key.'" id="'.$key.'" class="txt countries">'."\n";
		// Prepend some custom options for easier forms
		$q['options'] = array(
			false	=> ' - Choose - ',
			'US'	=> 'United States',
			'AU'	=> 'Australia',
			'UK'	=> 'United Kingdom',
		);
		foreach ($q['options'] as $val => $opt) {
			$sel = ($_POST[$key] == $val) ? ' selected="selected"' : '';
			echo "\t\t".'<option value="'.$val.'"'.$sel.'>'.$opt.'</option>'."\n";
		}
		// Standard country options
		$q['options'] = tt_country_array();
		foreach ($q['options'] as $val => $opt) {
			$sel = ($_POST[$key] == $opt) ? ' selected="selected"' : '';
			echo "\t\t".'<option value="'.$opt.'"'.$sel.'>'.$opt.'</option>'."\n";
		}
		echo "\t".'</select></label></p>'."\n";
	}
	
	
	
	
	
	/**
	 *	Admin Functions
	 */
	function admin_menu() {
		if (!$this->form_name)		return false;
		$menu_label == $this->form_name;
		// Add a counter to menu name?
		$counter = '';
		if ($this->log_type) {
			$count = wp_count_posts( $this->log_type );
			if ($count && $count->private>0)	$counter = '<span class="update-plugins"><span class="update-count">'. $count->private .'</span></span>';
		}
		$hook = add_submenu_page($this->admin_menu, $this->form_name, $this->form_name.$counter, 'edit_posts', $this->option_key,  array(&$this,'admin_page'));
		add_action("load-$hook", array(&$this,'admin_enqueue'));
	}
	
	function admin_enqueue() {
		wp_enqueue_style('tailored-tools', plugins_url('resource/admin.css', dirname(__FILE__)));
	}
	
	function admin_page() {
		echo '<div class="wrap">'."\n";
		echo '<h2>'.$this->form_name.'</h2>'."\n";
		// Save Settings
		if (isset($_POST['SaveSettings'])) {
			if (!wp_verify_nonce($_POST['_wpnonce'], $this->nonce)) {	echo '<div class="updated"><p>Invalid security.</p></div>'."\n"; return; }
			$_POST = stripslashes_deep($_POST);
			//echo '<pre>'; print_r($_POST); echo '</pre>';
			$this->opts['email'] = array_merge((array)$this->opts['email'], array(
				'to'		=> $_POST['email']['to'],
				'bcc'		=> $_POST['email']['bcc'],
				'subject'	=> $_POST['email']['subject'],
			));
			$this->opts['success'] = array_merge((array)$this->opts['success'], array(
				'message'	=> $_POST['success']['msg'],
				'redirect'	=> $_POST['success']['url'],
			));
			$this->opts['failure'] = array_merge((array)$this->opts['failure'], array(
				'message'	=> $_POST['failure']['msg'],
			));
			$this->opts['recaptcha'] = array_merge((array)$this->opts['recaptcha'], array(
				'use'		=> (($_POST['recaptcha']['use'] == 'yes') ? true : false),
				'public'	=> $_POST['recaptcha']['public'],
				'private'	=> $_POST['recaptcha']['private'],
			));
			$this->opts['akismet'] = array_merge((array)$this->opts['akismet'], array(
				'use'		=> (($_POST['akismet']['use'] == 'yes') ? true : false),
				'api_key'	=> $_POST['akismet']['api_key'],
			));
			$this->opts['ayah'] = array_merge((array)$this->opts['recaptcha'], array(
				'use'		=> (($_POST['ayah']['use'] == 'yes') ? true : false),
				'publisher_key'	=> $_POST['ayah']['publisher_key'],
				'scoring_key'	=> $_POST['ayah']['scoring_key'],
			));
			
			$this->save_options();
			echo '<div class="updated"><p>Settings have been saved.</p></div>'."\n";
		}
		// Show & Save logged submissions
		if ($this->log_type)	$this->admin_list_logs();
		// Settings Form
		?>
        <div class="widefat postbox" style="margin:1em 0 2em;">
        <h3 class="hndle" style="padding:0.5em; cursor:default;">Settings</h3>
        <div class="inside">
		<form class="plugin_settings" method="post" action="<?php echo esc_url($_SERVER['REQUEST_URI']); ?>">
		<?php echo wp_nonce_field($this->nonce); ?>
		<div class="column column_left">
		<fieldset>
		  <legend>Email Options</legend>
			<p><label for="emailTo">Email notifications to:</label>
				<input type="text" name="email[to]" id="emailTo" class="widefat" value="<?php echo $this->opts['email']['to']; ?>" /></p>
			<p><label for="emailBCC">BCC notifications to:</label>
				<input type="text" name="email[bcc]" id="emailBCC" class="widefat" value="<?php echo $this->opts['email']['bcc']; ?>" /></p>
			<p><label for="emailSubject">Email Subject Line:</label>
				<input type="text" name="email[subject]" id="emailSubject" class="widefat" value="<?php echo $this->opts['email']['subject']; ?>" /></p>
			<p class="note">If you leave the "Thank-you URL" blank, the "Thank-you Message" will be shown instead.
							If you provide a URL, the user will be redirected to that page when they successfully send a message.</p>
		</fieldset>
		<fieldset>
		  <legend>Response Options</legend>
			<p><label for="thanksURL">Thank-you URL:</label>
				<input name="success[url]" type="text" class="widefat" id="thanksURL" value="<?php echo $this->opts['success']['redirect']; ?>" /></p>
			<p><label for="successMsg">Thank-you Message:</label>
				<textarea name="success[msg]" class="widefat" id="successMsg"><?php echo $this->opts['success']['message']; ?></textarea></p>
			<p><label for="failMsg">Error Message:</label>
				<textarea name="failure[msg]" class="widefat" id="failMsg"><?php echo $this->opts['failure']['message']; ?></textarea></p>
		</fieldset>
		</div><!-- left column -->
		<div class="column column_right">
		<p><strong>Anti-Spam Services:</strong></p>
		<?php	if ($this->avail_recaptcha) {	?>
		<fieldset class="antispam recaptcha">
		  <legend>reCAPTCHA</legend>
			<p class="tick"><label>
				<input name="recaptcha[use]" type="checkbox" value="yes" <?php echo ($this->opts['recaptcha']['use']) ? 'checked="checked"' : ''; ?> /> 
				Use reCPATCHA?</label>
				&nbsp; &nbsp; &nbsp; &nbsp; <a href="https://www.google.com/recaptcha/admin/create" target="_blank">Get API Keys</a></p>
			<p><label><span>Public Key:</span>
				<input name="recaptcha[public]" type="text" value="<?php echo $this->opts['recaptcha']['public']; ?>" /></label></p>
			<p><label><span>Private Key:</span>
				<input name="recaptcha[private]" type="text" value="<?php echo $this->opts['recaptcha']['private']; ?>" /></label></p>
		</fieldset>
		<?php	}								?>
		<?php	if ($this->avail_akismet) {		?>
		<fieldset class="antispam akismet">
			<legend>Akismet Anti-Spam</legend>
			<p class="tick"><label>
				<input name="akismet[use]" type="checkbox" value="yes" <?php echo ($this->opts['akismet']['use']) ? 'checked="checked"' : ''; ?> /> 
				Use Akismet?</label>
				&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; <a href="https://akismet.com/signup/" target="_blank">Get API Key</a></p>
			<p><label><span>Public Key:</span>
				<input name="akismet[api_key]" type="text" value="<?php echo $this->opts['akismet']['api_key']; ?>" /></label></p>
		</fieldset>
		<?php	}								?>
		<?php	if ($this->avail_ayah) {		?>
		<fieldset class="antispam recaptcha">
		  <legend>Are You A Human?</legend>
			<p class="tick"><label>
				<input name="ayah[use]" type="checkbox" value="yes" <?php echo ($this->opts['ayah']['use']) ? 'checked="checked"' : ''; ?> /> 
				Use AYAH Service?</label>
				&nbsp; &nbsp; &nbsp; &nbsp; <a href="http://portal.areyouahuman.com/dashboard/add_site" target="_blank">Get API Keys</a></p>
			<p class="note">NOTE: Strongly suggest you set your "Game Style" to "Embedded" for best results.</p>
			<p><label><span>Publisher Key:</span>
				<input name="ayah[publisher_key]" type="text" value="<?php echo $this->opts['ayah']['publisher_key']; ?>" /></label></p>
			<p><label><span>Scoring Key:</span>
				<input name="ayah[scoring_key]" type="text" value="<?php echo $this->opts['ayah']['scoring_key']; ?>" /></label></p>
		</fieldset>
		<?php	}								?>
		<p class="note">Note: Use only one of these anti-spam methods!</p>
		</div><!-- right column -->

        <p style="text-align:center; clear:both;"><input class="button-primary" type="submit" value="Save Settings" name="SaveSettings" /></p>
		</form>
        </div></div>
		<?php
		echo '</div><!-- wrap -->'."\n";
		//echo '<pre>'; print_r($this->opts); echo '</pre>';
	}
	
	
	/**
	 *	Extend this if you want to list logged submissions
	 *	Or you can just create a WP_List_Table object with the right name
	 *	Name should be:  "{$this->log_type}_Table"
	 */
	function admin_list_logs() {
		$class_name = $this->log_type.'_Table';
		if (!class_exists($class_name))	return;
		$per_page = (is_numeric($_GET['per_page'])) ? $_GET['per_page'] : '20';
		$table = new $class_name( $this->log_type, $per_page );
		$table->prepare_items();
		?>
        <form id="enquiries" method="post">
            <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
            <?php $table->display() ?>
        </form>
		<?php
	}
	
	
	/** 
	 *	Take our logs, and convert to CSV file
	 */
	function output_csv_logs() {
		if (!$this->log_type)				return false;
		if (!$_GET['download'] == 'csv')	return false;
			$logs = $this->admin_csv_logs();
			if (!$logs)						return false;
		header("Content-type: application/csv");
		header("Content-Disposition: attachment; filename=log.csv");
		echo $logs;
		exit;
	}
	
	function admin_csv_logs() {
		$posts = get_posts(array(
			'numberposts'	=> -1,
			'post_type'		=> $this->log_type,
			'post_status'	=> 'private',
		));
		if (empty($posts))	return false;
		// Prepare our columns/headings
		$columns = $this->admin_csv_prepare_headings();
		// Prepare our form data
		$formdata = $this->admin_csv_prepare_data($posts, $columns);
		// Begin CSV output
		ob_start();
		echo '"'.implode('","',array_keys($formdata[0])).'"'."\n";
		foreach ($formdata as $row) {
			echo '"'.implode('","',$row).'"'."\n";
		}
		return ob_get_clean();
	}
	
	function admin_csv_prepare_headings() {
		$columns = array('date'=>'Date');
		foreach ($this->questions as $key => $q) {
			$columns[$key] = $q['label'];
		}
		$columns['Viewing'] = 'Viewing Page';
		return $columns;
	}
	
	function admin_csv_prepare_data($posts, $columns) {
		$data = array();
		foreach ($posts as $post) {
		  	$row = array();
			$form = $this->__unserialize($post->post_content);
			$form['date'] = date('d-m-Y h:i:sa', strtotime($post->post_date));
			
			foreach ($columns as $key => $label) {
				$row[$label] = $form[$key];
			}
			$data[] = $row;
		}
		return $data;
	}
	
	/**
	 *	Helper: format a timestamp
	 */
	function format_time_ago($timestamp) {
		if (!is_numeric($timestamp)) $timestamp = strtotime($timestamp);
		$t_diff = time() - $timestamp;
		if (abs($t_diff < 86400)) {	// 24 hours
			$h_time = sprintf( __( '%s ago' ), human_time_diff( $timestamp, current_time('timestamp') ) );
		} else {
			$h_time = date('Y/m/d', $timestamp);
		}
		return $h_time;
	}
	
	
	/**
	 *	Helper to fix the "Error at offset" issue
	 */
	function __unserialize($sObject) {
		$__ret =preg_replace('!s:(\d+):"(.*?)";!e', "'s:'.strlen('$2').':\"$2\";'", $sObject );
		return unserialize($__ret);
	}
	
	
}








?>