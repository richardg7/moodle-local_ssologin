<?php
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