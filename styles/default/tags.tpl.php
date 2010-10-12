<!-- Start of WPFlickrGallery -->
<div id='wp-flickr-gallery' class='wp-flickr-gallery'>

<h3 class='wp-flickr-gallery-title'>
	<a href='<?php echo $home_url; ?>'><?php echo $home_label; ?></a>&nbsp;&raquo;&nbsp;<?php echo $tags_label; ?>	
</h3>

<div class='wp-flickr-gallery-meta'>
</div>

<div class='wp-flickr-gallery-cloud'>
	<?php foreach ($tags as $tag): ?>
		<a href='<?php echo $tag['url']; ?>' class='<?php echo $tag['class']; ?>' title='<?php echo $tag['title']; ?>'><?php echo $tag['name']; ?></a>&nbsp;
	<?php endforeach; ?>	
</div>

</div>
<!-- End of Falbum -->


