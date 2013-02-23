<?php
/**
 * Pro Events 1.0
 * Advanced calendar events.
 *  
 * Language pack.
 * 
 * By Jared Williams
 * Copyright 2012
 * 
 * Website: http://www.jaredwilliams.com.au
 *  
 * Please do not redistribute or sell this plugin.
 */

define("IN_MYBB", 1);
require_once "global.php";

define('PAGE_URL',		'events.php');


//If the plugin is ready...
if (!function_exists('proevents_activate')) {
	die('Plugin has not been activated! Please contact your administrator!');
}


$lang->load("proevents");


// //Check if they are allowed to access the store...
// $isallowed = proevents_is_allowed();
if ($mybb->user['uid'] > 0)		$isallowed = true;

// //If not in allowed usergroup don't allow them...
if (!$isallowed) {
	error_no_permission();
}


//Check if they are a moderator... TODO: Check!
#$ismoderator = proevents_is_moderator();


//Get event info...
$eventid = intval($mybb->input['eventid']);
$event = proevents_event_getinfo($eventid);

//Check if creator...
if ($mybb->user['uid'] == $event['userid']) {
	$iscreator = true;
} else {
	$iscreator = false;
}

//TEMP: Is admin or super mod... TODO: Change to setting/use function commented out above!
if ($mybb->user['usergroup'] == 3 || $mybb->user['usergroup'] == 4 || in_array(3, explode(',', $mybb->user['additionalgroups'])) || in_array(4, explode(',', $mybb->user['additionalgroups']))) {
	$iscreator = true;
}


add_breadcrumb($lang->title_default, PAGE_URL);


//****************************************************[ DO CREATE ]
if ($mybb->input['action'] == 'do_event_create') {
	//Validate input...
	$input = proevents_validate_event_input();

	//If valid...
	if (count($input['errors']) == 0) {
		add_breadcrumb($lang->title_event_create);
		
		//If to post thread...
		if (intval($input['forumid']) > 0) {
			$threadid = proevents_post_event_to_forum($input);
		}
		
		$insert = array(
			'userid'		=> $db->escape_string($mybb->user['uid']),
			'threadid'		=> $threadid,
			'name' 			=> $db->escape_string($input['name']), 
			'description' 	=> $db->escape_string($input['description']), 
			'location'		=> $db->escape_string($input['location']),
			'imageurl'		=> $db->escape_string($input['imageurl']), 
			'datestart'		=> $db->escape_string($input['datestart']), 
			'dateend'		=> $db->escape_string($input['dateend']), 
			'allowrsvp'		=> $db->escape_string($input['allowrsvp']), 
			'rsvplimit'		=> $db->escape_string($input['rsvplimit']), 
			'approversvp'	=> $db->escape_string($input['approversvp']),
			'rsvpcount'		=> 0,
			'views'			=> 0,
			'datecreated'	=> time(),
			'dateupdated'	=> time()
		);
		$lastid = $db->insert_query("proevents", $insert);

		redirect(PAGE_URL.'?action=view&eventid='.$lastid, $lang->msg_event_create_success);
	} else {
		//Set up form errors...
		$form_errors = inline_error($input['errors']);
		$mybb->input['action'] = 'create';
		
		$event = $mybb->input;
		
		//TODO: Get this working!
		//if ($input['datestart'])	$datestart = my_date('dS F Y', $input['datestart'], $mybb->user['timezone']);
		//if ($input['dateend'])		$dateend = my_date('dS F Y', $input['dateend'], $mybb->user['timezone']);
	}
}
	
//****************************************************[ DO CANCEL ]
if ($mybb->input['action'] == 'do_event_cancel') {
	if ($iscreator) {
		if ($event) {
			$db->query("DELETE FROM `".TABLE_PREFIX."proevents` WHERE `eventid` = '".$db->escape_string($eventid)."'");

			redirect(PAGE_URL, $lang->msg_event_cancel_success);
		} else {
			error($lang->error_invalid_event);
		}
	} else {
		error($lang->error_not_creator);
	}
}
	
//******************************************************[ DO EDIT ]
if ($mybb->input['action'] == 'do_event_edit') {
	if ($iscreator) {
		if ($event) {
			add_breadcrumb($lang->title_event_edit, PAGE_URL);
			
			//Call an external function to deal with validation...
			$input = proevents_validate_event_input();

			if ($input['name']) {
				$db->query("
					UPDATE `".TABLE_PREFIX."proevents` SET
						`name` 			= '".$db->escape_string($input['name'])."', 
						`description` 	= '".$db->escape_string($input['description'])."', 
						`location`		= '".$db->escape_string($input['location'])."',
						`imageurl`		= '".$db->escape_string($input['imageurl'])."', 
						`datestart`		= '".$db->escape_string($input['datestart'])."', 
						`dateend`		= '".$db->escape_string($input['dateend'])."', 
						`allowrsvp`		= '".$db->escape_string($input['allowrsvp'])."',
						`allowcomments`	= '".$db->escape_string($input['allowcomments'])."', 
						`rsvplimit`		= '".$db->escape_string($input['rsvplimit'])."', 
						`approversvp`	= '".$db->escape_string($input['approversvp'])."', 
						`dateupdated`	= '".time()."'
					WHERE `eventid` = '".$db->escape_string($eventid)."'
				");
				
				redirect(PAGE_URL.'?action=view&eventid='.$eventid, $lang->msg_event_edit_success);
			} else {
				//Set up form errors...
				$form_errors = inline_error($input['errors']);
				$mybb->input['action'] = 'edit';
				
				$event = $mybb->input;
			}
		} else {
			error($lang->error_invalid_event);
		}
	} else {
		error($lang->error_not_creator);
	}
}

//******************************************************[ DO RSVP ]
if ($mybb->input['action'] == 'do_rsvp') {
	if ($event) {
		if ($event['canrsvp']) {
			//Validate input...
			$input = proevents_validate_rsvp_input();
			
			if (count($input['errors']) == 0) {
				add_breadcrumb($lang->title_rsvp_add);
				
				if (!$event['allowcomments']) {
					$input['comment'] = '';
				}
				
				//Insert into database...
				$insert = array(
					'eventid'		=> $db->escape_string($eventid),
					'userid'		=> $db->escape_string($mybb->user['uid']),
					'comment' 		=> $db->escape_string($input['comment']), 
					'notifywhen'	=> $db->escape_string($input['notifywhen']),
					'datecreated'	=> time(),
					'dateupdated'	=> time()
				);
				$lastid = $db->insert_query("proevents_rsvp", $insert);
				
				//Update event...
				$db->query("UPDATE `".TABLE_PREFIX."proevents` SET `rsvpcount` = '".($event['rsvpcount']+1)."' WHERE `eventid` = '".$eventid."'");

				redirect(PAGE_URL.'?action=view&eventid='.$eventid, $lang->msg_rsvp_success);
			} else {
				//Set up form errors...
				$form_errors = inline_error($input['errors']);
				$mybb->input['action'] = 'create';
				
				$event = $mybb->input;
				$event = $mybb->input;
			}
		} else {
			error($lang->error_cannot_rsvp);
		}
	} else {
		error($lang->error_invalid_event);
	}
}

//****************************************************[ DO UNRSVP ]
if ($mybb->input['action'] == 'do_unrsvp') {
	if ($event) {
		//Validate input...
		//$input = proevents_validate_rsvp_input();
		
		$currentuser = proevents_get_event_rsvp($eventid, false, true);
		
		//If actually currently attending...
		if (count($currentuser) > 0) {
			add_breadcrumb($lang->title_rsvp_remove);
			
			//Delete from database...
			$db->query("DELETE FROM `".TABLE_PREFIX."proevents_rsvp` WHERE `eventid` = '".$db->escape_string($eventid)."' AND `userid` = '".$db->escape_string($mybb->user['uid'])."'");
			
			//Update event...
			$db->query("UPDATE `".TABLE_PREFIX."proevents` SET `rsvpcount` = '".($event['rsvpcount']-1)."' WHERE `eventid` = '".$eventid."'");
			
			redirect(PAGE_URL.'?action=view&eventid='.$eventid, $lang->msg_unrsvp_success);
		} else {
			error($lang->error_not_attending);
		}
	} else {
		error($lang->error_invalid_event);
	}
}

//*******************************************************[ CREATE ]
if ($mybb->input['action'] == 'create') {
	add_breadcrumb($lang->title_event_create);
	
	if ($form_errors) {
		eval("\$form_error = \"".$templates->get('proevents_form_errors')."\";");
	}
	
	$forumoptions = proevents_generate_dropdown('forum');
	
	$allowrsvp = 'checked';
	$allowcomments = 'checked';
	$rsvplimit = 0;
	$postthread = 'checked';
	
	//Output templates...
	eval("\$proevents = \"".$templates->get('proevents_event_create')."\";");
	output_page($proevents);
}
	
//*******************************************************[ CANCEL ]
if ($mybb->input['action'] == 'cancel') {
	if ($iscreator) {
		if ($event) {
			add_breadcrumb($lang->title_event_cancel);
			
			eval("\$proevents = \"".$templates->get('proevents_event_cancel')."\";");
			
			output_page($proevents);
		} else {
			error($lang->error_invalid_event);
		}
	} else {
		error($lang->error_not_creator);
	}
}

//*********************************************************[ EDIT ]
if ($mybb->input['action'] == 'edit') {
	if ($iscreator) {
		if ($event) {
			add_breadcrumb($lang->title_event_edit, SCRIPT_URL);
			
			//Form errors...
			if ($form_errors) {
				eval("\$form_error = \"".$templates->get('proevents_form_errors')."\";");
			}
			
			//Select...
			$forumoptions = proevents_generate_dropdown('forum', $forum['forumid']);
			
			//Dates... TODO: These dates cause it to reset to original UNIX timestamp!
			if ($event['datestart']) {
				//$datestart = my_date($mybb->settings['dateformat'].' '.$mybb->settings['timeformat'], $event['datestart'], $mybb->user['timezone']); //TODO: Verify 'Y-m-d H:i:s'
			}
			if ($event['dateend']) {
				//$dateend = my_date($mybb->settings['dateformat'].' '.$mybb->settings['timeformat'], $event['dateend'], $mybb->user['timezone']);
			}
			
			//Checkbox...
			if ($event['allowrsvp']) {
				$allowrsvp = 'checked';
			}
			if ($event['allowcomments']) {
				$allowcomments = 'checked';
			}
			if ($event['approversvp']) {
				$approversvp = 'checked';
			}

			//Output templates...
			eval("\$proevents = \"".$templates->get('proevents_event_edit')."\";");
			output_page($proevents);
		} else {
			error($lang->error_invalid_event);
		}
	} else {
		error($lang->error_not_creator);
	}
}

//*********************************************************[ VIEW ]
if ($mybb->input['action'] == 'view'  && $mybb->input['eventid'] && $mybb->request_method == 'get') {
	if ($event) {
		add_breadcrumb($lang->title_event_view);
		
		//Get the event creator...
		$query 	= $db->simple_select("users","*","`uid` = '{$event['userid']}'");
		$user 	= $db->fetch_array($query);
		$creatorlink = "<a href=\"".get_profile_link($user['uid'])."\">".htmlspecialchars_uni($user['username'])."</a>";
		
		//Basic information...
		$name			= htmlspecialchars($event['name']);
		$location		= htmlspecialchars($event['location']);
		$description	= $event['description']; //Parser below will strip the HTML...
		
		require_once MYBB_ROOT."inc/class_parser.php";
		$parser = new postParser;

		// Set up the parser options.
		$parser_options = array(
			"allow_html" => 0,
			"allow_mycode" => 1,
			"allow_smilies" => 1,
			"allow_imgcode" => 0,
			"allow_videocode" => 0,
			"filter_badwords" => 0
		);
		
		$description = $parser->parse_message($description, $parser_options);
		
		//Dates...
		$datestart		= my_date($mybb->settings['dateformat'], $event['datestart'], $mybb->user['timezone']); //'dS F Y'
		$timestart		= my_date($mybb->settings['timeformat'], $event['datestart'], $mybb->user['timezone']); //'g:ia'

		$dateend 		= my_date($mybb->settings['dateformat'], $event['dateend'], $mybb->user['timezone']);
		$timeend		= my_date($mybb->settings['timeformat'], $event['dateend'], $mybb->user['timezone']);


		$when			= $datestart;

		//Time...
		if ($timestart && $timestart != '12:00am') {
			$when .= ' @ '.$timestart;
		}
		
		//End time...
		if ($event['dateend'] > 0) {
			$when .= ' - '.$dateend;
		
			if ($timeend && $timeend != '12:00am') {
				$when .= ' @ '.$timeend;
			}
		}
		
		//Image...
		if ($event['imageurl']) {
			$imagesrc = htmlspecialchars($event['imageurl']);
		} else {
			$imagesrc = $mybb->settings['bburl'].'/images/proevents/default_image.jpg';
		}
		$image = '<img src="'.$imagesrc.'" width="100%" />';
		
		//Thread...
		if ($event['threadid']) {
			eval("\$threadbutton .= \"".$templates->get('proevents_event_view_threadbutton')."\";");
		}
		
		//RSVPing...
		if ($event['rsvplimit'] > 0) {
			$rsvplimit = str_replace('[rsvplimit]', $event['rsvplimit'], $lang->event_view_rsvplimit);
		}
		if ($event['rsvpcount'] > 0) {
			$rsvpcount = ' ('.$event['rsvpcount'].')';
		}
		
		$currentuser = proevents_get_event_rsvp($eventid, false, true);

		//Get attendees...
		$attendees = proevents_get_event_rsvp($eventid);
		if (count($attendees) > 0) {
			foreach ($attendees as $userid => $rsvp) {
				$comment = array();
			
				$comment['avatar'] 		= '<img src="'.$rsvp['avatar'].'" width="100px" />';
				$comment['username']	= "<a href=\"".get_profile_link($rsvp['uid'])."\">".htmlspecialchars_uni($rsvp['username'])."</a>";
				$comment['message']		= htmlspecialchars($rsvp['comment']);
				$comment['when']		= my_date($mybb->settings['dateformat'], $rsvp['datecreated'], $mybb->user['timezone']);
				$comment['timestart']	= my_date($mybb->settings['timeformat'], $rsvp['datecreated'], $mybb->user['timezone']);
				
				//Time...
				if ($comment['timestart'] && $comment['timestart'] != '12:00am') {
					$comment['when'] .= ' @ '.$comment['timestart'];
				}
			
				eval("\$rsvplist .= \"".$templates->get('proevents_event_view_rsvplist_row')."\";");
				
				$rsvpcount++;
			}
		} else {
			eval("\$rsvplist .= \"".$templates->get('proevents_event_view_rsvplist_row_none')."\";");
		}
		
		//RSVP button...
		if ($event['canrsvp']) {
			if (count($currentuser) > 0) {
				eval("\$rsvpbutton = \"".$templates->get('proevents_event_view_unrsvpbutton')."\";");
			} else {
				eval("\$rsvpbutton = \"".$templates->get('proevents_event_view_rsvpbutton')."\";");
			}
		}
		
		if (count($attendees) > 0 || $event['allowrsvp']) {
			eval("\$rsvplist = \"".$templates->get('proevents_event_view_rsvplist')."\";");
			eval("\$rsvpcell = \"".$templates->get('proevents_event_view_rsvpcell')."\";");
		}
		
		//Get user controls...
		if ($iscreator) {
			eval("\$usercontrols = \"".$templates->get('proevents_event_user_controls_edit')."\";");
			eval("\$usercontrols = \"".$templates->get('proevents_event_user_controls')."\";");
		}

		//Get moderator controls...
		if ($ismoderator) {
			eval("\$moderatorcontrols = \"".$templates->get('proevents_event_manager_controls')."\";");
		}
		
		//Update view counter...
		proevents_update_view_counter($eventid);

		eval("\$proevents = \"".$templates->get('proevents_event_view')."\";");
		output_page($proevents);
	}  else {
		error($lang->error_invalid_event);
	}
}

//*********************************************************[ RSVP ]
if ($mybb->input['action'] == 'rsvp') {
	if ($event) {
		if ($event['canrsvp']) {
			add_breadcrumb($lang->title_rsvp_add);
			
			if ($event['allowcomments']) {
				eval("\$commentrow = \"".$templates->get('proevents_rsvp_add_commentrow')."\";");
			}

			eval("\$proevents = \"".$templates->get('proevents_rsvp_add')."\";");
			output_page($proevents);
		} else {
			error($lang->error_cannot_rsvp);
		}
	}  else {
		error($lang->error_invalid_event);
	}
}

//*******************************************************[ UNRSVP ]
if ($mybb->input['action'] == 'unrsvp') {
	if ($event) {
		add_breadcrumb($lang->title_rsvp_remove);

		eval("\$proevents = \"".$templates->get('proevents_rsvp_remove')."\";");
		output_page($proevents);
	}  else {
		error($lang->error_invalid_event);
	}
}

//******************************************************[ DEFAULT ]
if ($mybb->input['action'] == '' && $mybb->request_method == 'get') {
	//Get any unfinished or upcoming events... TODO: Split into days/weeks/months! TODO: Way to view old/outdated events!
	$query = $db->query("SELECT * FROM `".TABLE_PREFIX."proevents` WHERE `datestart` > ".time()." AND (`dateend` > ".time()." OR `dateend` = 0) ORDER BY `datestart` ASC");
	
	if ($db->num_rows($query) > 0) {
	while ($event = $db->fetch_array($query)) {
		$name 		= htmlspecialchars($event['name']);
		$when 		= my_date($mybb->settings['dateformat'], $event['datestart'], $mybb->user['timezone']);
		$timestart	= my_date($mybb->settings['timeformat'], $event['datestart'], $mybb->user['timezone']);
		$eventid 	= $event['eventid'];

		//Time...
		if ($timestart && $timestart != '12:00am') {
			$when .= ' @ '.$timestart;
		}
		
		//Image...
		if ($event['imageurl']) {
			$image = '<img src="'.htmlspecialchars($event['imageurl']).'" width="100%" />';
		} else {
			$image = '<img src="images/proevents/default_image.jpg" width="100%" />';
		}
		
		eval("\$events .= \"".$templates->get('proevents_calendar_view_event_row')."\";");
	}
	} else {
		eval("\$events = \"".$templates->get('proevents_calendar_view_event_row_none')."\";");
	}

	//Get moderator controls...
	if ($ismoderator) {
		eval("\$moderatorcontrols = \"".$templates->get('proevents_calendar_moderator_controls')."\";");
	}
	
	eval("\$usercontrols = \"".$templates->get('proevents_calendar_user_controls_create')."\";");
	eval("\$usercontrols = \"".$templates->get('proevents_calendar_user_controls')."\";");
	
	//Output templates...
	eval("\$proevents = \"".$templates->get('proevents_calendar_view')."\";");
	output_page($proevents);
}
?>