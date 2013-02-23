#Pro Events 1.0
Advanced calendar events for your MyBB forum. This plugin has been designed to completely replace the default calendar system provided with MyBB.

##Features
- event attendances through RSVPs
- RSVP limit, approval and commenting
- automatic thread creation for the event, with basic information about the event inside the thread
- event start notification customisable to event attendees

##Installation
- Upload all files to your MyBB forum. 
- Install from the Admin CP.
 
##Notes
**On installation the plugin will attempt to import events from the default MyBB calendar. It is highly recommended that you check these events immediately after installation to make sure they imported correctly.**

In order to notify event attendees, a task was generated on installation to run every 10 minutes. You may notice a performance reduction as sending out private messages seems to slow everything down, even on my local test forum. You may want to increase the timer although that may cause issues if people want to be notified very near event start times.