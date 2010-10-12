<!-- Start of WPFlickrGallery -->
<div id='wp-flickr-gallery' class='wp-flickr-gallery'>

<h3 class='wp-flickr-gallery-title'>
	<?php if (isset($recent_label)): ?>
	<a href='<?php echo $url; ?>'><?php echo $photos_label; ?></a>&nbsp;&raquo;&nbsp;<?php echo $recent_label; ?>
	<?php else: ?>
	<a href='<?php echo $url; ?>'><?php echo $photos_label; ?></a>&nbsp;&raquo;&nbsp;<a href='<?php echo $tag_url; ?>'><?php echo $tags_label; ?></a>:&nbsp;<?php echo $tags; ?>
	<?php endif; ?>
</h3>
<!--
<div class='wp-flickr-gallery-meta'>
	<div class='wp-flickr-gallery-slideshowlink'>
		<a href='#' onclick="window.open('http://www.flickr.com/slideShow/index.gne?set_id=<?php echo $album_id; ?>','slideShowWin','width=500,height=500,top=150,left=70,scrollbars=no, status=no, resizable=no')"><?php echo $slide_show_label; ?></a>
	</div>
</div>
-->

<?php
	if (isset($paging_top)) {
		echo $paging_top;
	}
?>
<table class='wp-flickr-gallery-albums'>
<?php
	foreach ($thumbnails as $i => $row) {
		echo (($i % $columns_per_page) == 0 ? "<tr>" : "");
?>
	<td width="<?php echo floor(100 / $columns_per_page); ?>%" class="wp-flickr-gallery-photo wp-caption">
		<div class='wp-flickr-gallery-thumbnail <?php echo $dropshadows ?>'>
			<a href='<?php echo $row['url']; ?>'>
				<img src='<?php echo $row['thumbnail']; ?>' title='<?php echo $row['title']; ?>' alt='<?php echo $row['title']; ?>' />
			</a>
		</div>
	</td>
<?php
		echo (($i % $columns_per_page) == $columns_per_page - 1 ? "</tr>" : "");
	}
	if (!count($thumbnails)) {
?>
		<tr><td class='wp-flickr-gallery-photo wp-caption'><h3 class='wp-flickr-gallery-title'>No photos were found for tag!</h3></td></tr>
<?php
	}
?>
</table>

<?php if (isset($paging_bottom)): ?>
	<?php echo $paging_bottom; ?>
<?php endif; ?>

</div>
<!-- End of Falbum -->