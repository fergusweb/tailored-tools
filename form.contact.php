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
				if ($form['cust_message'])	echo 	'<p>'.nl2br($form['cust_message']).'</p>';
				if ($form['Viewing'])		echo 	'<p>Viewing: <a target="_blank" href="'.$form['Viewing'].'">'.$form['Viewing'].'</a></p>';
				if (!$form)					echo	"\n<pre>Problem decoding information.\nRaw data: ".print_r($record->post_content,true)."</pre>\n";
				echo '</td>';
				echo '</tr>';
			}
		}
		
	}	
}


?>