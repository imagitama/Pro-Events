<?php
/**
 * Pro Events 0.9, 1.0 -> 1.1
 * 
 * Upgrade script.
 *  
 * By Jared Williams
 * Copyright 2013
 * 
 * Website: http://www.jaredwilliams.com.au
 *  
 * Please do not redistribute or sell this plugin.
 */

define("IN_MYBB", 1);
require_once "global.php";

//******************************************************[ UPGRADE ]
if ($mybb->input['action'] == 'upgrade') {
	if ($mybb->input['from']) {
		switch ($mybb->input['from']) {
			case '0.9':
			case '1.0':	

				//Templates to insert...
				if ($mybb->input['replace_templates'] == '1') {
					//Delete all current ones...
					$db->delete_query("templates", "`title` LIKE 'proevents%'");

					//Insert all new ones...
					$newtemplates = array();
				} else {
					$newtemplates = array(
						'proevents_event_thread_settings',
						'proevents_event_mod_thread_settings',
						

						'proevents_calendar_user_controls_old',

						'proevents_event_notice_ended',
						'proevents_event_notice_soon',
						'proevents_event_notice_running'
					);
				}
				
				//Insert new templates...
				proevents_insert_templates($newtemplates);
				
				//Templates to insert...
				if ($mybb->input['replace_settings'] == '1') {
					//Delete all current ones...
					$db->delete_query("settings", "name LIKE 'proevents%'");

					//Insert all new ones...
					$newsettings = array();
				} else {
					$newsettings = array(
						'prostore_soon_cutoff'
					);
				}
				
				//Insert new settings...
				prostore_insert_settings($newsettings);
				
				$success = true;
			break;
			default:
				die('Invalid version specified!');
		}
		
		if ($success) {
			die('<b>Upgrade from '.$mybb->input['form'].' complete!</b> Delete this file!');
		}
	} else {
		die('No version specified!');
	}
}

//******************************************************[ LANDING ]
if ($mybb->input['action'] == '') {
	$newversion = 1.1;
	
//	if (function_exists('prostore_info')) {
//		$currentplugin = prostore_info();
//		$currentversion = $currentplugin['version'];
//	} else {
//		$currentversion = 'unknown';
//	}
	
	$html  = '';
	$html .= '<h1>Pro Suite Upgrade Script 1.0</h1>';
	$html .= '<h2>Plugin: Pro Events '.$newversion.'</h2>';
//	$html .= '<strong>Current version:</strong> '.$currentversion;
//	
//	//Check if they actually need to upgrade...
//	if ($currentversion >= $newversion) {
//		$html .= ' <span style="color:red">You do not need to upgrade!</span>';
//	}
	
	$html .= '<h3>How to upgrade</h3>';
	$html .= '<ul>';
	$html .= '	<li>Do not deactivate the plugin. This script requires functions within the plugin. Disable access using .htaccess to prevent access.';
	$html .= '	<li>Perform a database backup.</li>';
	$html .= '	<li>Run this script. It will update your settings, database, and add new templates. Note: Some templates will need manual updating and removal.</li>';
	$html .= '</ul>';
	$html .= '<form action="" method="post">';
	$html .= '	<label>Select your current plugin version:</label>';
	$html .= '	<select name="from">';
	$html .= '		<option value="0.9">0.9</option>';
	$html .= '		<option value="1.0" selected>1.0</option>';
	$html .= '	</select>';
	$html .= '	<br /><br />';
	$html .= '	Replace all templates? <input type="checkbox" name="replace_templates" value="1" />';
	$html .= '	<br /><br />';
	$html .= '	Replace all settings? <input type="checkbox" name="replace_settings" value="1" />';
	$html .= '	<br /><br />';
	$html .= '	<strong>Remember to delete this file after the upgrade!</strong>';
	$html .= '	<br /><br />';
	$html .= '	<input type="hidden" name="action" value="upgrade" />';
	$html .= '	<input type="submit" value="Upgrade" style="margin-left: 100px" />';
	$html .= '</form>';

	echo $html;
}
?>