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
 * @copyright  2025 Richard Guedes  - Instituto de Defesa Cibernética (IDCiber)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
$string['eventssologinattempted'] = 'SSO login attempted';
$string['invalidtoken'] = 'Invalid or expired token.';
$string['jitprovisioning'] = 'Enable JIT Provisioning';
$string['jitprovisioning_desc'] = 'When enabled, new Moodle user accounts will be created automatically on first SSO login if they do not exist. The payload must include email, firstname, and lastname fields.';
$string['legacymode'] = 'Enable Legacy Mode';
$string['legacymode_desc'] = 'When enabled, the plugin will allow authentication requests that do not follow the new security standards (e.g., missing HMAC signatures). Use this only during transition periods.';
$string['loginfailure'] = 'SSO login failed for user: {$a}';
$string['loginsuccess'] = 'SSO login successful for user: {$a}';
$string['pluginname'] = 'SSO Login';
$string['privacy:metadata'] = 'This plugin stores nonces and timestamps to prevent replay attacks and ensure security during the authentication process.';
$string['privacy:metadata:nonce'] = 'A unique identifier (nonce) for each login request.';
$string['privacy:metadata:noncedesc'] = 'This table stores nonces to prevent the same login request from being used multiple times (replay attack).';
$string['privacy:metadata:timecreated'] = 'The timestamp when the login request was processed.';
$string['profilesync'] = 'Enable Profile Sync';
$string['profilesync_desc'] = 'When enabled, the Moodle user profile (name, email, institution, etc.) will be updated on every SSO login with the most recent data from the payload.';
$string['secretkey'] = 'Shared Secret Key';
$string['secretkey_desc'] = 'The shared HMAC key used to sign/verify the SSO request.';
$string['tokenexpire'] = 'Token Expiry Time (seconds)';
$string['tokenexpire_desc'] = 'Maximum age of token before it is considered expired.';
