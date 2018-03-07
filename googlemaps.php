<?php


/**
 *	Google Maps helper
 */



new TailoredTools_GoogleMaps();


class TailoredTools_GoogleMaps {
	public	$shortcode = 'GoogleMap';
	
	
	function __construct() {
		add_shortcode($this->shortcode, array($this,'handle_shortcode'));
		add_shortcode(strtolower($this->shortcode), array($this,'handle_shortcode'));
	}
	
	
	function handle_shortcode($atts=false) {
		$atts = shortcode_atts(array(
			'address'	=> false,
			'class'		=> 'googlemap',
			'width'		=> 1000,
			'height'	=> 400,
			'zoom'		=> 16,
		), $atts);
		if (!$atts['address'])	return '';
		
		ob_start();
		
		$http = (is_ssl()) ? 'https://' : 'http://';
		$address_url = $http.'maps.google.com.au/maps?f=q&source=s_q&hl=en&t=m&output=embed&z='.$atts['zoom'].'&q='.urlencode($atts['address']);
		$image_url = $http.'maps.google.com/maps/api/staticmap?format=jpg&size=500x280&sensor=true&zoom='.$atts['zoom'].'&markers='.urlencode($atts['address']);
		$link_url = $http.'maps.google.com.au/maps?q='.urlencode($atts['address']);
		
		?>
		
		<div class="<?php echo $atts['class']; ?>">
			<div>
				<a href="<?php echo $link_url; ?>"><img src="<?php echo $image_url; ?>" /></a>
				<iframe class="<?php echo $atts['class']; ?>" width="<?php echo $atts['width']; ?>" height="<?php echo $atts['height']; ?>" frameborder="0" scrolling="no" marginheight="0" marginwidth="0" src="<?php echo $address_url; ?>"></iframe>
			</div>
		</div><!-- Google Map -->
		
		<?php
		return ob_get_clean();
	}
	
	
}


return;		// Return now to avoid outputting CSS below.


?>


<style><!--
/**
 *	CSS: Responsive google map embeds.  Add to your theme style.css file.
 */
div.googlemap { border:1px solid #999; background:#FFF; padding:2px; box-shadow:1px 1px 2px #CCC; border-radius:10px; margin:10px 0 15px; clear:both; }
div.googlemap div { position:relative; padding-bottom:56.25%; /* 16/9 Ratio */ padding-top:30px; /* Fix for IE6*/ height:0; overflow:hidden; }
div.googlemap iframe { display:none; position:absolute; top:0; left:0; width:100%; height:100%; border-radius:10px; }
div.googlemap img { position:absolute; top:0; left:0; width:100%; height:100%; border-radius:10px; }

@media only screen and (min-width:600px) {
	div.googlemap iframe { display:block; }
	div.googlemap img { display:none; }
}
--></style>


