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

function task_notifyattendees($task) {
	global $mybb, $db, $lang;
	
	$lang->load("proevents");
	
	//Get all RSVPs (and their events) that need to be notified...
	$query = $db->query("SELECT * FROM `".TABLE_PREFIX."proevents_rsvp` rsvp 
		LEFT JOIN `".TABLE_PREFIX."proevents` e ON e.`eventid` = rsvp.`eventid`
		WHERE 
		`notifywhen` < ".time()." AND 
		`datestart` > ".time()." AND 
		`notifydone` = '0'
	");

	$notify = array();
	$where = array();
	while ($rsvp = $db->fetch_array($query)) {
		//If event is not cached...
		if (!$events[$rsvp['eventid']])	$events[$rsvp['eventid']] = $rsvp;
		
		//Add this RSVP...
		$events[$rsvp['eventid']]['rsvps'][] = $rsvp;
		
		//Make sure to update it later to be notified...
		$where[] = '`rsvpid` = \''.$rsvp['rsvpid'].'\'';
	}
	
	//Send...
	if (count($events) > 0) {
		foreach ($events as $eventid => $event) {
			require_once MYBB_ROOT."inc/datahandlers/pm.php";
			$pmhandler = new PMDataHandler();
			$pmhandler->admin_override = true;
			
			$eventname = '[url='.$mybb->settings['bburl'].'/events.php?action=view&eventid='.$event['eventid'].']'.$event['name'].'[/url]';
			
			//RSVP specific variables... TODO: Fix? Sending out individual PMs may be slow!
			//$message = str_replace('[username]', $rsvp['username'],  $message);
			//$notifywhen = proevents_calc_timegap($rsvp['notifywhen'], $rsvp['datestart']);
			//$message = str_replace('[notifywhen]', $notifywhen,  $message);
			
			$message = $lang->notify_pm_message;
			$message = str_replace('[eventname]', $eventname,  $message);
			$message = str_replace('[username]', '',  $message);
			$message = str_replace('[notifywhen]', '',  $message);
			
			$message = str_replace('[startsin]', proevents_calc_timegap(time(), $event['datestart']), $message);
			
			foreach ( $event['rsvps'] as $rsvp) {
				$uids[] = $rsvp['uid'];
			}
			
			$pm = array(
				"subject" 		=> $lang->notify_pm_subject,
				"message" 		=> $message,
				"icon" 			=> "-1",
				"toid" 			=> $uids,
				"fromid" 		=> 0,
				"do" 			=> '',
				"pmid" 			=> ''
			);
			$pm['options'] = array(
				"signature" 		=> "0",
				"disablesmilies" 	=> "0",
				"savecopy" 			=> "0",
				"readreceipt" 		=> "0"
			);
			$pmhandler->set_data($pm);

			//If valid...
			if ($pmhandler->validate_pm()) {
				//Insert a PM...
				$pmhandler->insert_pm();
			} else {
				//Error (log?)...
			}
		}
		
		//Update database...
		$db->query("UPDATE `".TABLE_PREFIX."proevents_rsvp` SET `notifydone` = 1 WHERE ".implode(' OR ', $where)."");
	}
	
	add_task_log($task, $lang->task_notifyattendees_ran);
} //END task_notifyattendees()
?>