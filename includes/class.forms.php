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
	public		$avail_recaptcha= true;
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
	public		$show_graph		= false;			// Embed a graph before results to show leads over time
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
		
		if (empty($this->opts['email']['from']))	$this->opts['email']['from']	= $this->opts['email']['to'];
		if (empty($this->opts['email']['from']))	$this->opts['email']['from']	= get_bloginfo('admin_email');
		if (empty($this->opts['email']['to']))		$this->opts['email']['to']		= get_bloginfo('admin_email');
		
		// Load akismet API key if one not already specified
		if (empty($this->opts['akismet']['api_key']))	$this->opts['akismet']['api_key'] = get_option('wordpress_api_key');
		
		return $this->opts;
	}
	function save_options($options = false) {
		if (!$options) { $options = $this->opts; }
		update_option($this->option_key, $options);
	}
	function default_options() {
		$this->opts = array(
			'email' => array(
				'from'		=> get_bloginfo('admin_email'),
				'to'		=> get_bloginfo('admin_email'),
				'bcc'		=> '',
				'subject'	=> 'TWS Form for '.site_url(),
			),
			'success' => array(
				'message'	=> 'Thank you, your message has been sent.',
				'redirect'	=> '',
			),
			'failure'	=> array(
				'message'	=> 'Sorry, your message could not be sent at this time.',
			),
		);
	}


	
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
			add_action('admin_menu', array($this,'admin_menu'), 11);
			add_action('load-dashboard_page_'.$this->option_key, array($this,'output_csv_logs'));
			return;
		}
		// Actions
		add_action('wp_enqueue_scripts', array($this,'enqueue_scripts'), 9);
		add_action('template_redirect', array($this,'process_form'));
		add_shortcode($this->shortcode, array($this,'handle_shortcode'));
		add_filter('ttools_form_filter_email_headers', array($this,'filter_headers'), 10, 2);
		// In case we need to tie-in with a particular form
		add_action('wp_print_footer_scripts ', array($this,'print_footer_scripts'));
		// TinyMCE Button
		add_filter('tailored_tools_mce_buttons', array($this,'add_mce_button'));
		// Build bad-words array
		add_filter('ttools_form_bad_words_to_check', array($this,'filter_bad_words_to_check'), 10, 2);
	}

	
	
	/**
	 *	Enqueue scripts & styles
	 */
	function enqueue_scripts() {
		wp_enqueue_script('jquery-validate');
		wp_enqueue_script('ttools-loader');
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
		// Preserve new-lines through json_encode
		if (is_array($data)) {
			foreach ($data as $key => $line) {
				if (is_array($line))	continue;
				if (strpos($data[$key], "\n")!==false)		$data[$key] = str_replace(array("\r\n", "\r", "\n"), "\\n", $data[$key]);
				$data[$key] = htmlspecialchars($data[$key], ENT_QUOTES);
			}
		}
		if ($this->debug) echo '<pre>To log: '.print_r($data,true).'</pre>';
		// Insert into DB
		$insertID = wp_insert_post(array(
			'post_title'	=> '',
			'post_content'	=> json_encode($data),
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
		// Over-ride this function to send from customer details.
		$headers = array(
			'From: '.get_bloginfo('name').' <'.get_bloginfo('admin_email').'>',			// From should be an email address at this domain.
			'Reply-To: '.get_bloginfo('name').' <'.get_bloginfo('admin_email').'>',		// Reply-to and -path should be visitor email.
			'Return-Path: '.get_bloginfo('name').' <'.get_bloginfo('admin_email').'>',
		);
		return $headers;
	}
	
	
	
	/**
	 *	Process upload & prepare attachments
	 *	Original function doesn't work for file questions inside fieldsets.  This one is recursive.
	 */
	function process_upload($questions=false) {
		if (!$questions) {
			return $this->process_upload( $this->questions );
		}
		$dir = wp_upload_dir();
		foreach ($questions as $key => $q) {
			if ($q['type'] == 'fieldset')	$this->process_upload( $questions[$key]['questions'] );
			if ($q['type'] != 'file')		continue;
			$upload = $dir['basedir'].'/'.$_FILES[$key]['name'];
			if (!move_uploaded_file($_FILES[$key]['tmp_name'], $upload))	continue;
			$this->files[] = $upload;
		}
	}
	
	/**
	 *	Process upload & prepare attachments
	 *
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
		$message .= " \r\n \r\n".'From page: '.get_permalink()." \r\n";
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
		// Strip all slashes, we don't want them.
		$_POST = stripslashes_deep($_POST);
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
		if (!$q['required'])	return;
		
		switch ($q['type']) {
			case 'name':
				if ( (!isset($_POST[$key]['first']) || trim($_POST[$key]['first'])=='') || (!isset($_POST[$key]['first']) || trim($_POST[$key]['first'])=='') ) {
					$this->error[] = $q['error'];
				}
			break;
			case 'address_long':
				if (!isset($_POST[$key]['number']) ||
					!isset($_POST[$key]['street']) ||
					!isset($_POST[$key]['city']) ||
					!isset($_POST[$key]['state']) ||
					!isset($_POST[$key]['postcode']) ) {
						$this->error[] = $q['error'];
				}
			break;
			case 'file':
				if (!isset($_FILES[$key]) || $_FILES[$key]['error'] != '0')	$this->error[] = $q['error'];
			break;
			default:
				if (!isset($_POST[$key]) || (!is_array($_POST[$key]) && trim($_POST[$key])==''))	$this->error[] = $q['error'];
				if ($q['type']=='email' && !empty($_POST[$key]) && !is_email($_POST[$key]))	$this->error[] = '<em>'.$_POST[$key].'</em> does not look like an email address';
			break;
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
			'buycialis', 'hydrocodone', 'viagraonline', 'cialisonline', 'phentermine', 'viagrabuy', 'percocet', 'tramadol', // 'ambien', 
			'propecia', 'xenical', 'meridia', 'levitra', 'vicodin', 'viagra', 'valium', 'porno', 'xanax', 'href=', // 'sex', 'soma', 'cialis', 
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
		if (!isset($_POST[$key]))		$_POST[$key] = '';
		if (!isset($q['required']))		$q['required'] = false;
		if (!isset($q['class']))		$q['class'] = false;
		// Separator line?
		if ($q['type'] == 'sep') { echo '<p class="sep">&nbsp;</p>'."\n"; return; }
		// Heading line
		if ($q['type'] == 'heading') { echo '<p class="heading">'.nl2br($q['label']).'</p>'."\n"; return; }
		// Text Note
		if ($q['type'] == 'note') { echo '<p class="note '.$q['class'].'">'.nl2br($q['label']).'</p>'."\n"; return; }
		// Prepare default value
		if (@empty($_POST[$key]) && isset($q['default']))	$_POST[$key] = $q['default'];
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
			case 'name':			$this->draw_name_inputs($key, $q);		break;
			case 'address':			$this->draw_address_input($key, $q);	break;
			case 'address_long':	$this->draw_address_input($key, $q);	break;
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
//		$is_assoc = array_keys($q['options']) !== range(0, count($q['options']) - 1);
		$is_assoc = (bool) count(array_filter(array_keys($q['options']), 'is_string'));
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
		$is_assoc = (bool) count(array_filter(array_keys($q['options']), 'is_string'));
		// Draw Element
		echo '<p'.$q['class'].'>'.$q['label']."\n";
		foreach ($q['options'] as $val => $opt) {
			if (!$is_assoc)	$val = $opt;
			if (is_string($val))	$val = trim($val);
			// Select if default, OR if _POST is set.  (But don't set default if _POST is already set)
			$sel = ($val == $_POST[$key] || @in_array($val, $_POST[$key])) ? ' checked="checked"' : '';
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
		echo "\t".'<input type="date" name="'.$key.'" id="'.$key.'" class="txt datepicker" value="'.esc_attr($_POST[$key]).'" /></label></p>'."\n";
		wp_enqueue_script('jquery-ui-datepicker');
	}
	
	function draw_timepicker($key, $q) {
		echo '<p'.$q['class'].'><label><span>'.$q['label'].'</span>'."\n";
		echo "\t".'<input type="time" name="'.$key.'" id="'.$key.'" class="txt timepicker" value="'.esc_attr($_POST[$key]).'" /></label></p>'."\n";
		wp_enqueue_script('jquery-timepicker');
	}
	
	function draw_datetimepicker($key, $q) {
		echo '<p'.$q['class'].'><label><span>'.$q['label'].'</span>'."\n";
		echo "\t".'<input type="datetime" name="'.$key.'" id="'.$key.'" class="txt datetimepicker" value="'.esc_attr($_POST[$key]).'" /></label></p>'."\n";
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
	
	function draw_name_inputs($key, $q) {
		// Input type
		$q['type'] = 'text';
		// Element class
		$class = array('txt');
		if ($q['required'])			$class[] = 'required';
		// Element Attributes
		$attrs = 'type="'.$q['type'].'" class="'.implode(' ',$class).'"';
		// Draw Element		
		echo '<p class="name"><label><span>'.$q['label'].'</span>'."\n";
		echo "\t".'<input name="'.$key.'[first]" '.$attrs.' placeholder="First name..." value="'.esc_attr(@$_POST[$key]['first']).'" />'."\n";
		echo "\t".'<input name="'.$key.'[last]" '.$attrs.' placeholder="Last name..." value="'.esc_attr(@$_POST[$key]['last']).'" />'."\n";
		echo '</label></p>'."\n";
	}
	
	
	
	function draw_address_input($key, $q) {
		wp_enqueue_script('jquery-geocomplete');
		// Div class
		$div_class = ($q['type'] == 'address_long') ? 'address address-long' : 'address address-short';
		// Input class
		$class = array('txt');
		if ($q['required'])			$class[] = 'required';
		// Begin output
		echo '<div class="'.$div_class.'">'."\n";
		if ($q['type'] == 'address') {
			// SHORT: Geocoder field
			if (empty($q['placeholder']))	$q['placeholder'] = 'Type your address...';
			$attrs = 'type="text" name="'.$key.'" id="'.$key.'" class="'.implode(' ',$class).'" placeholder="'.esc_attr($q['placeholder']).'" data-geo="formatted_address"';
			echo '<p class="geocomplete"><label><span>'.$q['label'].'</span>'."\n";
			echo "\t".'<input '.$attrs.' value="'.esc_attr($_POST[$key]).'" /></label></p>'."\n";
		} else {
			// LONG: Geocoder field
			if (empty($q['placeholder']))	$q['placeholder'] = 'Type your address...';
			echo '<p class="geocomplete"><label><span>'.$q['label'].'</span>'."\n";
			echo "\t".'<input type="text" name="'.$key.'[lookup]" class="txt" placeholder="'.esc_attr($q['placeholder']).'" value="'.esc_attr(@$_POST[$key]['lookup']).'" /></label></p>'."\n";
			// Specific fields for address elements
			$disabled = ($q['disabled']) ? 'disabled="true"' : '';
			$readonly = ($q['readonly']) ? 'readonly="true"' : '';
			
			$attrs = 'type="text" '.$disabled.' '.$readonly;
			
			echo '<p class="street"><label><span>Street</span>'."\n";
			echo "\t".'<input name="'.$key.'[number]" class="txt street-number" '.$attrs.' data-geo="street_number" placeholder="#" value="'.esc_attr(@$_POST[$key]['number']).'" />'."\n";
			echo "\t".'<input name="'.$key.'[street]" class="txt street-name" '.$attrs.' data-geo="route" placeholder="Street name" value="'.esc_attr(@$_POST[$key]['street']).'" />'."\n";
			echo '</label></p>'."\n";
			echo '<p class="city"><label><span>City</span>'."\n";
			echo "\t".'<input name="'.$key.'[city]" class="txt" '.$attrs.' data-geo="locality" placeholder="City" value="'.esc_attr(@$_POST[$key]['city']).'" />'."\n";
			echo '</label></p>'."\n";
			echo '<p class="state"><label><span>State</span>'."\n";
			echo "\t".'<input name="'.$key.'[state]" class="txt" '.$attrs.' data-geo="administrative_area_level_1" placeholder="State" value="'.esc_attr(@$_POST[$key]['state']).'" />'."\n";
			echo '</label></p>'."\n";
			echo '<p class="postcode"><label><span>Postcode</span>'."\n";
			echo "\t".'<input name="'.$key.'[postcode]" class="txt" '.$attrs.' data-geo="postal_code" placeholder="Postcode" value="'.esc_attr(@$_POST[$key]['postcode']).'" />'."\n";
			echo '</label></p>'."\n";
			echo '<p class="country"><label><span>Country</span>'."\n";
			echo "\t".'<input name="'.$key.'[country]" class="txt" '.$attrs.' data-geo="country" placeholder="Country" value="'.esc_attr(@$_POST[$key]['country']).'" />'."\n";
			echo '</label></p>'."\n";
		}
		echo '</div><!-- '.$div_class.' -->'."\n";
	}
	
	
	
	
	function count_logs() {
		// Used cached variable if available
		$counts = wp_cache_get( 'count_'.$this->log_type, 'counts' );
		// Query database to get recent count
		global $wpdb;
		$query = "SELECT post_status, COUNT( * ) AS num_posts FROM {$wpdb->posts} WHERE post_type = '{$this->log_type}' GROUP BY post_status";
		$results = (array) $wpdb->get_results( $query, ARRAY_A );
		$counts = array_fill_keys( get_post_stati(), 0 );
		foreach ( $results as $row )	$counts[ $row['post_status'] ] = $row['num_posts'];
		$counts = (object) $counts;
		// Set cache for next time, and return value
		wp_cache_set( 'count_'.$this->log_type, $counts, 'counts' );
		return $counts;
	}
	
	/**
	 *	Admin Functions
	 */
	function admin_menu() {
		if (!$this->form_name)		return false;
		$menu_label = $this->form_name;
		// Add a counter to menu name?
		$counter = '';
		if ($this->log_type) {
			$count = $this->count_logs();
			if ($count && $count->private>0)	$counter = '<span class="update-plugins"><span class="update-count">'. $count->private .'</span></span>';
		}
		$hook = add_submenu_page($this->admin_menu, $this->form_name, $this->form_name.$counter, 'edit_posts', $this->option_key,  array($this,'admin_page'));
//		add_action("load-$hook", array($this,'admin_enqueue'));
		add_action('admin_enqueue_scripts', array($this,'admin_enqueue'));
	}
	
	function admin_enqueue() {
		wp_enqueue_style('ttools-admin');
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
				'from'		=> $_POST['email']['from'],
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
				'use'		=> ((isset($_POST['recaptcha']['use']) && $_POST['recaptcha']['use'] == 'yes') ? true : false),
				'public'	=> ((isset($_POST['recaptcha']['public'])) ? $_POST['recaptcha']['public'] : ''),
				'private'	=> ((isset($_POST['recaptcha']['private'])) ? $_POST['recaptcha']['private'] : ''),
			));
			$this->opts['akismet'] = array_merge((array)$this->opts['akismet'], array(
				'use'		=> ((isset($_POST['akismet']['use']) && $_POST['akismet']['use'] == 'yes') ? true : false),
				'api_key'	=> ((isset($_POST['akismet']['api_key'])) ? $_POST['akismet']['api_key'] : ''),
			));
/*
			$this->opts['ayah'] = array_merge((array)$this->opts['recaptcha'], array(
				'use'			=> ((isset($_POST['ayah']['use']) && $_POST['ayah']['use'] == 'yes') ? true : false),
				'publisher_key'	=> ((isset($_POST['ayah']['publisher_key'])) ? $_POST['ayah']['publisher_key'] : ''),
				'scoring_key'	=> ((isset($_POST['ayah']['scoring_key'])) ? $_POST['ayah']['scoring_key'] : ''),
			));
*/
			$this->save_options();
			echo '<div class="updated"><p>Settings have been saved.</p></div>'."\n";
		}
		
		// Default
		if (!isset($this->opts['email']['from']))	$this->opts['email']['from'] = get_bloginfo('admin_email');
		
		// Show graph
		if ($this->log_type)	$this->admin_graph_logs();
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
		  	<p><label>
				<span>Email notifications to:</span>
				<input type="text" name="email[to]" class="widefat" value="<?php echo $this->opts['email']['to']; ?>" />
			</label></p>
			<p><label>
				<span>BCC notifications to:</span>
				<input type="text" name="email[bcc]" class="widefat" value="<?php echo $this->opts['email']['bcc']; ?>" />
			</label></p>
			<p><label>
				<span>Email Subject Line:</span>
				<input type="text" name="email[subject]" class="widefat" value="<?php echo $this->opts['email']['subject']; ?>" />
			</label></p>
		  	<p><label>
				<span>Sending email from:</span>
				<input type="text" name="email[from]" class="widefat" value="<?php echo $this->opts['email']['from']; ?>" />
			</label></p>
		</fieldset>
		<fieldset>
		  <legend>Response Options</legend>
			<p><label>
				<span>Thank-you URL:</span>
				<input name="success[url]" type="text" class="widefat" value="<?php echo $this->opts['success']['redirect']; ?>" />
			</label></p>
			<p class="note">
				If you leave the "Thank-you URL" blank, the "Thank-you Message" will be shown instead.<br />
				If you provide a URL, the user will be redirected to that page when they successfully send a message.
			</p>
			<p><label>
				<span>Thank-you Message:</span>
				<textarea name="success[msg]" class="widefat"><?php echo $this->opts['success']['message']; ?></textarea>
			</label></p>
			<p><label>
				<span>Error Message:</span>
				<textarea name="failure[msg]" class="widefat"><?php echo $this->opts['failure']['message']; ?></textarea>
			</label></p>
		</fieldset>
		</div><!-- left column -->
		<div class="column column_right">
		<p><strong>Anti-Spam Services:</strong></p>
		<?php	if ($this->avail_recaptcha) {	?>
		<?php
		if (!isset($this->opts['recaptcha']['use']))	$this->opts['recaptcha']['use'] = false;
		if (!isset($this->opts['recaptcha']['public']))	$this->opts['recaptcha']['public'] = '';
		if (!isset($this->opts['recaptcha']['private']))	$this->opts['recaptcha']['private'] = '';
		?>
		<fieldset class="antispam recaptcha">
		  <legend>reCAPTCHA</legend>
		  	<p class="tick">
				<label>
					<input name="recaptcha[use]" type="checkbox" value="yes" <?php echo ($this->opts['recaptcha']['use']) ? 'checked="checked"' : ''; ?> /> 
					Use reCPATCHA?
				</label>
				<a href="https://www.google.com/recaptcha/admin" target="_blank">Get API Keys</a>
			</p>
			<p><label><span>Public Key:</span>
				<input name="recaptcha[public]" type="text" value="<?php echo $this->opts['recaptcha']['public']; ?>" /></label></p>
			<p><label><span>Private Key:</span>
				<input name="recaptcha[private]" type="text" value="<?php echo $this->opts['recaptcha']['private']; ?>" /></label></p>
		</fieldset>
		<?php	}								?>
		
		<?php	if ($this->avail_akismet) {		?>
		<?php
		if (!isset($this->opts['akismet']['use']))		$this->opts['akismet']['use'] = false;
		if (!isset($this->opts['akismet']['api_key']))	$this->opts['akismet']['api_key'] = '';
		?>
		<fieldset class="antispam akismet">
			<legend>Akismet Anti-Spam</legend>
		  	<p class="tick">
				<label>
					<input name="akismet[use]" type="checkbox" value="yes" <?php echo ($this->opts['akismet']['use']) ? 'checked="checked"' : ''; ?> /> 
					Use Akismet?
				</label>
				<a href="https://akismet.com/signup/" target="_blank">Get API Key</a>
			</p>
			<p><label>
				<span>Public Key:</span>
				<input name="akismet[api_key]" type="text" value="<?php echo $this->opts['akismet']['api_key']; ?>" />
			</label></p>
		</fieldset>
		<?php	}								?>
		
		</div><!-- right column -->

        <p style="text-align:center; clear:both;"><input class="button-primary" type="submit" value="Save Settings" name="SaveSettings" /></p>
		</form>
        </div></div>
		<?php
		echo '</div><!-- wrap -->'."\n";
		//echo '<pre>'; print_r($this->opts); echo '</pre>';
	}
	
	
	
	/**
	 *	Graph our enquiries over time.
	 */
	function admin_graph_logs() {
		if (!$this->show_graph || !$this->log_type)		return false;
		wp_enqueue_script('google-jsapi', '//www.google.com/jsapi', false, false, true);
		wp_enqueue_script('jquery-ui-datepicker');
		wp_enqueue_style('ui-datepicker', '//ajax.googleapis.com/ajax/libs/jqueryui/1.7.2/themes/ui-lightness/jquery-ui.css');
		add_action('admin_print_footer_scripts', array($this,'admin_graph_js'));
		
		if (!isset($_REQUEST['date_from']))		$_REQUEST['date_from'] = '';
		if (!isset($_REQUEST['date_to']))		$_REQUEST['date_to'] = '';
		
		if ($_REQUEST['date_from']) {	$_REQUEST['date_from'] = date('M j, Y', strtotime( $_REQUEST['date_from'] ));	}
		if ($_REQUEST['date_to']) {	$_REQUEST['date_to'] = date('M j, Y', strtotime( $_REQUEST['date_to'] ));	}
		
		
		if (empty($_REQUEST['date_from']))	$_REQUEST['date_from'] = date('M j, Y', strtotime("-30 days"));
		if (empty($_REQUEST['date_to']))	$_REQUEST['date_to'] = date('M j, Y');
		
		global $wpdb;
		$total_leads = $wpdb->get_var("
			SELECT COUNT( * ) AS `num_leads`
			FROM `{$wpdb->posts}`
			WHERE `post_type` = '{$this->log_type}'
			  AND DATE(`post_date`) BETWEEN '".date('Y-m-d',strtotime($_REQUEST['date_from']))."' AND '".date('Y-m-d',strtotime($_REQUEST['date_to']))."'
		");
		?>
		<div id="graph">
			<p class="total_leads">Number of leads for selected time period: <?php echo $total_leads; ?></p>
			<div class="ranges">
			<form method="POST">
				<p><label><span>From:</span> <input type="text" name="date_from" value="<?php echo $_REQUEST['date_from']; ?>" /></label></p>
				<p><label><span>To:</span> <input type="text" name="date_to" value="<?php echo $_REQUEST['date_to']; ?>" /></label></p>
				<p class="submit"><input class="button-primary" type="submit" value="Go" /></p>
			</form>
			</div>
			<div id="chart"></div>
		</div>
<style><!--
#graph { background:#FFF; margin:1em 0.5em 2em; padding:0.5em; border:1px solid #DFDFDF; border-radius:0.3em; }
#graph p.total_leads { float:left; margin:0; padding:0.5em 0 0; }
#graph .ranges { text-align:right; }
#graph .ranges p { display:inline-block; margin:0 1em 0; padding:0; }
#graph .ranges p input { cursor:pointer; }
--></style>
		<?php
	}
	
	function admin_graph_js() {
		global $wpdb;
		$data = array();
		$date  = $_REQUEST['date_from'];
		$loop = 0;
		while (strtotime($date) <= strtotime($_REQUEST['date_to'])) {
			$result = $wpdb->get_results("
				SELECT COUNT( * ) AS `num_leads`, DATE(`post_date`) AS `date`
				FROM `{$wpdb->posts}`
				WHERE `post_type` = '{$this->log_type}'
				  AND DATE(`post_date`) = '".date('Y-m-d',strtotime($date))."';
			");
			$data[] = array(	
				'Date'	=> date('jS M Y', strtotime($date)),	
				'Leads'	=> $result[0]->num_leads,
			);
			$date = date('Y-m-d', strtotime("+1 day", strtotime($date)));

		}
		?>
<script type="text/javascript"><!--
jQuery(document).ready(function($){
	$('#graph .ranges label input').datepicker({ numberOfMonths:3, showButtonPanel:true, dateFormat:'M d, yy' });
});

google.load('visualization', '1.0', {'packages':['corechart']});
google.setOnLoadCallback(drawChart);
function drawChart() {
	
	var data = google.visualization.arrayToDataTable([
		['Day', 'Leads'],
		<?php
		foreach ($data as $row) {
			echo "['".$row['Date']."', ".$row['Leads']." ],";
		}
		?>
		
	]);
	
	var options = {
		title:	"Leads per day",
	};
	
	var chart = new google.visualization.LineChart(document.getElementById('chart'));
	chart.draw(data, options);
}
--></script>
		<?php
	}
	
	
	/**
	 *	To display logged data
	 */
	function admin_list_logs() {
		if (!$this->log_type || !class_exists('tws_form_log_Table'))	return false;
		
		$per_page = (isset($_GET['per_page']) && is_numeric($_GET['per_page'])) ? $_GET['per_page'] : '20';
		$table = new tws_form_log_Table();
		$table->prepare_items($this->log_type, $per_page);
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
		if (!$this->log_type)											return false;
		if (!isset($_GET['download']) || !$_GET['download'] == 'csv')	return false;
		if (!$this->questions)											return false;
		$logs = $this->admin_csv_logs();
		if (!$logs)														return false;
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
	public static function format_time_ago($timestamp) {
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
	public static function __unserialize($data) {
		// First attempt json_decode
		$decoded = json_decode($data);
		if ($decoded)	return (array) $decoded;
		// If not, go ahead with unseralize
		$data = preg_replace('!s:(\d+):"(.*?)";!e', "'s:'.strlen('$2').':\"$2\";'", $data );
		return unserialize($data);
	}
	
	
}






/**
 *	Ensure our standard version of wp-list-table is included
 */
if (is_admin() || !class_exists('tws_WP_List_Table')) {
	require( dirname(__FILE__).'/class-wp-list-table.php' );
}



/**
 *	This is used in the admin area to display logged enquiries.
 *	I see no reason not to extend tws_form_log_Table when extending the tws-form class.
 */
if (is_admin() || !class_exists('tws_WP_List_Table')) {
	
	class tws_form_log_Table extends tws_WP_List_Table {
	
		function get_columns() {
			return array(
				'cb'			=> '<input type="checkbox" />',
				'date'			=> __('Date'), //array( 'date', true ),
				'cust_name'		=> __('Name'),
				'cust_email'	=> __('Email'),
				'cust_phone'	=> __('Phone'),
			);
		}
		
		function get_bulk_actions() {
			return array(
				'delete'    => 'Delete'
			);
		} 
		
		function process_bulk_action() {
			if ('delete' === $this->current_action()) {
				foreach ($_POST['records'] as $delete_id) {
					wp_delete_post($delete_id, true);
				}
				echo '<div class="updated"><p>Selected logs have been deleted.</p></div>'."\n";
			}
		} 
		
		function prepare_items( $post_type='', $per_page=20 ) {
			$columns = $this->get_columns();
			$hidden = array();
			$sortable = $this->get_sortable_columns(); 
			$this->_column_headers = array($columns, $hidden, $sortable); 
			
			$this->process_bulk_action(); 
			
			$posts = get_posts(array(
				'numberposts'	=> -1,
				'post_type'		=> $post_type, //$this->post_type,
				'post_status'	=> 'all',
			));
			
			$current_page = $this->get_pagenum(); 
			$total_items = count($posts); 
			
			$this->items = array_slice($posts,(($current_page-1)*$per_page),$per_page);
			
			$this->set_pagination_args(array(
				'total_items' => $total_items,                  //WE have to calculate the total number of items
				'per_page'    => $per_page,                     //WE have to determine how many items to show on a page
				'total_pages' => ceil($total_items/$per_page)   //WE have to calculate the total number of pages
			));
		}
		
		function display_rows() {
			if (empty($this->items))	return false;
			$records = $this->items;
			list($columns, $hidden) = $this->get_column_info();
			foreach ($records as $record) {
				$form = TailoredForm::__unserialize($record->post_content);
				echo '<tr>'."\n";
				foreach ($columns as $column_name => $column_label) {
					switch ($column_name) {
						case 'cb':			echo '<th rowspan="2" class="check-column"><input type="checkbox" name="records[]" value="'.$record->ID.'" /></th>';	break;
						case 'date':		echo '<td rowspan="2">'.TailoredForm::format_time_ago( strtotime($record->post_date) ).'</td>';						break;
						case 'cust_name':	echo '<td>'.$form['cust_name'].'</td>';			break;
						case 'cust_email':	echo '<td>'.$form['cust_email'].'</td>';		break;
						case 'cust_phone':	echo '<td>'.$form['cust_phone'].'</td>';		break;
					}
				}
				echo '</tr>'."\n";
				echo '<tr class="more">';
				echo '<td colspan="3">';
				echo 	'<p>'.nl2br($form['cust_message']).'</p>';
				echo 	'<p>Viewing: <a target="_blank" href="'.$form['Viewing'].'">'.$form['Viewing'].'</a></p>';
				echo '</td>';
				echo '</tr>';
			}
		}
	}
	
}




?>