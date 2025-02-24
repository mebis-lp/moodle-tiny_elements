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

    if ($oldversion < 2025013100) {
        // Define field displayorder to be added to tiny_elements_flavor.
        $table = new xmldb_table('tiny_elements_flavor');
        $field = new xmldb_field('displayorder', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'displayname');

        // Conditionally launch add field displayorder.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Elements savepoint reached.
        upgrade_plugin_savepoint(true, 2025013100, 'tiny', 'elements');
    }

    if ($oldversion < 2025022402) {
        // Define field componentname to be added to tiny_elements_comp_variant.
        $table = new xmldb_table('tiny_elements_comp_variant');
        $field = new xmldb_field('componentname', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'variant');

        // Conditionally launch add field componentname.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Move componentid to componentname.
        $DB->execute(
            'UPDATE {tiny_elements_comp_variant}
            SET componentname = (SELECT name FROM {tiny_elements_component} WHERE id = component)'
        );

        // Delete all rows without componentname.
        $DB->execute('DELETE FROM {tiny_elements_comp_variant} WHERE componentname IS NULL');

        // Remove old foreign key.
        $key = new xmldb_key(
            'tinyelementscompvariant_comp_fk',
            XMLDB_KEY_FOREIGN,
            ['component'],
            'tiny_elements_component',
            ['id']
        );

        // Launch drop key tinyelementscompvariant_comp_fk.
        $dbman->drop_key($table, $key);

        // Add new foreign key.
        $key = new xmldb_key(
            'tinyelementscompvariant_comp_fk',
            XMLDB_KEY_FOREIGN,
            ['componentname'],
            'tiny_elements_component',
            ['name']
        );

        // Launch add key tinyelementscompvariant_comp_fk.
        $dbman->add_key($table, $key);

        $field = new xmldb_field('component');

        // Conditionally launch drop field component.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Elements savepoint reached.
        upgrade_plugin_savepoint(true, 2025022402, 'tiny', 'elements');
    }

    return true;
}
