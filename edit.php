<?php

/**
 * Scheduled Reports plugin for scheduling and sending Configurable Reports
 *
 * @package    local_scheduled_reports
 * @copyright  Josemaria Bolanos <josemabol@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("../../config.php");
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/local/scheduled_reports/lib.php');

$id = optional_param('id', 0, PARAM_INT);
$delete = optional_param('delete', 0, PARAM_BOOL);
$confirm = optional_param('confirm', 0, PARAM_BOOL);
$enable = optional_param('enable', 0, PARAM_BOOL);
$disable = optional_param('disable', 0, PARAM_BOOL);
$send = optional_param('send', 0, PARAM_BOOL);

require_login();
$context = context_system::instance();

$PAGE->set_context($context);

$localpluginsurl = new moodle_url($CFG->wwwroot . '/admin/category.php?category=localplugins');
$PAGE->navbar->add(get_string('localplugins'), $localpluginsurl);

$manageurl = new moodle_url($CFG->wwwroot . '/local/scheduled_reports/manage.php');
$PAGE->navbar->add(get_string('managescheduledreports', 'local_scheduled_reports'), $manageurl);

if ($id) {
    if (!$schedule = $DB->get_record('local_scheduled_reports', ['id' => $id])) {
        throw new moodle_exception('scheduledoesnotexist', 'local_scheduled_reports');
    }

    if (!$report = $DB->get_record('block_configurable_reports', ['id' => $schedule->reportid])) {
        throw new moodle_exception('reportdoesnotexists', 'block_configurable_reports');
    }

    $title = format_string($report->name);

    $PAGE->set_url('/local/scheduled_reports/edit.php', ['id' => $id]);
} else {
    $title = get_string('newschedule', 'local_scheduled_reports');
    $PAGE->set_url('/local/scheduled_reports/edit.php', null);
}

$PAGE->navbar->add($title);

// Common actions.
if (($enable || $disable) && confirm_sesskey()) {
    $enabled = ($enable) ? 1 : 0;
    if (!$DB->set_field('local_scheduled_reports', 'enabled', $enabled, ['id' => $schedule->id])) {
        throw new moodle_exception('cannotupdateschedule', 'local_scheduled_reports');
    }

    header("Location: $CFG->wwwroot/local/scheduled_reports/manage.php");
    die;
}

if ($delete && confirm_sesskey()) {
    if (!$confirm) {
        $PAGE->set_title($title);
        $PAGE->set_heading($title);
        echo $OUTPUT->header();
        $message = get_string('confirmdeleteschedule', 'local_scheduled_reports');
        $optionsyes = ['id' => $schedule->id, 'delete' => $delete, 'sesskey' => sesskey(), 'confirm' => 1];
        $optionsno = [];
        $buttoncontinue = new single_button(new moodle_url('edit.php', $optionsyes), get_string('yes'), 'get');
        $buttoncancel = new single_button(new moodle_url('manage.php', $optionsno), get_string('no'), 'get');
        echo $OUTPUT->confirm($message, $buttoncontinue, $buttoncancel);
        echo $OUTPUT->footer();
        exit;
    }

    $DB->delete_records('local_scheduled_reports', ['id' => $schedule->id]);
    header("Location: $CFG->wwwroot/local/scheduled_reports/manage.php");
    die;
}

if ($send && confirm_sesskey()) {
    if (!$confirm) {
        $PAGE->set_title($title);
        $PAGE->set_heading($title);
        echo $OUTPUT->header();
        $message = get_string('confirmsendnow', 'local_scheduled_reports');
        $optionsyes = ['id' => $schedule->id, 'send' => $send, 'sesskey' => sesskey(), 'confirm' => 1];
        $optionsno = [];
        $buttoncontinue = new single_button(new moodle_url('edit.php', $optionsyes), get_string('yes'), 'get');
        $buttoncancel = new single_button(new moodle_url('manage.php', $optionsno), get_string('no'), 'get');
        echo $OUTPUT->confirm($message, $buttoncontinue, $buttoncancel);
        echo $OUTPUT->footer();
        exit;
    }

    send_report($schedule);

    \core\notification::add(
        get_string('reportssent', 'local_scheduled_reports'),
        \core\notification::SUCCESS
    );

    header("Location: $CFG->wwwroot/local/scheduled_reports/manage.php");
    die;
}

require_once('edit_form.php');

if (!$availablereports = get_custom_reports()) {
    redirect($CFG->wwwroot . '/local/scheduled_reports/manage.php');
}

$exportplugins = get_list_of_plugins('blocks/configurable_reports/export');
$availableformats = [];
foreach ($exportplugins as $plugin) {
    $availableformats[$plugin] = get_string('export_' . $plugin, 'block_configurable_reports');
}

if (!empty($schedule)) {
    $schedule->nextreportdate = userdate($schedule->nextreport, get_string('strftimedate'));
    $editform = new edit_form('edit.php', compact('availablereports', 'availableformats', 'schedule'));
} else {
    $editform = new edit_form('edit.php', compact('availablereports', 'availableformats'));
}

if (!empty($schedule)) {
    $editform->set_data($schedule);
}

if ($editform->is_cancelled()) {
    redirect($CFG->wwwroot . '/local/scheduled_reports/manage.php');
} else if ($data = $editform->get_data()) {
    if (empty($data->enabled)) {
        $data->enabled = 0;
    }

    if (empty($data->reschedule)) {
        $data->reschedule = 0;
    }

    if (!empty($data->internal_users)) {
        $usernames = array_map('trim', preg_split("/[\s,]+/", $data->internal_users, -1, PREG_SPLIT_NO_EMPTY));
        $usernames = array_unique($usernames);
        $data->internal_users = implode(PHP_EOL, $usernames);
    }

    if (!empty($data->external_users)) {
        $emails = array_map('trim', preg_split("/[\s,]+/", $data->external_users, -1, PREG_SPLIT_NO_EMPTY));
        $emails = array_unique($emails);
        $data->external_users = implode(PHP_EOL, $emails);
    }

    if (empty($schedule)) {
        $data->userid = $USER->id;
        $data->nextreport = calculate_next_report($data->frequency);

        if (!$lastid = $DB->insert_record('local_scheduled_reports', $data)) {
            throw new moodle_exception('errorsavingschedule', 'local_scheduled_reports');
        }
    } else {
        if ($data->reschedule || $schedule->frequency != $data->frequency) {
            $data->nextreport = calculate_next_report($data->frequency);
        }

        if (!$DB->update_record('local_scheduled_reports', $data)) {
            throw new moodle_exception('errorsavingschedule', 'local_scheduled_reports');
        }
    }

    redirect($CFG->wwwroot . '/local/scheduled_reports/manage.php');
}

$PAGE->set_heading($title);

echo $OUTPUT->header();

$editform->display();

echo $OUTPUT->footer();
