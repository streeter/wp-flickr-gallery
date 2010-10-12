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

define('WPFLICKRGALLERY_STANDALONE', true);
require_once(dirname(__FILE__).'/wp-flickr-gallery.php');

$action = $_REQUEST['action'];


if (isset($action)){
	switch(strtolower($action))
	{
		case  'show_photo' :
		run_show_photos();
		break;
	
		case  'exif' :
		runExif();
		break;
		
		case  'edit' :
		runEdit();
		break;
		
		case  'ajax' :
		run_ajax();
		break;

		default:
		return '0';
	}
}else{
	echo 'action not found';
	return '0';
}

function run_ajax() {	
	global $wp_flickr_gallery;
	
	
	$album = $_GET['album'];
	$photo = $_GET['photo'];
	$page = $_GET['page'];
	$tags = $_GET['tags'];
	$show = $_GET['show'];
	
	if ($album == 'null') {
		$_GET['album'] = NULL;
	}
	if ($photo == 'null') {
		$_GET['photo'] = NULL;
	}
	if ($page == 'null') {
		$_GET['page'] = NULL;
	}
	if ($tags == 'null') {
		$_GET['tags'] = NULL;
	}
	if ($show == 'null') {
		$_GET['show'] = NULL;
	}
		
	header('Content-Type: text/html'); 
	
	//echo '<pre>' + $album + ' ' + $photo+ ' ' + 	$page+ ' ' + 	$tags + ' ' + 	$show + '</pre>';
	
	$output = $wp_flickr_gallery->show_photos_main();
	
	//$output = preg_replace('/<!-- JS Start -->.*<!-- JS End -->/msU', '', $output);
	
	echo $output;
}

function run_show_photos() {	
	global $wp_flickr_gallery;
	
	$album = $_POST['album'];
	$photo = $_POST['photo'];
	$page = $_POST['page'];
	$tags = $_POST['tags'];
		
	//$photo_id = $_GET['photo_id'];
	//$secret = $_GET['secret'];		
	header('Content-Type: text/html'); 
	
	echo $wp_flickr_gallery->show_photo($album, $tags, $photo, $page);
}
  

function runExif() {	
	global $wp_flickr_gallery;
	
	$photo_id = $_GET['photo_id'];
	$secret = $_GET['secret'];		
	header('Content-Type: text/html'); 
	
	echo $wp_flickr_gallery->show_exif($photo_id,$secret);
}

function runEdit() {	
	global $wp_flickr_gallery;
	
	$id = $_POST['id'];
	$photo_id = $_POST['photo_id'];
	$content = stripslashes( urldecode($_POST['content']) );
	     
    if ($id == 'wp-flickr-gallery-photo-desc') {    	
    	$o_title = html_entity_decode($_POST['o_title']);
    	$data = $wp_flickr_gallery->update_metadata($photo_id,$o_title,$content);
    	echo $data['description'];
    } else {
    	$o_description = html_entity_decode($_POST['o_desc']);
    	$data = $wp_flickr_gallery->update_metadata($photo_id,$content,$o_description);
    	echo $data['title'];    	
    }	
}
