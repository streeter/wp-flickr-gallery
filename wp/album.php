<?php 

define('WPFLICKRGALLERY', true);
define('WPFLICKRGALLERY_STANDALONE', true);

require_once (dirname(__FILE__).'/../wp-flickr-gallery.php');

if (file_exists(get_template_directory()."/wp-flickr-gallery.php")) {
	include_once(get_template_directory()."/wp-flickr-gallery.php");
}