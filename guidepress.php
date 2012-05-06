<?php
/*
Plugin Name: GuidePress
Plugin URI: http://guidepress.net/
Description: The GuidePress plugin puts WordPress video tutorials right into the the WP dashboard! It's a handy companion for new and seasoned WP users.
Author: GuidePress
Version: 0.1.3
Author URI: http://guidepress.net/

@license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
Copyright (c) 2012, GuidePress.net.

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/


if (!function_exists('add_action')) {
  die('Please don\'t open this file directly!');
}

define('CGP_DIR', plugin_dir_url(__FILE__));

require_once('gp-ajax.php');
require_once('videopress-player.php');

class client_gp {
  // Init
  function init() {
    if (is_admin()) {
      // settings registration
      add_action('admin_init', array(__CLASS__, 'register_settings'));
      
      // add guidepress menu item
      add_action('admin_menu', array(__CLASS__, 'admin_menu'));
      
      // add guidepress button to admin bar
      add_action('admin_bar_menu', array(__CLASS__, 'add_admin_bar_guidepress'), 999);
      
      // add aditional links in plugin description
      add_action('plugin_action_links_' . basename(dirname(__FILE__)) . '/' . basename(__FILE__), 
                 array('client_gp', 'plugin_action_links'));
                 
      // add jquery
      add_action('admin_enqueue_scripts', array(__CLASS__, 'admin_enqueue_scripts'));
      add_action('admin_print_scripts', array(__CLASS__, 'admin_footer_enqueus'));
      
      // Ajax Stuff
      add_action('wp_ajax_send_comment', array('client_gp_ajax', 'ajax_callback_send_comment'));
      add_action('wp_ajax_fetch_video', array('client_gp_ajax', 'ajax_callback_fetch_video'));
      add_action('wp_ajax_client_api', array('client_gp_ajax', 'ajax_callback_client_api'));
      add_action('wp_ajax_client_verification', array('client_gp_ajax', 'ajax_callback_client_verification'));
      add_action('wp_ajax_notice_dialog', array('client_gp_ajax', 'ajax_callback_notice_dialog'));
      add_action('wp_ajax_send_notice', array('client_gp_ajax', 'ajax_callback_send_notice'));

      // add guidepress tab content
      add_action('admin_footer', array(__CLASS__, 'guidepress_tab_content'));
      
      // add div elements for dialog content
      add_action('admin_footer', array(__CLASS__, 'prep_dialog'));
      
      // add dialog for subscription activation, new account creation and forgotten username
      add_action('admin_footer', array(__CLASS__, 'activate_subscription_message'));
      
      // send comments to master server
      add_action('admin_footer', array(__CLASS__, 'leave_comment'));
    } // if (is_admin)
  } // init


  // Add content to Guidepress tab
  function guidepress_tab_content() {
    global $current_screen;
    
    // Vars
    $output_link = '';
    
    // Setup data
    $query = $_SERVER["QUERY_STRING"];
    $category = $_SERVER['PHP_SELF'];
    $category = basename($category);
    $category = explode('.', $category);
  	
    
    // Get feautured tutorial
    $query_feautured = array('post_type' => 'video-tutorials',
                             'meta_query' => array(
                                      array('meta_key' => 'category',
                                            'meta_value' => $category[0])),
                             'meta_key' => 'gp_order_' . $category[0],
                             'orderby' => 'meta_value',
                             'posts_per_page' => '1',
                             'order' => 'ASC');
                    
    // Get feautured post
    $tutorials_feautured = query_posts($query_feautured);

    // Setup query
		$query = array('post_type' => 'video-tutorials',
 									 'meta_query' => array(
 									                   array('meta_key' => 'category',
 																	  			 'meta_value' => $category[0])),
 									 'meta_key' => 'gp_order_' . $category[0],
 									 'orderby' => 'meta_value',
 									 'order' => 'ASC');
    
    // Get the post
    $tutorials = query_posts($query);
    
    // Content output
    echo '<div id="guidepress_content" style="display:none;">
            <div id="guidepress_container">';
    
    // If query was successful and there are any posts...
    if ($tutorials && count($tutorials) > 0) {
       echo '<div id="guidepress_gallery_container">
             <!-- Main Video -->
             <div id="guidepress_main_video">
             <!-- Video -->
             <div id="guidepress_video_container">';
             
             // If videopress video is set...
             if (get_post_meta($tutorials_feautured[0]->ID, 'videopress', true) != '') {
               echo apply_filters('the_content', get_post_meta($tutorials_feautured[0]->ID, 'videopress', true));
             } else {
               // Get only ID from youtube URL
               $youtube_id = self::youtube_id(get_post_meta($tutorials_feautured[0]->ID, 'youtube', true));
               echo '<iframe width="525" height="297" src="http://www.youtube.com/embed/' . $youtube_id . '?rel=0" frameborder="0" allowfullscreen></iframe>';
             }
                
                
       $links = get_post_meta($tutorials_feautured[0]->ID, 'links', true);
       if (is_array($links) && count($links) > 0) {
         foreach ($links as $link) {
           $output_link .= '<a href="' . $link['url'] . '">' . $link['name'] . '</a>, ';
         }
         $output_link = rtrim($output_link, ', ');
       }
                
       echo '</div>';
       echo '<div id="guidpress_video_details">
             <p>' . get_post_meta($tutorials_feautured[0]->ID, 'description', true) . '</p>';
       
       // Check if comment_status is open
       if ($tutorials_feautured[0]->comment_status == 'open') {
         // Count comments for current post
		     $comment_count = get_post_meta($tutorials_feautured[0]->ID, 'comment_count', true);
         
         if ($comment_count == '0') {
           echo '<p><a href="#" class="comments" id="' . $tutorials_feautured[0]->post_name . '">No Comments</a></p>';
				 } else {
				   echo '<p><a href="#" class="comments" id="' . $tutorials_feautured[0]->post_name . '">' . $comment_count . ' Comments</a></p>';
				 }
       } // if (comment_status == open)
										
       echo '<!-- Video Level -->
             <div id="guidpress_video_level">
             <span class="guidepress_title">Level: </span>' . get_post_meta($tutorials_feautured[0]->ID, 'level', true) . '</div>
             <!-- Video Level End -->
             <!-- Video Status -->
             <div id="guidpress_video_status">
             <span class="guidepress_title">Status: </span>
             ' . get_post_meta($tutorials_feautured[0]->ID, 'status', true) . '
             </div>
             <!-- Video Status End -->
             <br style="clear:both;"/>';
  
       // Useful Links
       if ($output_link != '') {
         echo '<div id="guidpress_video_useful_links"><span class="guidepress_title">Useful links: </span>' . $output_link . '</div>';
       }
          
       echo '</div>
             <!-- Main Video End -->
             </div>
             <!-- Main Video End -->
             <!-- Video List -->
             <div id="guidepress_video_list">';
       
       // Foreach post
       foreach ($tutorials as $tutorial) {
         // Can user view this tutorial?
         $youtube = get_post_meta($tutorial->ID, 'youtube', true);

         if ($tutorial->post_content == '' && $youtube == '') {
				   $subscribe = 'premium-only';
         } else {
				   $subscribe = 'everyone';
         }
               		
         echo '<!-- Playlist Video -->
               <a href="#" class="play-video ' . $subscribe . '" id="' . $tutorial->ID . '">
               <div class="guidpress_playlist_video">
               <div class="guidpress_playlist_title">' . $tutorial->post_title . '</div>
               <div class="guidpress_playlist_description">' . get_post_meta($tutorial->ID, 'description', true) . '</div>
               <div class="guidpress_playlist_level">' . get_post_meta($tutorial->ID, 'level', true) . '</div>
               <div class="clear"></div>
               </div>
               </a>
               <!-- Playlist Video End -->
               <div class="clear"></div>';
       
       } // foreach ($tutorials)
               
       echo '<!-- Playlist Video End -->    
             </div>
             <!-- Video List End -->
             <br style="clear:both;"/>
             </div>';
    } else {
      echo '<h2>There are currently no videos for this page!</h2>';
    }
	  echo '</div></div>';
  } // guidepress_tab_content
  
  
  // Admin bar modification
  function add_admin_bar_guidepress() {
    global $wp_admin_bar;
    
    if ( !is_super_admin() || !is_admin_bar_showing() || get_option('client_gp_active') != 'true') {
      return;
    }
    
    $wp_admin_bar->add_menu( array('id' => 'guidepress_menu',
                                   'meta' => array('class'=>'guidepress_menu'),
                                   'title' => 'GuidePress Video tutorials',
                                   'href' => '#'));
  } // add_admin_bar_guidepress
  
  
  // Divs for Dialogs
  function prep_dialog() {
    echo '<div class="cgp-send-notice-dialog"></div>';
    echo '<div class="cgp-video-dialog"></div>';
  } // prep_dialog
  
  
  // Contextual Help Videos
  function help_videos($contextual_help, $screen_id, $screen) {
    $contextual_help .= '<a href="#" class="cgp_show_contextual_help">Show Wordpress Contextual Help</a>';
    $contextual_help .= '<h1>Help Videos</h1>';
    $contextual_help .= self::client_gp_widget_function();
    return $contextual_help;
  } // help_videos
  
  
  //
  function client_gp_widget_function() {
    global $wpdb, $current_screen;
    // Vars
    $output = '';
    
    // Setup data
    $url = $_SERVER['PHP_SELF'];
    $category = $current_screen->id;
    
    $output .= '<div class="cgp video-container">';
    $output .= '<div id="error">Please register if you wish to view this video!</div>';
              
    // Fetch all videos for current menu
    $videos = $wpdb->get_results('SELECT * FROM ' . $wpdb->prefix . 'cgp_videos WHERE category = "' . $category . '" ORDER by position ASC');
    
    // If there are any videos
    if ($videos) {
      // Verify User
      $inputs = client_gp::fetch_options();
      // Make the call for user verification
      $params = "?action=verify&username=" . $inputs['username'] . "&password=" . $inputs['password'];
      $response = client_gp_ajax::curl($params);
      $unserialized_response = unserialize($response);
      // Get user type
      $user_type = $unserialized_response['user_type'];
      // End Verify Proccess
      
      foreach($videos as $video) {
        
        // If youtube video exists
        if (!empty($video->youtube)) {
          $video_box = '<a class="various iframe" href="http://www.youtube.com/embed/' . $video->youtube . '?autoplay=0"><img src="http://img.youtube.com/vi/' . $video->youtube . '/0.jpg" width="240" alt="' . $video->title . '" /></a>';
        }
        
        // If vimeo video exists
        if (!empty($video->vimeo)) {
          // Is user allowed to see vimeo?
          if ($user_type != 2) {
            $video_box = '<a class="various iframe" href="http://player.vimeo.com/video/' . $video->vimeo . '?title=0&amp;byline=0&amp;portrait=0&amp;color=adadad"><img src="' . self::vimeo_thumb($video->vimeo) . '" alt="' . $video->title . '" width="240" /></a>';
          } else {
            $video_box = '<a class="various-inline" title="" href="#error"><img src="' . self::vimeo_thumb($video->vimeo) . '" alt="' . $video->title . '" width="240" /></a>';
          }
        } // if (!empty($video->vimeo))
        
        
        $output .=  '<div class="video-box" id="box-' . $video->id . '">
                    <b>' . $video->title . '</b><br/>
                    ' . $video_box . '
                    <br/>
                    <a href="#" class="cgp-send-notice" id="' . $video->title . '">Send notice to master server</a>
                    </div>';
         
      } // foreach ($videos)
    } else {
      $output .= '<p>No Videos for current menu :(</p>';
    } // if ($videos)
    
    $output .=  '<br style="clear:both;"/>';
    $output .=  '</div>'; 
    
    return $output;
  } // client_gp_widget_function

  
  //Register Settings for admin panel
  function register_settings() {
      register_setting('client_gp', 'client_gp', array('client_gp', 'sanitize_settings'));
  } // register_settings
  
  
  //Sanitize function for our settings panel
  function sanitize_settings($values) {
    //Output
    return $values;  
  } // sanitize_settings
    
  
  // Options page
  function options_page() {
    // Does user have enought privilages to access this page?
    if (!current_user_can('manage_options'))  {
      wp_die('You do not have sufficient permissions to access this page.');
    }
    
    // Retrive options for inputs
    $inputs = self::fetch_options();
    
    echo '<div class="wrap">
          <div class="icon32" id="icon-tools"><br /></div>
          <h2>GuidePress Configuration</h2>'; 
    
    // Response Message Class
    if ($inputs['status'] == '1') {
      $response_class = "updated";
    } else {
      $response_class = "error";
    }
    
    // Response Message body
    if ($inputs['message'] == '' || empty($inputs['message'])) {
      $inputs['message'] = 'Please insert your username and api-key which you have recived from LearnPress Shop!';
    }
    
    // New Table
    echo '<table class="wp-list-table widefat fixed" style="width:400px;">';
    echo '<thead>';
      echo '<tr>';
        echo '<th>';
          echo 'Subscription';
        echo '</th>';
      echo '</tr>';
    echo '</thead>';

    // If subscription is not active
    if (get_option('client_gp_active') != 'true') {
      echo '<tbody>';
        echo '<tr>';
          echo '<td>';
          echo '<p>Subscription: <strong>Not Active</strong> <a href="#" class="subscribe-dialog">Activate Now!!!</a></p>';
          echo '</td>';
        echo '</tr>';
        echo '<tr>';
          echo '<td>';
          echo '<p>GuidePress is almost ready. You must <a href="#" class="subscribe-dialog">activate your subscription</a> before you can start using GuidePress.</p>';
          echo '</td>';
        echo '</tr>';
      echo '</tbody>';
    } else {
      $subscription_type = get_option('client_gp_type');
      echo '<tbody>';
        echo '<tr>';
          echo '<td>';
          echo '<p>Subscription: <strong>Active</strong></p>';
          echo '</td>';
        echo '</tr>';
        echo '<tr>';
          echo '<td>';
          echo '<p>Subscription type: <strong>' . $subscription_type . '</strong></p>';
          echo '</td>';
        echo '</tr>';
        echo '<tr>';
          echo '<td>';
          echo '<p>Subscriber\'s id: <strong>' . $inputs['username'] . '</strong></p>';
          echo '</td>';
        echo '</tr>';
        if ($subscription_type == 'Free') {
          echo '<tr>';
            echo '<td>';
            echo '<p>Sign up for our <a href="http://guidepress.net/pricing" target="_blank">Premium Subscription</a> to access our complete library of video tutorials.</p>';
            echo '</td>';
          echo '</tr>';
        } elseif ($subscription_type == 'Premium') {
          echo '<tr>';
            echo '<td>';
            echo '<p>Please <a href="http://guidepress.net/contact" target="_blank">contact us</a> if you want to detach this subscription from this website or are having problems using GuidePress.</p>';
            echo '</td>';
          echo '</tr>';
        }
      echo '</tbody>';
    }
    
    echo '</table>';
    
    echo '<br/>';
    
    // Update Video Library
    echo '<table class="wp-list-table widefat fixed" style="width:400px;">';
    echo '<thead>';
      echo '<tr>';
        echo '<th>';
          echo 'Video Library';
        echo '</th>';
      echo '</tr>';
    echo '</thead>';
    
    echo '<tr>';
      echo '<td>';
        echo '<p>GuidePress has a built-in library that holds into for all the video tutorials. This library is updated automatically with all the latest videos. But you can also update it manually by clicking this button.</p>';
      echo '</td>';
    echo '</tr>';
          
    echo '<tr>';
      echo '<td>';
        echo '<span id="cgp-update-message" style="display:none;">This might take a few seconds!</span>';
        echo '<span id="cgp-updated-message" style="display:none;"></span>';
        echo '<p><input type="submit" id="update-videos" name="update-videos" value="Update Video Library" class="button-secondary" /></p>';
      echo '</td>';
    echo '</tr>';
    
    echo '</table>';
    
    echo '<form action="options.php" method="post" id="options-form">';
    
    settings_fields('client_gp');
    
    echo '<input type="hidden" name="username" id="username" value="' . $inputs['username'] . '" />';
    echo '<input type="hidden" name="password" id="password" value="' . $inputs['password'] . '" />';
    
    // echo '<p class=""><input type="submit" value="Save Changes" class="button-primary" name="Submit" /></p>';
    
    echo '</form>';
    echo '</div>';
  } // optionspage

  
  // Leave comment dialog
  function leave_comment() {	
    // Retrive options for inputs
    $inputs = self::fetch_options();
		$username = $inputs['username'];
				
    echo '<div id="comments-gp-dialog">';
    // Logo
    echo '<div align="center">
          <img src="' . plugins_url('images/logo.png', __FILE__) . '" width="275" height="95" alt="GuidePress" />
          </div>';
          
    // Details
    echo '<div align="center" class="details">';
    
    echo '<p>Please fill out your comment.</p>';
    
    // Form
    echo '<form method="POST" action="" id="comment-form">';
    echo '<input type="hidden" name="post_username" id="post_username" value="' . $username . '" />';
		echo '<input type="hidden" name="post_id" id="post_id" value="" />';
		echo '<input type="hidden" name="domain" id="domain" value="' . $_SERVER['HTTP_HOST'] . '" />';
    echo '<label for="comment"><strong>Comment:</strong><br/>
          <textarea id="comment" name="comment" cols="50" rows="10"></textarea>
    			</label><br/>';
    echo '<input type="button" name="send-form-submit" id="send-form-submit" value="Send your comment!" />';
    echo '</form>';
    
    echo '</div>';
    // End Details
    echo '</div>';
				
  } // leave_comment
  
  
  // display warning if test were never run
  function activate_subscription_message() {
    
  	if (get_option('client_gp_active') != 'true') {
      echo '<div id="message" class="updated"><p><strong>GuidePress is almost ready.</strong> You must <a href="#" class="subscribe-dialog">activate your subscription</a> for it to work.</p></div>';
		}
    
    echo '<div id="subscribe-dialog" style="display:none;">';
    // Logo
    echo '<div align="center">
          <img src="' . plugins_url('images/logo.png', __FILE__) . '" width="275" height="95" alt="GuidePress" />
          </div>';
          
    // Retrive options for inputs
    $inputs = self::fetch_options();
          
    // Details
    echo '<div align="center" class="details">';
    
    echo '<p>Please login using your GuidePress.net account details.</p>';
    
    // Form
    echo '<form method="POST" action="" id="verify-form">';
    echo '<label for="username_dlg"><strong>Username:</strong> <input type="text" id="username_dlg" name="username_dlg" value="' . $inputs['username'] . '" /></label>';
    echo '<br/>';
    echo '<label for="password_dlg"><strong>Password:</strong> <input type="password" id="password_dlg" name="password_dlg" value="' . $inputs['password'] . '" /></label>';
    echo '<br/>';    
    echo '<input type="submit" name="verify-form-submit" id="verify-form-submit" value="Verify!" />';
    echo '</form>';
    
    echo '<br/>';
    
    // Forgot username / dont have acc?
    echo '<a href="http://guidepress.net/wp-login.php?action=lostpassword" target="_blank" class="forgotten-username">Forgotten username?</a> or <a href="http://guidepress.net/wp-login.php?action=register" target="_blank" class="create-an-account">Create an account</a>';
    
    echo '</div>';
    // End Details
    
    echo '</div>';
    return;
  } // warning
  
  
  function admin_enqueue_scripts() {
      wp_enqueue_style('wp-jquery-ui-dialog');
      wp_enqueue_style('client-gp-style', plugins_url('css/gp.css', __FILE__), array(), '1.0.0');
  } // admin_enqueue_scripts
  
  
  function admin_footer_enqueus() {
      wp_enqueue_script('gp-video-client-common', plugins_url('js/gp-common.js', __FILE__), array(), '1.0.0');
      wp_enqueue_script('jquery-ui-dialog');
  } // admin_footer_enqueus
  
  
  // Create new admin menu
  function admin_menu() {
     add_options_page('GuidePress', 'GuidePress', 'manage_options', 'client-gp', array('client_gp', 'options_page')); 
  } // function admin_menu
  
  
  // add settings link to plugins page
  function plugin_action_links($links) {
    $settings_link = '<a href="options-general.php?page=client-gp">Settings</a>';
    array_unshift($links, $settings_link);
    return $links;
  } // function plugin_action_links
  
  
  // retrive options
  function fetch_options($options = 'client_gp') {
    return get_option($options);
  } // fetch_options
  
  
  // Extract Youtube ID from URL
  function youtube_id($url) {
    if (preg_match('#(?<=v=)[a-zA-Z0-9-]+(?=&)|(?<=v\/)[^&\n]+|(?<=v=)[^&\n]+|(?<=youtu.be/)[^&\n]+#', $url)) {
      // preg_match
      preg_match('#(?<=v=)[a-zA-Z0-9-]+(?=&)|(?<=v\/)[^&\n]+|(?<=v=)[^&\n]+|(?<=youtu.be/)[^&\n]+#', 
                 $url, $matches);
      // return
      return $matches[0];
    } else {
      // return
      return $url;
    }
  } // youtube_id
  
  
  // Activate plugin
  function activate() {
  } // activate
  
  
  // Deactivate plugin
  function deactivate() {
    wp_clear_scheduled_hook('gp_update_cron');
    delete_option('client_gp_active');
    delete_option('client_gp_type');
    delete_option('client_gp');
  } // deactivate
  
  
  // Update cRon
  function gp_update_cron() {
    global $wpdb;
    
    // Vars
    $errors = array();
    $output = array();
    $total_videos = 0;
    $client_status = 'Free';
    $tmp_meta = '';
    $inputs = self::fetch_options();
    
    // Setup username and domain data
    $params = "?username=" . $inputs['username'] . "&password=" . $inputs['password'] . "&domain=" . $_SERVER['HTTP_HOST'];
    
    $ch = curl_init();
    
    curl_setopt($ch, CURLOPT_URL, API_URL . $params);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 0);
    
    // grab URL and pass it to the browser
    $response = curl_exec($ch);
    curl_close($ch);

    // Unserialize response
    $unserialized_response = unserialize($response);
    
    // If Master Response is valid and contains required data...
    if ($unserialized_response && is_array($unserialized_response) && !isset($unserialized_response['error'])) {
      // Delete Old tutorials
      $posts = get_posts(array('post_type'=>'video-tutorials', 'numberposts'=>'-1'));
      foreach ($posts as $post) {
        wp_delete_post($post->ID);
      }

      // Loop for inserting new videos in Client database
      foreach ($unserialized_response as $post_item) {
  
        // Save post meta to tmp and unset
        $tmp_meta = $post_item->post_meta;
        
        // Unset unneeded data
        unset($post_item->post_meta);
        unset($post_item->ID);
        
        // If video does not have "exclude" flag status, retrive it
        if ($tmp_meta['status'] != 'exclude') {
          
          // Video level (Beginner, advanced etc..)
          $tmp_meta['level'] = str_replace('-', ' ', $tmp_meta['level']);
          $tmp_meta['level'] = ucfirst($tmp_meta['level']);
          // Video status (Up to date, old but relevant etc...)
          $tmp_meta['status'] = str_replace('-', ' ', $tmp_meta['status']);
          $tmp_meta['status'] = ucfirst($tmp_meta['status']);
          
          // Add new post to custom post type "video-tutorials"
          $new_post_id = wp_insert_post($post_item);
          
          // Update youtube video meta for new post
          update_post_meta($new_post_id, 'youtube', $tmp_meta['youtube']);
          
          // Update videopress meta for new post
          if (isset($tmp_meta['videopress'])) {
            // And set the new client status as "Premium"
            $client_status = 'Premium';
            update_post_meta($new_post_id, 'videopress', $tmp_meta['videopress']);
          }
          
          // Update category settings for new post
          if (is_array($tmp_meta['category'])) {
            foreach ($tmp_meta['category'] as $cat) {
              $name = explode('.', $cat->name);
              add_post_meta($new_post_id, 'category', $name[0]);
            }
          }
                    
          // Update post order
          if ($tmp_meta['order']) {
            foreach ($tmp_meta['order'] as $ord => $pos) {
              foreach ($pos as $order => $value) {
                update_post_meta($new_post_id, $order, $value);
              }
            }
          }
          
          // Get all other meta data for new post
          update_post_meta($new_post_id, 'description', $tmp_meta['description']);
          update_post_meta($new_post_id, 'level',       $tmp_meta['level']);
          update_post_meta($new_post_id, 'status',      $tmp_meta['status']);
          update_post_meta($new_post_id, 'links',       $tmp_meta['links']);
          update_post_meta($new_post_id, 'comment_count',       $tmp_meta['comment_count']);
          
          // Count how many videos are added
          $total_videos++;
        } // if ($tmp_meta['status'] != 'exclude')
      } // foreach ($unserialized_response)

      // Update client status - just for refreshing status from Free to Premium or otherwise
      update_option('client_gp_type', $client_status);
    } 
  } // gp_update_cron
  
  
} // class client_gp

add_action('init', array('client_gp', 'init'));
register_activation_hook(__FILE__, array('client_gp', 'activate'));
register_deactivation_hook(__FILE__, array('client_gp', 'deactivate'));

// Cron
add_action('gp_update_cron', 'gp_update_cron', 1);

//make new schedule round
if (!wp_next_scheduled('gp_update_cron')) {
  wp_schedule_event(time(), 'hourly', 'gp_update_cron');
}

// Cron Function
function gp_update_cron() {
  client_gp::gp_update_cron();
} // gp_update_cron3

?>