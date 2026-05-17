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
/**
 * Returns whether Legacy Mode is currently enabled.
 *
 * @return bool True if legacy mode is enabled.
 */
function local_ssologin_is_legacy_mode(): bool {
    return (bool) get_config('local_ssologin', 'legacymode');
}

/**
 * Verifies the HMAC token.
 *
 * @param string $data The raw data to verify.
 * @param string $signature The HMAC signature.
 * @param string $secret The secret key.
 * @return bool True if the token is valid, false otherwise.
 */
function local_ssologin_verify_token($data, $signature, $secret) {
    if (empty($data) || empty($signature)) {
        return false;
    }
    return hash_equals(hash_hmac('sha256', $data, $secret), $signature);
}

/**
 * Decrypts the given encrypted string.
 *
 * @param string $encrypted The encrypted string (Base64).
 * @param string $secret The secret key.
 * @return string|false The decrypted string or false on failure.
 */
function local_ssologin_decrypt($encrypted, $secret) {
    $decoded = base64_decode($encrypted, true);
    if ($decoded === false || strpos($decoded, '::') === false) {
        return false;
    }

    $parts = explode('::', $decoded, 2);
    if (count($parts) !== 2) {
        return false;
    }

    [$ciphertext, $iv] = $parts;

    // Ensure the key is exactly 32 bytes for AES-256.
    $key = hash('sha256', $secret, true);

    return openssl_decrypt($ciphertext, 'aes-256-cbc', $key, 0, $iv);
}

/**
 * Checks if a nonce has already been used.
 *
 * @param string $nonce The nonce to check.
 * @return bool True if used, false otherwise.
 */
function local_ssologin_is_nonce_used($nonce) {
    global $DB;
    return $DB->record_exists('local_ssologin_nonces', ['nonce' => $nonce]);
}

/**
 * Saves a used nonce to the database.
 *
 * @param string $nonce The nonce to save.
 * @return void
 */
function local_ssologin_save_nonce($nonce) {
    global $DB;
    $record = new \stdClass();
    $record->nonce = $nonce;
    $record->timecreated = time();
    $DB->insert_record('local_ssologin_nonces', $record);
}

/**
 * Provisions (creates) a new Moodle user from an SSO payload on first access (JIT).
 *
 * Required payload fields: username, email, firstname, lastname.
 *
 * @param array $payload The decoded SSO payload.
 * @return stdClass|false The newly created user object, or false on failure.
 */
function local_ssologin_provision_user(array $payload) {
    global $CFG;
    require_once($CFG->dirroot . '/user/lib.php');

    $requiredfields = ['username', 'email', 'firstname', 'lastname'];
    foreach ($requiredfields as $field) {
        if (empty($payload[$field])) {
            return false;
        }
    }

    $user = new \stdClass();
    $user->auth        = 'manual';
    $user->confirmed   = 1;
    $user->mnethostid  = 1;
    $user->username    = core_text::strtolower(trim($payload['username']));
    $user->email       = trim($payload['email']);
    $user->firstname   = trim($payload['firstname']);
    $user->lastname    = trim($payload['lastname']);
    $user->lang        = isset($payload['lang']) ? $payload['lang'] : $CFG->lang;
    $user->country     = isset($payload['country']) ? $payload['country'] : '';
    $user->city        = isset($payload['city']) ? $payload['city'] : '';
    $user->institution = isset($payload['institution']) ? $payload['institution'] : '';
    $user->department  = isset($payload['department']) ? $payload['department'] : '';
    $user->timecreated = time();
    $user->timemodified = time();

    try {
        $userid = user_create_user($user, false, false);
        return \core_user::get_user($userid);
    } catch (\Exception $e) {
        debugging('SSO Login JIT: Failed to provision user — ' . $e->getMessage(), DEBUG_DEVELOPER);
        return false;
    }
}

/**
 * Synchronises a Moodle user's profile with data from an SSO payload.
 *
 * Only updates fields that are present in the payload, leaving others untouched.
 * Call this after every successful SSO login when profile sync is enabled.
 *
 * @param stdClass $user The existing Moodle user object.
 * @param array $payload The decoded SSO payload.
 * @return void
 */
function local_ssologin_sync_profile(\stdClass $user, array $payload): void {
    global $CFG, $DB;
    require_once($CFG->dirroot . '/user/lib.php');

    $syncfields = ['email', 'firstname', 'lastname', 'lang', 'country', 'city', 'institution', 'department'];
    $changed = false;

    foreach ($syncfields as $field) {
        if (isset($payload[$field]) && trim($payload[$field]) !== '' && $user->$field !== trim($payload[$field])) {
            $user->$field = trim($payload[$field]);
            $changed = true;
        }
    }

    if ($changed) {
        $user->timemodified = time();
        try {
            user_update_user($user, false, false);
        } catch (\Exception $e) {
            debugging('SSO Login Sync: Failed to sync profile — ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
    }
}

/**
 * Logs an SSO login attempt.
 *
 * @param string $status The status of the attempt ('success' or 'fail').
 * @param int $userid The user ID.
 * @param string $username The username (optional).
 * @return void
 */
function local_ssologin_log_attempt($status, $userid, $username = '') {
    \local_ssologin\event\sso_login_attempted::create([
        'context' => \context_system::instance(),
        'other' => [
            'username' => $username,
            'status' => $status === 'success' ? 'success' : 'fail',
        ],
        'userid' => $userid,
    ])->trigger();
}
