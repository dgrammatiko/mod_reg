<?php
/**
 * @copyright  2025 dgrammatiko
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
  $settings = new admin_settingpage('local_customregistration', get_string('pluginname', 'local_customregistration'));

  $settings->add(new admin_setting_configtext(
    'local_customregistration/welcome_email_subject',
    'Welcome Email Subject',
    'Subject line for the welcome email sent to new users',
    'Welcome to ' . get_config('', 'fullname')
  ));

  $settings->add(new admin_setting_configtextarea(
    'local_customregistration/welcome_email_body',
    'Welcome Email Body Template',
    'Template for the welcome email body. Use {firstname}, {lastname}, {email}, {password}, {sitename}, {loginurl} as placeholders.',
    'Dear {firstname} {lastname},

Welcome to {sitename}!

Your account has been created successfully. Here are your login details:

Username: {email}
Temporary Password: {password}

IMPORTANT: You must change your password when you first log in.

Login at: {loginurl}

Best regards,
{sitename} Team'
  ));

  $ADMIN->add('localplugins', $settings);
}
