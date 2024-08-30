<?php

/**
 * Scheduled Reports plugin for scheduling and sending Configurable Reports
 *
 * @package    local_scheduled_reports
 * @copyright  Josemaria Bolanos <josemabol@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;
require_once($CFG->libdir . '/formslib.php');
require_once($CFG->dirroot.'/local/scheduled_reports/lib.php');

/**
 * Class edit_form
*/
class edit_form extends moodleform {

    /**
     * Form definition
     */
    public function definition(): void {
        global $CFG;

        $mform =& $this->_form;

        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Custom report
        $mform->addElement(
            'select',
            'reportid',
            get_string('customreport', 'local_scheduled_reports'),
            $this->_customdata['availablereports']
        );

        // Enable/disable.
        $mform->addElement(
            'checkbox',
            'enabled',
            get_string('active', 'local_scheduled_reports'),
            ' '
        );
        $mform->setDefault('enabled', 1);

        // Frequency
        $mform->addElement(
            'select',
            'frequency',
            get_string('frequency', 'local_scheduled_reports'),
            FREQUENCY
        );
        $mform->setDefault('frequency', MONTHLY);

        // Preferred format
        $mform->addElement(
            'select',
            'format',
            get_string('format', 'local_scheduled_reports'),
            $this->_customdata['availableformats']
        );
        $mform->addHelpButton('format', 'format', 'local_scheduled_reports');

        // Internal users
        $mform->addElement('textarea', 'internal_users', get_string('internalusers', 'local_scheduled_reports'), 'cols="40" rows="4"');
        $mform->setType('internal_users', PARAM_RAW);
        $mform->addHelpButton('internal_users', 'internalusers', 'local_scheduled_reports');

        // External users
        $mform->addElement('textarea', 'external_users', get_string('externalusers', 'local_scheduled_reports'), 'cols="40" rows="4"');
        $mform->setType('external_users', PARAM_RAW);
        $mform->addHelpButton('external_users', 'externalusers', 'local_scheduled_reports');

        // Next report
        if (isset($this->_customdata['schedule'])) {
            $mform->addElement(
                'static',
                'nextreportdate',
                get_string('nextreport', 'local_scheduled_reports')
            );

            // Reschedule next report
            $mform->addElement(
                'checkbox',
                'reschedule',
                get_string('reschedule', 'local_scheduled_reports'),
                ' '
            );
            $mform->setDefault('reschedule', 0);
            $mform->addHelpButton('reschedule', 'reschedule', 'local_scheduled_reports');
        }

        if (isset($this->_customdata['schedule']->id) && $this->_customdata['schedule']->id) {
            $mform->addElement('hidden', 'id', $this->_customdata['schedule']->id);
            $mform->setType('id', PARAM_INT);
        }

        // Buttons.
        $this->add_action_buttons(true, get_string('savechanges'));
    }

    function validation($data, $files) {
        global $DB;

        $errors = parent::validation($data, $files);

        $internalusers = array_map('trim', preg_split("/[\s,]+/", $data['internal_users'], -1, PREG_SPLIT_NO_EMPTY));
        $externalemails = array_map('trim', preg_split("/[\s,]+/", $data['external_users'], -1, PREG_SPLIT_NO_EMPTY));

        // Check if internal or external users are set
        if (empty($internalusers) && empty($externalemails)) {
            $errors['internal_users'] = get_string('requiredusers', 'local_scheduled_reports');
        }

        // Check if internal usernames are valid
        $validusernames = true;
        foreach ($internalusers as $username) {
            $validusernames &= $DB->record_exists('user', ['username' => $username]);
        }
        if (!$validusernames) {
            $errors['internal_users'] = get_string('invalidusername', 'local_scheduled_reports');
        }

        // Check if external emails are valid
        $validemails = true;
        foreach ($externalemails as $email) {
            $validemails &= validate_email($email);
        }
        if (!$validemails) {
            $errors['external_users'] = get_string('invalidemail', 'local_scheduled_reports');
        }

        return $errors;
    }
}
