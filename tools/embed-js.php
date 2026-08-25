<?php
/**
 *	Helper function to embed JS in a page.  Adds a meta-box to admin area for pages/posts.
 */

new ttools_embed_page_js();

class ttools_embed_page_js {
	public	$meta_value_key = 'ttools_embed_js';
	public	$meta_author_key= '_ttools_embed_js_author';
	public	$output_hook	= 'wp_print_footer_scripts';
	
	function __construct() {
		add_action('add_meta_boxes', array($this,'add_meta_boxes'));
		add_action('save_post', array($this,'save_post'));
		add_action($this->output_hook, array($this,'output_embed_code'));
		add_filter('is_protected_meta', array($this,'protect_meta'), 10, 2);
	}
	
	/**
	 *	The stored value is printed as raw HTML, so it must not be writable through the
	 *	generic Custom Fields box or the REST meta endpoints, which only check whether the
	 *	user may edit the post.  Marking it protected hides it from both.
	 */
	function protect_meta($protected, $meta_key) {
		if ($meta_key === $this->meta_value_key)	return true;
		return $protected;
	}
	
	/**
	 *	Who is allowed to save raw JS?  `unfiltered_html` is the capability WordPress uses
	 *	to gate unfiltered markup: on multisite only super admins hold it, while a site
	 *	Administrator has `manage_options` without it.
	 */
	function current_user_can_embed() {
		return current_user_can('unfiltered_html');
	}
	
	/**
	 *	Was this code saved by somebody who is trusted with raw HTML?
	 *	Code stored before the author was recorded is only trusted on single-site installs,
	 *	where the admins who could reach the meta box did hold `unfiltered_html` anyway.
	 */
	function code_is_trusted( $post_id ) {
		$saved_by = (int) get_post_meta($post_id, $this->meta_author_key, true);
		if ($saved_by)	return user_can($saved_by, 'unfiltered_html');
		return (!is_multisite() && !defined('DISALLOW_UNFILTERED_HTML'));
	}
	
	function output_embed_code() {
		global $post;
		if (!$post || !is_object($post))	return false;
		$code = get_post_meta($post->ID, $this->meta_value_key, true);
		if (empty($code))	return false;
		//	Output is deliberately unescaped, so only print it when whoever saved it may
		//	publish raw HTML.  Otherwise the meta value is a stored XSS.
		if (!$this->code_is_trusted($post->ID))	return false;
		// Output
		echo $code;
	}
	
	function save_post( $post_id ) {
		// Security check
		if (empty($_POST) || !isset($_POST['embed_javascript']))						return;
		if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)								return;
		if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id))				return;
		if (!current_user_can('edit_post', $post_id))									return;
		// Raw, unescaped JS is echoed verbatim on the front end, so only trust users with `unfiltered_html`.
		if (!$this->current_user_can_embed())											return;
		if (!isset($_POST['ttools_embedjs']) || ! wp_verify_nonce($_POST['ttools_embedjs'], plugin_basename(__FILE__)))	return;
		// Save embed code
		$code = (string) wp_unslash($_POST['embed_javascript']);
		if (trim($code) === '') {
			delete_post_meta($post_id, $this->meta_value_key);
			delete_post_meta($post_id, $this->meta_author_key);
			return;
		}
		update_post_meta($post_id, $this->meta_value_key, wp_slash($code));
		// Record who vouched for this code, so output can re-check the capability later.
		update_post_meta($post_id, $this->meta_author_key, get_current_user_id());
	}
	
	function add_meta_boxes() {
		if (!$this->current_user_can_embed())	return;
		
		$screens = array('page', 'post');
		foreach ($screens as $screen) {
			add_meta_box('embed_js', 'Embed JS (eg, Adwords Conversion Code)', array($this,'metabox_embed_js'), $screen, 'normal', 'low');
		}
	}
	
	function metabox_embed_js( $post ) {
		$code = get_post_meta($post->ID, $this->meta_value_key, true);
		wp_nonce_field( plugin_basename( __FILE__ ), 'ttools_embedjs' );
		?>
		<p><label>
			You can embed javascript on this page by pasting it into this box.  Helpful for Adwords conversions, etc.  Code will embed on this page only in the footer.
			<textarea class="widefat" name="embed_javascript"><?php echo esc_textarea($code); ?></textarea>
		</label></p>
		<?php if (!empty($code) && !$this->code_is_trusted($post->ID)) { ?>
		<p class="notice notice-warning" style="padding:0.5em;">
			This code is not being output, because it was saved by a user who is not permitted to publish unfiltered HTML.
			Review it and save the page again to re-enable it.
		</p>
		<?php } ?>
		<style><!--
		div#embed_js { }
		div#embed_js p textarea { height:10em; }
		--></style>
		<?php
	}
}


?>
