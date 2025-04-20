<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Plugin settings
 *
 * @package    local_ssologin
 * @copyright  2025 Richard Guedes <richard@example.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
$string['pluginname'] = 'SSO Login';
$string['secretkey'] = 'Shared Secret Key';
$string['secretkey_desc'] = 'The shared HMAC key used to sign/verify the SSO request.';
$string['tokenexpire'] = 'Token Expiry Time (seconds)';
$string['tokenexpire_desc'] = 'Maximum age of token before it is considered expired.';
$string['invalidtoken'] = 'Invalid or expired token.';
$string['loginsuccess'] = 'SSO login successful for user: {$a}';
$string['loginfailure'] = 'SSO login failed for user: {$a}';
$string['eventssologinattempted'] = 'SSO login attempted';
$string['privacy:metadata'] = 'This plugin does not store any personal data.';