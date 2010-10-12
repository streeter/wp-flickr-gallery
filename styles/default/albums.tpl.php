<!-- Start of WPFlickrGallery -->
<div id='wp-flickr-gallery' class='wp-flickr-gallery'>
<?php
	if (isset($paging_top)) {
		echo $paging_top;
	}
?>
	<table class='wp-flickr-gallery-albums'>
<?php
	foreach ($albums as $i => $row) {
		echo (($i % $columns_per_page) == 0 ? "<tr>" : "");
?>
		<td width="<?php echo floor(100 / $columns_per_page); ?>%" class="wp-flickr-gallery-album wp-caption">
			<h3 class='wp-flickr-gallery-title'>
				<a href='<?php echo $row['url']; ?>' title='<?php echo $row['title_d'] ?>'><?php echo $row['title']; ?></a>
<?php
		if (isset($row['tags_url'])) {
?>
				/ <a href='<?php echo $row['tags_url']; ?>' title='<?php echo $row['tags_title_d'] ?>'><?php echo $row['tags_title']; ?></a>
<?php
		}
?>
			</h3>
			<div class='wp-flickr-gallery-thumbnail <?php echo $dropshadows ?>'>
				<a href='<?php echo $row['url']; ?>'>
					<img src='<?php echo $row['thumbnail']; ?>' title='<?php echo $row['title']; ?>' alt='<?php echo $row['title']; ?>' />
				</a>
			</div>
<?php
		if ($row['meta']) {
?>
			<p class='wp-flickr-gallery-meta wp-caption-text'><?php echo $row['meta'] ?></p>
<?php
		}
		if ($row['description']) {
?>
			<p class='wp-flickr-gallery-description'><?php echo $row['description']; ?></p>
<?php
		}
?>
		</td>
<?php
		echo (($i % $columns_per_page) == $columns_per_page - 1 ? "</tr>" : "");
	}
?>
	</table>
<?php
	if (isset($paging_bottom)) {
		echo $paging_bottom;
	}
?>
</div>
<!-- End of Falbum -->