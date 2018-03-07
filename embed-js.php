<?php
/**
 *	Helper function to embed JS in a page.  Adds a meta-box to admin area for pages/posts.
 */

new ttools_embed_page_js();

class ttools_embed_page_js {
	public	$meta_value_key = 'ttools_embed_js';
	public	$output_hook	= 'wp_print_footer_scripts';
	
	function __construct() {
		add_action('add_meta_boxes', array($this,'add_meta_boxes'));
		add_action('save_post', array($this,'save_post'));
		add_action($this->output_hook, array($this,'output_embed_code'));
	}
	
	function output_embed_code() {
		global $post;
		if (!$post || !is_object($post))	return false;
		$code = get_post_meta($post->ID, $this->meta_value_key, true);
		if (empty($code))	return false;
		// Output
		echo $code;
	}
	
	function save_post( $post_id ) {
		// Security check
		if (empty($_POST) || !isset($_POST['embed_javascript']))						return;
		if ($_POST['post_type'] == 'page' && !current_user_can('edit_page', $post_id))	return;
		if ($_POST['post_type'] == 'post' && !current_user_can('edit_post', $post_id))	return;
		if (!isset($_POST['ttools_embedjs']) || ! wp_verify_nonce($_POST['ttools_embedjs'], plugin_basename(__FILE__)))	return;
		// Save embed code
		update_post_meta($post_id, $this->meta_value_key, $_POST['embed_javascript']);
	}
	
	function add_meta_boxes() {
		global $post;
		$code = get_post_meta($post->ID, $this->meta_value_key, true);
		
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
		<style><!--
		div#embed_js { }
		div#embed_js p textarea { height:10em; }
		--></style>
		<?php
	}
}


?>