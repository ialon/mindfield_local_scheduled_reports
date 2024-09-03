<?php
/**
 * Scheduled Reports plugin for scheduling and sending Configurable Reports
 *
 * @package    local_scheduled_reports
 * @copyright  Josemaria Bolanos <josemabol@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = "Scheduled Reports";
$string['emailreports'] = "Send scheduled custom reports";
$string['managescheduledreports'] = "Manage scheduled reports";

$string['name'] = "Report";
$string['owner'] = "Owner";
$string['frequency'] = "Frequency";
$string['format'] = "Preferred format";
$string['format_help'] = "The format to use if it is enabled for the selected report.";
$string['nextreport'] = "Next report";
$string['reschedule'] = "Reschedule next report";
$string['reschedule_help'] = "Resets the next report date counting from today";
$string['enable'] = "Enable";
$string['disable'] = "Disable";
$string['active'] = "Active";
$string['actions'] = "Actions";
$string['sendnow'] = "Send now";
$string['addreport'] = "Add report";
$string['customreport'] = "Custom report";
$string['internalusers'] = "Internal users";
$string['internalusers_help'] = "A list of usernames to send the report to separated by either commas or new lines";
$string['externalusers'] = "External users";
$string['externalusers_help'] = "A list of emails to send the report to separated by either commas or new lines";
$string['daily'] = "Daily";
$string['weekly'] = "Weekly";
$string['every_monday'] = "Every Monday";
$string['every_tuesday'] = "Every Tuesday";
$string['every_wednesday'] = "Every Wednesday";
$string['every_thursday'] = "Every Thursday";
$string['every_friday'] = "Every Friday";
$string['every_saturday'] = "Every Saturday";
$string['every_sunday'] = "Every Sunday";
$string['biweekly'] = "Biweekly";
$string['monthly'] = "Monthly";
$string['start_month'] = "Start of the month";
$string['end_month'] = "End of the month";

$string['scheduledoesnotexist'] = "No such schedule with this ID";
$string['newschedule'] = "New scheduled report";
$string['cannotupdateschedule'] = "Cannot update schedule";
$string['confirmdeleteschedule'] = "Are you sure you want to delete this scheduled report?";
$string['confirmsendnow'] = "Are you sure you want to send these reports now?";
$string['reportssent'] = "The reports have been sent";
$string['errorsavingschedule'] = "Error saving schedule";
$string['invalidusername'] = "The list contains an invalid username";
$string['invalidemail'] = "The list of emails contains an invalid address";
$string['requiredusers'] = "You must specify either internal or external users";
$string['errorfetchingfile'] = "Error fetching file";
$string['errorsavingfile'] = "Error saving file";

$string['messagebody'] = '<p>Here is the scheduled report you requested: {$a}</p>';
