<?php
defined('MOODLE_INTERNAL') || die();

$settings = new admin_settingpage('local_ssologin', get_string('pluginname', 'local_ssologin'));

$settings->add(new admin_setting_configtext(
    'local_ssologin/secretkey',
    get_string('secretkey', 'local_ssologin'),
    get_string('secretkey_desc', 'local_ssologin'),
    bin2hex(random_bytes(16)), // Gera um valor padrão seguro
    PARAM_ALPHANUMEXT
));

$settings->add(new admin_setting_configtext(
    'local_ssologin/tokenexpire',
    get_string('tokenexpire', 'local_ssologin'),
    get_string('tokenexpire_desc', 'local_ssologin'),
    300,
    PARAM_INT
));

$ADMIN->add('localplugins', $settings);