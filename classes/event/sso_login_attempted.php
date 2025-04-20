<?php
namespace local_ssologin\event;

defined('MOODLE_INTERNAL') || die();

class sso_login_attempted extends \core\event\base {

    protected function init() {
        $this->data['crud'] = 'r'; // r = read (tentativa de login)
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }

    public static function get_name() {
        return get_string('eventssologinattempted', 'local_ssologin');
    }

    public function get_description() {
        return "SSO login attempt for user '{$this->other['username']}' with status '{$this->other['status']}'";
    }

    public function get_url() {
        return new \moodle_url('/local/ssologin/login.php');
    }

    protected function get_legacy_logdata() {
        return [
            SITEID,
            'local_ssologin',
            'SSO login attempted',
            'login.php',
            $this->other['username']
        ];
    }

    public static function get_other_mapping() {
        return false;
    }
}