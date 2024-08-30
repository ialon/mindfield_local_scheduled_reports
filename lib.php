<?php

/**
 * This file contains functions used by the scheduled reports plugin
 *
 * @package    local_scheduled_reports
 * @copyright  Josemaria Bolanos <josemabol@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

define("DAILY", 1);
define("WEEKLY", 2);
define("MONTHLY", 3);

// Define constants
define("FREQUENCY", [
    DAILY => get_string('daily', 'local_scheduled_reports'),
    WEEKLY => get_string('weekly', 'local_scheduled_reports'),
    MONTHLY => get_string('monthly', 'local_scheduled_reports')
]);

function get_custom_reports() {
    global $DB, $USER;

    $params = [];

    // Only admins can see all the reports.
    if (!is_siteadmin()) {
        $params['userid'] = $USER->id;
    }

    return $DB->get_records_menu('block_configurable_reports', $params, '', 'id, name');
}

/**
 * Retrieves a list of scheduled reports.
 *
 * This function queries the database to retrieve a list of scheduled reports.
 * Each report includes the report ID, name, frequency, and the next report date.
 *
 * @global moodle_database $DB The global Moodle database object.
 * @return array An array of scheduled reports, each containing the report ID, name, frequency, and next report date.
 */
function get_scheduled_reports() {
    global $DB, $USER;

    $sql = "SELECT sr.id, cr.name, sr.userid, sr.frequency, sr.nextreport, sr.enabled
              FROM {local_scheduled_reports} sr
         LEFT JOIN {block_configurable_reports} cr ON sr.reportid = cr.id";
    $params = [];

    // Only admins can see all the scheduled reports.
    if (!is_siteadmin()) {
        $sql .= " WHERE sr.userid = :userid";
        $params['userid'] = $USER->id;
    }

    $reports = $DB->get_records_sql($sql, $params);

    foreach ($reports as $report) {
        // Get the user's full name.
        if ($report->userid != $USER->id) {
            $user = $DB->get_record('user', ['id' => $report->userid]);
        } else {
            $user = $USER;
        }
        $report->username = fullname($user);

        // Format the next report date.

        $report->nextreport = userdate($report->nextreport, get_string('strftimedate'));
        $report->frequency = FREQUENCY[$report->frequency];
    }

    return $reports;
}
