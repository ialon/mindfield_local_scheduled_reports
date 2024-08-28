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
    global $DB;

    $sql = "SELECT sr.id, cr.name, sr.frequency, sr.nextreport
              FROM {local_scheduled_reports} sr
         LEFT JOIN {block_configurable_reports} cr ON sr.reportid = cr.id";
    $reports = $DB->get_records_sql($sql);

    foreach ($reports as $report) {
        $report->nextreport = userdate($report->nextreport);
        $report->frequency = FREQUENCY[$report->frequency];
    }

    return $reports;
}
