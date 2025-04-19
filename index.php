<?php
require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/moodlelib.php');
require_once($CFG->dirroot . '/user/lib.php');
require_once($CFG->libdir . '/weblib.php'); // para redirect()

// ⚠️ NÃO usar require_login() aqui

$secret = get_config('local_sso_login', 'secret');
$allowed_time_window = 300;

if (empty($secret)) {
    throw new moodle_exception('Chave secreta não configurada. Acesse as configurações do plugin Local SSO Login.');
}

if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on') {
    throw new moodle_exception('Conexão insegura detectada. A autenticação SSO requer HTTPS.');
}

$username = required_param('username', PARAM_ALPHANUMEXT);
$email = required_param('email', PARAM_EMAIL);
$time = required_param('time', PARAM_INT);
$token = required_param('token', PARAM_ALPHANUMEXT);

// Validação temporal do token
if (abs(time() - $time) > $allowed_time_window) {
    throw new moodle_exception('Token expirado. Por favor, tente novamente.');
}

// Validação do token
$expected = hash_hmac('sha256', $username . $email . $time . $secret, $secret);
if (!hash_equals($expected, $token)) {
    throw new moodle_exception('Token inválido. Acesso negado.');
}

// Busca do usuário
$user = get_complete_user_data('username', $username);
if (!$user || strtolower($user->email) !== strtolower($email)) {
    throw new moodle_exception('Usuário ou e-mail não conferem.');
}

// Faz login manualmente
complete_user_login($user);

// Garante que o cookie de sessão será enviado
$SESSION->wantsurl = null; // opcional: limpa redirect anterior

// Redirecionamento após login
$courseid = optional_param('courseid', 0, PARAM_INT);
if ($courseid > 0) {
    redirect(new moodle_url('/course/view.php', ['id' => $courseid]));
} else {
    redirect(new moodle_url('/my/'));
}
