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
				'class'		=> 'testclass',
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
	 *	Admin: This is the part that lists logged submissions
	 */
	function admin_list_logs() {
		// Delete posts?
		if (isset($_POST['DeleteLogs']) && !empty($_POST['enquiries']) && is_array($_POST['enquiries'])) {
			foreach ($_POST['enquiries'] as $delete_id) {
				wp_delete_post($delete_id, true);
			}
			echo '<div class="updated"><p>Selected logs have been deleted.</p></div>'."\n";
		}
		// Load posts?
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
		  if (empty($posts)) {
			  echo '<tr><td colspan="5" align="center">No logs available.</td></tr>'."\n";
		  }
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
            	<p>Viewing: <a href="<?php echo $form['Viewing']; ?>"><?php echo $form['Viewing']; ?></a></p>
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

<script><!--
jQuery(document).ready(function($){
	$('input#check_all').click(function(e) {
		if ($(this).attr('checked') == 'checked') {
			$('table.widefat td.ctrl input').attr('checked', 'checked');
		} else {
			$('table.widefat td.ctrl input').attr('checked', false);
		}
	});
});
--></script>

		<?php
	}
	
	
}



?>