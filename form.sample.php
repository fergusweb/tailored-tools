<?php

/**
 *	Sample Form Class
 *	Uses TailoredForm as parent
 */


new SampleForm();


class SampleForm extends TailoredForm {
	public		$form_name		= 'Sample Form';
	public		$option_key		= 'sample_form_opts';
	public		$shortcode		= 'SampleForm';
	public		$log_type		= 'sample_form_log';
	public		$submit_key		= 'submit_sample_form';
	public		$submit_label	= 'Submit Form';
	public		$form_class		= 'test class';

	
	/**
	 *	Constructor
	 */
	function __construct() {
		$this->load_questions();
		$this->init();
		add_action('ttools_form_before_submit_button', array(&$this,'do_before_submit_button'));
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
				'subject'	=> 'Test Form Submission for '.site_url(),
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
	function filter_headers($headers=false) {
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
				'class'		=> 'testclass',
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
				'type'		=> 'text',
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
				'options'	=> array( 'one'=>'Option One', 'two'=>'Option Two', 'three'=>'Option Three' ),
				'required'	=> true,
				'error'		=> 'Please use the select box',
//				'default'	=> 'two',
			),
			'test_checks' => array(
				'type'		=>'fieldset',
				'label' 	=> 'Some radios & checkboxes...',
				'questions'	=> array(
					'test_radios'	=> array(
						'label'		=> 'Choose Rad',
						'type'		=> 'radio',
						'options'	=> array( 'one'=>'Option One', 'two'=>'Option Two', 'three'=>'Option Three' ),
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
	 *	Admin: This is the part that lists logged submissions
	 */
	function admin_list_logs() {
		$posts = get_posts(array(
			'numberposts'	=> -1,
			'post_type'		=> $this->log_type,
			'post_status'	=> 'private',
		));
		?>
		<form class="plugin_settings" method="post" action="<?php echo esc_url($_SERVER['REQUEST_URI']); ?>">
		<?php echo wp_nonce_field($this->nonce); ?>
		<table class="widefat">
		  <thead><tr><th><input type="checkbox" id="check_all" /></th><th>Date</th><th>Name</th><th>Email</th><th>Phone</th></tr></thead>
		  <tbody>
		  <?php
		  foreach ($posts as $post) {
		  	$form = $this->__unserialize($post->post_content);
			$date = $this->format_time_ago( strtotime($post->post_date) );
			?>
			<tr>
				<td class="ctrl" rowspan="2"><input type="checkbox" name="enquiries[]" value="<?php echo $post->ID; ?>" /></td>
				<td class="date" rowspan="2"><?php echo $date; ?></td>
				<td class="name"><?php echo $form['cust_name']; ?></td>
				<td class="email"><?php echo $form['cust_email']; ?></td>
				<td class="phone"><?php echo $form['cust_phone']; ?></td>
			</tr>
			<tr class="message"><td class="msg" colspan="3">
            	Viewing: <a href="<?php echo $form['Viewing']; ?>"><?php echo $form['Viewing']; ?></a>
                <p><?php echo nl2br($form['cust_message']); ?></p>
            </td></tr>
			<?php
		  }
		  ?>
		  </tbody>
		  <tfoot><tr><th colspan="5">
		  	<input class="button-primary" type="submit" value="Delete Selected" name="DeleteLogs" onclick='return confirm("Are you sure?\nThis action cannot be undone.")'>
		  </th></tr></tfoot>
		</table>
		</form>
		<?php
	}
	
	
}



?>