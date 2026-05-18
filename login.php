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
 * Handles SSO login requests.
 *
 * @package    local_ssologin
 * @copyright  2025 Richard Guedes  - Instituto de Defesa Cibernética (IDCiber)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// phpcs:disable moodle.Files.RequireLogin.Missing
require('../../config.php');
require_once($CFG->libdir . '/authlib.php');
require_once(__DIR__ . '/locallib.php');

$secret = get_config('local_ssologin', 'secretkey');
$tokenexpire = get_config('local_ssologin', 'tokenexpire');

$encdata = required_param('data', PARAM_RAW);
$signature = optional_param('sig', '', PARAM_ALPHANUMEXT);
$legacymode = get_config('local_ssologin', 'legacymode');

// 1. Verificar HMAC (Previne Padding Oracle).
// No modo legado, permitimos que a assinatura seja omitida se necessário.
if (!empty($signature)) {
    if (!local_ssologin_verify_token($encdata, $signature, $secret)) {
        throw new moodle_exception('invalidtoken', 'local_ssologin');
    }
} else if (!$legacymode) {
    // Se não estiver em modo legado, a assinatura é obrigatória.
    throw new moodle_exception('invalidtoken', 'local_ssologin');
} else {
    // Logar que o modo legado foi usado para que o admin saiba quais integrações atualizar.
    debugging('SSO Login: Legacy mode used for a request without HMAC signature.', DEBUG_DEVELOPER);
}

// 2. Descriptografar o payload.
$data = local_ssologin_decrypt($encdata, $secret);
if ($data === false) {
    throw new moodle_exception('invalidtoken', 'local_ssologin');
}

// 3. Decodificar JSON e validar estrutura básica.
$payload = json_decode($data, true);
if (json_last_error() !== JSON_ERROR_NONE || !isset($payload['username'], $payload['timestamp'])) {
    throw new moodle_exception('invalidtoken', 'local_ssologin');
}

// 4. Tratamento de Clock Skew (tolerância de expiração bidirecional).
$skew = abs(time() - (int)$payload['timestamp']);
if ($skew > $tokenexpire) {
    throw new moodle_exception('invalidtoken', 'local_ssologin');
}

// 5. Proteção contra Replay Attack (Nonce).
if (isset($payload['nonce'])) {
    if (local_ssologin_is_nonce_used($payload['nonce'])) {
        throw new moodle_exception('invalidtoken', 'local_ssologin');
    }
    local_ssologin_save_nonce($payload['nonce']);
}

$username = $payload['username'];
$jitprovisioning = get_config('local_ssologin', 'jitprovisioning');
$profilesync     = get_config('local_ssologin', 'profilesync');

$user = $DB->get_record('user', ['username' => $username, 'deleted' => 0]);

// Account Linking: Se não encontrou pelo username, tenta encontrar pelo e-mail (Fallback)
if (!$user && !empty($payload['email'])) {
    $email = trim($payload['email']);
    $user = $DB->get_record('user', ['email' => $email, 'deleted' => 0], '*', IGNORE_MULTIPLE);
    
    // Se encontrou, atualiza o username no banco para ficar sincronizado com o sistema de membros
    if ($user) {
        try {
            $DB->set_field('user', 'username', $username, ['id' => $user->id]);
            $user->username = $username;
        } catch (\Exception $e) {
            // Em caso de colisão de username (raro), ignora o update e apenas prossegue com o login.
            debugging('SSO Login Account Linking: Failed to sync username — ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
    }
}

// 6. JIT Provisioning — cria o utilizador automaticamente se não existir.
if (!$user && $jitprovisioning) {
    $user = local_ssologin_provision_user($payload);
    if (!$user) {
        local_ssologin_log_attempt('fail', 0, $username);
        throw new moodle_exception('loginfailure', 'local_ssologin', '', $username);
    }
}

if ($user) {
    // 7. Profile Sync — actualiza o perfil com dados mais recentes do payload.
    if ($profilesync) {
        local_ssologin_sync_profile($user, $payload);
    }

    complete_user_login($user);
    local_ssologin_log_attempt('success', $user->id, $username);

    // 8. Redirecionamento Seguro (Apenas links internos).
    if ($redirectquery = optional_param('redirect', null, PARAM_URL)) {
        $moodleurl = new moodle_url('/');
        $targeturl = new moodle_url($redirectquery);

        // Compara o host do redirect com o host do Moodle.
        if ($targeturl->get_host() === $moodleurl->get_host() || empty($targeturl->get_host())) {
            redirect($targeturl);
        }
    }

    redirect(new moodle_url('/'));
} else {
    local_ssologin_log_attempt('fail', 0, $username);
    throw new moodle_exception('loginfailure', 'local_ssologin', '', $username);
}
