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
defined('MOODLE_INTERNAL') || die();

function local_ssologin_verify_token($data, $signature, $secret) {
    return hash_equals(hash_hmac('sha256', $data, $secret), $signature);
}

function local_ssologin_decrypt($encrypted, $secret) {
    list($ciphertext, $iv) = explode('::', base64_decode($encrypted), 2);
    return openssl_decrypt($ciphertext, 'aes-256-cbc', $secret, 0, $iv);
}

function local_ssologin_log_attempt($status, $userid, $username = '') {
    $eventdata = [
        'component' => 'local_ssologin',
        'eventname' => 'SSO login attempt',
        'userid' => $userid,
        'other' => ['username' => $username, 'status' => $status]
    ];
    \local_ssologin\event\sso_login_attempted::create([
        'context' => \context_system::instance(),
        'other' => [
            'username' => $username,
            'status' => 'success' // ou 'fail'
        ],
        'userid' => $user ? $user->id : null
    ])->trigger();
}