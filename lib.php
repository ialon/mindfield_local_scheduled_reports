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
 * Converts a frequency value to seconds.
 *
 * @param int $frequency The frequency value to convert.
 * @return int The equivalent number of seconds.
 */
function frequency_to_seconds($frequency) {
    $seconds = 0;
    switch ($frequency) {
        case DAILY:
            $seconds = 86400;
            break;
        case WEEKLY:
            $seconds = 604800;
            break;
        case MONTHLY:
            $seconds = 2592000;
            break;
    }

    return $seconds;
}

/**
 * Retrieves custom reports from the database.
 *
 * @return array An associative array of custom reports, with the report ID as the key and the report name as the value.
 */
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

    $sql = "SELECT sr.id, cr.name, cr.courseid, sr.userid, sr.frequency, sr.nextreport, sr.enabled
              FROM {local_scheduled_reports} sr
         LEFT JOIN {block_configurable_reports} cr ON sr.reportid = cr.id
             WHERE cr.id IS NOT NULL";
    $params = [];

    // Only admins can see all the scheduled reports.
    if (!is_siteadmin()) {
        $sql .= " AND sr.userid = :userid";
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

/**
 * Retrieves the recipient users for scheduled reports.
 *
 * @param string $external The email addresses of external users, separated by commas or spaces.
 * @param string $internal The usernames of internal users, separated by commas or spaces.
 * @return array An array of recipient users, where the keys are the email addresses and the values are objects representing the users.
 */
function get_recipient_users($external, $internal) {
    global $DB;

    $recipients = [];

    // External users
    $emails = array_map('trim', preg_split("/[\s,]+/", $external, -1, PREG_SPLIT_NO_EMPTY));
    foreach ($emails as $email) {
        $user = new \stdClass();
        $user->id = 0; // to prevent anything annoying happening
        $user->email = $email;
    
        if (validate_email($user->email)) {
            $recipients[$user->email] = $user;
        }
    }

    // Internal users
    $usernames = array_map('trim', preg_split("/[\s,]+/", $internal, -1, PREG_SPLIT_NO_EMPTY));
    foreach ($usernames as $username) {
        if ($user = $DB->get_record('user', ['username' => $username])) {
            $recipients[$user->email] = $user;
        }
    }

    return $recipients;
}

/**
 * Prepares an email attachment for a scheduled report.
 *
 * @param object $report The report object.
 * @param string $preferredformat The preferred format for the report.
 * @return array An array containing the temporary file path and the filename of the attachment.
 * @throws moodle_exception If there is an error fetching the file.
 */
function prepare_email_attachment($report, $preferredformat) {
    global $CFG;

    require_once($CFG->dirroot . "/blocks/configurable_reports/locallib.php");
    require_once($CFG->dirroot . '/blocks/configurable_reports/report.class.php');
    require_once($CFG->dirroot . '/blocks/configurable_reports/reports/' . $report->type . '/report.class.php');

    // Check if the preferred format is available, default to the first available format otherwise
    $availableformats = explode(',', $report->export);
    if (in_array($preferredformat, $availableformats)) {
        $format = $preferredformat;
    } else {
        $format = reset($availableformats);
    }

    // Generate a unique filename in the temporary directory
    $tempdir = make_temp_directory('scheduled_reports');
    $tempfile = tempnam($tempdir, 'report_');

    // Generate the report
    $reportclassname = 'report_' . $report->type;
    $reportclass = new $reportclassname($report);
    
    if ($report->type === "sql") {
        $reportclass->set_forexport(true);
    }
    $reportclass->create_report();

    // Large exports are likely to take their time and memory.
    core_php_time_limit::raise();
    raise_memory_limit(MEMORY_EXTRA);

    // Require and use fixed version of the export_report function
    require_fixed_export_report($format, $tempdir);
    ob_start();
    export_report($reportclass->finalreport);
    $content = ob_get_contents();
    ob_end_clean();

    if ($content === false) {
        throw new moodle_exception('errorfetchingfile', 'local_scheduled_reports');
    }

    // Save the file to the temporary directory
    if (!file_put_contents($tempfile, $content)) {
        throw new moodle_exception('errorsavingfile', 'local_scheduled_reports');
    }

    // Prepare file name
    $filename = 'report_' . (time()) . '.' . $format;
    $filename = clean_filename($filename);

    return [$tempfile, $filename];
}

/**
 * Requires and executes a fixed export report.
 *
 * This function saves the export file to the temporary directory and removes the exit statement from the export file.
 * It also handles special cases for CSV and ODS formats by modifying the export file content accordingly.
 *
 * @param string $format The format of the export report.
 * @param string $tempdir The temporary directory to save the export file.
 * @return void
 */
function require_fixed_export_report($format, $tempdir) {
    global $CFG;

    // Save the export file to the temporary directory
    $tempexport = tempnam($tempdir, 'export_');

    // Hacky way to remove the exit statement from the export file
    $export = file_get_contents($CFG->dirroot . '/blocks/configurable_reports/export/' . $format . '/export.php');
    $export = str_replace('exit;', '', $export);

    // Special cases for CSV and ODS
    if ($format === 'csv') {
        $export = str_replace(
            '$csvexport->download_file();',
            '$csvexport->print_csv_data();',
            $export
        );
    } elseif ($format === 'ods') {
        $export = str_replace(
            '$workbook->close();',
            '$sheets = new ReflectionProperty("MoodleODSWorkbook", "worksheets");
            $sheets->setAccessible(true);
            $writer = new MoodleODSWriter($sheets->getValue($workbook));
            echo $writer->get_file_content();',
            $export
        );
    }

    file_put_contents($tempexport, $export);
    require_once($tempexport);
    unlink($tempexport);
}

/**
 * Sends a scheduled report to recipients.
 *
 * @param object $schedule The schedule object containing information about the report.
 * @return void
 */
function send_report($schedule) {
    global $DB;

    // Get the user
    $from = $DB->get_record('user', ['id' => $schedule->userid]);

    // Get the report
    $report = $DB->get_record('block_configurable_reports', ['id' => $schedule->reportid]);

    // Get the recipients
    $recipients = get_recipient_users($schedule->external_users, $schedule->internal_users);

    // Prepare the email
    $messagehtml = get_string('messagebody', 'local_scheduled_reports', $report->name);

    // Prepare the attachment
    list($attachment, $attachmentname) = prepare_email_attachment($report, $schedule->format);

    // Send the email
    foreach ($recipients as $recipient) {
        email_to_user(
            $recipient,
            $from,
            $report->name,
            html_to_text($messagehtml),
            $messagehtml,
            $attachment,
            $attachmentname
        );
    }

    // Clean up
    unlink($attachment);

    // Update the next report date
    $seconds = frequency_to_seconds($schedule->frequency);
    $schedule->nextreport = time() + $seconds;
    $DB->update_record('local_scheduled_reports', $schedule);
}
