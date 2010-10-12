<?php
/**
 * Template Name: WP Flickr Gallery
 *
 * This is the template that displays all pages by default.
 * Please note that this is the WordPress construct of pages
 * and that other 'pages' on your WordPress site will use a
 * different template.
 *
 * @package WordPress
 */

get_header(); ?>

		<div id="container">
			<div id="content" role="main">
				<div id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
					<div class="entry-content">
						<?php $wp_flickr_gallery->show_photos(); ?>
					</div><!-- .entry-content -->
				</div><!-- #post-## -->
			</div><!-- #content -->
		</div><!-- #container -->

<?php get_sidebar(); ?>
<?php get_footer(); ?>