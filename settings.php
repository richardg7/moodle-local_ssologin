<?php
defined('MOODLE_INTERNAL') || die;

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_sso_login', get_string('pluginname', 'local_sso_login'));

    $settings->add(new admin_setting_configtext(
        'local_sso_login/secret',
        get_string('secret', 'local_sso_login'),
        get_string('secret_desc', 'local_sso_login'),
        bin2hex(random_bytes(16)) // Gera um valor padrão seguro
    ));

    $ADMIN->add('localplugins', $settings);
}