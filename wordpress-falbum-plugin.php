<?php /*
Plugin Name: WPFlickrGallery
Version: 0.8.0
Plugin URI: http://www.randombyte.net/
Description: A plugin for displaying your <a href="http://www.flickr.com/">Flickr</a> photosets and photos in a gallery format on your Wordpress site.
Author: Elijah Cornell
Author URI: http://www.randombyte.net/

Copyright (c) 2010
Released under the GPL license
http://www.gnu.org/licenses/gpl.txt

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*/

$wp_flickr_gallery_options = null;

// plugin menu
function wp_flickr_gallery_admin_add_options_page() {
	if (function_exists('add_options_page')) {
		add_options_page('WPFlickrGallery Options', 'WPFlickrGallery', 8, basename(__FILE__), 'wp-flickr-gallery_admin_print_options_page');
	}
}

function wp_flickr_gallery_init() {
	global $wpdb, $user_level;
		 
	$fa_table =  $wpdb->prefix . "wp-flickr-gallery_cache";
	
	get_currentuserinfo();
	if ($user_level < 8) {
		return;
	}

	if ($wpdb->get_var("show tables like '$fa_table'") != $fa_table) {
		$sql = "CREATE TABLE ".$fa_table." (
			ID varchar(40) PRIMARY KEY,
			data text,
			expires datetime
		)";
		
		require_once (ABSPATH.'wp-admin/upgrade-functions.php');
		dbDelta($sql);
	}
	
	register_setting('wp-flickr-gallery-options', 'wp-flickr-gallery_options');
}

function wp_flickr_gallery_admin_print_options_page() {
	global $wp_flickr_gallery_options;
	$options = $wp_flickr_gallery_options;

	require_once (dirname(__FILE__).'/wp/WPFlickrGallery_WP.class.php');

	$wp_flickr_gallery = new WPFlickrGallery_WP();

	global $is_apache, $wpdb;
	$fa_table =  $wpdb->prefix . "wp-flickr-gallery_cache";

	$ver = $options['version'];
	if ($ver != FALBUM_VERSION) {
		wp_flickr_gallery_init();
	}

	// Setup htaccess 
	$urlinfo = parse_url(get_settings('siteurl'));
	$path = $urlinfo['path'];

	$furl = trailingslashit($options['url_root']);
	if ($furl {
		0 }
	== "/") {
		$furl = substr($furl, 1);
	}
	if (strpos('/'.$furl, $path.'/') === false) {
		$home_path = parse_url("/");
		$home_path = $home_path['path'];
		$root2 = str_replace($_SERVER["PHP_SELF"], '', $_SERVER["SCRIPT_FILENAME"]);
		$home_path = trailingslashit($root2.$home_path);
	} else {
		$furl = str_replace($path.'/', '', '/'.$furl);
		$home_path = get_home_path();
	}
	if ($furl {
		0 }
	== "/") {
		$furl = substr($furl, 1);
	}
	if ((!file_exists($home_path.'.htaccess') && is_writable($home_path)) || is_writable($home_path.'.htaccess')) {
		$writable = true;
	} else {
		$writable = false;
	}

	$rewriteRule = "<IfModule mod_rewrite.c>\n"."RewriteEngine On\n"."RewriteRule ^".$furl."?([^/]*)?/?([^/]*)?/?([^/]*)?/?([^/]*)?/?([^/]*)?/?([^/]*)?/?([^/]*)?/?([^/]*)?/?$ ".$path."/wp-content/plugins/wp-flickr-gallery/wp/album.php?$1=$2&$3=$4&$5=$6&$7=$8 [QSA,L]\n"."</IfModule>";

	//echo '<pre>$path-'.$path.'/'.'</pre>';
	//echo '<pre>$furl-'.'/'.$furl.'</pre>';
	//echo '<pre>1-'.strpos('/'.$furl, $path.'/').'</pre>';
	//echo '<pre>$furl-'.$furl.'</pre>';
	//echo '<pre>'.$rewriteRule.'</pre>';

	// posting logic
	if (isset ($_POST['Submit'])) {
		
		check_admin_referer('wp-flickr-gallery-options-options');

		$options['tsize'] = $_POST['tsize'];
		$options['columns_per_page'] = $_POST['columns_per_page'];
		$options['show_private'] = $_POST['show_private'];
		$options['friendly_urls'] = $_POST['friendly_urls'];
		$options['url_root'] = $_POST['url_root'];
		$options['albums_per_page'] = $_POST['albums_per_page'];
		$options['photos_per_page'] = $_POST['photos_per_page'];
		$options['max_photo_width'] = $_POST['max_photo_width'];
		$options['display_dropshadows'] = $_POST['display_dropshadows'];
		$options['display_sizes'] = $_POST['display_sizes'];
		$options['display_exif'] = $_POST['display_exif'];
		$options['view_private_level'] = $_POST['view_private_level'];
		$options['number_recent'] = $_POST['number_recent'];
		$options['can_edit_level'] = $_POST['can_edit_level'];
		$options['style'] = $_POST['style'];
		$options['url_wp-flickr-gallery_dir'] = $_POST['url_wp-flickr-gallery_dir'];

		$options['wp_enable_wp-flickr-gallery_globally'] = $_POST['wp_enable_wp-flickr-gallery_globally'];

		$furl = $options['url_root'];
		$pos = strpos($furl, '/');
		if ($furl {
			0 }
		!= "/") {
			$furl = '/'.$furl;
		}
		$pos = strpos($furl, '.php');
		if ($pos === false) {
			$furl = trailingslashit($furl);
		}

		$options['url_root'] = $furl;

		update_option('wp-flickr-gallery_options', $options);

		$updateMessage .= fa__('Options saved')."<br /><br />";

		if ($options['friendly_urls'] != 'false') {

			if ($is_apache) {

				$urlinfo = parse_url(get_settings('siteurl'));
				$path = $urlinfo['path'];

				$furl = trailingslashit($options['url_root']);
				if ($furl {
					0 }
				== "/") {
					$furl = substr($furl, 1);
				}

				//echo '<pre>$path-'.$path.'/'.'</pre>';
				//echo '<pre>$furl-'.'/'.$furl.'</pre>';
				//echo '<pre>1-'.strpos('/'.$furl, $path.'/').'</pre>';

				$pos = strpos('/'.$furl, $path.'/');

				if ($path != '/' && strpos('/'.$furl, $path.'/') === false) {
					//use root .htaccess file
					//echo '<pre>root</pre>';
					$home_path = parse_url("/");
					$home_path = $home_path['path'];
					$root2 = str_replace($_SERVER["PHP_SELF"], '', $_SERVER["SCRIPT_FILENAME"]);
					$home_path = trailingslashit($root2.$home_path);
				} else {
					//use wp .htaccess file
					//echo '<pre>wp</pre>';
					if (strlen($path) > 1) {
						$furl = str_replace($path.'/', '', '/'.$furl);
					}
					$home_path = get_home_path();
				}
				if ((!file_exists($home_path.'.htaccess') && is_writable($home_path)) || is_writable($home_path.'.htaccess')) {
					$writable = true;
				} else {
					$writable = false;
				}
				if ($furl {
					0 }
				== "/") {
					$furl = substr($furl, 1);
				}

				$rewriteRule = "<IfModule mod_rewrite.c>\n"."RewriteEngine On\n"."RewriteRule ^".$furl."?([^/]*)?/?([^/]*)?/?([^/]*)?/?([^/]*)?/?([^/]*)?/?([^/]*)?/?([^/]*)?/?([^/]*)?/?$ ".$path."/wp-content/plugins/wp-flickr-gallery/wp/album.php?$1=$2&$3=$4&$5=$6&$7=$8 [QSA,L]\n"."</IfModule>";

				//echo '<pre>'.$rewriteRule.'</pre>';

				if ($writable) {
					$rules = explode("\n", $rewriteRule);
					wp_flickr_gallery_insert_with_markers($home_path.'.htaccess', 'WPFlickrGallery', $rules);
					$updateMessage .= fa__('Mod rewrite rules updated')."<br /><br />";
				}
			}

		} else {
			if ($writable) {
				wp_flickr_gallery_insert_with_markers($home_path.'.htaccess', 'WPFlickrGallery', explode("\n", ""));
			}
		}

		$wpdb->query("DELETE from ".$fa_table."");
		$updateMessage .= fa__('Cache cleared')."<br />";

	}

	if (isset ($_POST['ClearToken'])) {
		$options['token'] = null;
		update_option('wp-flickr-gallery_options', $options);
		$updateMessage .= fa__('Flickr authorization reset')."<br />";
	}

	if (isset ($_POST['ClearCache'])) {
		$wp_flickr_gallery->_clear_cached_data();
		$updateMessage .= fa__('Cache cleared')."<br />";
	}

	if (isset ($_POST['GetToken'])) {
		$frob2 = $_POST['frob'];
	
		$resp = $wp_flickr_gallery->_call_flickr_php('flickr.auth.getToken',array ("frob" => $frob2), $cache_option = FALBUM_CACHE_EXPIRE_SHORT, $post = true);

		if (isset($resp)) {
			$token = $resp['auth']['token']['_content'];
			$nsid = $resp['auth']['user']['nsid'];

			$options['token'] = $token;
			$options['nsid'] = $nsid;

			update_option('wp-flickr-gallery_options', $options);

			$updateMessage .= fa__('Successfully set token')."<br />";
		} else {
			$updateMessage .= fa__('You have not Authorized Falbum. Please perform Step 1.');
			$updateMessage .= "<br /><br />Flickr message: $xpath";
		}
	}

	if (isset ($updateMessage)) {
?> 		<div class="updated"><p><strong><?php echo $updateMessage?></strong></p></div> <?php
	}

	//Init Settings
	if (!isset ($options['tsize']) || $options['tsize'] == "") {
		$options['tsize'] = "t";
	}
	if (!isset ($options['columns_per_page']) || !is_numeric($options['columns_per_page'])) {
		$options['columns_per_page'] = 3;
	}
	if (!isset ($options['show_private']) || $options['show_private'] == "") {
		$options['show_private'] = "false";
	}
	if (!isset ($options['friendly_urls']) || $options['friendly_urls'] == "") {
		$options['friendly_urls'] = "false";
	}
	if (!isset ($options['url_root']) || $options['url_root'] == "") {
		$options['url_root'] = $path."/wp-content/plugins/wp-flickr-gallery/wp/album.php";
	}
	if (!isset ($options['albums_per_page']) || $options['albums_per_page'] == "") {
		$options['albums_per_page'] = "5";
	}
	if (!isset ($options['photos_per_page']) || $options['photos_per_page'] == "") {
		$options['photos_per_page'] = "20";
	}
	if (!isset ($options['max_photo_width']) || $options['max_photo_width'] == "") {
		$options['max_photo_width'] = "500";
	}
	if (!isset ($options['display_dropshadows']) || $options['display_dropshadows'] == ""
		|| !$options['display_dropshadows'] || $options['display_dropshadows'] == '0') {
		$options['display_dropshadows'] = "";
	} else {
		$options['display_dropshadows'] = "wp-flickr-gallery-dropshadow";
	}
	if (!isset ($options['display_sizes']) || $options['display_sizes'] == "") {
		$options['display_sizes'] = "false";
	}
	if (!isset ($options['display_exif']) || $options['display_exif'] == "") {
		$options['display_exif'] = "false";
	}
	if (!isset ($options['view_private_level']) || $options['view_private_level'] == "") {
		$options['view_private_level'] = "10";
	}
	if (!isset ($options['number_recent']) || $options['number_recent'] == "") {
		$options['number_recent'] = "0";
	}
	if (!isset ($options['can_edit_level']) || $options['can_edit_level'] == "") {
		$options['can_edit_level'] = "10";
	}
	if (!isset ($options['wp_enable_wp-flickr-gallery_globally']) || $options['wp_enable_wp-flickr-gallery_globally'] == "") {
		$options['wp_enable_wp-flickr-gallery_globally'] = "true";
	}
	if (!isset ($options['style']) || $options['style'] == "") {
		$options['style'] = "default";
	}
	if (!isset ($options['url_wp-flickr-gallery_dir']) || $options['style'] == "") {
		$options['url_wp-flickr-gallery_dir'] = $path."/wp-content/plugins/wp-flickr-gallery";
	}
?>


<div class="wrap">
  <h2><?php fa_e('WPFlickrGallery Options');?></h2>
    <form method=post action="<?php echo $_SERVER['PHP_SELF']; ?>?page=wordpress-wp-flickr-gallery-plugin.php">
        <input type="hidden" name="update" value="true">
                       
<?php if (!isset($options['token']) || $options['token'] == '' ) { ?>

       <fieldset class="options">
       <legend><?php fa_e('Initial Setup');?></legend>
        
    <?php	
	$resp = $wp_flickr_gallery->_call_flickr_php('flickr.auth.getFrob',array (), $cache_option = FALBUM_DO_NOT_CACHE, $post = true);
	
	if (isset($resp)) {

		$frob = $resp['frob']['_content'];
		if ($frob == '') {
		
			echo "<p>Error: Unable to get frob value <br /><pre>".print_r($resp,true).'</pre>Trying again...</p>';
		
			$resp = $wp_flickr_gallery->_call_flickr_php('flickr.auth.getFrob',array (), $cache_option = FALBUM_DO_NOT_CACHE, $post = true);
			$frob = $resp['frob']['_content'];
			
			if ($frob == '') {
				echo '<p>Error: Unable to get frob value again - Try clicking on the WPFlickrGallery options tab again. \n'.print_r($resp,true).'</p>';
			}
		}

		//echo '<pre>$frob-'.htmlentities($frob).'</pre>';

		$link = 'http://flickr.com/services/auth/?api_key='.FALBUM_API_KEY.'&frob='.$frob.'&perms=write';
		$parms = 'api_key'.FALBUM_API_KEY.'frob'.$frob.'permswrite';
		$link .= '&api_sig='.md5(FALBUM_SECRET.$parms);
?>
       
		       <input type="hidden" name="frob" value="<?php echo $frob?>">
		       <p>	      
		       <?php fa_e('Please complete the following steps to allow WPFlickrGallery access to your Flickr photos.');?>
		       </p>
       
		       <p>
		       <?php fa_e('Step 1:');?> <a href="<?php echo $link?>" target="_blank"><?php fa_e('Authorize WPFlickrGallery to access your Flickr account');?></a>
		       </p>
       	       	       	 
		       <p>
		       <?php fa_e('Step 2:');?> <input type="submit" name="GetToken" value="<?php fa_e('Get Authentication Token');?>" />
		       </p>
	       <?php


	} else {
		echo "<p>Error: $resp </p>";
	}
?>
      	
                       
      </fieldset>
      
<?php } else { ?>
            
		<fieldset class="options">
		<legend><?php fa_e('WPFlickrGallery Admin');?></legend>
         
		<p>
		<input type="submit" name="ClearCache" value="<?php fa_e('Clear Cache');?>" />
		&nbsp;&nbsp;&nbsp;
         
		<?php if (isset($options['token'])) { ?>
			<input type="submit" name="ClearToken" value="<?php fa_e('Reset Flickr Authorization');?>" />
		<?php } ?>
         
		</p>
		</fieldset>
       
		<hr />
       
		<fieldset class="options">
		<legend><?php fa_e('WPFlickrGallery Configuration');?></legend>
		<table width="100%" cellspacing="2" cellpadding="5" class="editform">

            	<tr valign="top">
                    <th width="33%" scope="row"><?php fa_e('Enable globally');?>:</th>
                    <td>
                    <select name="wp_enable_wp-flickr-gallery_globally">
                    <option value="true"<?php if ($options['wp_enable_wp-flickr-gallery_globally'] == 'true') { ?> selected="selected"<?php } ?> ><?php fa_e('true');?></option>
                    <option value="false"<?php if ($options['wp_enable_wp-flickr-gallery_globally'] == 'false') { ?> selected="selected"<?php } ?> ><?php fa_e('false');?></option>
                    </select>
                    <br />
                    <?php fa_e('Enables WPFlickrGallery methods to be used in any WordPress page (ex. sidebar.php).');?></td>
                </tr>
                
                <tr valign="top">
                    <th width="33%" scope="row"><?php fa_e('Selected style');?>:</th>
                    <td>
                    <select name="style">                    
<?php

	$d = dir(dirname(__FILE__)."/styles");
	while (false !== ($entry = $d->read())) {
		if (strstr($entry, '.') != $entry) {
?>
				   		<option value="<?php echo $entry ?>"<?php if ($options['style'] == $entry) { ?> selected="selected"<?php } ?> ><?php echo $entry?></option>
<?php
		}
	}
	$d->close();
	
?>
                    </select>
                    <br />
                    <?php fa_e('Select the current style.');?></td>
                </tr>            	
            	
            	<tr valign="top">            	
            	    <th width="33%" scope="row"><?php fa_e('Thumbnail Size');?>:</th>
                    <td>
		    
                    <select name="tsize">
                    <option value="s"<?php if ($options['tsize'] == 's') { ?> selected="selected"<?php } ?> ><?php fa_e('Square');?> (75px x 75px)</option>
                    <option value="t"<?php if ($options['tsize'] == 't') { ?> selected="selected"<?php } ?> ><?php fa_e('Thumbnail');?> (100px x 75px)</option>
                    <option value="m"<?php if ($options['tsize'] == 'm') { ?> selected="selected"<?php } ?> ><?php fa_e('Small');?> (240px x 180px)</option>
                    </select><br />
                    <?php fa_e('Size of the thumbnail you want to appear in the album thumbnail page');?><br /></td>
                </tr>
                
                <tr valign="top">
                    <th width="33%" scope="row"><?php fa_e('Columns Per Page');?>:</th>
                    <td><input type="text" name="columns_per_page" size="3" value="<?php echo $options['columns_per_page'] ?>"/><br />
                   <?php fa_e('How many columns to show on a page');?></td>
                </tr>
                <tr valign="top">
                    <th width="33%" scope="row"><?php fa_e('Albums Per Page');?>:</th>
                    <td><input type="text" name="albums_per_page" size="3" value="<?php echo $options['albums_per_page'] ?>"/><br />
                   <?php fa_e('How many albums to show on a page (0 for no paging)');?></td>
                </tr>
                <tr valign="top">
                    <th width="33%" scope="row"><?php fa_e('Photos Per Page');?>:</th>
                    <td><input type="text" name="photos_per_page" size="3" value="<?php echo $options['photos_per_page'] ?>"/><br />
                   <?php fa_e('How many photos to show on a page (0 for no paging)');?></td>
                </tr>
                <tr valign="top">
                    <th width="33%" scope="row"><?php fa_e('Recent Images');?>:</th>
                    <td><input type="text" name="number_recent" size="3" value="<?php echo $options['number_recent'] ?>"/><br />
                   <?php fa_e('How many of the most recent photos to show (0 for no recent images / -1 to show all available images)');?></td>
                </tr>
				
		<tr valign="top">
                    <th width="33%" scope="row"><?php fa_e('Max Photo Width');?>:</th>
                    <td><input type="text" name="max_photo_width" size="3" value="<?php echo $options['max_photo_width'] ?>"/><br />
                   <?php fa_e('Maximum photo width in pixels (0 for no resizing).  The default size of the images returned from Flickr is 500 pixels.');?></td>
                </tr>  
                <?php /*
                 <tr valign="top">
                    <th width="33%" scope="row"><?php fa_e('Display Drop Shadows');?>:</th>
                    <td>
                    <select name="display_dropshadows">
                    <option value="1"<?php if ($options['display_dropshadows']) { ?> selected="selected"<?php } ?> ><?php fa_e('true');?></option>
                    <option value="0"<?php if ($options['display_dropshadows']) { ?> selected="selected"<?php } ?> ><?php fa_e('false');?></option>
                    </select>
                    <br />
                    <?php fa_e('Whether or not to show drop shadows under photos');?></td>
                </tr>
		*/ ?>
               
                <tr valign="top">
                    <th width="33%" scope="row"><?php fa_e('Display Photo Sizes');?>:</th>
                    <td>
                    <select name="display_sizes">
                    <option value="true"<?php if ($options['display_sizes'] == 'true') { ?> selected="selected"<?php } ?> ><?php fa_e('true');?></option>
                    <option value="false"<?php if ($options['display_sizes'] == 'false') { ?> selected="selected"<?php } ?> ><?php fa_e('false');?></option>
                    </select>
                    <br />
                    <?php fa_e('Whether or not to show photo sizes links');?></td>
                </tr>
                
                <tr valign="top">
                    <th width="33%" scope="row"><?php fa_e('Display EXIF Data');?>:</th>
                    <td>
                    <select name="display_exif">
                   <option value="true"<?php if ($options['display_exif'] == 'true') { ?> selected="selected"<?php } ?> ><?php fa_e('true');?></option>
                    <option value="false"<?php if ($options['display_exif'] == 'false') { ?> selected="selected"<?php } ?> ><?php fa_e('false');?></option>
                     </select>
                    <br />
                    <?php fa_e('Whether or not to show EXIF link');?></td>
                </tr>
             
                <tr valign="top">
                    <th width="33%" scope="row"><?php fa_e('Show Private');?>:</th>
                    <td>
                    <select name="show_private">
                    <option value="true"<?php if ($options['show_private'] == 'true') { ?> selected="selected"<?php } ?> ><?php fa_e('true');?></option>
                    <option value="false"<?php if ($options['show_private'] == 'false') { ?> selected="selected"<?php } ?> ><?php fa_e('false');?></option>
                    </select>
                    <br />
                    <?php fa_e('Whether or not to show your "private" Flickr photos');?></td>
                </tr>
                
                <tr valign="top">
                    <th width="33%" scope="row"><?php fa_e('View Private Level');?>:</th>
                    <td><input type="text" name="view_private_level" size="3" value="<?php echo $options['view_private_level'] ?>"/>
                    <br />
                    <?php fa_e('Set the Wordpress user level that is allowed to view "private" Flickr photos if "Show Private" is true. <br /> (0 to allow anonymous users)');?></td>
                </tr>
                
                <tr valign="top">
                    <th width="33%" scope="row"><?php fa_e('Can Edit Level');?>:</th>
                    <td><input type="text" name="can_edit_level" size="3" value="<?php echo $options['can_edit_level'] ?>"/>
                    <br />
                    <?php fa_e('Set the Wordpress user level that is allowed to edit album and photo meta data.');?></td>
                </tr>
                                              
                
                <tr valign="top">
                    <th width="33%" scope="row"><?php fa_e('Use Friendly URLS');?>:</th>
                    <td>
                    <select name="friendly_urls">
                    <option value="title"<?php if ($options['friendly_urls'] == 'title') { ?> selected="selected"<?php } ?> ><?php fa_e('title');?></option>
                    <option value="numeric"<?php if ($options['friendly_urls'] == 'numeric') { ?> selected="selected"<?php } ?> ><?php fa_e('numeric');?></option>
                    <option value="false"<?php if ($options['friendly_urls'] == 'false') { ?> selected="selected"<?php } ?> ><?php fa_e('false');?></option>
                    </select>
                    <br />
                    <?php fa_e('Set to title or numeric if you want to use "friendly" URLs (requires mod_rewrite), false otherwise');?>
                </tr>
                
                <tr valign="top">	
                    <th width="33%" scope="row"><?php fa_e('Navigation Root');?>:</th>
                    <td><input type="text" name="url_root" size="60" value="<?php echo $options['url_root'] ?>"/><br />
                   <?php


	fa_e('URL to use as the root for all navigational links.<br /><strong>NOTE:</strong>It is important that you specify something here, for example:<br />If friendly URLs is <strong>enabled</strong> use - /photos/<br />If friendly URLs is <strong>disabled</strong> use - ');
	echo $path."/wp-content/plugins/wp-flickr-gallery/wp/album.php";
?>
				   </td>
                </tr>
				
				<tr valign="top">	
                    <th width="33%" scope="row"><?php fa_e('WPFlickrGallery Directory URL');?>:</th>
                    <td><input type="text" name="url_wp-flickr-gallery_dir" size="60" value="<?php echo $options['url_wp-flickr-gallery_dir'] ?>"/><br />
                   <?php


	fa_e('URL of the wp-flickr-gallery directory<br />Example: ');
	echo $path."/wp-content/plugins/wp-flickr-gallery";
?>
				   </td>
                </tr>
                
                 <tr valign="top">
                    <th width="33%" scope="row"></th>
                    <td>
                    <?php if ( !$writable && $is_apache) { ?>
  <p><?php echo strtr(fa__('If your #htaccess# file was <a href="http://codex.wordpress.org/Make_a_Directory_Writable">writable</a> we could do this automatically, but it isn\'t so these are the mod_rewrite rules you should have in your <code>.htaccess</code> file. Click in the field and press <kbd>CTRL + a</kbd> to select all.'), array("#htaccess#" => "<code>$home_path.htaccess</code>")) ?></p>
  <p><textarea rows="5" style="width: 98%;" name="rules"><?php echo $rewriteRule; ?>
  </textarea></p><?php } ?>    
					</td>
                </tr>        
				                                 
		</table>
     
		<p class="submit">
		<input type="submit" name="Submit" value="<?php fa_e('Update Options');?> &raquo;" />
		</p>
       
		</fieldset>
		
		<?php settings_fields('wp-flickr-gallery-options'); ?>
   
		</form>
		</div>

		<?php
	}
}


// Updates the .htaccess file with WPFlickrGallery ModRewrite rules 
// The WPFlickrGallery rules block need to come before the WP2 rules
// so they are always inserted at the top of the file
function wp_flickr_gallery_insert_with_markers($filename, $marker, $insertion) {
	if (!file_exists($filename) || is_writeable($filename)) {

		if (!file_exists($filename)) {
			$markerdata = '';
		} else {
			$markerdata = explode("\n", implode('', file($filename)));
		}

		$f = fopen($filename, 'w');

		fwrite($f, "# BEGIN {$marker}\n");
		foreach ($insertion as $insertline)
			fwrite($f, "{$insertline}\n");
		fwrite($f, "# END {$marker}\n");

		if ($markerdata) {
			$state = true;
			foreach ($markerdata as $markerline) {
				if (strstr($markerline, "# BEGIN {$marker}"))
					$state = false;
				if ($state)
					fwrite($f, "{$markerline}\n");
				if (strstr($markerline, "# END {$marker}")) {
					$state = true;
				}
			}
		}

		fclose($f);
		return true;
	} else {
		return false;
	}
}
//

// function for outputting header information
//
function wp_flickr_gallery_action_init() {
	global $wp_flickr_gallery_options;
	$wp_flickr_gallery_options = get_option('wp-flickr-gallery_options');
	if ($wp_flickr_gallery_options['wp_enable_wp-flickr-gallery_globally'] == 'true') {
		require_once (dirname(__FILE__).'/wp-flickr-gallery.php');
	}
	
	/* Insert CSS */
	if ((defined('FALBUM') && constant('FALBUM')) || $wp_flickr_gallery_options['wp_enable_wp-flickr-gallery_globally'] == 'true') {
		$tdir_uri = get_template_directory_uri();
		$cssUrl = get_settings('siteurl')."/wp-content/plugins/wp-flickr-gallery/styles/".$wp_flickr_gallery_options['style']."/wp-flickr-gallery.css";
		wp_enqueue_style('wp-flickr-gallery', $cssUrl);
		wp_enqueue_script('wp-flickr-gallery-js', get_settings('siteurl').'/wp-content/plugins/wp-flickr-gallery/res/wp-flickr-gallery.js', array('jquery'), null);
	}
}

function wp_flickr_gallery_filter($content) {

	global $wp_flickr_gallery;

	$matches = array ();
	
	$content = preg_replace('`\<p>(\[fa:(.*?)\].*?)</p>`ms', '$1', $content);

	preg_match_all('`\[fa:(.*?)\]`', $content, $matches);

	for ($i = 0; $i < count($matches[0]); $i ++) {

		$s = '';
		$v = split(":", $matches[1][$i]);

		$style = '';
		$album = '';
		$tag = '';
		$id = '';
		$page = NULL;
		$linkto = '';

		// Defaults				
		$size = 'm';
		$float = 'left';

		//Parse Parms
		if (count($v) == 2) {
			$parms = split(",", $v[1]);

			for ($index = 0; $index < sizeof($parms); $index ++) {
				$pv = trim($parms[$index]);
				$p = split("=", $pv);

				switch ($p[0]) {

					case 'a' :
					case 'album' :
						$album = $p[1];
						break;

					case 'j' :
					case 'justification' :
						if ($p[1] == 'left' || $p[1] == 'l') {
							$style = 'float: left; margin: 0px 5px -5px 0px';
						} else
							if ($p[1] == 'right' || $p[1] == 'r') {
								$style = 'float: right; margin: 0px -5px -5px 5px';
							} else {
								$style = 'position: relative; margin: 0 auto; text-align: center;';
							}
						break;

					case 'l' :
					case 'linkto' :
						$linkto = $p[1];
						break;

					case 'id' :
						$id = $p[1];
						break;

					case 'p' :
					case 'page' :
						$page = $p[1];
						break;

					case 's' :
					case 'size' :
						$size = $p[1];
						break;

					case 't' :
					case 'tag' :
						$tag = $p[1];
						break;

				}
			}
		}

		//Parse Action
		switch ($v[0]) {

			case 'a' :
			case 'album' :
				$s = $wp_flickr_gallery->show_album_tn($id);
				break;

			case 'r' :
			case 'random' :
				if ($album == '') {
					$s = $wp_flickr_gallery->show_random(1, $tag, 1, $size);
				} else {
					$s = $wp_flickr_gallery->show_album_tn($album);
				}
				break;

			case 'p' :
			case 'photo' :
				//$album, $tags, $photo, $page, $size
				$s = $wp_flickr_gallery->show_single_photo($album, $tag, $id, $page, $size, $linkto);
				break;

		}

		$s = "<div class='wp-flickr-gallery-post-box' style='".$style."'>".$s."</div>";
		$s = "<div class='wp-flickr-gallery-post-box'>".$s."</div>";
		
		//$s = str_replace('div', 'span', $s);

		$content = str_replace($matches[0][$i], $s, $content);
	}

	return $content;
}

function wp_flickr_gallery_action_parse_query($wp_query) {
	if (defined('FALBUM') && constant('FALBUM')) {
		$wp_query->is_404 = false;
	}
}


add_action('parse_query', 'wp-flickr-gallery_action_parse_query');
add_action('init', 'wp-flickr-gallery_action_init');
add_action('admin_menu', 'wp-flickr-gallery_admin_add_options_page');

add_filter('the_content', 'wp-flickr-gallery_filter');
