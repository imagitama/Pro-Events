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

//Disallow direct access to this file for security reasons...
if(!defined("IN_MYBB")) {
	die("This file cannot be accessed directly.");
}


//Tell MyBB when to run our functions...
$plugins->add_hook("showthread_start", 	"proevents_showthread");


//FUNCTION: Plugin info
function proevents_info() {
	return array(
		"name"				=> "Pro Events",
		"description"		=> "Advanced calendar events.",
		"author"			=> "Jared Williams",
		"authorsite"		=> "http://www.jaredwilliams.com.au",
		"version"			=> "1.0",
		"compatibility"		=> "6"
	);
} //END proevents_info()


//FUNCTION: Is it installed
function proevents_is_installed() {
	global $mybb, $db;
	
	//TODO: Use wildcard!
	$tables = array(
		'proevents',
		'proevents_rsvp'
	);

	//Loop through all tables and if one exists, it is installed...
	foreach ($tables as $tablename) {
		if ($db->table_exists($tablename)) {
			return true;
		}
	}
	
	return false;
} //END proevents_is_installed()


//FUNCTION: Perform a clean install.
function proevents_install() {
	global $mybb, $db;
	
	//Insert database stuff...
	proevents_insert_database();
	
	//Insert all templates...
	proevents_insert_templates();
	
	//Insert all settings...
	proevents_insert_settings();
} //END proevents_install()


//FUNCTION: Inserts tasks.
function proevents_insert_tasks() {
	global $mybb, $db;
	
	$insert = array(
		'title'			=> 'Notify Attendees',
		'description'	=> 'Notifies event attendees for Pro Events.',
		'file'			=> 'notifyattendees',
		'minute'		=> '10,20,30,40,50', //Every 10 minutes
		'hour'			=> '*',
		'day'			=> '*',
		'month'			=> '*',
		'weekday'		=> '*',
		'nextrun'		=> strtotime('+10 minutes', time()),
		'enabled'		=> 1,
		'logging'		=> 1,
		'locked'		=> 0
	);
	$lastid = $db->insert_query("tasks", $insert);
} //END proevents_insert_tasks()


//FUNCTION: Removes tasks.
function proevents_remove_tasks() {
	global $mybb, $db;
	
	$db->query("DELETE FROM `".TABLE_PREFIX."tasks` WHERE `file` = 'notifyattendees'");
} //END proevents_remove_tasks()


//FUNCTION: Inserts database tables and default rows.
function proevents_insert_database() {
	global $mybb, $db;
	
	$collation = $db->build_create_table_collation();
	
	//Create tables...
	if (!$db->table_exists('proevents')) {
		$db->write_query("CREATE TABLE `".TABLE_PREFIX."proevents` (
			`eventid` 		int(10) NOT NULL AUTO_INCREMENT,
			`userid` 		int(10) NOT NULL,
			`threadid`		int(10) NOT NULL,
			`name` 			varchar(512) NOT NULL default '',
			`description` 	varchar(2048) NOT NULL default '',
			`location`		varchar(256) NOT NULL default '',
			`geolocation`	varchar(256) NOT NULL default '',
			`imageurl`		varchar(256) NOT NULL default '',
			`datestart`		int(10) NOT NULL,
			`dateend`		int(10) NOT NULL,
			`allowrsvp`		int(1) NOT NULL default 1,
			`allowcomments`	int(1) NOT NULL default 1,
			`rsvplimit`		int(10) NOT NULL default 0,
			`approversvp`	int(1) NOT NULL default 0,
			`rsvpcount`		int(10) NOT NULL default 0,
			`views`			int(10) NOT NULL default 0,
			`datecreated`	int(10) NOT NULL,
			`dateupdated`	int(10) NOT NULL,
			PRIMARY KEY  (`eventid`)
		) ENGINE=MyISAM{$collation}");
	}
	
	//TEMP - test event
	$insert = array(
			'userid'		=> $mybb->user['uid'],
			'threadid'		=> 18,
			'name'			=> 'Foo Bar Party',
			'description'	=> 'This is a programmer party. Please RSVP to get in.',
			'location'		=> '100 Party Street, Partyhattan',
			'imageurl'		=> '',
			'datestart'		=> strtotime('+30 minutes', time()),
			'dateend'		=> strtotime('+60 minutes', time()),
			'allowrsvp'		=> 1,
			'allowcomments'	=> 1,
			'rsvplimit'		=> 4,
			'approversvp'	=> 0,
			'rsvpcount'		=> 1,
			'views'			=> 12,
			'datecreated'	=> time(),
			'dateupdated'	=> time()
	);
	$lastid = $db->insert_query("proevents", $insert);

	//Insert all MyBB calendar events... TODO: Setting?
	$query = $db->simple_select("events", "*", "eid", "", "WHERE `starttime` > '".time()."' AND `visible` = 1 AND `private` = 0");
	while ($event = $db->fetch_array($query)) {
		$insert = array(
			'userid'		=> $db->escape_string($event['uid']),
			'threadid'		=> 0,
			'name'			=> $db->escape_string($event['name']),
			'description'	=> $db->escape_string($event['description']),
			'location'		=> '',
			'imageurl'		=> '',
			'datestart'		=> $db->escape_string($event['starttime']),
			'dateend'		=> $db->escape_string($event['endtime']),
			'allowrsvp'		=> 1,
			'allowcomments'	=> 1,
			'rsvplimit'		=> 0,
			'approversvp'	=> 0,
			'rsvpcount'		=> 0,
			'views'			=> 0,
			'datecreated'	=> time(),
			'dateupdated'	=> time()
		);
		$lastid = $db->insert_query("proevents", $insert);
	}
	
	if (!$db->table_exists('proevents_rsvp')) {
		$db->write_query("CREATE TABLE `".TABLE_PREFIX."proevents_rsvp` (
			`rsvpid` 		int(10) NOT NULL AUTO_INCREMENT,
			`eventid`		int(10) NOT NULL,
			`userid`		int(10) NOT NULL,
			`comment`		varchar(2048) NOT NULL,
			`location`		varchar(256) NOT NULL,
			`geolocation`	varchar(256) NOT NULL,
			`notifywhen`	int(10) NOT NULL,
			`notifydone`	int(1) NOT NULL default 0,
			`approved`		int(1) NOT NULL default 1,
			`datecreated`	int(10) NOT NULL,
			`dateupdated`	int(10) NOT NULL,
			PRIMARY KEY  (`rsvpid`)
		) ENGINE=MyISAM{$collation}");
	}
	
	//TEMP - test RSVP
	$insert = array(
			'eventid'		=> 1,
			'userid'		=> $mybb->user['uid'],
			'comment'		=> 'This party is going to be rad!',
			'location'		=> '100 Party Street, Partyhattan',
			'geolocation'	=> '',
			'notifywhen'	=> strtotime('+10 minutes', time()),
			'notifydone'	=> 0,
			'datecreated'	=> time(),
			'dateupdated'	=> time()
	);
	$lastid = $db->insert_query("proevents_rsvp", $insert);
} //END proevents_insert_database()
	
	
//FUNCTION: Insert templates.
function proevents_insert_templates($Doadd=array(), $Donotadd=array()) {
	global $mybb, $db;
	
	//Add templates...
	$templates = array(
		//TEMPLATES: Event
		'proevents_event_view'					=> '
<html>
	<head>
		<title>{$mybb->settings[\'bbname\']} - {$lang->title_event_view}</title>
		{$headerinclude}
	</head>
	<body>
		{$header}

		<table width="100%" class="tborder">
			<tr>
				<td width="30%" class="tcat1">{$image}</td>
				<td width="70%" class="trow1">
					<div style="padding: 20px">
						<span style="font-size: 150%; font-weight: bold">{$name}</span><br />
						<span style="font-size: 120%">{$location}</span><br />
						<span style="font-size: 120%">{$when}</span><br />
						<span style="font-size: 120%">{$rsvplimit}</span><br />
						{$threadbutton}
					</div>
				</td>
			</tr>
			{$maprow}
		</table>
		<br />
			
		<table width="100%" class="tborder">
			<tr>
				<td width="60%" valign="top" class="trow1">
					<span style="font-size: 120%; font-weight: bold">Description</span><br />
					{$description}<br />
				</td>
				{$rsvpcell}
			</tr>
		</table>
		{$usercontrols}
		{$moderatorcontrols}
		
		{$footer}
	</body>
</html>',
	
		'proevents_event_view_rsvpcell' => '
<td width="40%" valign="top" class="trow1">
	<span style="font-size: 120%; font-weight: bold">Attendees{$rsvpcount}</span>
	{$rsvplist}
	<br />
	<div style="text-align: right">{$rsvpbutton}</div>
</td>',

		'proevents_event_view_rsvplist'		=> '
<table width="100%" class="tborder">
	{$rsvplist}
</table>',
		'proevents_event_view_rsvplist_row'		=> '
<tr>
	<td width="100px" class="trow1">
		{$comment[\'avatar\']}
	</td>
	<td class="trow1">
		<strong>{$comment[\'username\']}</strong><br />
		{$comment[\'message\']}<br />
		<span style="font-size: 80%">{$comment[\'when\']}</span>
	</td>
</tr>',
		'proevents_event_view_rsvplist_row_none'		=> '
<tr>
	<td width="100%" class="trow1" style="text-align: center">
		No attendees
	</td>
</tr>',
		'proevents_event_view_rsvpbutton'		=> '
<a href="events.php?action=rsvp&eventid={$eventid}"><button type="button">{$lang->button_rsvp}</button></a>',

		'proevents_event_view_unrsvpbutton'		=> '
<a href="events.php?action=unrsvp&eventid={$eventid}"><button type="button">{$lang->button_unrsvp}</button></a>',

		'proevents_event_view_threadbutton'		=> '
<a href="{$mybb->settings[\'bburl\']}/showthread.php?tid={$event[\'threadid\']}"><button type="button">{$lang->button_viewthread}</button></a>',

		//TEMPLATES: Modify
		'proevents_event_create'					=> '
<html>
	<head>
		<title>{$mybb->settings[\'bbname\']} - {$lang->title_event_create}</title>
		{$headerinclude}
	</head>
	<body>
		{$header}

		<form action="events.php" method="post" name="create_event">
		
		<table width="100%" class="tborder">
			<tr>
				<td class="thead" colspan="2">{$lang->title_event_create}</td>
			</tr>
			{$form_errors}
			<tr>
				<td width="20%" class="trow1"><strong>{$lang->label_event_edit_name}</strong></td>
				<td width="80%" class="trow1"><input type="text" class="textbox" name="name" size="40" maxlength="2048" value="{$event[\'name\']}" /></td>
			</tr>
			<tr>
				<td width="20%" class="trow1"><strong>{$lang->label_event_edit_description}</strong></td>
				<td width="80%" class="trow1"><textarea name="description" rows="5" cols="36" class="textbox">{$event[\'description\']}</textarea></td>
			</tr>
			<tr>
				<td width="20%" class="trow1"><strong>{$lang->label_event_edit_location}</strong></td>
				<td width="80%" class="trow1"><input type="text" class="textbox" name="location" size="40" maxlength="256" value="{$event[\'location\']}" /></td>
			</tr>
			<tr>
				<td width="20%" class="trow1"><strong>{$lang->label_event_edit_imageurl}</strong></td>
				<td width="80%" class="trow1"><input type="text" class="textbox" name="imageurl" size="40" maxlength="256" value="{$event[\'imageurl\']}" /></td>
			</tr>
			<tr>
				<td width="20%" class="trow1"><strong>{$lang->label_event_edit_dates}</strong></td>
				<td width="80%" class="trow1">
					<input type="text" class="textbox" name="datestart" size="20" maxlength="256" value="{$datestart}" placeholder="eg. 10:30am 24 January" /> until <input type="text" class="textbox" name="dateend" size="20" maxlength="256" value="{$dateend}" /><br />
				</td>
			</tr>
			
			<tr>
				<td width="20%" class="trow1"><strong>{$lang->label_event_edit_rsvp}</strong></td>
				<td width="80%" class="trow1">
					<input type="checkbox" class="checkbox" name="allowrsvp" {$allowrsvp} /> {$lang->label_event_edit_allowrsvp}<br />
					<input type="checkbox" class="checkbox" name="allowcomments" {$allowcomments} /> {$lang->label_event_edit_allowcomments}<br />
					<input type="text" class="textbox" name="rsvplimit" size="5" maxlength="256" value="0" /> {$lang->label_event_edit_rsvplimit}<br />
					<input type="checkbox" class="checkbox" name="approversvp" {$approversvp} /> {$lang->label_event_edit_approversvp}
				</td>
			</tr>
			
			<tr>
				<td width="20%" class="trow1"><strong>{$lang->label_event_edit_thread}</strong></td>
				<td width="80%" class="trow1">
					<input type="checkbox" class="checkbox" name="postthread" {$postthread} /> {$lang->label_event_edit_postthread}<br />
					{$lang->label_event_edit_forum} <select class="textbox" name="forumid">{$forumoptions}</select>
				</td>
			</tr>
		</table>
		<br />
		<div style="text-align: center"><input type="submit" class="button" name="submit" value="{$lang->button_create}" accesskey="s" /></div>
		<br /><br />
		
			<input type="hidden" name="action" value="do_event_create" />
			<input type="hidden" name="eventid" value="{$event[\'eventid\']}" />
			<!-- <input type="hidden" name="posthash" value="{$posthash}" /> -->
		</form>
		
		{$footer}
	</body>
</html>',

		'proevents_event_edit'					=> '
<html>
	<head>
		<title>{$mybb->settings[\'bbname\']} - {$lang->title_event_edit}</title>
		{$headerinclude}
	</head>
	<body>
		{$header}

		<form action="events.php" method="post" name="edit_event">
		
		<table width="100%" class="tborder">
			<tr>
				<td class="thead" colspan="2">{$lang->title_event_edit}</td>
			</tr>
			{$form_errors}
			<tr>
				<td width="20%" class="trow1"><strong>{$lang->label_event_edit_name}</strong></td>
				<td width="80%" class="trow1"><input type="text" class="textbox" name="name" size="40" maxlength="2048" value="{$event[\'name\']}" /></td>
			</tr>
			<tr>
				<td width="20%" class="trow1"><strong>{$lang->label_event_edit_description}</strong></td>
				<td width="80%" class="trow1"><textarea name="description" rows="5" cols="36" class="textbox">{$event[\'description\']}</textarea></td>
			</tr>
			<tr>
				<td width="20%" class="trow1"><strong>{$lang->label_event_edit_location}</strong></td>
				<td width="80%" class="trow1"><input type="text" class="textbox" name="location" size="40" maxlength="256" value="{$event[\'location\']}" /></td>
			</tr>
			<tr>
				<td width="20%" class="trow1"><strong>{$lang->label_event_edit_imageurl}</strong></td>
				<td width="80%" class="trow1"><input type="text" class="textbox" name="imageurl" size="40" maxlength="256" value="{$event[\'imageurl\']}" /></td>
			</tr>
			<tr>
				<td width="20%" class="trow1"><strong>{$lang->label_event_edit_dates}</strong></td>
				<td width="80%" class="trow1">
					<input type="text" class="textbox" name="datestart" size="20" maxlength="256" value="{$datestart}" placeholder="eg. 10:30am 24 January" /> until <input type="text" class="textbox" name="dateend" size="20" maxlength="256" value="{$dateend}" /><br />
				</td>
			</tr>
			
			<tr>
				<td width="20%" class="trow1"><strong>{$lang->label_event_edit_rsvp}</strong></td>
				<td width="80%" class="trow1">
					<input type="checkbox" class="checkbox" name="allowrsvp" {$allowrsvp} /> {$lang->label_event_edit_allowrsvp}<br />
					<input type="checkbox" class="checkbox" name="allowcomments" {$allowcomments} /> {$lang->label_event_edit_allowcomments}<br />
					<input type="text" class="textbox" name="rsvplimit" size="5" maxlength="256" value="{$event[\'rsvplimit\']}" /> {$lang->label_event_edit_rsvplimit}<br />
					<input type="checkbox" class="checkbox" name="approversvp" {$approversvp} /> {$lang->label_event_edit_approversvp}
				</td>
			</tr>
		</table>
		<br />
		<div style="text-align: center"><input type="submit" class="button" name="submit" value="{$lang->button_edit}" accesskey="s" /></div>
		<br /><br />
		
			<input type="hidden" name="action" value="do_event_edit" />
			<input type="hidden" name="eventid" value="{$event[\'eventid\']}" />
			<!-- <input type="hidden" name="posthash" value="{$posthash}" /> -->
		</form>
		
		{$footer}
	</body>
</html>',

		'proevents_event_cancel'		=> '
<html>
	<head>
		<title>{$mybb->settings[\'bbname\']} - {$lang->title_event_cancel}</title>
		{$headerinclude}
	</head>
	<body>
		{$header}
		<form action="events.php" method="post" name="event_delete">
		
		<table width="100%" class="tborder">
			<tr>
				<td class="thead" colspan="2">{$lang->title_event_cancel}</td>
			</tr>
			<tr>
				<td class="trow1" align="center">
					<br />
					{$lang->msg_event_cancel_confirm}
					<br /><br />
					<input type="submit" class="button" name="submit" value="{$lang->button_cancel}" accesskey="s" />
					<br /><br />
				</td>
			</tr>
		</table>
		<br />

			<input type="hidden" name="action" value="do_event_cancel" />
			<input type="hidden" name="eventid" value="{$event[\'eventid\']}" />
			<!-- <input type="hidden" name="posthash" value="{$posthash}" /> -->
		</form>

		{$footer}
	</body>
</html>',

		//TEMPLATE: User Controls	
		'proevents_event_user_controls'		=> '
<br />
<table width="100%" class="tborder">
	<tr>
		<td class="thead" colspan="4">
			{$lang->title_user_controls}
		</td>
	</tr>
	<tr>
		{$usercontrols}
	</tr>
</table>',

			'proevents_event_user_controls_edit' => '
<td width="25%" class="trow1">
	<a href="events.php?action=edit&eventid={$eventid}"><button type="button">{$lang->user_controls_edit}</button></a>
</td>
<td width="25%" class="trow1">
	<a href="events.php?action=cancel&eventid={$eventid}"><button type="button">{$lang->user_controls_cancel}</button></a>
</td>',

		//TEMPLATES: RSVP
		'proevents_rsvp_add'					=> '
<html>
	<head>
		<title>{$mybb->settings[\'bbname\']} - {$lang->title_rsvp_add}</title>
		{$headerinclude}
	</head>
	<body>
		{$header}

		<form action="events.php" method="post" name="rsvp_add">
		
		<table width="100%" class="tborder">
			<tr>
				<td class="thead" colspan="2">{$lang->title_rsvp_add}</td>
			</tr>
			{$commentrow}
			<tr>
				<td width="20%" class="trow1"><strong>{$lang->label_rsvp_edit_notify}</strong></td>
				<td width="80%" class="trow1">
					{$lang->label_rsvp_edit_notify_start}<input type="text" class="textbox" name="notifywhen" size="20" maxlength="256" value="{$event[\'notifywhen\']}" placeholder="eg. 2 days, 30 seconds" />{$lang->label_rsvp_edit_notify_end}
				</td>
			</tr>
		</table>

			<input type="hidden" name="action" value="do_rsvp" />
			<input type="hidden" name="eventid" value="{$event[\'eventid\']}" />
			<!-- <input type="hidden" name="posthash" value="{$posthash}" /> -->
			
			<br />
			<div style="text-align: center"><input type="submit" class="button" name="submit" value="{$lang->button_rsvp}" accesskey="s" /></div>
			<br />
		</form>
		
		{$footer}
	</body>
</html>',
	
		'proevents_rsvp_add_commentrow'			=> '
<tr>
	<td width="20%" class="trow1"><strong>{$lang->label_rsvp_edit_comment}</strong></td>
	<td width="80%" class="trow1"><textarea name="comment" rows="5" cols="36" class="textbox"></textarea></td>
</tr>
',

		//TEMPLATES: UnRSVP
		'proevents_rsvp_remove'					=> '
<html>
	<head>
		<title>{$mybb->settings[\'bbname\']} - {$lang->title_rsvp_remove}</title>
		{$headerinclude}
	</head>
	<body>
		{$header}
		<form action="events.php" method="post" name="rsvp_remove">

		<table width="100%" class="tborder">
			<tr>
				<td class="thead" colspan="2">{$lang->title_rsvp_remove}</td>
			</tr>
			<tr>
				<td class="trow1" align="center">
					<br />
					{$lang->msg_unrsvp_confirm}
					<br /><br />
					<input type="submit" class="button" name="submit" value="{$lang->button_unrsvp}" accesskey="s" />
					<br /><br />
				</td>
			</tr>
		</table>
		<br />
		
			<input type="hidden" name="action" value="do_unrsvp" />
			<input type="hidden" name="eventid" value="{$event[\'eventid\']}" />
			<!-- <input type="hidden" name="posthash" value="{$posthash}" /> -->
		</form>
		{$footer}
	</body>
</html>',

		//TEMPLATES: Calendar
		'proevents_calendar_view'					=> '
<html>
	<head>
		<title>{$mybb->settings[\'bbname\']} - {$lang->title_calendar_view}</title>
		{$headerinclude}
	</head>
	<body>
		{$header}

		<table width="100%" class="tborder">
			<tr>
				<td width="100%" colspan="4" class="thead">{$lang->title_calendar_view}</td>
			</tr>
			{$events}
		</table>
		{$usercontrols}
		{$moderatorcontrols}
		
		{$footer}
	</body>
</html>',

		'proevents_calendar_view_event_row'		=> '
<tr>
	<td width="15%" class="trow1">
		{$image}
	</td>
	<td class="trow1">
		<a href="events.php?action=view&eventid={$eventid}">{$name}</a><br />
		{$when}
	</td>
</tr>',
		'proevents_calendar_view_event_row_none'		=> '
<tr>
	<td width="100%" class="trow1" style="text-align: center">
		No events to display
	</td>
</tr>',

		'proevents_calendar_user_controls'		=> '
<br />
<table width="100%" class="tborder">
	<tr>
		<td class="thead" colspan="4">
			{$lang->title_user_controls}
		</td>
	</tr>
	<tr>
		{$usercontrols}
	</tr>
</table>',

		'proevents_calendar_user_controls_create' => '
<td width="25%" class="trow1">
	<a href="events.php?action=create"><button type="button">{$lang->user_controls_create}</button></a>
</td>',

		//TEMPLATES: Thread
		'proevents_thread_view'					=> '
<table width="100%" class="tborder">
	<tr>
		<td width="100%" colspan="4" class="thead">{$lang->title_thread_view}</td>
	</tr>
	<tr>
		<td class="trow1">
				<h2><a href="{$mybb->settings[\'bburl\']}/events.php?action=view&eventid={$eventid}">{$name}</a></h2>
				<h3>{$location}</h3>
				<h3>{$when}</h3>
		</td>
	</tr>
</table>',
	);

	//Insert templates...
	foreach ($templates as $title => $data) {
		//If to insert or to insert all, and we are permitted to insert it...
		if ((in_array($title, $Doadd) || count($Doadd) == 0) && !in_array($title, $Donotadd)) {
			$insert = array(
				'title' => $db->escape_string($title),
				'template' => $db->escape_string($data),
				'sid' => "-1",
				'version' => '1',
				'dateline' => TIME_NOW
			);
			$db->insert_query('templates', $insert);
		}
	}
} //END proevents_insert_templates()
	

//FUNCTION: Insert settings.
function proevents_insert_settings($Doadd=array(), $Donotadd=array()) {
	global $mybb, $db;
	
  //Insert a new settings group...
	$insertgroup = array(
		'name' => 'proevents',
		'title' => 'Pro Events',
		'description' => 'Settings for Pro Events.',
		'disporder' => '62',
		'isdefault' => 0
	);
	$group['gid'] = $db->insert_query("settinggroups", $insertgroup);
	
	$insertarray = array();
	
	//Enabling...
	$insertarray[] = array(
		'name' => 'proevents_enable',
		'title' => 'Enable events',
		'description' => 'Enable users access to the events system.',
		'optionscode' => 'yesno',
		'value' => '0',			//Off to let admin set up!
		'disporder' => 1,
		'gid' => $group['gid']
	);
	
	//Other
	$insertarray[] = array(
		'name' => 'proevents_moderator_usergroups',
		'title' => 'Moderator Usergroups',
		'description' => 'Comma delimited list of moderator usergroups (leave 0 or blank for none).',
		'optionscode' => 'text',
		'value' => '3,4',
		'disporder' => 1,
		'gid' => $group['gid']
	);
	
	//Event creation...
	$insertarray[] = array(
		'name' => 'proevents_enable_new_events',
		'title' => 'Enable New Events',
		'description' => 'Enables the creation of new events.',
		'optionscode' => 'yesno',
		'value' => '1',
		'disporder' => 1,
		'gid' => $group['gid']
	);
	$insertarray[] = array(
		'name' => 'proevents_enable_location',
		'title' => 'Enable Locations',
		'description' => 'Enables specifying an event location.',
		'optionscode' => 'yesno',
		'value' => '1',	
		'disporder' => 1,
		'gid' => $group['gid']
	);
	$insertarray[] = array(
		'name' => 'proevents_enable_imageurl',
		'title' => 'Enable Image URL',
		'description' => 'Enables specifying an event image URL.',
		'optionscode' => 'yesno',
		'value' => '1',
		'disporder' => 1,
		'gid' => $group['gid']
	);
	$insertarray[] = array(
		'name' => 'proevents_max_rsvp',
		'title' => 'Maximum RSVPs',
		'description' => 'Maximum number of RSVPs an event can have (0 for infinite, blank for disable).',
		'optionscode' => 'text',
		'value' => '0',
		'disporder' => 1,
		'gid' => $group['gid']
	);
	$insertarray[] = array(
		'name' => 'proevents_enable_rsvp_comments',
		'title' => 'Enable RSVP Comments',
		'description' => 'Enables comments for RSVPs.',
		'optionscode' => 'text',
		'value' => '1',
		'disporder' => 1,
		'gid' => $group['gid']
	);
	$insertarray[] = array(
		'name' => 'proevents_enable_rsvp_approval',
		'title' => 'Enable RSVP Approvals',
		'description' => 'Enables approvals for new RSVPs.',
		'optionscode' => 'text',
		'value' => '1',
		'disporder' => 1,
		'gid' => $group['gid']
	);
	$insertarray[] = array(
		'name' => 'proevents_enable_event_thread',
		'title' => 'Enable Thread Creation',
		'description' => 'Enable automatic posting of event threads.',
		'optionscode' => 'yesno',
		'value' => '1',
		'disporder' => 1,
		'gid' => $group['gid']
	);
	$insertarray[] = array(
		'name' => 'proevents_event_thread_forums',
		'title' => 'Allowed Event Forums',
		'description' => 'Comma delimited list of forums event creators can automatically post to (leave blank to allow all).',
		'optionscode' => 'text',
		'value' => '',
		'disporder' => 1,
		'gid' => $group['gid']
	);
	
	foreach ($insertarray as $properties) {
		//If to insert or to insert all, and we are permitted to insert it...
		if ((in_array($properties['name'], $Doadd) || count($Doadd) == 0) && !in_array($properties['name'], $Donotadd)) {
			//Insert our setting...
			$db->insert_query("settings", $properties);
		}
	}
	
	//Update all settings...
	rebuild_settings();
} //END proevents_insert_settings()


//FUNCTION: Uninstall the plugin
function proevents_uninstall() {
	global $mybb, $db;
	
	//Deactivate just to be sure...
	proevents_deactivate();
	
	//Remove all settings from the database...
	$db->delete_query("settings", "name LIKE '%proevents%'");
	$db->delete_query("settinggroups", "name = 'proevents'");

	//Update the settings...
	rebuild_settings();
	
	//TODO: Use wildcard!
	$tables = array(
		'proevents',
		'proevents_rsvp'
	);

	//Drop tables if they exist...
	foreach ($tables as $tablename) {
		if ($db->table_exists($tablename)) {
			$db->drop_table($tablename);
		}
	}
	
	//Remove all other templates...
	$db->delete_query("templates", "`title` LIKE '%proevents%'");
} //END proevents_uninstall()


//FUNCTION: Activate the plugin
function proevents_activate() {
	global $mybb, $db;
	
	//Deactivate it first so we start fresh...
	proevents_deactivate();
	
	//Add the variable to templates...
	require_once MYBB_ROOT."/inc/adminfunctions_templates.php";
	
	find_replace_templatesets("header", '#/calendar.php#', "/events.php");
	//find_replace_templatesets("header", "#$lang->toplinks_calendar#", '$lang->toplinks_events', 0);
	
	//Insert tasks...
	proevents_insert_tasks();
} //END proevents_activate()


//FUNCTION: Deactivate the plugin
function proevents_deactivate() {
	global $mybb, $db;
	
	//Remove the variable from templates...
	require_once MYBB_ROOT."/inc/adminfunctions_templates.php";
	
	find_replace_templatesets("header", "#/events.php#", '/calendar.php', 0);
	//find_replace_templatesets("header", "#$lang->toplinks_events#", '$lang->toplinks_calendar', 0);
	
	//Remove tasks...
	proevents_remove_tasks();
} //END proevents_deactivate()


//FUNCTION: Check if user is a moderator.
function proevents_is_moderator($userobj=array()) {
	global $mybb, $db;
	
	$modgroups = explode(',', $mybb->settings['proevents_moderator_usergroups']);
	
	//If we're being passed an array...
	if ($userobj['usergroup'] || $userobj['additionalgroups']) {
		$usergroup = $userobj['usergroup'];
		$additionalgroups = explode(',', $userobj['additionalgroups']);
	} else {
		$usergroup = $mybb->user['usergroup'];
		$additionalgroups = explode(',', $mybb->user['additionalgroups']);
	}
	
	//Check if they are allowed...
	if (in_array($usergroup, $modgroups)) {
		return true;
	} else {
		foreach ($additionalgroups as $gid) {
			if (in_array($gid, $modgroups)) {
				return true;
			}
		}
		
		return false;
	}
} //END proevents_is_moderator()


//FUNCTION: Gets an event.
function proevents_event_getinfo($eventid) {
	global $mybb, $db;
	
	//Sanatise...
	if ($eventid = intval($eventid)) {
		//Get data...
		$query = $db->simple_select("proevents", "*", "`eventid` = '{$eventid}'", array('limit' => 1));
		$event = $db->fetch_array($query);

		//If successful, return it...
		if ($event['eventid']) {
			//Check if can RSVP...
			if (
				//Allowed on event...
				$event['allowrsvp'] && 
				(
					//Current RSVPs less than limit
					($event['rsvpcount'] < $event['rsvplimit']) ||
					
					//No limit
					($event['rsvplimit'] == 0)
				) && (
					//Current date is before start date and end date is in future
					($event['datestart'] > time()) && 
					
					//Either no end date is set or end date is in future...
					($event['dateend'] == 0 || $event['dateend'] > time())
				)
			) {
				$event['canrsvp'] = true;
			} else {
				$event['canrsvp'] = false;
			}
		
			return $event;
		}
	}
	
	return false;
} //END proevents_event_getinfo()


//FUNCTION: Gets an event RSVP/attendee list.
function proevents_get_event_rsvp($eventid, $unapproved=false, $currentuser=false) {
	global $mybb, $db;
	
	//Sanatise...
	if ($eventid = intval($eventid)) {
		//Get data...
		//$query = $db->simple_select("proevents_rsvp", "*", "`eventid` = '{$eventid}'");
		
		if ($currentuser) {
			$wherecurrent = " AND rsvp.`userid` = '".$db->escape_string($mybb->user['uid'])."'";
		} else {
			$wherecurrent = '';
		}
		
		$query = $db->query("SELECT * FROM `".TABLE_PREFIX."proevents_rsvp` rsvp
			LEFT JOIN `".TABLE_PREFIX."users` u ON u.`uid` = rsvp.`userid`
			WHERE `eventid` = '{$eventid}'
			".$wherecurrent."
		");

		$allrsvp = array();
		while ($rsvp = $db->fetch_array($query)) {
			$allrsvp[$rsvp['userid']] = $rsvp;
		}
		return $allrsvp;
	}
	
	return false;
} //END proevents_event_rsvp()


//FUNCTION: Validate new event data.
function proevents_validate_event_input() {
	global $mybb, $db;
	
	$time 		= proevents_get_user_timestamp();
	$offset 	= proevents_get_user_timezoneoffset();
	
	$input 		= array();
	
	//Strings...
	if ($mybb->input['name']) {
		$input['name'] = $mybb->input['name'];
	} else {
		$errors[] = 'No event name';
	}
	if ($mybb->input['description']) {
		$input['description'] = $mybb->input['description'];
	} else {
		$errors[] = 'No event description';
	}
	if ($mybb->input['location']) {
		$input['location'] = $mybb->input['location'];
	} else {
		$errors[] = 'No event location';
	}
	
	//URLs...
	if ($mybb->input['imageurl']) {
		//Parse the URL...
		$imageurl = parse_url($mybb->input['imageurl']);
	
		//If valid, accept... TODO: Check if image file!
		if (count($imageurl) > 0) {
			$input['imageurl'] = $mybb->input['imageurl'];
		}
	} else {
		$input['imageurl'] = '';
	}
	
	//Dates...
	if ($mybb->input['datestart']) {
		//First get timestamp for what the user actually specified...
		$datestart = strtotime($mybb->input['datestart'], $time);
		
		//Adjust back to UNIX timestamp...
		$datestart = $datestart - ($offset * 3600);

		if ($datestart) {
			$input['datestart'] = $datestart;
		} else {
			$errors[] = 'Invalid event starting date';
		}
	} else {
		$errors[] = 'No event starting date specified';
	}
	if ($mybb->input['dateend']) {
		//First get timestamp for what the user actually specified...
		$dateend = strtotime($mybb->input['dateend'], $time);
		
		//Adjust back to UNIX timestamp...
		$dateend = $dateend - ($offset * 3600);
		
		if ($dateend) {
			if ($dateend > $datestart) {
				$input['dateend'] = $dateend;
			} else {
				$errors[] = 'Ending date cannot be before the starting date';
			}
			
		} else {
			$errors[] = 'Invalid event ending date';
		}
		
		
	} else {
		$dateend = 0;
	}
	
	//Checkbox...
	if ($mybb->input['allowrsvp']) {
		$input['allowrsvp'] = 1;
	} else {
		$input['allowrsvp'] = 0;
	}
	if ($mybb->input['allowcomments']) {
		$input['allowcomments'] = 1;
	} else {
		$input['allowcomments'] = 0;
	}
	if ($mybb->input['approversvp']) {
		$input['approversvp'] = 1;
	} else {
		$input['approversvp'] = 0;
	}
	
	if ($mybb->input['rsvplimit']) {
		if ($rsvplimit = intval($mybb->input['rsvplimit'])) {
			$input['rsvplimit']	= $rsvplimit;
		} else {
			$errors[] = 'Invalid RSVP limit';
		}
	} else {
		$input['rsvplimit'] = 0;
	}
	
	//Special...
	if ($mybb->input['postthread']) {
		if ($forumid = intval($mybb->input['forumid'])) {
			$query = $db->simple_select("forums", "*", "`fid` = '".$db->escape_string($forumid)."'", array('limit' => 1));

			if ($forum = $db->fetch_array($query)) {
				$input['forumid'] = $forumid;
			} else {
				$errors[] = 'Invalid forum';
			}
		} else {
			$errors[] = 'Invalid forum';
		}
	} else {
		$input['forumid'] = 0;
	}

	//If we have any errors, return them, otherwise return valid product...
	if (count($errors) > 0) {
		return array('errors' => $errors);
	} else {
		return $input;
	}
} //END proevents_validate_event_input()


//FUNCTION: Validate new event data.
function proevents_validate_rsvp_input() {
	global $mybb, $db;
	
	$time 		= proevents_get_user_timestamp();
	$offset 	= proevents_get_user_timezoneoffset();
	
	$input = array();
	
	//Strings...
	if ($mybb->input['comment']) {
		$input['comment'] = $mybb->input['comment'];
	} else {
		$input['comment'] = '';
	}
	if ($mybb->input['location']) {
		$input['location'] = $mybb->input['location'];
	} else {
		$input['location'] = '';
	}
	
	//Dates...
	if ($mybb->input['notifywhen']) {
		//First get timestamp for what the user actually specified...
		$notifywhen = strtotime('-' . $mybb->input['notifywhen'], $time);
		
		//Adjust back to UNIX timestamp...
		$notifywhen = $notifywhen - ($offset * 3600);
		
		if ($notifywhen) {
			$input['notifywhen'] = $notifywhen;
		} else {
			$errors[] = 'Invalid notification send date';
		}
	} else {
		$input['notifywhen'] = 0;
	}

	//If we have any errors, return them, otherwise return valid product...
	if (count($errors) > 0) {
		return array('errors' => $errors);
	} else {
		return $input;
	}
} //END proevents_validate_rsvp_input()


//FUNCTION: Generate HTML dropdown lists.
function proevents_generate_dropdown($Name, $Selected=null) {
	global $mybb, $db, $lang;
	
	switch ($Name) {
		// case 'calendar':
			// $query = $db->simple_select("proevents_calendars", "*");

			// if ($db->num_rows($query) > 0) {
				// while ($calendar = $db->fetch_array($query)) {
					// if ($calendar['calendarid'] == $Selected || $calendar['name'] == $Selected) {
						// $selectit = ' selected';
					// } else {
						// $selectit = '';
					// }

					// $options .= '	<option value="'.$calendar['calendarid'].'"'.$selectit.'>'.$calendar['name'].'</option>';
				// }
			// } else {
				// $options .= '<option value="">--- NONE ---</option>';
			// }
		// break;
		
		case 'forum':
			$query = $db->simple_select("forums", "*", "`pid` > 0 AND `fid` IN (".$db->escape_string($mybb->settings['proevents_event_thread_forums']).")");

			if ($db->num_rows($query) > 0) {
				while ($forum = $db->fetch_array($query)) {
					if ($forum['fid'] == $Selected || $forum['name'] == $Selected) {
						$selectit = ' selected';
					} else {
						$selectit = '';
					}
				
					$options .= '	<option value="'.$forum['fid'].'"'.$selectit.'>'.$forum['name'].'</option>';
				}
			}
		break;
	}
	
	return $options;
} //END proevents_generate_dropdown()


//FUNCTION: Gets event details for thread.
function proevents_showthread() {
	global $mybb, $db, $templates, $lang;
	global $proevent, $tid;
	
	$lang->load("proevents");
	
	//Try and find thread...
	$query = $db->query("SELECT * FROM `".TABLE_PREFIX."proevents` 
		WHERE `threadid` = '".$db->escape_string($tid)."'
	");
	$event = $db->fetch_array($query);
	
	if (count($event)) {
		$name			= $event['name'];
		$location		= $event['location'];
		$eventid		= $event['eventid'];
		
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
	
		eval("\$proevent = \"".$templates->get('proevents_thread_view')."\";");
	}
} //END proevents_showthread()


//FUNCTION: Calculate how long ago using timestamp.
function proevents_calc_timegap($Timestamp, $Now='') {
	//TODO: Validate timestamp!
	
	//Difference...
	if (!$Now) 			$Now = time();
	
	$timedifference = $Now - $Timestamp;

	$tokens = array (
		31536000 => 'year',
		2592000 => 'month',
		604800 => 'week',
		86400 => 'day',
		3600 => 'hour',
		60 => 'minute',
		1 => 'second'
	);

	//TODO: Comment! What's happening here?!
	foreach ($tokens as $unit => $text) {
		if ($timedifference < $unit) continue;
		
		$numberOfUnits = floor($timedifference / $unit);
		return $numberOfUnits.' '.$text.(($numberOfUnits>1)?'s':'');
	}
} //END proevents_calc_timegap()


//FUNCTION: Posts event as thread to forum.
function proevents_post_event_to_forum($Event) {
	global $mybb, $db, $lang;
	
	//TODO: Update thread on event update/deletion!
	
	$thread = array(
		"fid" 			=> $Event['forumid'],
		"prefix" 		=> 0,
		"subject" 		=> $Event['name'],
		"icon" 			=> "",
		"uid" 			=> $mybb->user['uid'],
		"username" 		=> $mybb->user['username'],
		"dateline" 		=> TIME_NOW,
		"message" 		=> $lang->thread_message,
		"ipaddress" 	=> get_ip(),
		"posthash" 		=> md5($mybb->user['uid'].random_str())
	);
	
	require_once MYBB_ROOT."inc/datahandlers/post.php";
	$posthandler = new PostDataHandler("insert");
	$posthandler->action = "thread";
	$posthandler->set_data($thread);
	if ($posthandler->validate_thread()) {
		$thread = $posthandler->insert_thread();
		if ($dh->method == "insert") {
			$dh->event_insert_data['tid'] = $thread['tid'];
		} else {
			$dh->event_update_data['tid'] = $thread['tid'];
		}
		
		return $thread['tid'];
	}
} //END proevents_post_event_to_forum()


//FUNCTION: Updates view counter.
function proevents_update_view_counter($EventID) {
	global $mybb, $db;
	
	$db->query("UPDATE `".TABLE_PREFIX."proevents` SET `views` = '".$new."' WHERE `eventid` = '".$EventID."'");
}


//FUNCTION: Get current user's time(). Based off MyBB's my_date().
function proevents_get_user_timestamp() {
	$offset = proevents_get_user_timezoneoffset();
	$timestamp = TIME_NOW + ($offset * 3600);
		
	return $timestamp;
}


//FUNCTION:
function proevents_get_user_timezoneoffset() {
	global $mybb, $lang, $mybbadmin, $plugins;
	
	if( $mybb->user['uid'] != 0 && array_key_exists("timezone", $mybb->user)) {
		$offset = $mybb->user['timezone'];
		$dstcorrection = $mybb->user['dst'];
	} elseif(defined("IN_ADMINCP")) {
		$offset =  $mybbadmin['timezone'];
		$dstcorrection = $mybbadmin['dst'];
	} else {
		$offset = $mybb->settings['timezoneoffset'];
		$dstcorrection = $mybb->settings['dstcorrection'];
	}

	// If DST correction is enabled, add an additional hour to the timezone.
	if($dstcorrection == 1) {
		++$offset;
		if (my_substr($offset, 0, 1) != "-") {
			$offset = "+".$offset;
		}
	}
	
	return $offset;
}
?>