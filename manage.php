<?php

/**
 * Allows user to schedule a configurable report
 * Displays a list of Scheduled Reports 
 *
 * @package    local_scheduled_reports
 * @copyright  Josemaria Bolanos <josemabol@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->libdir.'/tablelib.php');
require_once($CFG->dirroot.'/local/scheduled_reports/lib.php');

admin_externalpage_setup('managescheduledreports');

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('managescheduledreports', 'local_scheduled_reports'));

// Add scheduled report button
$addreporturl = $CFG->wwwroot . '/local/scheduled_reports/edit.php';
$addreporthtml = html_writer::div(
    html_writer::link($addreporturl, get_string('addreport', 'local_scheduled_reports'), ['class' => 'btn btn-secondary']),
    'addbutton text-right'
);
echo $OUTPUT->heading($addreporthtml);

/// Print the table of all scheduled reports
$reports = get_scheduled_reports();

$table = new flexible_table('local_scheduled_reports_administration_table');
$table->define_columns(array('name', 'frequency', 'nextreport', 'actions'));
$table->define_headers(array(
    get_string('name', 'local_scheduled_reports'),
    get_string('frequency', 'local_scheduled_reports'),
    get_string('nextreport', 'local_scheduled_reports'),
    get_string('actions', 'local_scheduled_reports')
));
$table->define_baseurl($PAGE->url);
$table->set_attribute('id', 'localscheduledreports'); 
$table->set_attribute('class', 'admintable generaltable');
$table->setup();

$stredit = get_string('edit');
$strdelete = get_string('delete');
$strhide = get_string('hide');
$strshow = get_string('show');

foreach ($reports as $report) {
    $actions = '';

    // Edit link
    $actions .= html_writer::link(
        new moodle_url('/local/scheduled_reports/edit.php', ['id' => $report->id]),
        $OUTPUT->pix_icon('t/edit', $stredit),
        ['title' => $stredit]
    );

    // Delete link
    $actions .= html_writer::link(
        new moodle_url('/local/scheduled_reports/edit.php', ['id' => $report->id, 'delete' => 1, 'sesskey' => sesskey()]),
        $OUTPUT->pix_icon('t/delete', $strdelete),
        ['title' => $strdelete]
    );

    // Enable/Disable link
    if (!empty($report->enabled)) {
        $actions .= html_writer::link(
            new moodle_url('/local/scheduled_reports/edit.php', ['id' => $report->id, 'hide' => 1, 'sesskey' => sesskey()]),
            $OUTPUT->pix_icon('t/hide', $strhide),
            ['title' => $strhide]
        );
    } else {
        $actions .= html_writer::link(
            new moodle_url('/local/scheduled_reports/edit.php', ['id' => $report->id, 'show' => 1, 'sesskey' => sesskey()]),
            $OUTPUT->pix_icon('t/show', $strshow),
            ['title' => $strshow]
        );
    }

    $table->add_data(array($report->name, $report->frequency, $report->nextreport, $actions));
}

$table->print_html();

echo $OUTPUT->footer();
