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


use core_privacy\local\metadata\collection;
use core_privacy\local\request\contextlist;

/**
 * Privacy provider for local_ssologin.
 *
 * @package    local_ssologin
 */
class provider implements \core_privacy\local\metadata\provider, \core_privacy\local\request\plugin\provider {
    /**
     * Returns metadata about the data stored by this plugin.
     *
     * @param collection $collection The collection to add metadata to.
     * @return collection The collection with metadata.
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('local_ssologin_nonces', [
            'nonce' => 'privacy:metadata:nonce',
            'timecreated' => 'privacy:metadata:timecreated',
        ], 'privacy:metadata:noncedesc');

        return $collection;
    }

    /**
     * Get the list of contexts where data is stored for this user.
     *
     * @param int $userid The user ID to get contexts for.
     * @return contextlist The list of contexts.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        return new contextlist();
    }

    /**
     * Export all user data for the specified contexts.
     *
     * @param \core_privacy\local\request\approved_contextlist $contextlist The list of contexts.
     */
    public static function export_user_data(\core_privacy\local\request\approved_contextlist $contextlist) {
        // This plugin only stores nonces which are not linked to a specific user.
    }

    /**
     * Delete all user data for the specified context.
     *
     * @param \core_privacy\local\request\context $context The context to delete data for.
     */
    public static function delete_data_for_all_users_in_context(\core_privacy\local\request\context $context) {
        // This plugin only stores nonces which are not linked to a specific user.
    }

    /**
     * Delete all user data for the specified approved contextlist.
     *
     * @param \core_privacy\local\request\approved_contextlist $contextlist The list of contexts.
     */
    public static function delete_data_for_user(\core_privacy\local\request\approved_contextlist $contextlist) {
        // This plugin only stores nonces which are not linked to a specific user.
    }
}
