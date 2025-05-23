<?php
/**
 * @copyright  2025 dgrammatiko
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/user/lib.php');

/**
 * Creates a new user account and sends welcome email with temporary password
 *
 * @param object $data Form data containing user information
 * @return array Result array with success status and message
 */
function local_customregistration_create_user($data)
{
  global $DB, $CFG;

  try {
    // Start transaction
    $transaction = $DB->start_delegated_transaction();

    // Generate temp password
    $temppassword = local_customregistration_generate_password(12);

    // Clean and prepare user data
    $username  = clean_param(strtolower($data->email), PARAM_USERNAME);
    $email     = clean_param(strtolower($data->email), PARAM_EMAIL);
    $firstname = clean_param($data->firstname, PARAM_TEXT);
    $lastname  = clean_param($data->lastname, PARAM_TEXT);
    $country   = clean_param($data->country, PARAM_ALPHA);
    $mobile    = clean_param($data->mobile, PARAM_TEXT);

    // Create user object with required fields
    $user = (object) [
      'username' => $username,
      'email'         => $email,
      'firstname'     => $firstname,
      'lastname'      => $lastname,
      'country'       => $country,
      'phone2'        => $mobile,
      'password'      => hash_internal_user_password($temppassword),
      'confirmed'     => 1,
      'mnethostid'    => $CFG->mnet_localhost_id,
      'timecreated'   => time(),
      'timemodified'  => time(),
      'auth'          => 'manual',
      'lang'          => current_language(),
      'timezone'      => core_date::get_server_timezone(),
      'mailformat'    => 1, // HTML email format
      'maildigest'    => 0,
      'maildisplay'   => 2,
      'autosubscribe' => 1,
      'trackforums'   => 0,
      'trustbitmask'  => 0
    ];

    // Insert user into database
    $userid = user_create_user($user, false, false);

    if ($userid) {
      // Get the complete user record
      $newuser = core_user::get_user($userid);

      // Set password change preference
      set_user_preference('auth_forcepasswordchange', 1, $userid);

      // Log the registration
      local_customregistration_log_registration($newuser, $mobile, $country);

      // Send welcome email
      $emailsent = local_customregistration_send_welcome_email($newuser, $temppassword);

      // Update log with email status
      if ($emailsent) {
        $DB->set_field('local_customregistration_log', 'emailsent', 1, ['userid' => $userid]);
      }

      // Trigger user created event
      \core\event\user_created::create_from_userid($userid)->trigger();

      // Commit transaction
      $transaction->allow_commit();

      return ['success' => true, 'message' => 'Registration successful'];
    } else {
      $transaction->rollback(new Exception('Failed to create user'));
      return ['success' => false, 'message' => get_string('registrationfailed', 'local_customregistration')];
    }
  } catch (Exception $e) {
    if (isset($transaction)) {
      $transaction->rollback($e);
    }
    debugging('Registration error: ' . $e->getMessage(), DEBUG_DEVELOPER);
    return ['success' => false, 'message' => get_string('registrationfailed', 'local_customregistration')];
  }
}

/**
 * Sends welcome email with temporary password
 *
 * @param object $user User object
 * @param string $temppassword Temporary password
 */
function local_customregistration_send_welcome_email($user, $temppassword)
{
  global $CFG, $SITE;

  try {
    $from = core_user::get_support_user();

    $subject = 'Welcome to ' . format_string($SITE->fullname);

    $messagetext = "Dear " . fullname($user) . ",\n\n";
    $messagetext .= "Welcome to " . format_string($SITE->fullname) . "!\n\n";
    $messagetext .= "Your account has been successfully created. Here are your login details:\n\n";
    $messagetext .= "Username: {$user->email}\n";
    $messagetext .= "Temporary Password: {$temppassword}\n\n";
    $messagetext .= "IMPORTANT: You will be required to change your password when you first log in.\n\n";
    $messagetext .= "You can log in at: {$CFG->wwwroot}/login/\n\n";
    $messagetext .= "If you have any questions, please contact our support team.\n\n";
    $messagetext .= "Best regards,\n";
    $messagetext .= format_string($SITE->fullname) . "\n";

    // HTML version
    $messagehtml = "<p>Dear " . fullname($user) . ",</p>";
    $messagehtml .= "<p>Welcome to " . format_string($SITE->fullname) . "!</p>";
    $messagehtml .= "<p>Your account has been successfully created. Here are your login details:</p>";
    $messagehtml .= "<p><strong>Username:</strong> " . s($user->email) . "<br>";
    $messagehtml .= "<strong>Temporary Password:</strong> " . s($temppassword) . "</p>";
    $messagehtml .= "<p><strong>IMPORTANT:</strong> You will be required to change your password when you first log in.</p>";
    $messagehtml .= "<p>You can log in at: <a href='" . $CFG->wwwroot . "/login/'>" . $CFG->wwwroot . "/login/</a></p>";
    $messagehtml .= "<p>If you have any questions, please contact our support team.</p>";
    $messagehtml .= "<p>Best regards,<br>" . format_string($SITE->fullname) . "</p>";

    // Use Moodle's message API instead of direct email
    $message = new \core\message\message();
    $message->component = 'local_customregistration';
    $message->name = 'registration';
    $message->userfrom = $from;
    $message->userto = $user;
    $message->subject = $subject;
    $message->fullmessage = $messagetext;
    $message->fullmessageformat = FORMAT_PLAIN;
    $message->fullmessagehtml = $messagehtml;
    $message->smallmessage = 'Welcome to ' . format_string($SITE->fullname);

    return message_send($message);
  } catch (Exception $e) {
    debugging('Error sending welcome email: ' . $e->getMessage(), DEBUG_DEVELOPER);
    return false;
  }
}

/**
 * Logs registration attempt
 *
 * @param object $user User object
 * @param string $mobile Mobile number
 * @param string $country Country code
 */
function local_customregistration_log_registration($user, $mobile, $country)
{
  global $DB;

  try {
    $log = (object) [
      'userid'      => $user->id,
      'email'       => $user->email,
      'mobile'      => $mobile,
      'country'     => $country,
      'timecreated' => time(),
      'emailsent'   => 0
    ];

    $DB->insert_record('local_customregistration_log', $log);
  } catch (Exception $e) {
    debugging('Failed to log registration: ' . $e->getMessage(), DEBUG_DEVELOPER);
  }
}

/**
 * Generates a random password
 *
 * @param int $length Length of password
 * @return string Generated password
 */
function local_customregistration_generate_password($length = 12)
{
  // Use Moodle's generate_password function if available
  if (function_exists('generate_password')) {
    return generate_password($length);
  }

  // Fallback password generation
  $lowercase = 'abcdefghijklmnopqrstuvwxyz';
  $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
  $numbers   = '0123456789';
  $symbols   = '!@#$%^&*';
  $password  = '';

  // Ensure at least one character from each type
  $password .= $lowercase[mt_rand(0, strlen($lowercase) - 1)];
  $password .= $uppercase[mt_rand(0, strlen($uppercase) - 1)];
  $password .= $numbers[mt_rand(0, strlen($numbers) - 1)];
  $password .= $symbols[mt_rand(0, strlen($symbols) - 1)];

  // Fill the rest randomly
  $allchars = $lowercase . $uppercase . $numbers . $symbols;
  for ($i = 4; $i < $length; $i++) {
    $password .= $allchars[mt_rand(0, strlen($allchars) - 1)];
  }

  // Shuffle the password
  return str_shuffle($password);
}
