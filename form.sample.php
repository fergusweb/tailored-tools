<?php

/**
 *	Sample Form Class
 *	Uses TailoredForm as parent
 *	This is a demo form, showing some possible ways to do stuff
 */


new SampleForm();


class SampleForm extends TailoredForm {
	public		$form_name		= 'Sample Form';
	public		$option_key		= 'sample_form_opts';
	public		$shortcode		= 'SampleForm';
	public		$log_type		= 'sample_form_log';
	public		$submit_key		= 'submit_sample_form';
	public		$submit_label	= 'Submit Form';
	public		$form_class		= 'test class validate';
	// Which anti-spam modules are available?
	public		$avail_recaptcha= true;
	public		$avail_akismet	= false;
	public		$check_bad_words= false;
	
	/**
	 *	Constructor
	 */
	function __construct() {
		$this->load_questions();
		$this->init();
		add_action('ttools_form_before_submit_button', array($this,'do_before_submit_button'));
		
		// If using Akismet, map fields to values
		add_filter('ttools_map_akismet_fields', array($this,'map_form_fields'), 10, 2);
	}
	
	/**
	 *	This shows how you can tie into the actions, executing only for this particular form.
	 */
	function do_before_submit_button($form) {
		if ($this->form_name !== $form->form_name)	return;
		echo '<p>Marker!! ttools_form_before_submit_button</p>';
	}
	
	/**
	 *	Options for sending mail
	 */
	function default_options() {
		$this->opts = array(
			'email' => array(
				'to'		=> get_bloginfo('admin_email'),
				'bcc'		=> '',
				'subject'	=> 'Sample Form Submission for '.site_url(),
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
	 *	Filter to generate email headers
	 */
	function filter_headers($headers=false, $form=false) {
		// Only run if its for THIS form.
		if ($this->form_name !== $form->form_name)	return $headers;
		// Build headers
		$from_name = $_POST['cust_name'];
		$from_email = $_POST['cust_email'];
		$headers = array(
			"From: ".$this->opts['email']['from'],			// From should be an email address at this domain.
			"Reply-To: {$from_name} <{$from_email}>",		// Reply-to and -path should be visitor email.
			"Return-Path: {$from_name} <{$from_email}>",
		);
		return $headers;
	}
	
	
	/**
	 *	Map form fields to Akismet array for anti-spam check
	 */
	function map_form_fields($fields=false, $form=false) {
		if ($this->form_name !== $form->form_name)	return $fields;
		$fields = array(
			'name'		=> $_POST['cust_name'],
			'email'		=> $_POST['cust_email'],
			'message'	=> $_POST['cust_message'],
		);
		return $fields;
	}
	
	
	/**
	 *	Questions to show in form
	 */
	function load_questions() {
		$this->questions = array(
			'cust_name'		=> array(
				'label'		=> 'Your Name',
				'type'		=> 'text',
				'required'	=> true,
				'error'		=> 'Please provide your name',
			),
			'cust_note'		=> array(
				'type'		=> 'note',
				'label'		=> "testing text here",
				'class'		=> 'test class',
			),
			'cust_email'	=> array(
				'label'		=> 'Email Address',
				'type'		=> 'email',
				'required'	=> true,
				'error'		=> 'Please provide your email address',
			),
			'cust_phone'	=> array(
				'label'		=> 'Phone Number',
				'type'		=> 'tel',
				'required'	=> true,
				'error'		=> 'Please provide your phone number',
			),
			'cust_message'	=> array(
				'label'		=> 'Your Message',
				'type'		=> 'textarea',
				'required'	=> true,
				'error'		=> 'Please provide your message',
			),
			'test_select'	=> array(
				'label'		=> 'Choose Sel',
				'type'		=> 'select',
				'options'	=> array( 'one'=>'Option One', 'two'=>'Option Two', 'three'=>'Option Three','four'=>'Option Four' ),
				'required'	=> true,
				'error'		=> 'Please use the select box',
//				'default'	=> 'two',
			),
			'cust_date'	=> array(
				'label'		=> 'Date',
				'type'		=> 'date',
			),
			'cust_time'	=> array(
				'label'		=> 'Time',
				'type'		=> 'time',
			),
			'cust_date_time'	=> array(
				'label'		=> 'Date/time',
				'type'		=> 'datetime',
			),
			
			'cust_country'	=> array(
				'label'		=> 'Country',
				'type'		=> 'country',
			),
			'cust_number'	=> array(
				'label'		=> 'Number',
				'type'		=> 'number',
			),
			'cust_range'	=> array(
				'label'		=> 'Range',
				'type'		=> 'range',
				'min'		=> 0,
				'max'		=> 100,
			),
			
			
			'test_checks' => array(
				'type'		=>'fieldset',
				'label' 	=> 'Some radios & checkboxes...',
				'questions'	=> array(
					'test_radios'	=> array(
						'label'		=> 'Choose Rad',
						'type'		=> 'radio',
						'options'	=> array( 'one'=>'Option One', 'two'=>'Option Two', 'three'=>'Option Three', 'four'=>'Option Four' ),
						'required'	=> true,
						'error'		=> 'Please use the radio boxes',
		//				'default'	=> 'two',
					),
					'test_tickbox'	=> array(
						'label'		=> 'Choose Checks',
						'type'		=> 'checkbox',
						'options'	=> array( 'one'=>'Option One', 'two'=>'Option Two', 'three'=>'Option Three', 'four'=>'Option Four' ),
						'required'	=> true,
						'error'		=> 'Please use the checkboxes',
		//				'default'	=> array('two', 'three'),
					),
				),
			),
			'cust_upload'	=> array(
				'label'		=> 'Choose File',
				'type'		=> 'file',
				'required'	=> true,
				'error'		=> 'Please upload a file',
			),
		);
	}
	
	
	/**
	 *	Main form class includes a function that starts a wp_list_table
	 *	You can use an empty function if you don't want to display logs.
	 */
	function admin_list_logs() {
		if (!$this->log_type)	return false;
		return false;
	}
	
	
	
	
}



?>