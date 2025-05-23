<?php
defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/formslib.php");

/**
 * Registration form class
 *
 * @copyright  2025 dgrammatiko
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_customregistration_form extends moodleform
{

  public function definition()
  {
    $mform = $this->_form;

    // Add form header
    $mform->addElement('header', 'registrationheader', get_string('registration', 'local_customregistration'));

    // First name
    $mform->addElement('text', 'firstname', get_string('firstname', 'local_customregistration'));
    $mform->setType('firstname', PARAM_TEXT);
    $mform->addRule('firstname', get_string('required'), 'required', null, 'client');

    // Last name
    $mform->addElement('text', 'lastname', get_string('lastname', 'local_customregistration'));
    $mform->setType('lastname', PARAM_TEXT);
    $mform->addRule('lastname', get_string('required'), 'required', null, 'client');

    // Email field
    $mform->addElement('text', 'email', get_string('email', 'local_customregistration'));
    $mform->setType('email', PARAM_EMAIL);
    $mform->addRule('email', get_string('required'), 'required', null, 'client');

    // Country dropdown
    $countries = get_string_manager()->get_list_of_countries();
    $countries = array('' => 'Choose...') + $countries; // Add empty option
    $mform->addElement('select', 'country', get_string('country', 'local_customregistration'), $countries);
    $mform->addRule('country', get_string('required'), 'required', null, 'client');

    // Mobile number
    $mform->addElement('text', 'mobile', get_string('mobile', 'local_customregistration'));
    $mform->setType('mobile', PARAM_TEXT);
    $mform->addRule('mobile', get_string('required'), 'required', null, 'client');

    // Submit button
    $this->add_action_buttons(true, get_string('submit', 'local_customregistration'));
  }

  public function validation($data, $files)
  {
    global $DB;
    $errors = parent::validation($data, $files);

    // Check if email already exists
    if ($DB->record_exists('user', ['email' => $data['email'], 'deleted' => 0])) {
      $errors['email'] = get_string('emailexists', 'local_customregistration');
    }

    // Validate mobile number
    if (!empty($data['mobile']) && !preg_match('/^[\+]?[\d\s\-\(\)]{7,15}$/', $data['mobile'])) {
      $errors['mobile'] = get_string('invalidmobile', 'local_customregistration');
    }

    return $errors;
  }
}
