<?php

/**
 * Task definition for Scheduled Reports
 * @package    local_scheduled_reports
 * @copyright  Josemaria Bolanos <josemabol@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$tasks = array(
    array(
        'classname' => '\local_scheduled_reports\task\email_reports',
        'blocking' => 0,
        'minute' => '0',
        'hour' => '2',
        'day' => '*',
        'month' => '*',
        'dayofweek' => '*'
    )
);
