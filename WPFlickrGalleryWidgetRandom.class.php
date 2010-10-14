<?php

/**
 * Random widget class
 *
 * Displays a random image in a widget.
 *
 * @since 0.9
 */
class WPFlickrGallery_Widget_Random extends WP_Widget {
	
	protected $sizes = array('s', 't', 'm');

	function WPFlickrGallery_Widget_Random() {
		$widget_ops = array(
			'classname' => 'wp_flickr_gallery_random',
			'description' => __( "A random image from Flickr is displayed.") );
		$this->WP_Widget('wp_flickr_gallery_random', __('WP Flickr Gallery Random'), $widget_ops);
	}

	function widget( $args, $instance ) {
		extract($args);
		$title = apply_filters('widget_title',
			empty($instance['title']) ? __('Random Image') : $instance['title'],
			$instance,
			$this->id_base);

		echo $before_widget;
		if ( $title )
			echo $before_title . $title . $after_title;
		
		require_once('wp-flickr-gallery.php');
		global $wp_flickr_gallery;
		
		$number = 1;
		$tags = '';
		$style = 0;
		$size = 'm';
		echo $wp_flickr_gallery->show_random($number, $tags, $style, $size);

		echo $after_widget;
	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['number'] = (int)strip_tags($new_instance['number']);
		$instance['tags'] = strip_tags($new_instance['tags']);
		$instance['style'] = (int)strip_tags($new_instance['style']);
		$instance['size'] = strip_tags($new_instance['size']);

		return $instance;
	}

	function form( $instance ) {
		$instance = wp_parse_args( (array) $instance, array( 'title' => '', 'number' => 1, 'tags' => '', 'style' => 0, 'size' => 'm' ) );
		$title = strip_tags($instance['title']);
		$number = (int)strip_tags($instance['number']);
		$tags = strip_tags($instance['tags']);
		$style = (int)strip_tags($instance['style']);
		$size = strip_tags($instance['size']);
?>
		<p>
			<label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('number'); ?>"><?php _e('Number to Display:'); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id('number'); ?>" name="<?php echo $this->get_field_name('number'); ?>" type="text" value="<?php echo esc_attr($number); ?>" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('tags'); ?>"><?php _e('Tags to Display:'); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id('tags'); ?>" name="<?php echo $this->get_field_name('tags'); ?>" type="text" value="<?php echo esc_attr($tags); ?>" />
		</p>
		<?php /*
		<p>
			<label for="<?php echo $this->get_field_id('style'); ?>"><?php _e('Style to Display:'); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id('style'); ?>" name="<?php echo $this->get_field_name('style'); ?>" type="text" value="<?php echo esc_attr($style); ?>" />
		</p>
		*/ ?>
		<p>
			<label for="<?php echo $this->get_field_id('size'); ?>"><?php _e('Size to Display:'); ?></label>
			<select class="widefat" id="<?php echo $this->get_field_id('size'); ?>" name="<?php echo $this->get_field_name('size'); ?>">
<?php
				foreach ($this->sizes as $s) {
?>
				<option value="<?php echo esc_attr($s); ?>" <?php if ($s == $size) { echo "selected"; } ?> ><?php echo esc_attr($s); ?></option>
<?php
				}
?>
			</select>
		</p>
<?php
	}
}

?>