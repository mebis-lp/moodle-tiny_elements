<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace tiny_elements\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\writer;

/**
 * Privacy API implementation for the Components for Learning (Elements) plugin.
 *
 * @package     tiny_elements
 * @category    privacy
 * @copyright   2023 Marc Català <reskit@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\user_preference_provider {
    /**
     * Returns meta data about this system.
     *
     * @param   collection     $collection The initialised collection to add items to.
     * @return  collection     A listing of user data stored through this system.
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_user_preference('elements_category', 'privacy:preference:category');
        $collection->add_user_preference('elements_components_variants', 'privacy:preference:components_variants');
        $collection->add_user_preference('elements_components_flavors', 'privacy:preference:components_flavors');

        return $collection;
    }

    /**
     * Export all user preferences for the plugin.
     *
     * @param   int     $userid The userid of the user whose data is to be exported.
     */
    public static function export_user_preferences(int $userid) {

        // Variants.
        $variants = get_user_preferences('elements_components_variants', null, $userid);
        if ($variants !== null) {
            writer::export_user_preference(
                'tiny_elements',
                'elements_components_variants',
                $variants,
                get_string('privacy:preference:components_variants', 'tiny_elements')
            );
        }

        $flavors = get_user_preferences('elements_components_flavors', null, $userid);
        if ($flavors !== null) {
            writer::export_user_preference(
                'tiny_elements',
                'elements_components_flavors',
                $flavors,
                get_string('privacy:preference:components_flavors', 'tiny_elements')
            );
        }

        $category = get_user_preferences('elements_category', null, $userid);
        if ($category !== null) {
            writer::export_user_preference(
                'tiny_elements',
                'elements_category',
                $category,
                get_string('privacy:preference:category', 'tiny_elements')
            );
        }
    }
}
