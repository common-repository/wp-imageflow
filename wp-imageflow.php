<?php
/*
Plugin Name: WP-ImageFlow
Plugin URI: http://www.svenkubiak.de/wp-imageflow
Description: WordPress implementation of the picture gallery ImageFlow. 
Version: 1.0
Author: Sven Kubiak
Author URI: http://www.svenkubiak.de

ImageFlow Author: Finn Rudoplh
ImageFlow Homepage: http://imageflow.finnrudolph.de
(WP-ImageFlow currently contains ImageFlow Version 0.9)

Copyright 2008 Sven Kubiak

This program is free software; you can redistribute it and/or modify
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
global $wp_version;
define('WPIMAGEFLOW25', version_compare($wp_version, '2.5', '>='));

Class WPImageFlow
{
	var $isrss = false;
	
	function wpimageflow()
	{
		if (!WPIMAGEFLOW25)
		{
			add_action ('admin_notices',__('WP-ImageFlow requires at least WordPress 2.5','wp-imageflow'));
			return;
		}	
		
		add_action('init', array(&$this, 'isRssFeed'));

		if ($this->isrss == true)
			return;
			
		add_action('activate_wp-imageflow/wp-imageflow.php', array(&$this, 'activate'));
		add_action('deactivate_wp-imageflow/wp-imageflow.php', array(&$this, 'deactivate'));
		add_action('wp_head', array(&$this, 'addScripts'));	
		add_action('admin_menu', array(&$this, 'wpImageFlowAdminMenu'));	
		add_filter('the_content', array(&$this, 'checkForFlow'));
	}
	
	function activate()
	{
		add_option('wpimageflow_galleries', 0, '', 'yes');
	}
	
	function deactivate()
	{
		delete_option('wpimageflow_galleries');
	}			
	
	function checkForFlow($content)
	{
		global $wpdb;
			
		if (stristr($content, '[wp-imageflow'))
		{
			$replace = '';
			$galleries_path = get_option('wpimageflow_galleries');

			if (!file_exists($galleries_path))
				return $content;

			$plugin_url = get_option('siteurl') . "/" . PLUGINDIR . "/" . plugin_basename(dirname(__FILE__)); 			
			
			$search = "@(?:<p>)*\s*\[WP-IMAGEFLOW\s*=\s*(\w+|^\+)\]\s*(?:</p>)*@i";
			if (preg_match_all($search, $content, $matches, PREG_SET_ORDER))
			{ 			
				foreach ($matches as $match) 
				{				
					$gallerypath = $galleries_path . $match [1];
					
					if (file_exists($gallerypath))
					{		
						$replace  = '<div id="imageflow">'; 
						$replace .= '<div id="loading">';
						$replace .= '<b>';
						$replace .= __('Loading Images','wp-imageflow');
						$replace .= '</b><br/>';
						$replace .= '<img src="'.$plugin_url.'/imageflow/loading.gif" width="208" height="13" alt="loading" />';
						$replace .= '</div>';
						$replace .= '<div id="images">';	
					
						$handle = opendir($gallerypath);
						while ($image=readdir($handle))
						{
						    $imagepath = $gallerypath."/".$image;
							if (filetype($imagepath) != "dir" && !eregi('refl_',$image))
						    {						
								$pic_reflected 	= $plugin_url.'/imageflow/reflect.php?img=/'.$imagepath;
								$pic_original 	= get_option('siteurl') . "/" . $imagepath;
								$replace .= '<img src="'.$pic_reflected.'" longdesc="'.$pic_original.'" alt="'.$image.'"/>';
						    }				
						}			
						closedir($handle);
			
						$replace .= '</div>';
						$replace .= '<div id="captions"></div>';
						$replace .= '<div id="scrollbar">';
						$replace .= '<div id="slider"></div>';
						$replace .= '</div>';
						$replace .= '</div>';	
						
						$content = str_replace ($match[0], $replace, $content);	
					}
				}
			}
		}		
		return $content;	
	}
	
	function addScripts()
	{
		$plugin_url = get_option('siteurl') . "/" . PLUGINDIR . "/" . plugin_basename(dirname(__FILE__)); 
		
		echo "<!-- WP-ImageFlow -->\n";
		echo '<link rel="stylesheet" href="'.$plugin_url.'/imageflow/screen.css" type="text/css" media="screen" />';
		echo '<script language="JavaScript" type="text/javascript" src="'.$plugin_url.'/imageflow/imageflow.js"></script>';
		echo "<!-- /WP-ImageFlow -->\n";
	}	
	
	function isRssFeed()
	{
		switch (basename($_SERVER['PHP_SELF']))
		{
			case 'wp-rss.php':
				$this->isrss = true;
			break;
			case 'wp-rss2.php':
				$this->isrss = true;
			break;
			case 'wp-atom.php':
				$this->isrss = true;
			break;
			case 'wp-rdf.php':
				$this->isrss = true;
			break;
			default:
				$this->isrss = false;	
		}		
	}
	
	function wpImageFlowAdminMenu()
	{
		add_options_page('WP-ImageFlow', 'WP-ImageFlow', 8, 'wpImageFlow', array(&$this, 'wpImageFlowOptionPage'));	
	}
	
	function wpImageFlowOptionPage()
	{		
		if (!current_user_can('manage_options'))
			wp_die(__('Sorry, but you have no permission to change settings.','wp-imageflow'));	
			
		if ($_POST['save_wpimageflow'] == 'true')
		{
			update_option('wpimageflow_galleries', $_POST['wpimageflow_path']);
			echo "<div id='message' class='updated fade'><p>".__('Settings were saved.','wp-imageflow')."</p></div>";	
		}
			
		?>
					
		<div class="wrap">
			<h2>WP-ImageFlow</h2>
			<form action="options-general.php?page=wpImageFlow" method="post">
	    	<h3><?php echo __('Settings','wp-imageflow'); ?></h3>
	    	<table class="form-table">
	    		<tr>
					<th scope="row" valign="top">
					<?php echo __('Path to Galleries from hompage root path','wp-imageflow'); ?>	
					</th>
					<td>
					<input type="text" size="35" name="wpimageflow_path" value="<?php echo get_option('wpimageflow_galleries'); ?>">
					<br /><?php echo __('e.g.','wp-imageflow'); ?> wp-content/galleries/
					<br /><?php echo __('Ending slash, but NO starting slash','wp-imageflow'); ?>
					</td>
				</tr>
				<tr>
					<th scope="row" valign="top">&nbsp;</th>
					<td>
					<input type="hidden" value="true" name="save_wpimageflow">
					<input name="submit" value="<?php echo __('Save','wp-imageflow'); ?>" type="submit" />			
					</td>
				</tr>				
			</table>
			</form>				
	    	<table class="form-table">
	    		<tr>
					<th scope="row" valign="top">
					<?php echo __('Galleries','wp-imageflow'); ?>	
					</th>
					<td>
					<?php
						$galleries_path = $_SERVER['DOCUMENT_ROOT'] ."/". get_option('wpimageflow_galleries');						
						if (file_exists($galleries_path))
						{							
							$handle	= opendir($galleries_path);
							while ($dir=readdir($handle))
							{
								if ($dir != "." && $dir != "..")
								{									
									echo "[wp-imageflow=".$dir."]";
									echo "<br />";
								}
							}
							closedir($handle);								
						}					
					?>
					</td>
				</tr>
			</table>		
		</div>
		
		<?php			
	}		
}
$wpimageflow = new WPImageFlow();
?>