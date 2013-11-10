# Pro Events 1.1
Advanced calendar events for your MyBB forum. This plugin has been designed to completely replace the default calendar system provided with MyBB.

## Features
- Event attendances through RSVPs
- RSVP limit, approval and commenting
- Automatic thread creation for the event, with basic information about the event inside the thread
- Event start notification customisable for event attendees

## Upgrading
- Upload files in /Uploads and /Upgrade to your MyBB forum
- Run proevents_upgrade.php
- Follow the instructions

## Installation
- Upload files in /Uploads to your MyBB forum
- Install from the Admin CP
 
## Notes
**On installation the plugin will attempt to import events from the default MyBB calendar. It is highly recommended that you check these events immediately after installation to make sure they imported correctly.**

In order to notify event attendees, a task was generated on installation to run every 10 minutes. You may notice a performance reduction as sending out private messages seems to slow everything down, even on my local test forum. You may want to increase the timer although that may cause issues if people want to be notified very near event start times.

## Changelog

### 1.1
- Added event notices
- Added list of ended events
- Added moderator option to change thread ID for an event
- Added attendee number to thread view
- Replaced date text fields with dropdown menus
- Fixed checking if moderator
- Fixed bug in proevents_generate_dropdown() if no forum ID's are set
- Fixed currently running events not showing in event listing

### 1.0
- First release