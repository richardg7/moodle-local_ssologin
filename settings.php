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
 * Plugin settings
 *
 * @package    local_ssologin
 * @copyright  2025 Richard Guedes  - Instituto de Defesa Cibernética (IDCiber)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

$settings = new admin_settingpage('local_ssologin', get_string('pluginname', 'local_ssologin'));

$settings->add(new admin_setting_configtext(
    'local_ssologin/secretkey',
    get_string('secretkey', 'local_ssologin'),
    get_string('secretkey_desc', 'local_ssologin'),
    bin2hex(random_bytes(16)), // Generates a secure default value.
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
