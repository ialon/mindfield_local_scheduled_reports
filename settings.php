<?php
/**
 * Settings for scheduled Reports 
 *
 * @package    local_scheduled_reports
 * @copyright  Josemaria Bolanos <josemabol@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($hassiteconfig) {
    $page = new admin_externalpage(
        'managescheduledreports',
        new lang_string('managescheduledreports', 'local_scheduled_reports'),
        $CFG->wwwroot . '/local/scheduled_reports/manage.php'
    );
    $ADMIN->add('localplugins', $page);
}
