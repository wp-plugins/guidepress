// Vars
var tmp_sortable = [];
var sorted_list = [];

jQuery(document).ready(function($) {
		
  // Add active class to current viewed video
  $('.guidpress_playlist_video:first', 'div#guidepress_video_list').addClass('active');
  
  // Add GuidePress Help Content
  $('#screen-meta').append($('#guidepress_content').html());
  
  // Hide guidepress video container on contextual help click
  $('#contextual-help-link').live('click', function(){
				$('#guidepress_container').hide();
  });
  
  // Hide guidepress video container on contextual help click
  $('#show-settings-link').live('click', function(){
				$('#guidepress_container').hide();
  });
  
  // Open subscribe dialog
  $('.subscribe-dialog').click(function(){
    $('#subscribe-dialog').dialog({ title: 'GuidePress Subscription' })
                          .dialog('open');
    $('div.create-an-account', '#subscribe-dialog').hide();
    $('div.forgotten-username', '#subscribe-dialog').hide();
    $('div.details', '#subscribe-dialog').show();
    return false;
  });
  
  
  // Open Commenting Dialog - TODO: Do we need this?
  $('.comments').live('click', function(){
    var post_id = $(this).attr('id');
  	$('#post_id', '#comments-gp-dialog').val(post_id);
    $('#comments-gp-dialog').dialog({ title: 'GuidePress Comments' })
    												.dialog('open');
    return false;
  });
  
  
  // Sending Comments - TODO: Do we need this?
  $('#send-form-submit').live('click', function(){
  		var obj = $(this);
  		obj.attr('disabled','disabled');
  		obj.val('Sending...');
      
  		var data = {action: 'send_comment', 
  								username: jQuery('#post_username', '#comments-gp-dialog').val(),
  								post_id: jQuery('#post_id', '#comments-gp-dialog').val(),
  								comment: jQuery('#comment', '#comments-gp-dialog').val(),
  								domain: jQuery('#domain', '#comments-gp-dialog').val()};
                  
    // ajax call
    jQuery.ajaxSetup({async:false});
    jQuery.post(ajaxurl, data, function(response) {
  	  obj.removeAttr('disabled');
  		obj.val('Send your comment!');
    	if (!response) {
    	  $('#comment', '#comments-gp-dialog').val('');
				alert('Your comment has not been sent! Please try again!');
				window.location.reload();
      } else {
    	  $('#comment', '#comments-gp-dialog').val('');
				alert('Your comment has been successfully sent! Thank you!');
				window.location.reload();
      }
		});	  
		return false;
  });
  
  
  // Guidepress account verification
  $('#verify-form-submit').live('click', function(){
    // Clean our logging div elements
    $('.error', '#subscribe-dialog').remove();
    $('.updated', '#subscribe-dialog').remove();
    
    var data = {action: 'client_verification', username: jQuery('#username_dlg').val(), password: jQuery('#password_dlg').val()};
    
    // ajax call
    jQuery.ajaxSetup({async:false});
    jQuery.post(ajaxurl, data, function(response) {
      if (response == '0' || response == 'false') {
        $('#subscribe-dialog').prepend('<div class="error"><p>Your account couldn\'t be verified!</p></div>');
      } else {
        // data
        var data = {action: 'client_api', username: jQuery('#username_dlg').val(), password: jQuery('#password_dlg').val()};
        // ajax call to update video library
        jQuery.post(ajaxurl, data, function(response) {
          $('#subscribe-dialog').prepend('<div class="updated"><p>Your account was successfully verified!</p></div>');
          if (!response) {
            alert('Bad AJAX response. Please reload the page.');
          } else {
            var json_response = jQuery.parseJSON(response);
            jQuery('.updated', '#subscribe-dialog').append('<p>Your GuidePress video library has been updated!</p>');
            if (json_response.errors_number > 0) {
              jQuery('.updated', '#subscribe-dialog').append('<p>There were some errors while updating your GuidePress video library</p>');
              jQuery('.updated', '#subscribe-dialog').html('<br/><b>Errors:</b> ' + json_response.errors);
            }
          } // if
        }); // jQuery.post
      } // if
    }); // jQuery.post
    
    return false;
  });

  
  
  // Hide wordpress contextual help and screen options on guidpress tab open
  $('.guidepress_menu').click(function(){
				$('#contextual-help-wrap').hide();
				$('#screen-options-wrap').hide();
  });
  
  
  // Admin bar menu click
  $('.guidepress_menu').toggle(function(){
    $('#screen-meta').slideDown('fast', function() {
		  $('#contextual-help-link').removeClass('screen-meta-active');
			$('#contextual-help-link-wrap').hide();
			$('#show-settings-link').removeClass('screen-meta-active');
			$('#screen-options-link-wrap').hide();
			// Hide wordpress contextual help and screen options
			$('#contextual-help-link').hide();
			$('#show-settings-link').hide();
      // Guidepress
      $('#guidepress_container').show();
    });
    return false;
  }, function() {
	  $('#contextual-help-link').removeClass('screen-meta-active');
    $('#contextual-help-link-wrap').show().css({visibility:'visible'});
		$('#show-settings-link').removeClass('screen-meta-active');
		$('#screen-options-link-wrap').show().css({visibility:'visible'});
  	// Guidepress show
		$('#contextual-help-link').show();
		$('#show-settings-link').show();
		// Guidepress
    $('#guidepress_container').hide();
    $('#screen-meta').slideUp('fast');
    return false;
  });
  
  
  // Free user clicked on premium video - show subscription message!
  $('.play-video.premium-only').live('click', function(){
    var details = $('.details', '#subscribe-dialog');
    
  	if (!$('div.error', details).length) {
  	  details.prepend('<div class="error"><p>You cannot view this tutorial using your current <strong>free</strong> subscription. If you wish to view this video you should upgrade your account to <strong>premium</strong> subscription!</p></div>');
    }
	  
    // Open Subscribe dialog
    $('#subscribe-dialog').dialog({ title: 'GuidePress Subscription' })
                          .dialog('open');
    
    $('div.create-an-account', '#subscribe-dialog').hide();
    $('div.forgotten-username', '#subscribe-dialog').hide();
    $('div.details', '#subscribe-dialog').show();
		return false;
  });
  
  
  // PlayVideo Function
  $('.play-video.everyone').live('click', function() {
    var tutorial_id = $(this).attr('id');
    var obj = $(this);
    
    // Set active
    $('.guidpress_playlist_video').removeClass('active');
    $('.guidpress_playlist_video', obj).addClass('active');
    
    // Vars
    var data = {action: 'fetch_video', video_id: tutorial_id};
    
    // jQuery.Post    
    jQuery.post(ajaxurl, data, function(response) {
      if (!response) {
        alert('Bad AJAX response. Please reload the page.');
      } else {
        var output = $.parseJSON(response);
        $('#guidepress_video_container').empty()
                                        .append(output.video);
        $('#guidpress_video_details').empty()
                                     .append(output.details);
      } // if
    }); // jQuery.post                          
    return false;
  });
  
  
  // Send Notice/Comment to Master - TODO: Do we need this?
  jQuery('.cgp-send-notice').click(function(){
    // Vars
    var data = {action: 'notice_dialog', video_title: jQuery(this).attr('id')};
    // jQuery.Post    
    jQuery.post(ajaxurl, data, function(response) {
      if (!response) {
        alert('Bad AJAX response. Please reload the page.');
      } else {
        jQuery('.cgp-send-notice-dialog').html(response)
                                         .dialog({ title: 'Send notice to master' })
                                         .dialog('open');
      } // if
    }); // jQuery.post
  }); // jQuery.Click - .clp-send-notice - Send Notice
  
  
  // jQuery Send Notice Dialog - TODO: Do we need this?
  $('.cgp-send-notice-dialog').dialog({
      autoOpen: false,
      height: 400,
      width: 500,
      dialogClass: 'wp-dialog',
      modal: true,
      buttons: [{
        text: 'Send', class: 'button-primary',
        'click': function(){
                   var data = {action: 'send_notice', 
                               video_title: jQuery('#video_title', '#cgp-send-notice-form').val(), 
                               notice: jQuery('#notice', '#cgp-send-notice-form').val()}

                   // Ajax Call
                   jQuery.post(ajaxurl, data, function(response){
                     if (!response) {
                       alert('Bad AJAX response. Please reload the page.');
                     } else {
                       $(':input', this).not(':hidden').val('');
                       alert('Your notice has been sent! thank you!');
                     }
                   });
          $(this).dialog("close");
        }
      }, { text: 'Cancel', class: 'button-secondary',
        'click': function() {
            $('.ui-widget-overlay').unbind('click');
            $('input', this).val('');
            $(this).dialog("close");
          }
        }]
  }); // jQuery Send Notice Dialog
  
  
  // jQuery View Video Dialog
  $('.cgp-video-dialog').dialog({
      autoOpen: false,
      height: 450,
      width: 550,
      dialogClass: 'wp-dialog',
      modal: true,
      buttons: [{ text: 'Close', class: 'button-secondary',
        'click': function() {
            $('.ui-widget-overlay').unbind('click');
            $(this).dialog("close");
          }
        }]
  }); // jQuery Send Notice Dialog
  
  
  // Jquery Video Preview Request
  $('.video-preview').click(function(){
    
    if ($(this).hasClass('youtube')) {
      var type = 'youtube';
    } else {
      var type = 'vimeo';
    }
    
    var title = jQuery('img', this).attr('alt');
    var data = {action: 'preview_video', id: jQuery(this).attr('id'), type: type};
    
    jQuery.post(ajaxurl, data, function(response) {
      if (!response) {
        alert('Bad Ajax Call!');
      } else {
        $('.cgp-video-dialog').html(response)
                              .dialog({title: title})
                              .dialog('open');
      }
    });
    
    return false;
  }); // jquery video preview
  
  
  // Retrive all Videos
  jQuery('#update-videos').click(function(){
    var button = jQuery(this);
    button.attr('disabled','disabled');
    jQuery('#cgp-update-message').show();
    
    // data
    var data = {action: 'client_api', username: jQuery('#username').val(), password: jQuery('#password').val()};
    // ajax call
    jQuery.ajaxSetup({async:false});
    jQuery.post(ajaxurl, data, function(response) {
      if (!response) {
        alert('Bad AJAX response. Please reload the page.');
      } else {
        var json_response = jQuery.parseJSON(response);
        jQuery('#cgp-update-message').hide();
        button.removeAttr('disabled');
        jQuery('#cgp-updated-message').show().empty().html('<b>Total videos updated:</b> ' + json_response.total_videos
                                                   + '<br/><b>Total errors:</b> ' + json_response.errors_number);
        if (json_response.errors_number > 0) {
          jQuery('#cgp-updated-message').html('<br/><b>Errors:</b> ' + json_response.errors);
        }
      } // if
    }); // jQuery.post
  }); // Retrive All Videos - #update-videos
  
  
  // Options Page Stuff
  jQuery('#options-form').submit(function(){
    // data
    var data = {action: 'client_verification', 
                username: jQuery('#username', this).val(), 
                password: jQuery('#password', this).val(), 
                api_key: jQuery('#api_key', this).val()};
  
    // ajax call
    jQuery.ajaxSetup({async:false});
    jQuery.post(ajaxurl, data, function(response) {
      if (!response) {
        alert('Bad AJAX response. Please reload the page.');
      } else {
        var json_response = jQuery.parseJSON(response);
        jQuery('#status').val(json_response.status);
        jQuery('#message').val(json_response.message);
      } // if
    }); // jQuery.post
  }); // jQuery.Submit - #options-form
  
  
  // Define Subscribe Dialog
  $('#subscribe-dialog').dialog({
      autoOpen: false,
      height: 400,
      width: 500,
      dialogClass: 'wp-dialog',
      modal: true,
      buttons: [{
        text: 'Close', class: 'button-secondary',
        'click': function() {
            $('.ui-widget-overlay').unbind('click');
            //$('input', this).val('');
            $(this).dialog("close");
            //window.location.reload();
          }
      }]
  }); // jQuery Subscribe dialog
  
  
  // Define Commenting dialog - TODO: Do we need this?
  $('#comments-gp-dialog').dialog({
      autoOpen: false,
      height: 480,
      width: 500,
      dialogClass: 'wp-dialog',
      modal: true,
      buttons: [{
        text: 'Close', class: 'button-secondary',
        'click': function() {
            $('.ui-widget-overlay').unbind('click');
            $('#comment').val('');
            $(this).dialog("close");
            //window.location.reload();
          }
      }]
  }); // jQuery Comment dialog
}); // jQuery