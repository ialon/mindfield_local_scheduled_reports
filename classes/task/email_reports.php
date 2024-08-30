<?php

/**
 * Task definition for Scheduled Reports
 * @package    local_scheduled_reports
 * @copyright  Josemaria Bolanos <josemabol@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_scheduled_reports\task;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot.'/local/scheduled_reports/lib.php');

class email_reports extends \core\task\scheduled_task {

    /**
     * Name for this task.
     *
     * @return string
     */
    public function get_name() {
        return get_string('emailreports', 'local_scheduled_reports');
    }

    /**
     * Prepare and send emails
     */
    public function execute() {
        global $DB;

        // Get reports that are due to be sent
        $sql = "SELECT *
                  FROM {local_scheduled_reports} lsr
                 WHERE lsr.nextreport < :time AND lsr.enabled = 1";
        $params = ['time' => time()];
        $schedules = $DB->get_records_sql($sql, $params);

        foreach ($schedules as $schedule) {
            send_report($schedule);
        }
    }
}
