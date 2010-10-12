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

require_once (dirname(__FILE__).'/../WPFlickrGallery.class.php');
//require_once (dirname(__FILE__).'/../WPFlickrGallery_Prof.class.php');

class WPFlickrGallery_WP extends WPFlickrGallery {

	function __construct() {
		parent::__construct();
	}

	function get_options() {
		$wp_flickr_gallery_options = get_option('wp-flickr-gallery_options');
		return $wp_flickr_gallery_options;
	}

	/* Gets info from Cache Table */
	function _get_cached_data($key, $cache_option = WPFLICKRGALLERY_CACHE_EXPIRE_SHORT) {
		$data = null;

		if ($cache_option != WPFLICKRGALLERY_REFRESH_CACHE || $cache_option != WPFLICKRGALLERY_DO_NOT_CACHE) {

			global $wpdb;

			$table = $wpdb->prefix . 'wp-flickr-gallery_cache';

			$expired = null;

			if ($this->show_private == 'true') {
				$key .= '-private';
			}

			if ($this->can_edit == 'true') {
				$key .= '-edit';
			}

			//get existing data from db
			$output = $wpdb->get_row("SELECT data, (UNIX_TIMESTAMP(expires) - UNIX_TIMESTAMP()) expires FROM ".$table." WHERE ID='".md5($key)."'");
			if (isset ($output)) {
				$data = unserialize($output->data);
				$expires_value = $output->expires;
				if ($expires_value < 0) {
					$data = null;
				}
			}

			$this->logger->debug('cache get - key - '.$key.'<br />'.'cache - '. (isset ($data) ? 'hit' : 'miss'));

		}

		return $data;
	}

	/* Function to store the data in the cache table */
	function _set_cached_data($key, $data, $cache_option = WPFLICKRGALLERY_CACHE_EXPIRE_SHORT) {
		global $wpdb;

		$table = $wpdb->prefix . 'wp-flickr-gallery_cache';

		if ($this->show_private == 'true') {
			$key .= '-private';
		}

		if ($this->can_edit == 'true') {
			$key .= '-edit';
		}

		$this->logger->debug('cache set - key - '.$key);

		$wpdb->query("REPLACE INTO $table SET ID='".md5($key)."', data='".addslashes(serialize($data))."', expires=DATE_ADD(NOW(), INTERVAL $cache_option SECOND)");
	}

	function _clear_cached_data() {
		global $wpdb;
		$fa_table = $wpdb->prefix . "wp-flickr-gallery_cache";
		$wpdb->query("DELETE from ".$fa_table."");
	}

	/* Outputs a true or false variable for showing private photos based on the registered user level */
	function _show_private() {

		$PrivateAlbumChoice = false;
		if (strtolower($this->options['show_private']) == 'true') {
			global $user_level;

			$u_level = $user_level;
			if (!isset ($u_level)) {
				$u_level = 0;
			}

			if ($u_level >= $this->options['view_private_level']) {
				$PrivateAlbumChoice = true;
			}
		}

		return $PrivateAlbumChoice;
	}

	function _can_edit() {
		global $user_level;
		$can_edit = false;

		$u_level = $user_level;
		if (!isset ($u_level)) {
			$u_level = 0;
		}

		if ($u_level >= $this->options['can_edit_level']) {
			$can_edit = true;
		}

		return $can_edit;
	}


	/**
	 * Overridden from base class.
	 */
	function _construct_template($_style)	{
		// use display style specified in current wordpress theme folder ?

		if ($_style == '_current_wordpress_theme') {
			require_once('Template_WP.class.php');
			$this->template = new Template_WP();
		} else {
      // else display style is specified in wp-flickr-gallery styles folder
			parent::_construct_template($_style);
		}
	}

}
