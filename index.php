<?php
require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/moodlelib.php');
require_once($CFG->dirroot . '/user/lib.php');

$secret = get_config('local_sso_login', 'secret');
$allowed_time_window = 300;

if (empty($secret)) {
    print_error('Chave secreta não configurada. Acesse as configurações do plugin.');
}

if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on') {
    print_error('Conexão insegura. Use HTTPS.');
}

$username = required_param('username', PARAM_ALPHANUMEXT);
$email = required_param('email', PARAM_EMAIL);
$time = required_param('time', PARAM_INT);
$token = required_param('token', PARAM_ALPHANUMEXT);

if (abs(time() - $time) > $allowed_time_window) {
    print_error('Token expirado.');
}

$expected = hash_hmac('sha256', $username . $email . $time . $secret, $secret);
if (!hash_equals($expected, $token)) {
    print_error('Token inválido.');
}

$user = get_complete_user_data('username', $username);
if (!$user || strtolower($user->email) !== strtolower($email)) {
    print_error('Usuário ou e-mail inválido.');
}

complete_user_login($user);

$courseid = optional_param('courseid', 0, PARAM_INT);
if ($courseid > 0) {
    redirect(new moodle_url('/course/view.php', ['id' => $courseid]));
} else {
    redirect(new moodle_url('/my/'));
}