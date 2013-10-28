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
	public		$avail_ayah		= true;
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
			"From: {$from_name} <{$from_email}>",
			"Reply-To: {$from_name} <{$from_email}>",
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



/**
 *	This is used in the admin area to display logged enquiries
 */
if (is_admin()) {
	if(!class_exists('WP_List_Table'))	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
	
	// Will auto-load if you use $log_type as table name, like this:  $logtype_Table	
	class contact_form_log_Table extends WP_List_Table {
		
		function __construct( $post_type='', $per_page=20) {
			$this->post_type = $post_type;
			$this->per_page = $per_page;
			parent::__construct(array(
				'singular'	=> 'enquiry',
				'plural'	=> 'enquiries',
				'ajax'		=> false,
			));
		}
	
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
		
		function prepare_items() {
			
			$per_page = $this->per_page;
			$columns = $this->get_columns();
			$hidden = array();
			$sortable = $this->get_sortable_columns(); 
			$this->_column_headers = array($columns, $hidden, $sortable); 
			
			$this->process_bulk_action(); 
			
			$posts = get_posts(array(
				'numberposts'	=> -1,
				'post_type'		=> $this->post_type,
				'post_status'	=> 'private',
			));
			
			$current_page = $this->get_pagenum(); 
			
			$total_items = count($posts); 
			
			$this->items = array_slice($posts,(($current_page-1)*$per_page),$per_page);
			
			$this->set_pagination_args( array(
				'total_items' => $total_items,                  //WE have to calculate the total number of items
				'per_page'    => $per_page,                     //WE have to determine how many items to show on a page
				'total_pages' => ceil($total_items/$per_page)   //WE have to calculate the total number of pages
			) );
			
		}
		
		function display_rows() {
			if (empty($this->items))	return false;
			$records = $this->items;
			list($columns, $hidden) = $this->get_column_info();
			foreach ($records as $record) {
				$form = TailoredForm::__unserialize($record->post_content);
				echo '<tr id="record_">'."\n";
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
				echo '<tr>';
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