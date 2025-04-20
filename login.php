<?php
require('../../config.php');
require_once($CFG->libdir . '/authlib.php');
require_once(__DIR__.'/locallib.php');

$secret = get_config('local_ssologin', 'secretkey');
$tokenexpire = get_config('local_ssologin', 'tokenexpire');

$encdata = required_param('data', PARAM_RAW);
$signature = required_param('sig', PARAM_ALPHANUMEXT);

$data = local_ssologin_decrypt($encdata, $secret);
$payload = json_decode($data, true);

if (!local_ssologin_verify_token($data, $signature, $secret)) {
    print_error('invalidtoken', 'local_ssologin');
}

if (time() - $payload['timestamp'] > $tokenexpire) {
    print_error('invalidtoken', 'local_ssologin');
}

$username = $payload['username'];

if ($user = $DB->get_record('user', ['username' => $username, 'deleted' => 0])) {
    complete_user_login($user);
    local_ssologin_log_attempt('success', $user->id, $username);
    redirect(new moodle_url('/'));
} else {
    local_ssologin_log_attempt('fail', 0, $username);
    print_error('loginfailure', 'local_ssologin', '', $username);
}