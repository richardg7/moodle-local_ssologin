<?php
namespace local_ssologin\privacy;

defined('MOODLE_INTERNAL') || die();

use core_privacy\local\metadata\null_provider;

class provider implements null_provider {
    public static function get_reason(): string {
        return get_string('privacy:metadata', 'local_ssologin');
    }
}