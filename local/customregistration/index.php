<?php
/**
 * @copyright  2025 dgrammatiko
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->dirroot . '/local/customregistration/classes/registration_form.php');
require_once($CFG->dirroot . '/local/customregistration/lib.php');

// Set up the page
$PAGE->set_url('/local/customregistration/index.php');
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('registration', 'local_customregistration'));
$PAGE->set_heading(get_string('registration', 'local_customregistration'));
$PAGE->set_pagelayout('login');

// Check if user is already logged in
if (isloggedin() && !isguestuser()) {
  redirect($CFG->wwwroot);
}

// Check if self registration is enabled
if (empty($CFG->registerauth)) {
  throw new moodle_exception('registrationdisabled', 'error');
}

// Create the form
$mform = new local_customregistration_form();

// Handle form submission
if ($mform->is_cancelled()) {
  redirect($CFG->wwwroot);
} else if ($data = $mform->get_data()) {
  $result = local_customregistration_create_user($data);

  if ($result['success']) {
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('registration', 'local_customregistration'));
    echo $OUTPUT->box_start('generalbox centerpara');
    echo html_writer::tag(
      'p',
      get_string('registrationsuccess', 'local_customregistration'),
      array('class' => 'alert alert-success')
    );
    echo html_writer::link(
      $CFG->wwwroot . '/login/',
      get_string('login'),
      array('class' => 'btn btn-primary')
    );
    echo $OUTPUT->box_end();
    echo $OUTPUT->footer();
    exit;
  } else {
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('registration', 'local_customregistration'));
    echo $OUTPUT->notification($result['message'], 'error');
    $mform->display();
    echo $OUTPUT->footer();
    exit;
  }
}

// Display the form
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('registration', 'local_customregistration'));

echo $OUTPUT->box_start('generalbox');
echo html_writer::tag('p', 'Please fill in all the required fields to create your account. You will receive an email with your login details.');
echo $OUTPUT->box_end();

$mform->display();
echo $OUTPUT->footer();
