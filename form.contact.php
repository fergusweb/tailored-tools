<?php

/**
 *	Contact Form
 *	Uses TailoredForm as parent
 */


new ContactForm();


class ContactForm extends TailoredForm {
	public		$form_name		= 'Contact Form';
	public		$option_key		= 'contact_form_opts';
	public		$shortcode		= 'ContactForm';
	public		$log_type		= 'contact_form_log';
	public		$submit_key		= 'submit_contact_form';
	public		$submit_label	= 'Enquire Now';
	public		$form_class		= 'validate contact';
	// Which anti-spam modules are available?
	public		$avail_recaptcha= true;
	public		$avail_akismet	= true;
	public		$check_bad_words= true;
	
	public		$show_graph		= true;
	
	
	
	/**
	 *	Constructor
	 */
	function __construct() {
		$this->load_questions();
		$this->init();
	}
	
	
	/**
	 *	Register our button for TinyMCE to add our shortcode
	 */
	function add_mce_button($buttons) {
		array_push($buttons, array(
			'label'		=> 'Contact Form',
			'shortcode'	=> '['.$this->shortcode.']',
		));
		return $buttons;
	}
	
	
	/**
	 *	Options for sending mail
	 */
	function default_options() {
		$this->opts = array(
			'email' => array(
				'from'		=> get_bloginfo('admin_email'),
				'to'		=> get_bloginfo('admin_email'),
				'bcc'		=> '',
				'subject'	=> 'Contact Form for '.site_url(),
			),
			'success' => array(
				'message'	=> 'Thank you, your message has been sent.',
				'redirect'	=> '',
			),
			'failure'	=> array(
				'message'	=> 'Sorry, your message could not be sent at this time.',
			),
			'recaptcha'	=> array(
				'use'		=> false,
				'public'	=> '',
				'private'	=> '',
			),
		);
	}
	
	
	/**
	 *	Filter to generate email headers
	 */
	function filter_headers($headers=false, $form=false) {
		if ($this->form_name !== $form->form_name)	return $headers;
		$from_name = $_POST['cust_name'];
		$from_email = $_POST['cust_email'];
		$headers = array(
//			"From: ".$this->opts['email']['from'].'>',								// From should be an email address at this domain.
			"From: ".get_bloginfo('name').' <'.$this->opts['email']['from'].'>',	// From should be an email address at this domain.
			"Reply-To: {$from_name} <{$from_email}>",								// Reply-to and -path should be visitor email.
			"Return-Path: {$from_name} <{$from_email}>",
		);
		return $headers;
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
			'cust_email'	=> array(
				'label'		=> 'Email Address',
				'type'		=> 'email',
				'required'	=> true,
				'error'		=> 'Please provide your email address',
			),
			'cust_phone'	=> array(
				'label'		=> 'Phone Number',
				'type'		=> 'text',
			),
			'cust_message'	=> array(
				'label'		=> 'Your Message',
				'type'		=> 'textarea',
				'required'	=> true,
				'error'		=> 'Please provide your message',
			),
		);
	}
	
	
}


?>