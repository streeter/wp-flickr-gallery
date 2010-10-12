<?php
/*
Copyright (c) 2010
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

define('WPFLICKRGALLERY_VERSION', '0.9.0');

define('WPFLICKRGALLERY_PATH', dirname(__FILE__));

define('WPFLICKRGALLERY_DO_NOT_CACHE', 0);
define('WPFLICKRGALLERY_CACHE_EXPIRE_SHORT', 3600); //How many seconds to wait between refreshing cache (default = 3600 seconds - hour)
define('WPFLICKRGALLERY_CACHE_EXPIRE_LONG', 604800); //How many seconds to wait between refreshing cache (default = 604800 seconds - 1 week)

define('WPFLICKRGALLERY_API_KEY', '');
define('WPFLICKRGALLERY_SECRET', '');

define('WPFLICKRGALLERY_FLICKR_URL_IMAGE_1', 'http://farm');
define('WPFLICKRGALLERY_FLICKR_URL_IMAGE_2', '.static.flickr.com');

class WPFlickrGallery {

	var $options = none;

	var $can_edit;
	var $show_private;

	var $has_error;
	var $error_detail;

	var $logger;

	var $template;
	
	var $title;

	function __construct() {

		require_once WPFLICKRGALLERY_PATH.'/lib/Log.php';

		// Init Lang
		include_once(WPFLICKRGALLERY_PATH . '/wp-flickr-gallery-lang.php');

		add_filter('wp_title', array(&$this, 'title_filter'));

		$this->has_error = false;
		$this->error_detail = null;

		$this->options = $this->get_options();

		$this->can_edit = $this->_can_edit();
		$this->show_private = $this->_show_private();

		$this->_construct_template($this->options['style']);

		$conf = array ('title' => 'WPFlickrGallery Log Output');
		if ($this->can_edit == true) {
			//this->logger = & Log :: factory('fwin', 'LogWindow', '', $conf);
			//$this->logger = & Log :: factory('display', 'LogWindow', '', $conf);
			$this->logger = & Log :: factory('null', 'LogWindow');
		} else {
			//$this->logger = & Log :: factory('fwin', 'LogWindow', '', $conf);
			$this->logger = & Log :: factory('null', 'LogWindow');
		}

		//$mask = Log::UPTO(PEAR_LOG_INFO);
		//$this->logger->setMask($mask);

	}

	/* The main function - called in album.php, and can be called in any WP template. */
	function show_photos() {
		echo $this->show_photos_main();
	}

	function show_photos_main() {
		$album = $_GET['album'];
		$photo = $_GET['photo'];
		$page = $_GET['page'];
		$tags = $_GET['tags'];
		$show = $_GET['show'];

		$output = '';
		$continue = true;
		if (!is_null($show)) {
			if ($show == 'tags') {
				$output = $this->show_tags();
				$continue = false;
			}
			elseif ($show == 'recent') {
				$tags = '';
			}
		}

		if ($continue) {
			// Show list of albums/photosets (none have been selected yet)
			if (is_null($album) && is_null($tags) && is_null($photo)) {
				$output = $this->show_albums($page);
			}
			// Show list of photos in the selected album/photoset
			elseif (!is_null($album) && is_null($photo)) {
				$output = $this->show_album($album, $page);
			}
			// Show list of photos of the selected tags
			elseif (!is_null($tags) && is_null($photo)) {
				$output = $this->show_tags_thumbnails($tags, $page);
			}
			// Show the selected photo in the slected album/photoset
			elseif ((!is_null($album) || !is_null($tags)) && !is_null($photo)) {
				$output = $this->show_photo($album, $tags, $photo, $page);
			}
		}

		if ($this->has_error) {
			$this->template->reset('error');
			$this->template->set('message', $this->error_detail);
			$output = $this->template->fetch();
		}

		return $output;
	}

	/* Shows list of all albums - this is the first thing you see */
	function show_albums($page = 1) {

		$this->logger->info("show_albums($page)");

		if (!is_numeric($page)) { $page = 1; }
		$output = $this->_get_cached_data("showAlbums-$page");
		if (true||!$output) {
			$output = '';
			$this->template->reset('albums');
			
			$this->template->set('page_title', $this->get_page_title());

			$count = 0;
			$albums_list = array();

			if ($this->options['number_recent'] != 0 && $page == 1) {
				$resp = $this->_call_flickr_php('flickr.photos.search', array ("user_id" => $this->options['nsid'], "per_page" => '1', "sort" => 'date-taken-desc'));
				if (!isset ($resp)) {
					return;
				}
				$server = $resp['photos']['photo']['0']['server'];
				$farm = $resp['photos']['photo']['0']['farm'];
				$secret = $resp['photos']['photo']['0']['secret'];
				$photo_id = $resp['photos']['photo']['0']['id'];
				$thumbnail = self::create_flickr_image_url($farm, $server, $photo_id, $secret, $this->options['tsize']); 
				
				$data['tsize'] = $this->options['tsize'];
				$data['url'] = $this->create_url("show/recent/");
				$data['title'] = fa__('Recent Photos');
				$data['title_d'] = fa__('View all recent photos');
				$data['tags_url'] = $this->create_url("show/tags/");
				$data['tags_title'] = fa__('Tags');
				$data['tags_title_d'] = fa__('Tags');
				$data['description'] = fa__('See the most recent photos posted, regardless of which photo set they belong to.');
				$data['thumbnail'] = $thumbnail;
				$albums_list[] = $data;
				$count++;
			}

			$resp = $this->_call_flickr_php('flickr.photosets.getList', array ("user_id" => $this->options['nsid']));
			if (isset ($resp)) {
				$countResult = sizeof($resp['photosets']['photoset']);

				$photo_title_array = array ();
				for ($i = 0; $i < $countResult; $i ++) {
	
					if (($this->options['albums_per_page'] == 0) || (($count >= ($page - 1) * $this->options['albums_per_page']) && ($count < $page * $this->options['albums_per_page']))) {
						$photos = $resp['photosets']['photoset'][$i]['photos'];
						if ($photos != 0) {
							$data = array();
							
							$id = $resp['photosets']['photoset'][$i]['id'];
							$server = $resp['photosets']['photoset'][$i]['server'];
							$farm = $resp['photosets']['photoset'][$i]['farm'];
							$primary = $resp['photosets']['photoset'][$i]['primary'];
							$secret = $resp['photosets']['photoset'][$i]['secret'];
							$title = self::unhtmlentities($resp['photosets']['photoset'][$i]['title']['_content']);
							$description = self::unhtmlentities($resp['photosets']['photoset'][$i]['description']['_content']);
	
							$link_title = $this->get_link_title($title, $id, $photo_title_array);
							$thumbnail = self::create_flickr_image_url($farm, $server, $primary, $secret, $this->options['tsize']); 
							
							$data['tsize'] = $this->options['tsize'];
							$data['url'] = $this->create_url("album/$link_title/");
							$data['title'] = $title;
							$data['title_d'] = strtr(fa__('View all pictures in #title#'), array ("#title#" => $title));
							$data['meta'] = strtr(fa__('This photoset has #num_photots# pictures'), array ("#num_photots#" => $photos));
							$data['description'] = $description;
							$data['thumbnail'] = $thumbnail;
	
							$albums_list[] = $data;
	
						} else {
							$count --;
						}
					}
					$count ++;
				}
	
				$this->template->set('albums', $albums_list);
				$this->template->set('columns_per_page', $this->options['columns_per_page']);
	
				if ($this->options['albums_per_page'] != 0) {
					$pages = ceil($count / $this->options['albums_per_page']);
					if ($pages > 1) {
						$this->template->set('paging_top', $this->build_paging($page, $pages, '', 'top'));
						$this->template->set('paging_bottom', $this->build_paging($page, $pages, '', 'bottom'));
					}
				}
			}

			$this->template->set('dropshadows', $this->options['display_dropshadows']);

			$this->template->set('remote_url', $this->options['url_wp-flickr-gallery_dir']."/wp-flickr-gallery-remote.php");
			$this->template->set('url_root', $this->options['url_root']);

			$output = $this->template->fetch();

			$this->_set_cached_data("showAlbums-$page", $output);

		}

		return $output;
	}

	/* Shows Thumbnails of all photos in selected album */
	function show_album($album, $page = 1) {

		$this->logger->info("show_album($album, $page)");

		if (!is_numeric($page)) { $page = 1; }
		

		$output = $this->_get_cached_data("showAlbumThumbnails-$album-$page");
		if (!$output) {
			$this->template->reset('album');
			
			$this->template->set('columns_per_page', $this->options['columns_per_page']);

			list ($album_id, $album_title) = $this->_get_album_info($album);

			$resp = $this->_call_flickr_php('flickr.photosets.getPhotos', array ("photoset_id" => $album_id));
			if (!$resp) {
				return;
			}
			$countResult = count($resp['photoset']['photo']);

			$photo_title_array = array();
			$thumbnails_list = array();

			for ($i = 0; $i < $countResult; $i ++) {
				if (($this->options['photos_per_page'] == 0)
					|| (($i >= ($page - 1) * $this->options['photos_per_page'])
						&& ($i < ($page * $this->options['photos_per_page'])))) {
					
					$photo_id = $resp['photoset']['photo'][$i]['id'];
					$photo_title = $resp['photoset']['photo'][$i]['title'];
					$server = $resp['photoset']['photo'][$i]['server'];
					$farm = $resp['photoset']['photo'][$i]['farm'];
					$secret = $resp['photoset']['photo'][$i]['secret'];

					$photo_link = $this->get_link_title($photo_title, $photo_id, $photo_title_array);
					$thumbnail = self::create_flickr_image_url($farm, $server, $photo_id, $secret, $this->options['tsize']); 
					
					$data['tsize'] = $this->options['tsize'];
					$data['url'] = $this->create_url("album/$album".($page > 1 ? "/page/{$page}" : '')."/photo/$photo_link");
					$data['title'] = $photo_title;
					$data['thumbnail'] = $thumbnail;

					$thumbnails_list[] = $data;
				}
			}

			if ($this->options['photos_per_page'] != 0) {
				$pages = ceil($countResult / $this->options['photos_per_page']);

				if ($pages > 1) {
					$this->template->set('paging_top', $this->build_paging($page, $pages, 'album/'.$album.'/', 'top'));
					$this->template->set('paging_bottom', $this->build_paging($page, $pages, 'album/'.$album.'/', 'bottom'));
				}
			}

			$this->template->set('url', $this->create_url());
			$this->template->set('album_title', $album_title);
			$this->template->set('album_id', $album_id);
			$this->template->set('photos_label', fa__('Photos'));
			$this->template->set('slide_show_label', fa__('View as a slide show'));
			$this->template->set('thumbnails', $thumbnails_list);

			$this->template->set('dropshadows', $this->options['display_dropshadows']);

			$this->template->set('remote_url', $this->options['url_wp-flickr-gallery_dir']."/wp-flickr-gallery-remote.php");
			$this->template->set('url_root', $this->options['url_root']);

			$output = $this->template->fetch();

			$this->_set_cached_data("showAlbumThumbnails-$album-$page", $output);

		}
		return $output;
	}

	/* Shows thumbnails for all Recent and Tag thumbnails */
	function show_tags_thumbnails($tags, $page = 1) {

		$this->logger->info("show_tags_thumbnails($tags, $page)");

		if ($page == '') {
			$page = 1;
		}

		$output = $this->_get_cached_data("show_tags_thumbnails-$tags-$page");
		if (!$output) {

			$this->template->reset('tag-thumbnails');
			$this->template->set('columns_per_page', $this->options['columns_per_page']);
			
			$this->template->set('page_title', $this->get_page_title());

			$output = '';

			if ($tags == '') {
				// Get recent photos
				if ($this->options['number_recent'] == -1) {
					$resp = $this->_call_flickr_php('flickr.photos.search', array ('user_id' => $this->options['nsid'], 'sort' => 'date-taken-desc', 'per_page' => $this->options['photos_per_page'], 'page' => $page));
				} else {
					$resp = $this->_call_flickr_php('flickr.photos.search', array ('user_id' => $this->options['nsid'], 'sort' => 'date-taken-desc', 'per_page' => $this->options['number_recent'], 'page' => '1'));
				}
			} else {
				$resp = $this->_call_flickr_php('flickr.photos.search', array ('user_id' => $this->options['nsid'], 'tags' => $tags, 'tag_mode' => 'all', 'per_page' => $this->options['photos_per_page'], 'page' => $page));
			}

			if (!isset ($resp)) {
				return;
			}

			$countResult = sizeof($resp['photos']['photo']);

			if ($tags == '') {
				$urlPrefix = 'show/recent/';
				$this->template->set('recent_label', fa__('Recent Photos'));
			} else {
				$urlPrefix = "tags/$tags/";

				$this->template->set('tag_url', $this->create_url('show/tags'));
				$this->template->set('tags_label', fa__('Tags'));
				$this->template->set('tags', $tags);
			}

			$photo_title_array = array ();
			$thumbnails_list = array ();
			$count = 0;

			for ($i = 0; $i < $countResult; $i ++) {

				if (($this->options['photos_per_page'] == 0) || $tags != '' || $this->options['number_recent'] == -1 || (($count >= ($page -1) * $this->options['photos_per_page']) && ($count < ($page * $this->options['photos_per_page'])))) {

					$photo_id = $resp['photos']['photo'][$i]['id'];
					$photo_title = $resp['photos']['photo'][$i]['title'];
					$server = $resp['photos']['photo'][$i]['server'];
					$farm = $resp['photos']['photo'][$i]['farm'];
					$secret = $resp['photos']['photo'][$i]['secret'];

					$photo_link = $this->get_link_title($photo_title, $photo_id, $photo_title_array);
					$thumbnail = self::create_flickr_image_url($farm, $server, $photo_id, $secret, $this->options['tsize']); 
					
					$data['tsize'] = $this->options['tsize'];
					$data['url'] = $this->create_url($urlPrefix."$page/photo/$photo_link");
					$data['title'] = $photo_title;
					$data['thumbnail'] = $thumbnail;

					$thumbnails_list[] = $data;

				}
				$count ++;
			}

			if ($this->options['photos_per_page'] != 0) {

				$this->logger->info("tags($tags)");
				$this->logger->info("number_recent->".$this->options['number_recent']);

				if ($tags == '' && $this->options['number_recent'] != -1) {

					$this->logger->info("here");

					$pages = ceil($this->options['number_recent'] / $this->options['photos_per_page']);
				} else {
					$pages = $resp['photos']['pages'];
				}

				$this->logger->info("pages($pages)");

				if ($pages > 1) {
					$this->template->set('paging_top', $this->build_paging($page, $pages, $urlPrefix, 'top'));
					$this->template->set('paging_bottom', $this->build_paging($page, $pages, $urlPrefix, 'bottom'));
				}
			}

			$this->template->set('thumbnails', $thumbnails_list);
			$this->template->set('url', $this->create_url());
			$this->template->set('photos_label', fa__('Photos'));

			$this->template->set('dropshadows', $this->options['display_dropshadows']);

			$this->template->set('remote_url', $this->options['url_wp-flickr-gallery_dir']."/wp-flickr-gallery-remote.php");
			$this->template->set('url_root', $this->options['url_root']);

			$output = $this->template->fetch();

			$this->_set_cached_data("show_tags_thumbnails-$tags-$page", $output);

		}
		return $output;
	}

	/* Shows the selected photo */
	function show_photo($album, $tags, $photo, $page = 1) {

		$this->logger->info("show_photo($album, $tags, $photo, $page)");
		
		if (!is_numeric($page)) {
			$page = 1;
		}
		if ($album == '') {
			$album = null;
		}

		$in_photo = $photo;
		$in_album = $album;

		$output = $this->_get_cached_data("show_photo-$in_album-$tags-$in_photo-$page");

		if (true || !$output) {
			$this->template->reset('photo');

			$this->template->set('page_title', $this->get_page_title());

			$this->template->set('album', $album);
			$this->template->set('in_tags', $tags);

			$output = '';
			$photo_title_array = array();

			if (!is_null($album) && $album != '') {
				$url_prefix = "album/$album";
				list($album_id, $album_title) = $this->_get_album_info($album);
				$resp = $this->_call_flickr_php('flickr.photosets.getPhotos', array ('photoset_id' => $album_id));
			} else {
				if (!$tags) {
					$url_prefix = 'show/recent';
					$album_title = fa__('Recent Photos');
					$resp = $this->_call_flickr_php('flickr.photos.search', array ('user_id' => $this->options['nsid'], 'sort' => 'date-taken-desc', 'per_page' => $this->options['photos_per_page'], 'page' => $page));
				} else {
					$url_prefix = "tags/$tags";
					$album_title = fa__('Tags');
					$album_title = $tags;
					$resp = $this->_call_flickr_php('flickr.photos.search', array ('user_id' => $this->options['nsid'], 'tags' => $tags, 'tag_mode' => 'all', 'per_page' => $this->options['photos_per_page'], 'page' => $page));
				}
			}
			if (!$resp) {
				return;
			}

			$photo_id = $this->_get_photo_id($resp, $photo);
			if (!is_null($album)) {
				$result = $resp['photoset']['photo'];
			} else {
				$total_pages = $resp['photos']['pages'];
				$total_photos = $resp['photos']['total'];
				$result = $resp['photos']['photo'];
			}
			
			/* Get the current photo and it's relatives */
			$photo_location = 0;
			foreach ($result as $i => $photo_result) {
				if ($photo_result['id'] == $photo_id) {
					$photo_location = $i;
					break;
				}
			}
			$previous_photo_found = $next_photo_found = false;
			$photo_title = $this->get_link_title($result[$photo_location]['title'], $photo_id, $photo_title_array);
			if ($photo_location > 0) {
				$previous_photo_found = true;
				
				if ($photo_location - 1 >= $this->options['photos_per_page'] * ($page - 1)
					&& $photo_location - 1 < $this->options['photos_per_page'] * $page) {
					$previous_photo_page = $page;
				} else {
					$previous_photo_page = $page - 1;
				}
				$previous_photo_id = $result[$photo_location - 1]['id'];
				$previous_photo_title = $result[$photo_location -1]['title'];
				$previous_photo_farm = $result[$photo_location - 1]['farm'];
				$previous_photo_secret = $result[$photo_location - 1]['secret'];
				$previous_photo_server = $result[$photo_location - 1]['server'];
			}
			if ($photo_location < count($result) - 1) {
				$next_photo_found = true;
				if ($photo_location + 1 >= $this->options['photos_per_page'] * ($page - 1)
					&& $photo_location + 1 < $this->options['photos_per_page'] * $page) {
					$next_photo_page = $page;
				} else {
					$next_photo_page = $page + 1;
				}
				$next_photo_id = $result[$photo_location + 1]['id'];
				$next_photo_title = $result[$photo_location + 1]['title'];
				$next_photo_farm = $result[$photo_location + 1]['farm'];
				$next_photo_secret = $result[$photo_location + 1]['secret'];
				$next_photo_server = $result[$photo_location + 1]['server'];
			}
			
			/* Find the previous photo */
			if (!$previous_photo_found && is_null($album)) {
				if ($tags) {
					$url_prefix = "tags/$tags";
					$resp = $this->_call_flickr_php('flickr.photos.search', array ('user_id' => $this->options['nsid'], 'tags' => $tags, 'tag_mode' => 'all', 'per_page' => $this->options['photos_per_page'], 'page' => $page - 1));
				} else {
					$url_prefix = 'show/recent';
					$resp = $this->_call_flickr_php('flickr.photos.search', array ('user_id' => $this->options['nsid'], 'sort' => 'date-taken-desc', 'per_page' => $this->options['photos_per_page'], 'page' => $page - 1));
				}
				if ($resp) {
					$temp_result = $resp['photos']['photo'];
					$previous_photo_location = count($temp_result) - 1;
					
					$previous_photo_found = true;
					$previous_photo_page = $page - 1;
					$previous_photo_id = $result[$previous_photo_location]['id'];
					$previous_photo_title = $result[$previous_photo_location]['title'];
					$previous_photo_farm = $result[$previous_photo_location]['farm'];
					$previous_photo_secret = $result[$previous_photo_location]['secret'];
					$previous_photo_server = $result[$previous_photo_location]['server'];
					$previous_photo_title = $this->get_link_title($previous_photo_title, $previous_photo_id, $photo_title_array);
				}
			}
			/* Find the next photo */
			if (!$next_photo_found && is_null($album)) {
				if ($tags) {
					$url_prefix = "tags/$tags";
					$resp = $this->_call_flickr_php('flickr.photos.search', array ('user_id' => $this->options['nsid'], 'tags' => $tags, 'tag_mode' => 'all', 'per_page' => $this->options['photos_per_page'], 'page' => $page + 1));
				} else {
					$url_prefix = 'show/recent';
					$resp = $this->_call_flickr_php('flickr.photos.search', array ('user_id' => $this->options['nsid'], 'sort' => 'date-taken-desc', 'per_page' => $this->options['photos_per_page'], 'page' => $page + 1));
				}
				if ($resp) {
					$temp_result = $resp['photos']['photo'];
					$next_photo_location = 0;
					
					$next_photo_found = true;
					$next_photo_page = $page + 1;
					$next_photo_id = $result[$next_photo_location]['id'];
					$next_photo_title = $result[$next_photo_location]['title'];
					$next_photo_farm = $result[$next_photo_location]['farm'];
					$next_photo_secret = $result[$next_photo_location]['secret'];
					$next_photo_server = $result[$next_photo_location]['server'];
					$next_photo_title = $this->get_link_title($next_photo_title, $next_photo_id, $photo_title_array);
				}
			}

			if ($this->options['friendly_urls'] == 'title') {
				$nav_next = sanitize_title($next_photo_title);
				$nav_prev = sanitize_title($previous_photo_title);
			} else {
				$nav_next = $next_photo_id;
				$nav_prev = $previous_photo_id;
			}

			// Display Photo
			$resp = $this->_call_flickr_php('flickr.photos.getInfo', array ('photo_id' => $photo_id));
			if (!$resp) {
				return;
			}

			$server = $resp['photo']['server'];
			$secret = $resp['photo']['secret'];
			$farm = $resp['photo']['farm'];
			$title = self::unhtmlentities($resp['photo']['title']['_content']);
			$date_taken = $resp['photo']['dates']['taken'];
			$description = self::unhtmlentities(nl2br($resp['photo']['description']['_content']));
			$comments = $resp['photo']['comments']['_content'];
			
			$image_src = self::create_flickr_image_url($farm, $server, $photo_id, $secret, 'z');
			$this->template->set('image', $image_src);

			//Get Next Photo Size Data
			$next_source = self::create_flickr_image_url($next_photo_farm, $next_photo_server, $next_photo_photo_id, $next_photo_secret, 'z');
			$this->template->set('next_image', $next_source);

			//Get Current Photo Size Data
			$resp_sizes = $this->_call_flickr_php('flickr.photos.getSizes', array ('photo_id' => $photo_id));
			if (!$resp_sizes) {
				return;
			}

			$sizes_list = array();
			$display_width = 0;
			$display_height = 0;
			foreach ($resp_sizes['sizes']['size'] as $i => $size) {
				$width = $size['width'];
				$height = $size['height'];
				$source = $size['source'];
				$data = array(
					'image' => $source,
					'title' => $size['label']." ({$width} x {$height})",
				);
				switch ($size['label']) {
					case 'Square':
						$data['display'] = fa__('SQ');
						$data['value'] = 'sq';
						break;
					case 'Thumbnail':
						$data['display'] = fa__('T');
						$data['value'] = 't';
						break;
					case 'Small':
						$data['display'] = fa__('S');
						$data['value'] = 's';
						break;
					case 'Medium':
						$data['display'] = fa__('M');
						$data['value'] = 'm';
						$display_width = $width;
						$display_height = $height;
						break;
					case 'Medium 640':
						$data['display'] = fa__('M2');
						$data['value'] = 'z';
						break;
					case 'Large':
						$data['display'] = fa__('L');
						$data['value'] = 'l';
						break;
					case 'Original':
						$data['display'] = fa__('O');
						$data['value'] = 'o';
						break;
				}
				$sizes_list[] = $data;
			}
			$this->template->set('sizes', $sizes_list);

			$this->template->set('home_url', $this->create_url());
			$this->template->set('home_label', fa__('Photos'));
			$this->template->set('title_url', $this->create_url("$url_prefix/page/{$page}/"));
			$this->template->set('title_label', $album_title);
			$this->template->set('title', $title);

			//Date Taken
			$this->template->set('date_taken', strtr(fa__('Taken on: #date_taken#'), array ("#date_taken#" => $date_taken)));

			//Tags
			$result = $resp['photo']['tags']['tag'];
			if (count($result) > 0) {
				$this->template->set('tags_url', $this->create_url('show/tags'));
				$this->template->set('tags_label', fa__('Tags'));
				$tags_list = array ();
				foreach ($result as $i => $content) {
					$value = $content['raw'];
					$data['url'] = $this->create_url('tags/'.$content['_content'].'/');
					$data['tag'] = $value;
					$tags_list[] = $data;
				}
				$this->template->set('tags', $tags_list);
			}

			//Photo
			if ($next_photo_found) {
				$this->template->set('photo_url', $this->create_url("$url_prefix/page/{$next_photo_page}/photo/$nav_next/"));
				$this->template->set('photo_title_label', fa__('Click to view next image'));

			} else {
				$this->template->set('photo_url', $this->create_url("$url_prefix/page/$page/"));
				$this->template->set('photo_title_label', fa__('Click to return to album'));
			}

			if ($this->options['max_photo_width'] != '0' && $this->options['max_photo_width'] < $orig_w_m) {
				$this->template->set('photo_width', $this->options['max_photo_width']);
			} else {
				$this->template->set('photo_width', $orig_w_m);
			}

			// Navigation
			if ($previous_photo_found) {
				$this->template->set('prev_button', $this->_create_button($this->create_url("$url_prefix/".($previous_photo_page == 1 ? "" : "page/{$previous_photo_page}/")."photo/$nav_prev/"), "&laquo; ".fa__('Previous'), fa__('Previous Photo'), true));

				$this->template->set('prev_page', $page - 1);
				$this->template->set('prev_id', $nav_prev);
			}
			if ($next_photo_found) {
				$this->template->set('next_button', $this->_create_button($this->create_url("$url_prefix/".($next_photo_page == 1 ? "" : "page/{$next_photo_page}/")."photo/$nav_next/"), "".fa__('Next')." &raquo;", fa__('Next Photo'), true));

				$this->template->set('next_page', $page + 1);
				$this->template->set('next_id', $nav_next);
			}
			$this->template->set('return_button', $this->_create_button($this->create_url("$url_prefix/".($page == 1 ? "" : "page/$page/")), fa__('Album Index'), fa__('Return to album index'), true));

			//Description
			$this->template->set('description_orig', $description);
			if (trim($description) == '') {
				$this->template->set('no_description_text', fa__('click here to add a description'));
				$this->template->set('description', '&nbsp;&nbsp;');
			} else {
				$this->template->set('description', $description);
			}

			//Meta Information
			//Sizes
			if ($this->options['display_sizes'] == 'true') {
				$this->template->set('sizes_label', fa__('Available Sizes'));
			}

			// Flickr / Comments
			if ($comments > 0) {

				$resp_comments = $this->_call_flickr_php('flickr.photos.comments.getList', array ('photo_id' => $photo_id));
				if ($resp_comments) {
					$result = $resp_comments['comments']['comment'];
					$notes_countResult = count($result);

					$this->logger->info($notes_countResult);

					$comments_list = array ();
					for ($i = 0; $i < $notes_countResult; $i ++) {
						$value = nl2br($result[$i]['_content']);
						$author = $result[$i]['author'];

						//flickr.people.getInfo
						$resp_info = $this->_call_flickr_php('flickr.people.getInfo', array ('user_id' => $author));
						if (isset ($resp_info)) {
							$data['author_name'] = $resp_info['person']['username']['_content'];
							$data['author_url'] = $resp_info['person']['photosurl']['_content'];
							$data['author_location'] = $resp_info['person']['location']['_content'];
						}

						$data['text'] = self::unhtmlentities($value);

						$comments_list[] = $data;
					}
					$this->template->set('comments', $comments_list);

				}

			}

			$this->template->set('nsid', $this->options['nsid']);
			$this->template->set('photo', $photo_id);

			$this->template->set('flickr_label', fa__('See this photo on Flickr'));

			$remote_url = $this->options['url_wp-flickr-gallery_dir']."/wp-flickr-gallery-remote.php";
			$this->template->set('remote_url', $remote_url);

			$this->template->set('url_root', $this->options['url_root']);

			$this->template->set('photo_id', $photo_id);

			//Exif
			if (strtolower($this->options['display_exif']) == 'true') {
				$this->template->set('exif_data', "{$photo_id}','{$secret}','{$remote_url}");
				$this->template->set('exif_label', fa__('Show Exif Data'));
			}

			$this->template->set('can_edit', $this->can_edit);

			//Post Helper
			$post_value = '[fa:p:';
			if ($tags != '') {
				$post_value .= "t=$tags,";
			} else
				if ($album != '') {
					$post_value .= "a=$album,";
				}
			if ($page != '' and $page != 1) {
				$post_value .= "p=$page,";
			}
			$post_value .= "id=$photo_id,j=l,s=s,l=p]";
			$this->template->set('post_value', $post_value);

			$this->template->set('css_type_photo', $this->options['display_dropshadows']);

			$output = $this->template->fetch();

			$this->_set_cached_data("show_photo-$in_album-$tags-$in_photo-$page", $output);

		}

		return $output;
	}

	/* Shows all the tag cloud */
	function show_tags() {

		$this->logger->info("show_tags()");

		$output = $this->_get_cached_data('show_tags');

		if (!isset ($output)) {
			
			$this->template->reset('show_tags');
			
			$this->template->set('page_title', $this->get_page_title());

			$resp = $this->_call_flickr_php('flickr.tags.getListUserPopular', array ('count' => '500', user_id => $this->options['nsid']));

			if (!isset ($resp)) {
				return;
			}

			$this->template->reset('tags');

			$this->template->set('home_url', $this->create_url());
			$this->template->set('home_label', fa__(Photos));
			$this->template->set('tags_label', fa__('Tags'));

			$result = $resp['who']['tags']['tag'];
			$countResult = sizeof($result);

			$tagcount = 0;
			$maxcount = 0;
			for ($i = 0; $i < $countResult; $i ++) {
				$tagcount = $result[$i]['count'];
				if ($tagcount > $maxcount) {
					$maxcount = $tagcount;
				}
			}

			$tags_list = array ();

			for ($i = 0; $i < $countResult; $i ++) {

				$tagcount = $result[$i]['count'];
				$tag = $result[$i]['_content'];

				if ($tagcount <= ($maxcount * .1)) {
					$tagclass = 'wp-flickr-gallery-tag1';
				}
				elseif ($tagcount <= ($maxcount * .2)) {
					$tagclass = 'wp-flickr-gallery-tag2';
				}
				elseif ($tagcount <= ($maxcount * .3)) {
					$tagclass = 'wp-flickr-gallery-tag3';
				}
				elseif ($tagcount <= ($maxcount * .5)) {
					$tagclass = 'wp-flickr-gallery-tag4';
				}
				elseif ($tagcount <= ($maxcount * .7)) {
					$tagclass = 'wp-flickr-gallery-tag5';
				}
				elseif ($tagcount <= ($maxcount * .8)) {
					$tagclass = 'wp-flickr-gallery-tag6';
				} else {
					$tagclass = 'wp-flickr-gallery-tag7';
				}

				$data['url'] = $this->create_url("tags/$tag");
				$data['class'] = $tagclass;
				$data['title'] = $tagcount." ".fa__('photos');
				$data['name'] = $tag;

				$tags_list[] = $data;
			}

			$this->template->set('tags', $tags_list);

			$remote_url = $this->options['url_wp-flickr-gallery_dir']."/wp-flickr-gallery-remote.php";
			$this->template->set('remote_url', $remote_url);

			$output = $this->template->fetch();

			$this->_set_cached_data('show_tags', $output);

		}
		return $output;
	}

	/* Return EXIF data for the selected photo */
	function show_exif($photo_id, $secret) {

		$this->logger->info("show_exif($photo_id, $secret)");

		$output = $this->_get_cached_data("get_exif-$photo_id-$secret");
		if (!isset ($output)) {

			$this->template->reset('exif');

			$exif_resp = $this->_call_flickr_php('flickr.photos.getExif', array ('photo_id' => $photo_id, 'secret' => $secret), WPFLICKRGALLERY_CACHE_EXPIRE_LONG);
			if (!isset ($exif_resp)) {
				return;
			}

			$result = $exif_resp['photo']['exif'];
			$countResult = sizeof($result);

			$exif_list = array ();

			for ($i = 0; $i < $countResult; $i ++) {
				$label = $result[$i]['label'];

				if ($i % 2 == 0) {
					$data['class'] = 'even';
				} else {
					$data['class'] = 'odd';
				}

				$data['label'] = $label;

				$r1 = $result[$i]['clean'];
				if (count($r1) > 0) {
					$data['data'] = htmlentities($result[$i]['clean']['_content']);
				} else {
					$data['data'] = htmlentities($result[$i]['raw']['_content']);
				}

				$exif_list[] = $data;
			}

			$this->template->set('exif', $exif_list);

			$output = $this->template->fetch();

			$this->_set_cached_data("get_exif-$photo_id-$secret", $output);
		}

		return $output;
	}

	function update_metadata($photo_id, $title, $description) {

		$this->logger->info("update_metadata($photo_id, $title, $description)");

		if ($this->_can_edit()) {

			$resp = $this->_call_flickr_php('flickr.photos.setMeta', array ('photo_id' => $photo_id, 'title' => $title, 'description' => $description), WPFLICKRGALLERY_DO_NOT_CACHE, true);
			if (!isset ($resp)) {
				return;
			}

			$this->_clear_cached_data();

			$resp = $this->_call_flickr_php('flickr.photos.getInfo', array ('photo_id' => $photo_id));

			if (!isset ($resp)) {
				return;
			}

			$data['title'] = $resp['photo']['title'];
			$data['description'] = nl2br($resp['photo']['description']['_content']);

		}

		return $data;
	}

	/* Function to show recent photos - commonly used in the sidebar */
	function show_recent($num = 5, $style = 0, $size = '') {

		$this->logger->info("show_recent($num, $style, $size)");

		if ($size == '') {
			$size = $this->options['tsize'];
		}

		$output = $this->_get_cached_data("show_recent-$num-$style-$size");

		if (!isset ($output)) {

			$output = '';

			$resp = $this->_call_flickr_php('flickr.photos.search', array ('user_id' => $this->options['nsid'], 'per_page' => $num, 'sort' => 'date-taken-desc'));
			if (!isset ($resp)) {
				return;
			}

			if ($style == 0) {
				$output .= "<ul class='wp-flickr-gallery-recent'>\n";
			} else {
				$output .= "<div class='wp-flickr-gallery-recent'>\n";
			}

			$result = $resp['photos']['photo'];
			$countResult = sizeof($result);

			for ($i = 0; $i < $countResult; $i ++) {
				$server = $result[$i]['server'];
				$farm = $result[$i]['farm'];
				$secret = $result[$i]['secret'];
				$photo_id = $result[$i]['id'];
				$photo_title = $result[$i]['title'];

				$photo_link = $photo_id;

				if ($style == 0) {
					$output .= "<li>\n";
				} else {
					$output .= "<div class='wp-flickr-gallery-thumbnail".$this->options['display_dropshadows']."'>";
				}

				$thumbnail = self::create_flickr_image_url($farm, $server, $photo_id, $secret, $size); 
								
				$output .= "<a href='".$this->create_url("show/recent/photo/$photo_link/")."'>";

				$output .= "<img src='$thumbnail' alt=\"".htmlentities($photo_title)."\" title=\"".htmlentities($photo_title)."\" />";
				$output .= "</a>\n";

				if ($style == 0) {
					$output .= "</li>\n";
				} else {
					$output .= "</div>\n";
				}
			}
			if ($style == 0) {
				$output .= "</ul>\n";
			} else {
				$output .= "</div>\n";
			}

			$this->_set_cached_data("show_recent-$num-$style-$size", $output);
		}
		return $output;
	}

	/* Function to show a random selection of photos - commonly used in the sidebar */
	function show_random($num = 5, $tags = '', $style = 0, $size = '') {

		$this->logger->info("show_random($num, $tags, $style, $size)");

		if ($size == '') {
			$size = $this->options['tsize'];
		}

		$output = '';
		$page = 1;

		if ($tags == '') {
			$resp = $this->_call_flickr_php('flickr.photos.search', array ('user_id' => $this->options['nsid'], 'sort' => 'date-taken-desc', 'per_page' => $this->options['photos_per_page']));
		} else {
			$resp = $this->_call_flickr_php('flickr.photos.search', array ('user_id' => $this->options['nsid'], 'tags' => $tags, 'tag_mode' => 'all', 'per_page' => $this->options['photos_per_page'], 'page' => $page));
		}

		if (!isset ($resp)) {
			return;
		}

		$totalPages = $resp['photos']['pages'];
		$total = $resp['photos']['total'];

		$no_dups = ($total - $num >= 0);

		if ($style == 0) {
			$output .= "<ul class='wp-flickr-gallery-random'>\n";
		} else {
			$output .= "<div class='wp-flickr-gallery-random'>\n";
		}

		$rand_array = array ();

		for ($j = 0; $j < $num; $j ++) {
			$page = mt_rand(1, $totalPages);
			if ($tags == '') {
				$resp = $this->_call_flickr_php('flickr.photos.search', array ('user_id' => $this->options['nsid'], 'sort' => 'date-taken-desc', 'per_page' => $this->options['photos_per_page'], 'page' => $page));
			} else {
				$resp = $this->_call_flickr_php('flickr.photos.search', array ('user_id' => $this->options['nsid'], 'tags' => $tags, 'tag_mode' => 'all', 'per_page' => $this->options['photos_per_page'], 'page' => $page));
			}

			if (!isset ($resp)) {
				return;
			}
			$result = $resp['photos']['photo'];
			$countResult = count($result);

			$randPhoto = mt_rand(0, $countResult -1);

			$photo_id = $result[$randPhoto]['id'];

			$dup = false;
			if ($no_dups) {
				if (in_array($photo_id, $rand_array)) {
					$dup = true;
					$j --;
				} else {
					$rand_array[] = $photo_id;
				}
			}

			$this->logger->debug("dup->". ($dup ? 't' : 'f'));

			if (!$dup) {

				$server = $result[$randPhoto]['server'];
				$farm = $result[$randPhoto]['farm'];
				$secret = $result[$randPhoto]['secret'];
				$photo_title = $result[$randPhoto]['title'];

				$photo_link = $photo_id;

				if ($style == 0) {
					$output .= "<li>\n";
				} else {
					$output .= "<div class='wp-flickr-gallery-thumbnail".$this->options['display_dropshadows']."'>";
				}

				$thumbnail = self::create_flickr_image_url($farm, $server, $photo_id, $secret, $size);  
				
				if ($tags != '') {
					$output .= "<a href='".$this->create_url("tags/$tags/page/$page/photo/$photo_link/")."'>";
				} else {
					$output .= "<a href='".$this->create_url("show/recent/page/$page/photo/$photo_link/")."'>";
				}

				$output .= "<img src='$thumbnail' alt=\"".htmlentities($photo_title)."\" title=\"".htmlentities($photo_title)."\" class='wp-flickr-gallery-recent-thumbnail' />";
				$output .= "</a>\n";
				if ($style == 0) {
					$output .= "</li>\n";
				} else {
					$output .= "</div>\n";
				}
			}

		}
		if ($style == 0) {
			$output .= "</ul>\n";
		} else {
			$output .= "</div>\n";
		}

		return $output;
	}

	function show_album_tn($album, $size = 'm') {

		$this->logger->info("show_album_tn($album)");

		$output = $this->_get_cached_data("show_album_tn-$album");

		if (!isset ($output)) {

			$output = '';

			$resp = $this->_call_flickr_php('flickr.photosets.getList', array ("user_id" => $this->options['nsid']));
			if (!isset ($resp)) {
				return;
			}

			$photosets = $resp['photosets']['photoset'];
			$count = sizeof($photosets);

			for ($j = 0; $j < $count; $j ++) {
				if ($photosets[$j]['id'] == $album) {
					$result = $photosets[$j];
					break;
				}
			}

			//$result = $xpath->match("/rsp/photosets/photoset[@id=$album]");
			//$result = $result[0];

			$photos = $result['photos'];

			if ($photos > 0) {
				$id = $result['id'];
				$server = $result['server'];
				$farm = $result['farm'];
				$primary = $result['primary'];
				$secret = $result['secret'];
				$title = self::unhtmlentities($result['title']['_content']);
				$thumbnail = self::create_flickr_image_url($farm, $server, $primary, $secret, $size); 
				
				$url = $this->create_url("album/$album/");

				$output .= '	<div class=\'wp-flickr-gallery-thumbnail'.$this->options['display_dropshadows'].'\'>';
				$output .= "		<a href='$url' title='$title'>";
				$output .= '			<img src="'.$thumbnail.'" alt="" />';
				$output .= '		</a>';
				$output .= '	</div>';
			}

			$this->_set_cached_data("show_album_tn-$album", $output);

		}

		return $output;
	}

	function show_single_photo($album, $tags, $photo, $page, $size, $linkto) {

		$this->logger->info("show_single_photo($album, $tags, $photo, $page, $size, $linkto)");

		$output = $this->_get_cached_data("show_single_photo-$album-$tags-$photo-$page-$size-$linkto");

		if (true || !isset ($output)) {
			$output = '';

			if ($size == 'sq') { $size = 's'; }

			// Get the photo information from Flickr
			$resp = $this->_call_flickr_php('flickr.photos.getInfo', array ('photo_id' => $photo));
			if (!isset($resp)) {
				return;
			}

			$id = $resp['photo']['id'];
			$server = $resp['photo']['server'];
			$farm = $resp['photo']['farm'];
			$secret = $resp['photo']['secret'];
			$title = self::unhtmlentities($resp['photo']['title']['_content']);
			$description = self::unhtmlentities($resp['photo']['description']['_content']);
			$thumbnail = self::create_flickr_image_url($farm, $server, $id, $secret, $size); 
			
			if ($tags != '') {
				$url_prefix = "tags/$tags";
			} else
				if ($album != '') {
					$url_prefix = "album/$album";
				} else {
					$url_prefix = 'show/recent';
				}

			if (isset ($page)) {
				$url_prefix .= '/page/'.$page;
			}

			if (!($linkto == 'i' || $linkto == 'index')) {
				$url_prefix .= '/photo/'.$photo;
			}
			
			$align = 'aligncenter';
			$pixel_width = self::convert_flickr_size($size);
			$pixel_width_frame = $pixel_width + 10;

			$url = $this->create_url("$url_prefix");
			$output .= "<div class='wp-caption {$align}' style='width: {$pixel_width_frame}px'>";
			$output .= "<a href='{$url}'>";
			$output .= "<img class='size-full wp-image-340' title='{$title}' src='{$thumbnail}' alt='' width='{$pixel_width}' />";
			$output .= "</a>";
			$output .= "<p class='wp-caption-text'>".($description ? $description : $title)."</p>";
			$output .= "</div>";

			$this->_set_cached_data("show_single_photo-{$album}-{$tags}-{$photo}-{$page}-{$size}-{$linkto}", $output);

		}

		return $output;
	}

	/* Creates the URLs used in Falbum */
	function create_url($parms = '') {
		if ($parms != '') {
			$element = explode('/', $parms);
			for ($x = 1; $x < count($element); $x ++) {
				$element[$x] = urlencode($element[$x]);
			}
			if (strtolower($this->options['friendly_urls']) == 'false') {
				$parms = '?'.$element[0].'='.$element[1].'&'.$element[2].'='.$element[3].'&'.$element[4].'='.$element[5].'&'.$element[6].'='.$element[7];
				$parms = str_replace('&=', '', $parms);
			} else {
				$parms = implode('/', $element);
			}

			if ($this->options['photos_per_page'] == 0) {
				$parms = preg_replace("`/page/[0-9]+`", "", $parms);
			}

		}
		return htmlspecialchars($this->options['url_root']."$parms");
	}

	function get_page_title($sep = '&raquo;', $prefix = true, $ltr = true) {

		$this->logger->info("get_page_title($sep)");

		$_GET = array_merge($_POST,$_GET);

		$album = $_GET['album'];
		$photo = $_GET['photo'];
		$page = $_GET['page'];
		$tags = $_GET['tags'];
		$show = $_GET['show'];

		$this->logger->info("get_page_title_v($album $photo $page $tags $show)");
		
		if (!is_null($album)) {
			list ($album_id, $album_title) = $this->_get_album_info($album);
			if (!is_null($photo)) {
				$resp = $this->_call_flickr_php('flickr.photosets.getPhotos', 
					array ('photoset_id' => $album_id));
			}
		} else {
			if ($show == 'tags') {
				$album_title = fa__('Tags');
			} else
				if ($show == 'recent') {
					$album_title = fa__('Recent Photos');
					if (!is_null($photo)) {
						$resp = $this->_call_flickr_php('flickr.photos.search', 
							array ('user_id' => $this->options['nsid'], 'sort' => 'date-taken-desc', 'per_page' => $this->options['photos_per_page'], 'page' => $page));
					}
				} else {
					//$album_title = fa__('Tags');
					$album_title = $tags;
					if (!is_null($photo)) {
						$resp = $this->_call_flickr_php('flickr.photos.search', 
							array ('user_id' => $this->options['nsid'], 'tags' => $tags, 'tag_mode' => 'all', 'per_page' => $this->options['photos_per_page'], 'page' => $page));
					}
				}
		}

		if (!is_null($photo)) {
			if (!isset ($resp)) {
				return;
			}
			$photo = $this->_get_photo_id($resp, $photo);
			//$this->logger->debug("photo-$photo");
			//$photo_title = $xpath->getData("//photo[@id='$photo']/@title");

			//$photos = $resp['photos']['photo'];
			
			if (!is_null($album)) {
				$photos = $resp['photoset']['photo'];
			} else {
				$photos = $resp['photos']['photo'];
			}
			
			$count = sizeof($photos);
			for ($j = 0; $j < $count; $j ++) {
				if ($photos[$j]['id'] == $photo) {
					$photo_title = $photos[$j]['title'];
					break;
				}
			}
		}

		if ($prefix) {
			$title = fa__('Photos');
		}
		if (isset ($album_title)) {
			if ($ltr) {
				$title .= ($prefix ?'&nbsp;'.$sep.'&nbsp;' : '').$album_title;
			} else {
				$title = $album_title.($prefix ?'&nbsp;'.$sep.'&nbsp;' : '').$title;
			}
		}
		if (isset ($photo_title)) {
			if ($ltr) {
				$title .= (strlen($title) ?'&nbsp;'.$sep.'&nbsp;' : '').$photo_title;
			} else {
				$title = $photo_title.(strlen($title) ?'&nbsp;'.$sep.'&nbsp;' : '').$title;
			}
		}
		
		$this->title = $title;

		return $title;
	}
	
	function title_filter($value) {
		if (!isset($this->title)) {
			$this->get_page_title('|', false, false);
		}
		return str_replace('Page not found', $this->title, $value);
	}

	function get_options() {

		$wp_flickr_gallery_options = array ();

		include('wp-flickr-gallery-config.php');

		//echo '<pre>'.print_r($wp_flickr_gallery_options, true).'</pre>';

		return $wp_flickr_gallery_options;
	}

	/* Function that actually makes the flickr calls */
	function _call_flickr_php($method, $args = array (), $cache_option = WPFLICKRGALLERY_CACHE_EXPIRE_SHORT, $post = false) {

		$args = array_merge(array ('method' => $method, 'api_key' => WPFLICKRGALLERY_API_KEY, 'format'	=> 'php_serial'), $args);

		if ($this->_show_private() == 'true' || $post == true) {
			$args = array_merge($args, array ('auth_token' => $this->options['token']));
		}

		ksort($args);

		$auth_sig = '';
		foreach ($args as $key => $data) {
			$auth_sig .= $key.$data;
		}

		$api_sig = '';
		if ($this->_show_private() == 'true' || $post == true) {
			$api_sig = md5(WPFLICKRGALLERY_SECRET.$auth_sig);
		}

		$args = array_merge($args, array ('api_sig' => $api_sig));
		ksort($args);

		$url = 'http://www.flickr.com/services/rest/';
		if ($post) {
			$resp = $this->_fopen_url($url, $args, $cache_option, true);
		} else {
			$resp = $this->_get_cached_data($url.implode('-', $args), $cache_option);
			if (!$resp) {
				$resp = $this->_fopen_url($url, $args, $cache_option, false);

				// only cache successful calls to Flickr
				$pos = strrpos($resp, '"ok"');
				if ($pos !== false) {
					$this->_set_cached_data($url.implode('-', $args), $resp, $cache_option);
				}
			}
		}

		$resp_data = unserialize($resp);

		$this->logger->debug(print_r($resp_data, true));

		return $resp_data;
	}

	/* Function that opens the URLS - uses libcurl by default, else falls back to fsockopen */
	function _fopen_url($url, $args = array (), $cache_option = WPFLICKRGALLERY_CACHE_EXPIRE_SHORT, $post = false, $fsocket_timeout = 120) {

		$urlParts = parse_url($url);
		$host = $urlParts['host'];
		$port = (isset ($urlParts['port'])) ? $urlParts['port'] : 80;

		if (!extension_loaded('curl')) {
			/* Use fsockopen */
			$this->logger->debug('request - fsockopen<br />'.htmlentities($url));

			$errno = '';
			$errstr = '';

			if (!$fp = @ fsockopen($host, $port, $errno, $errstr, $fsocket_timeout)) {
				$data = fa__('fsockopen:Flickr server not responding');
			} else {

				$postdata = implode('&', array_map(create_function('$a', 'return $a[0] . \'=\' . urlencode($a[1]);'), $this->_flattenArray('', $args)));

				$this->logger->debug('request - fsockopen<br />'.htmlentities($url).'<br />'.$postdata);

				//if (isset ($postdata)) {
				$post = "POST $url HTTP/1.0\r\nHost: $host\r\nContent-type: application/x-www-form-urlencoded\r\nUser-Agent: Mozilla 4.0\r\nContent-length: ".strlen($postdata)."\r\nConnection: close\r\n\r\n$postdata";
				if (!fwrite($fp, $post)) {
					$data = fa__('fsockopen:Unable to send request');
				}
				//} else {
				//	if (!fputs($fp, "GET $url?$postdata	HTTP/1.0\r\nHost:$host\r\n\r\n")) {
				//		$data = fa__('fsockopen:Unable to send request');
				//	}
				//}

				$ndata = null;
				stream_set_timeout($fp, $fsocket_timeout);
				$status = socket_get_status($fp);
				while (!feof($fp) && !$status['timed_out']) {
					$ndata .= fgets($fp, 8192);
					$status = socket_get_status($fp);
				}
				fclose($fp);

				// strip headers
				$sData = split("\r\n\r\n", $ndata, 2);
				$ndata = $sData[1];
			}
		} else {
			/* Use curl */
			$this->logger->debug('request - curl<br />'.htmlentities($url));

			$ch = curl_init();
			curl_setopt($ch, CURLOPT_PORT, $port);
			curl_setopt($ch, CURLOPT_TIMEOUT, $fsocket_timeout);
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_FAILONERROR, 1);
			curl_setopt($ch, CURLOPT_HEADER, false);

			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $args);

			$ndata = curl_exec($ch);
			$error = curl_error($ch);
			curl_close($ch);
		}

		$this->logger->debug('response - <br />'.htmlentities($ndata));

		return $ndata;
	}


	/* Function that builds the album pages */
	function build_paging($page, $pages, $urlPrefix, $pos) {

		$output .= "<div class='wp-flickr-gallery-navigation wp-flickr-gallery-navigation-{$pos}'>";

		if ($page > 1 && $pages > 1) {
			$title = strtr(fa__('Go to previous page (#page#)'), array ("#page#" => $page -1));
			$output .= $this->_create_button($this->create_url($urlPrefix.($page > 2 ? 'page/'.($page - 1).'/' : '')), fa__('Previous'), $title, true);
		}

		for ($i = 1; $i <= $pages; $i ++) {
			// Display 1 ... 14 15 16 17 18 ... 29 when there are too many pages
			if ($pages > 10) {
				$min = $page - 3;
				$max = $page + 3;

				if ($i <= $min) {
					if ($i == 2)
						$output .= "<span class='pagedots'&hellip;</span>";
					if ($i != 1)
						continue;
				}
				if ($i >= $max) {
					if ($i == $pages -1)
						$output .= "<span class='pagedots'>&hellip;</span>";
					if ($i != $pages)
						continue;
				}
			}
			$page_url = $urlPrefix . ($i > 1 ? 'page/'.$i.'/' : '');
			$output .= $this->_create_button($this->create_url($page_url), $i, "Page {$i}", false, ($i == $page));
		}
		if ($page < $pages) {
			$title = strtr(fa__('Go to next page (#page#)'), array ("#page#" => $page +1));
			$output .= $this->_create_button($this->create_url($urlPrefix. 'page/'.($page + 1)).'/', fa__('Next'), $title, true);
		}
		$output .= "</div>\n\n";

		return $output;
	}

	/* Build pretty navigation buttons */
	function _create_button($href, $text, $title, $prevOrNext = false, $current = false) {
		$class = 'wp-flickr-gallery-navigation-button'.($prevOrNext ? '-prevornext' : '').($current ? ' wp-flickr-gallery-navigation-current-page' : '');
		$output = '';

		if (!empty ($href))
			$output .= "<a class='$class' href='$href' title='$title'>$text</a>";
		else
			$output .= "<span class='wp-flickr-gallery-navigation-button-disabled'>$text</span>";
		return $output;
	}

	protected function get_link_title($title, $id, & $title_array) {

		if ($this->options['friendly_urls'] == 'title') {

			$s_title = sanitize_title($title);

			if (preg_match("/^[A-Za-z0-9-_]+$/", $s_title)) {
				if (!in_array($s_title, $title_array)) {
					$title_array[$id] = $s_title;
					$link_title = $s_title;
				} else {
					$dup_count = 1;
					while (in_array($s_title.'-'.$dup_count, $title_array)) {
						$dup_count ++;
					}
					$link_title = $s_title.'-'.$dup_count;
					$title_array[$id] = $link_title;
				}
			} else {
				$link_title = $id;
			}

		} else {
			$link_title = $id;
		}
		return $link_title;

	}

	/* et photo id from title if using friendly URLs */
	function _get_photo_id(& $resp, $photo) {

		if ($this->options['friendly_urls'] == 'title') {

			if ($resp['photos']) {
				$result = $resp['photos']['photo'];
			} else {
				$result = $resp['photoset']['photo'];
			}

			$photo_title_array = array ();
			for ($i = 0; $i < count($result); $i ++) {
				$photo_title = sanitize_title($result[$i]['title']);
				if (preg_match("/^[A-Za-z0-9-_]+$/", $photo_title)) {
					$photo_id = $result[$i]['id'];
					if (!in_array($photo_title, $photo_title_array)) {
						$photo_title_array[$photo_id] = $photo_title;
					} else {
						$dup_count = 1;
						while (in_array($photo_title.'-'.$dup_count, $photo_title_array)) {
							$dup_count ++;
						}
						$photo_title = $photo_title.'-'.$dup_count;
						$photo_title_array[$photo_id] = $photo_title;
					}
				}
			}
			if (in_array($photo, $photo_title_array)) {
				$photo = array_search($photo, $photo_title_array);
			}
		}

		return $photo;
	}

	/* Get album ID from the album title */
	function _get_album_info($album) {

		$resp = $this->_call_flickr_php('flickr.photosets.getList', array ('user_id' => $this->options['nsid']));
		if (!isset ($resp)) {
			return;
		}

		if ($this->options['friendly_urls'] == 'title') {

			$album_id_array = array ();
			$photosets = $resp['photosets']['photoset'];
			for ($i = 0; $i < count($photosets); $i ++) {

				$album_title = $photosets[$i]['title']['_content'];

				$album_title = sanitize_title($album_title);

				if (preg_match("/^[A-Za-z0-9-_]+$/", $album_title)) {

					$album_id = $photosets[$i]['id'];
					if (!in_array($album_title, $album_id_array)) {
						$album_id_array[$album_id] = $album_title;
					} else {
						$count = 1;
						while (in_array($album_title.'-'.$count, $album_id_array)) {
							$count ++;
						}
						$album_id_array[$album_id] = $album_title.'-'.$count;
					}
				}
			}

			if (in_array($album, $album_id_array)) {
				$album_id = array_search($album, $album_id_array);
			} else {
				$album_id = $album;
			}

		} else {
			$album_id = $album;
		}

		//$album_title = $xpath->getData("//photoset[@id='$album_id']/title");

		$photosets = $resp['photosets']['photoset'];
		$count = sizeof($photosets);
		for ($j = 0; $j < $count; $j ++) {
			if ($photosets[$j]['id'] == $album_id) {
				$album_title = $photosets[$j]['title']['_content'];
				break;
			}
		}

		return array ($album_id, $album_title);
	}

	/* Outputs a true or false variable for showing private photos based on the registered user level */
	function _show_private() {
		$PrivateAlbumChoice = false;
		return $PrivateAlbumChoice;
	}

	/* Gets info from Cache Table */
	function _get_cached_data($key, $cache_option = WPFLICKRGALLERY_CACHE_EXPIRE_SHORT) {

		require_once (WPFLICKRGALLERY_PATH.'/lib/Lite.php');

		$options = array ("cacheDir" => WPFLICKRGALLERY_PATH."/cache/", "lifeTime" => $cache_option);

		$Cache_Lite = new Cache_Lite($options);
		$data = $Cache_Lite->get($key);

		if ($data == '') {
			$data = null;
		}

	    $this->logger->debug('cache get - key - '.$key.'<br />'.'cache - '. (isset ($data) ? 'hit' : 'miss'));

		$data = null;

		return $data;
	}

	/* Function to store the data in the cache table */
	function _set_cached_data($key, $data, $cache_option = WPFLICKRGALLERY_CACHE_EXPIRE_SHORT) {

		require_once (WPFLICKRGALLERY_PATH.'/lib/Lite.php');

		$options = array ("cacheDir" => WPFLICKRGALLERY_PATH."/cache/", "lifeTime" => $cache_option);

		$Cache_Lite = new Cache_Lite($options);

		$Cache_Lite->save($data, $key);

		$this->logger->debug('cache set - key - '.$key);

	}

	function _clear_cached_data() {

	}

	function _can_edit() {
		return false;
	}

	function _flattenArray($name, $values) {
		if (!is_array($values)) {
			return array (array ($name, $values));
		} else {
			$ret = array ();
			foreach ($values as $k => $v) {
				if (empty ($name)) {
					$newName = $k;
				}
				//elseif ($this->_useBrackets) {
				//	$newName = $name.'['.$k.']';
				//}
				else {
					$newName = $name;
				}
				$ret = array_merge($ret, $this->_flattenArray($newName, $v));
			}
			return $ret;
		}
	}


	function _error($message) {
		$this->has_error = true;

		$msg .= "<b>$message</b>\n\n";

		$msg .= "Backtrace:\n";
		$backtrace = debug_backtrace();

		foreach ($backtrace as $bt) {
			$args = '';
			if (is_array($bt['args'])) {
				foreach ($bt['args'] as $a) {
					if (!empty ($args)) {
						$args .= ', ';
					}
					switch (gettype($a)) {
						case 'integer' :
						case 'double' :
							$args .= $a;
							break;
						case 'string' :
							$a = htmlspecialchars(substr($a, 0, 64)). ((strlen($a) > 64) ? '...' : '');
							$args .= "\"$a\"";
							break;
						case 'array' :
							$args .= 'Array('.count($a).')';
							break;
						case 'object' :
							$args .= 'Object('.get_class($a).')';
							break;
						case 'resource' :
							$args .= 'Resource('.strstr($a, '#').')';
							break;
						case 'boolean' :
							$args .= $a ? 'True' : 'False';
							break;
						case 'NULL' :
							$args .= 'Null';
							break;
						default :
							$args .= 'Unknown';
					}
				}
			}

			$file_path = str_replace('\\', '/', $bt['file']);

			$file = substr($file_path, strrpos($file_path, '/') + 1);
			$line = $bt['line'];

			$args = '';

			$msg .= "  $file:{$line} - {$file_path}\n";
			$msg .= "     {$bt['class']}{$bt['type']}{$bt['function']}($args)\n";

		}

		$this->error_detail .= $msg."\n\n";
		$this->logger->err($msg);
	}

	function is_album_page() {
		return defined('WPFLICKRGALLERY') && constant('WPFLICKRGALLERY');
	}


	/**
	 * Construct Template object which will be used to
	 * generate output HTML.
	 *
	 * @param $_style The template style to use.
	 */
	function _construct_template($_style) {
		require_once(WPFLICKRGALLERY_PATH.'/Template.class.php');
		$this->template = new Template($_style);
	}
	
	/* Removes all HTML entities - commonly used for the descriptions */
	static function unhtmlentities($string) {
		// replace numeric entities
		$string = preg_replace('~&#x([0-9a-f]+);~ei', 'chr(hexdec("\\1"))', $string);
		$string = preg_replace('~&#([0-9]+);~e', 'chr(\\1)', $string);
		// replace literal entities
		$trans_tbl = get_html_translation_table(HTML_ENTITIES);
		$trans_tbl = array_flip($trans_tbl);
		return strtr($string, $trans_tbl);
	}
	
	static function create_flickr_image_url($farm, $server, $photo_id, $secret, $size)  {
		$url = WPFLICKRGALLERY_FLICKR_URL_IMAGE_1."{$farm}".WPFLICKRGALLERY_FLICKR_URL_IMAGE_2."/{$server}/{$photo_id}_{$secret}";
		if ($size) {
			$url .= "_{$size}";
		}
		$url .= ".jpg";
		return $url;
	}
	
	static function convert_flickr_size($size) {
		switch ($size) {
			case 'sq':
			case 's':
				$pixel_width = 75;
				break;
			case 't': $pixel_width = 100; break;
			case 'm': $pixel_width = 240; break;
			case 'z': $pixel_width = 640; break;
			case 'b':
			case 'o':
				$pixel_width = 1024;
				break;
			default:
				$pixel_width = 500;
				break;
		}
		return $pixel_width;
	}

}
