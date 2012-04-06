<?php
/*
  Client GuidePress Ajax
  AJAX endpoint
  (c) 2011. Web factory Ltd
*/ 

define('API_URL', 'http://wpdev4.webfactoryltd.com/wp-content/plugins/master-guidepress/index.php');
define('E_MAIL', 'hrvoje.krbavac@gmail.com');

class client_gp_ajax extends client_gp {
  
  
  // Show embed code for user selected video
  function ajax_callback_fetch_video() {
    // Vars
    $output = array();
    $output_link = '';
    $comment  = '';
    
    // Get video ID
    $video_id = trim($_POST['video_id']);
    
    // Retrive custom post type
    $post = get_post($video_id);
    
    // If videopress is available show it else show youtube video
    if (get_post_meta($post->ID, 'videopress', true) != '') {
      $output['video'] = apply_filters('the_content', get_post_meta($post->ID, 'videopress', true));
    } else {
      // Get only youtube video ID!
      $youtube_id = self::youtube_id(get_post_meta($post->ID, 'youtube', true));
      $output['video'] = '<iframe width="525" height="297" src="http://www.youtube.com/embed/' . $youtube_id . '?rel=0" frameborder="0" allowfullscreen></iframe>';
    }
    
    // Fetch links for current video
    $links = get_post_meta($post->ID, 'links', true);
    if (is_array($links) && count($links) > 0) {
      foreach ($links as $link) {
        $output_link .= '<a href="' . $link['url'] . '">' . $link['name'] . '</a>, ';
      }
      $output_link = rtrim($output_link, ', ');
    } // if (is_array($links))
    

    // If comments are allowed
    if ($post->comment_status == 'open') {
      $comment_count = get_post_meta($post->ID, 'comment_count', true);
      if ($comment_count == '0') {
        $comment = '<p><a href="#" class="comments" id="' . $post->post_name . '">No Comments</a></p>';
			} else {
			  $comment = '<p><a href="#" class="comments" id="' . $post->post_name . '">' . $comment_count . ' Comments</a></p>';
			}
		}
    
    // Get video details (Description, Comments, Level, Status)
    $output['details'] = '<p>' . get_post_meta($post->ID, 'description', true) . '</p>
    											' . $comment . '
                          <div id="guidpress_video_level"><span class="guidepress_title">Level:</span>
                          ' . get_post_meta($post->ID, 'level', true) . '</div>
                          <div id="guidpress_video_status"><span class="guidepress_title">Status:</span>
                          ' . get_post_meta($post->ID, 'status', true) . '</div>
                          <br style="clear:both;"/>';
    
    // If video has some links
    if ($output_link != '') {
      $output['details'] .= '<div id="guidpress_video_useful_links">'
                          . '<span class="guidepress_title">Useful links: </span>'
                          . $output_link
                          . '</div>';
    } // if ($output_link != '')
    
    // Echo output in JSON
    echo json_encode($output);
    die();
  } // ajax_callback_fetch_video
  
  
  // Retrive all videos from Master Server
  function ajax_callback_client_api() {
    global $wpdb;
    
    // Vars
    $errors = array();
    $output = array();
    $total_videos = 0;
    $client_status = 'Free';
    $tmp_meta = '';
    
    // Setup username and domain data
    $params = "?username=" . $_POST['username'] . "&password=" . $_POST['password'] . "&domain=" . $_SERVER['HTTP_HOST'];
    
    // Send request by CURL to Master Server
    $response = self::curl($params);

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
      
    } else {
      // There has been sam error? Return it!
      $errors['wpdb'][] = $unserialized_response['error'];
    } // if ($unserialized_response ...)

    // How many videos have we fetched and how many errors :(
    $output['total_videos'] = $total_videos;
    $output['errors_number'] = count($errors['wpdb']);
    
    // If there are some errors
    if (isset($errors['wpdb'])) {
      foreach ($errors['wpdb'] as $key => $error) {
        $output['errors'] .= $error . ', ';
      }
      $output['errors'] = rtrim($output['errors'], ', ');
    } // if (isset($errors['wpdb']))
    
    // Echo JSON
    echo json_encode($output);
    die();
  } // ajax_callback_client_api
  
  
  // Client Credentials Verification
  function ajax_callback_client_verification() {
    
    // Setup username and domain data
    $params = "?action=verify&username=" . trim($_POST['username']) . "&password=" . trim($_POST['password']) . "&domain=" . $_SERVER['HTTP_HOST'];
    
    // Fetch plugin options
    $options = client_gp::fetch_options();
    
    // Update username
    $options['username'] = trim($_POST['username']);
    $options['password'] = trim($_POST['password']);
    update_option('client_gp', $options);
    
    // Make a call to Master Server
    $response = self::curl($params);

    // If response is 0 or false
    if ($response == '0') {
      // Deactivate client plugin and unset user type
      update_option('client_gp_active', 'false');
      update_option('client_gp_type', '');
    } else {
      // Activate client plugin and set user type
      update_option('client_gp_active', 'true');
      if ($response == 1) {
        $response_type = 'Free';
      } else {
        $response_type = 'Premium';
      }
      update_option('client_gp_type', $response_type);
    }
    
    // Echo response
    echo $response;    
    die();
  } // ajax_callback_client_verification
  
  
  // Callback for sending comments to Master plugin
  function ajax_callback_send_comment() {
    // Setup data for call
    $data['username'] = trim($_POST['username']);
    $data['post_id']  = trim($_POST['post_id']);
    $data['comment']  = trim($_POST['comment']);
    $data['domain']   = trim($_POST['comment']);
		
    // Make the call to Master plugin - Send the comment data
    $output = self::curl('?action=leave_comment&username=' . $data['username'] . '&domain=' . $data['domain'] . '&post_id=' . $data['post_id'] . '&comment=' . urlencode($data['comment']));
		
    // Echo output in JSON
		echo $output;
		die();
  } // ajax_callback_send_comment
  
  
  // Curl Function
  function curl($params) {
    $ch = curl_init();
    
    curl_setopt($ch, CURLOPT_URL, API_URL . $params);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 0);
    
    // grab URL and pass it to the browser
    $response = curl_exec($ch);
    curl_close($ch);
    
    return $response;
  } // curl

  
} // class client_gp_ajax

?>