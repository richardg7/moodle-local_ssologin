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
 * Privacy provider implementation for the local_ssologin plugin.
 *
 * @package    local_ssologin
 * @copyright  2025 Richard Guedes  - Instituto de Defesa Cibernética (IDCiber)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_ssologin\privacy;

defined('MOODLE_INTERNAL') || die();

use core_privacy\local\metadata\null_provider;

/**
 * Privacy provider for local_ssologin.
 *
 * This plugin does not store any personal user data.
 *
 * @package    local_ssologin
 */
class provider implements null_provider {
    /**
     * Returns the reason why this plugin does not store any data.
     *
     * @return string The reason string.
     */
    public static function get_reason(): string {
        return get_string('privacy:metadata', 'local_ssologin');
    }
}
