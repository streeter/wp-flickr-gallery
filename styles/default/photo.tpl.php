<div id='wp-flickr-gallery' class='wp-flickr-gallery'>
<!-- Start of WPFlickrGallery -->

<?php if ($can_edit == true): ?>
	<div id='wp-flickr-gallery-post-helper-switch'><img src="<?php echo get_settings('siteurl'); ?>/wp-content/plugins/wp-flickr-gallery/styles/default/images/wrench.png" alt="Post Helper" title="Post Helper" width="16" height="16" /></div>
	<div id='wp-flickr-gallery-post-helper-block' style="display: none">
		<img id="wp-flickr-gallery-post-helper-block-close" src="<?php echo get_settings('siteurl'); ?>/wp-content/plugins/wp-flickr-gallery/styles/default/images/cross.png" alt="Close" title="Close" width="16" height="16" />
		<div id='wp-flickr-gallery-post-helper-block-rb'>
			<table>
				<tr>
					<td valign="top"><?php echo fa__('Size'); ?>:</td>
					<td>
						<?php foreach ($sizes as $size): ?>
						<input type="radio" name="size" value="<?php echo $size['value']; ?>" id="size-<?php echo $size['value']; ?>">
						<label for="size-<?php echo $size['value']; ?>"><?php echo $size['title']; ?></label>
						<br />
						<?php endforeach; ?>	
					</td>
				</tr>			
				<tr>
					<td valign="top"><?php echo fa__('Position'); ?>:</td>
					<td>
						<input type="radio" name="position" value="l" id="position-l"> <label for="position-l"><?php echo fa__('Float left'); ?></label><br />
						<input type="radio" name="position" value="r" id="position-r"> <label for="position-r"><?php echo fa__('Float right'); ?></label><br />
						<input type="radio" name="position" value="" checked="checked" id="position-none"> <label for="position-none"><?php echo fa__('No Float'); ?></label><br />
					</td>
				</tr>
				<tr>
					<td valign="top"><?php echo fa__('Link to'); ?>:</td>
					<td>
						<input type="radio" name="linkto" value="p" checked="checked" id="linkto-photo"> <label for="linkto-photo"><?php echo fa__('Photo'); ?></label><br />
						<input type="radio" name="linkto" value="i" id="linkto-index"> <label for="linkto-index"><?php echo fa__('Index page'); ?></label><br />
					</td>
				</tr>
			</table>
		</div>
	<br />
	<?php echo fa__('Copy and paste the following line into your post'); ?>:
	<div id='wp-flickr-gallery-post-helper-value'><?php echo $post_value; ?></div> 
	</div> 
<?php endif; ?>	

<h3 class='wp-flickr-gallery-title'>
	<a href='<?php echo $home_url; ?>'><?php echo $home_label; ?></a>
	&raquo;
	<a href='<?php echo $title_url; ?>'><?php echo $title_label; ?></a>
	&raquo;
	<span id="wp-flickr-gallery-photo-title"><?php echo $title; ?></span>
</h3>

<div class='wp-flickr-gallery-navigation wp-flickr-gallery-navigation-top'>
	<?php if (isset($prev_button)): ?>
		<?php echo $prev_button; ?>
	<?php endif; ?> 
	
	<?php echo $return_button; ?>
	
	<?php if (isset($next_button)): ?>
		<?php echo $next_button; ?>
	<?php endif; ?>
</div>

<div class='wp-flickr-gallery-photo wp-caption'>
	<div class='wp-flickr-gallery-thumbnail'>
		<a href='<?php echo $photo_url; ?>'>
			<img src='<?php echo $image; ?>' alt='' title='<?php echo $photo_title_label; ?>' width='<?php echo $photo_width; ?>'/>
		</a>
	</div>
	
	<p class='wp-flickr-gallery-meta wp-caption-text'><?php echo $description; ?></p>
</div> 

<div class='wp-flickr-gallery-meta'>
	
	<div class='wp-flickr-gallery-date-taken'><?php echo $date_taken; ?></div>

	<?php if (isset($tags)): ?>
	<div class='wp-flickr-gallery-tags-block'>
		<span class="wp-flickr-gallery-tags-label"><a href='<?php echo $tags_url; ?>'><?php echo $tags_label; ?></a>:</span>
		<span class="wp-flickr-gallery-tags">
		<?php while($tag = current($tags)): ?>
			<a href='<?php echo $tag['url']; ?>'><?php echo $tag['tag']; ?></a><?php if ($this->has_next($tags)): ?>, <?php endif;?>
			<?php next($tags); ?>
		<?php endwhile; ?>
		</span>
	</div>
	<?php endif; ?>

	<?php if (isset($sizes_label)): ?>
	<div class='wp-flickr-gallery-photo-sizes-container'>
		<?php echo $sizes_label; ?>: 
		<?php foreach ($sizes as $size): ?>					
		<a href='<?php echo $size['image']; ?>' class='wp-flickr-gallery-photo-size' title="<?php echo $size['title']; ?>"><?php echo $size['display']; ?></a>
		<?php endforeach; ?>
	</div>
	<?php endif; ?>

	<p>
		<a href='http://www.flickr.com/photos/<?php echo $nsid; ?>/<?php echo $photo; ?>/'><?php echo $flickr_label; ?></a>
	</p>

	<?php if (isset($exif_label)): ?>
	<div id='exif' class='wp-flickr-gallery-exif'>
		<a href="javascript:wp-flickr-gallery.showExif('<?php echo $exif_data; ?>')"><?php echo $exif_label; ?></a>
	</div>
	<?php endif; ?>
	
	<?php if (isset($comments)): ?>
	<div class="wp-flickr-gallery-comment-block">
	<span class="wp-flickr-gallery-comment-title"><?php echo fa__('Comments'); ?>:</span>
	<?php foreach ($comments as $comment): ?>
		<div class="wp-flickr-gallery-comment-author"><a href="<?php echo $comment['author_url']; ?>"><?php echo $comment['author_name']; ?></a></div>
		<div class="wp-flickr-gallery-comment"><?php echo $comment['text']; ?></div>
	<?php endforeach; ?>
	</div>
	<?php endif; ?>	
	
</div> 


<script type='text/javascript'>
//<!--
	wp_flickr_gallery_remote_url = '<?php echo $remote_url; ?>';
	
<?php if ($can_edit == true): ?>	
	wp_flickr_gallery.photo_id = '<?php echo $photo_id; ?>';
	wp_flickr_gallery.title = '<?php echo preg_replace('/[\n|\r]/','',htmlspecialchars($title, ENT_QUOTES)); ?>';
	wp_flickr_gallery.desc = '<?php echo preg_replace('/[\n|\r]/','',htmlspecialchars($description_orig, ENT_QUOTES)); ?>';
	wp_flickr_gallery.nodesc = '<?php echo $no_description_text; ?>';
	
	wp_flickr_gallery.next_page = '<?php echo $next_page; ?>';
	wp_flickr_gallery.next_id = '<?php echo $next_id; ?>';
	wp_flickr_gallery.prev_page = '<?php echo $prev_page; ?>';
	wp_flickr_gallery.prev_id = '<?php echo $prev_id; ?>';
	
	wp_flickr_gallery.album = '<?php echo $album; ?>';
	wp_flickr_gallery.tags = '<?php echo $in_tags; ?>';	
	
	wp_flickr_gallery.post_value = '<?php echo $post_value; ?>';
	
	//wp_flickr_gallery.makeEditable('wp-flickr-gallery-photo-desc');
	//wp_flickr_gallery.makeEditable('wp-flickr-gallery-photo-title');   		
	wp_flickr_gallery.enable_post_helper();
<?php endif; ?>
	

	wp_flickr_gallery.prefetch('<?php echo $next_image; ?>');
	
//-->
</script>


<!-- End of Falbum -->
</div>