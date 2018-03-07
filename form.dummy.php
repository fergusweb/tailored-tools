<?php

/**
 *	Dummy Form
 *	Uses TailoredForm as parent
 */


new DummyForm();


class DummyForm extends TailoredForm {
	public		$form_name		= 'Dummy Form';
	public		$option_key		= 'dummy_form_opts';
	public		$shortcode		= 'DummyForm';
	public		$log_type		= 'dummy_form_log';
	public		$submit_key		= 'submit_dummy_form';
	public		$submit_label	= 'Enquire Now';
	public		$form_class		= 'validate dummy';
	// Which anti-spam modules are available?
//	public		$avail_recaptcha= true;
//	public		$avail_akismet	= true;
//	public		$check_bad_words= true;
	
//	public		$show_graph		= true;
	
	
	
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
			'label'		=> $this->form_name,
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
				'subject'	=> $this->form_name.' for '.site_url(),
			),
			'success' => array(
				'message'	=> 'Thank you, your enquiry has been sent.',
				'redirect'	=> '',
			),
			'failure'	=> array(
				'message'	=> 'Sorry, your enquiry could not be sent at this time.',
			),
		);
	}
	
	
	/**
	 *	Filter to generate email headers
	 */
	function filter_headers($headers=false, $form=false) {
		if ($this->form_name !== $form->form_name)	return $headers;
		$visitor_name = $_POST['cust_name'];
		$visitor_email = $_POST['cust_email'];
		$headers = array(
			"From: ".get_bloginfo('name').' <'.$this->opts['email']['from'].'>',	// From should be an email address at this domain.
			"Reply-To: {$visitor_name} <{$visitor_email}>",							// Reply-to and return-path should be visitor email.
			"Return-Path: {$visitor_name} <{$visitor_email}>",
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
			'full_name'		=> array(
				'label'		=> 'Full Name',
				'type'		=> 'name',
				'required'	=> true,
				'error'		=> 'Please provide your full name',
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
			),
			'cust_address'	=> array(
				'label'		=> 'Address',
				'type'		=> 'address',
			),
			'cust_message'	=> array(
				'label'		=> 'Your Message',
				'type'		=> 'textarea',
				'required'	=> true,
				'error'		=> 'Please provide your message',
			),
			'cust_address_long'	=> array(
				'label'		=> 'Address (Long)',
				'type'		=> 'address_long',
				'required'	=> true,
				'error'		=> 'Please provide your long address',
			),
			
			'test_select'	=> array(
				'label'		=> 'Choose Sel (default 2)',
				'type'		=> 'select',
				'options'	=> array( 'one'=>'Option One', 'two'=>'Option Two', 'three'=>'Option Three','four'=>'Option Four' ),
				'required'	=> true,
				'error'		=> 'Please use the select box',
				'default'	=> 'two',
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
						'label'		=> 'Choose Rad (default 2)',
						'type'		=> 'radio',
						'options'	=> array( 'one'=>'Option One', 'two'=>'Option Two', 'three'=>'Option Three', 'four'=>'Option Four' ),
						'required'	=> true,
						'error'		=> 'Please use the radio boxes',
						'default'	=> 'two',
					),
					'test_tickbox'	=> array(
						'label'		=> 'Choose Checks (default 3,4)',
						'type'		=> 'checkbox',
						'options'	=> array( 'one'=>'Option One', 'two'=>'Option Two', 'three'=>'Option Three', 'four'=>'Option Four' ),
						'required'	=> true,
						'error'		=> 'Please use the checkboxes',
						'default'	=> array( 'three', 'four' ),
					),
				),
			),
			'test_radios2'	=> array(
				'label'		=> 'Choose Checks (no key, default 3)',
				'type'		=> 'checkbox',
				'options'	=> array( 'Option One', 'Option Two', 'Option Three', 'Option Four', 'Option Five' ),
				'required'	=> true,
				'error'		=> 'Please use the radio boxes',
//				'default'	=> 'Option Three',
				'default'	=> array('Option Three', 'Option Five'),
			),
			'cust_upload'	=> array(
				'label'		=> 'Choose File',
				'type'		=> 'file',
//				'required'	=> true,
//				'error'		=> 'Please upload a file',
			),
		);
	}
	
	
	/**
	 *	To display logged data for this form
	 */
	function admin_list_logs() {
		$class_name = 'contact_form_log_Table';
		if (!$this->log_type || !class_exists($class_name))	return false;
		$per_page = (isset($_GET['per_page']) && is_numeric($_GET['per_page'])) ? $_GET['per_page'] : '20';
		$table = new $class_name();
		$table->prepare_items($this->log_type, $per_page);
		?>
        <form id="enquiries" method="post">
            <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
            <?php $table->display() ?>
        </form>
		<?php
	}
	
	/* */
}



/**
 *	Helper to display logged enquiries
 */
if (is_admin() && class_exists('tws_form_log_Table') && !class_exists('contact_form_log_Table')) {
	class contact_form_log_Table extends tws_form_log_Table {
	
		function get_columns() {
			return array(
				'cb'			=> '<input type="checkbox" />',
				'date'			=> __('Date'), //array( 'date', true ),
				'cust_name'		=> __('Name'),
				'cust_email'	=> __('Email'),
				'cust_phone'	=> __('Phone'),
			);
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