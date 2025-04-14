<?php
defined('MOODLE_INTERNAL') || die();

$capabilities = [
    'local/sso_login:use' => [
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => [
            'guest' => CAP_ALLOW,
            'user' => CAP_ALLOW,
        ],
    ],
];
