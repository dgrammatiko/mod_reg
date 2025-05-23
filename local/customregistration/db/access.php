<?php
/**
 * @copyright  2025 dgrammatiko
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$capabilities = [
  'local/customregistration:manage' => [
    'captype'      => 'write',
    'contextlevel' => CONTEXT_SYSTEM,
    'archetypes'   => ['manager' => CAP_ALLOW],
  ]
];
