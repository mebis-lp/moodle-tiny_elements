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
 * Upgrade steps for Components for Learning (Elements)
 *
 * Documentation: {@link https://moodledev.io/docs/guides/upgrade}
 *
 * @package    tiny_elements
 * @category   upgrade
 * @copyright  2024 ISB Bayern
 * @author     Stefan Hanauska <stefan.hanauska@csg-in.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Execute the plugin upgrade steps from the given old version.
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_tiny_elements_upgrade($oldversion): bool {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2025081700) {
        // Changing precision of field displayname on table tiny_elements_component to (1333).
        $table = new xmldb_table('tiny_elements_component');
        $field = new xmldb_field('displayname', XMLDB_TYPE_CHAR, '1333', null, null, null, null, 'name');

        // Launch change of precision for field displayname.
        $dbman->change_field_precision($table, $field);

        // Changing precision of field displayname on table tiny_elements_compcat to (1333).
        $table = new xmldb_table('tiny_elements_compcat');
        $field = new xmldb_field('displayname', XMLDB_TYPE_CHAR, '1333', null, null, null, null, 'name');

        // Launch change of precision for field displayname.
        $dbman->change_field_precision($table, $field);

        // Changing precision of field displayname on table tiny_elements_variant to (1333).
        $table = new xmldb_table('tiny_elements_variant');
        $field = new xmldb_field('displayname', XMLDB_TYPE_CHAR, '1333', null, null, null, null, 'name');

        // Launch change of precision for field displayname.
        $dbman->change_field_precision($table, $field);

        // Changing precision of field displayname on table tiny_elements_flavor to (1333).
        $table = new xmldb_table('tiny_elements_flavor');
        $field = new xmldb_field('displayname', XMLDB_TYPE_CHAR, '1333', null, null, null, null, 'name');

        // Launch change of precision for field displayname.
        $dbman->change_field_precision($table, $field);

        // Elements savepoint reached.
        upgrade_plugin_savepoint(true, 2025081700, 'tiny', 'elements');
    }
    return true;
}
