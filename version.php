<?php
/**
 * Version details for Local Scheduled Reports
 *
 * @package    local_scheduled_reports
 * @copyright  Josemaria Bolanos <josemabol@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->version   = 2024082800;                // The current plugin version (Date: YYYYMMDDXX).
$plugin->requires  = 2017111300;                // Requires this Moodle version.
$plugin->component = 'local_scheduled_reports'; // Full name of the plugin (used for diagnostics).
$plugin->dependencies = array(
    'block_configurable_reports' => 2023121803, // This plugin requires block_configurable_reports version 2023121803 or higher.
);
