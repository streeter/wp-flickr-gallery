<?php
/*
Copyright (c) 2007
Released under the GPL license
http://www.gnu.org/licenses/gpl.txt

This file is part of WPFlickrGallery.
WPFlickrGallery is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

global $wp_flickr_gallery;

if (!isset ($wp_flickr_gallery)) {

	$wp_flickr_gallery = null;

	if (strpos(strtolower(dirname(__FILE__)), 'wp-content') !== false) {
		if (WPFLICKRGALLERY_STANDALONE === true) {
			require_once (dirname(__FILE__).'/../../../wp-blog-header.php');
		}
				
		if (defined('WPLANG')) {
			define('WPFLICKRGALLERY_LANG', WPLANG); 
		}
		
		require_once (dirname(__FILE__).'/wp/WPFlickrGallery_WP.class.php');
		$wp_flickr_gallery = new WPFlickrGallery_WP();
			
	} else {
	
	
		require_once (dirname(__FILE__).'/WPFlickrGallery.class.php');
		$wp_flickr_gallery = new WPFlickrGallery();
	}

}
