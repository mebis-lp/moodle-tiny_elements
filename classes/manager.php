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

namespace tiny_elements;

use moodle_exception;
use stored_file;
use tiny_elements\local\constants;

/**
 * Class manager
 *
 * @package    tiny_elements
 * @copyright  2024 Tobias Garske
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class manager {
    /** @var int $contextid */
    protected int $contextid = 1;

    /**
     * Constructor.
     *
     * @param int $contextid
     */
    public function __construct(int $contextid = SYSCONTEXTID) {
        $this->contextid = $contextid;
    }

    /**
     * Delete a category.
     *
     * @param int $id
     */
    public function delete_compcat(int $id): void {
        global $DB;
        $fs = get_file_storage();
        $fs->delete_area_files($this->contextid, 'tiny_elements', 'images', $id);
        $DB->delete_records('tiny_elements_compcat', ['id' => $id]);
        foreach ($DB->get_records('tiny_elements_component', ['compcat' => $id]) as $component) {
            $this->delete_component($component->id);
        }
        // Todo: Delete all flavors and variants that were only used by this category.
    }

    /**
     * Delete a flavor.
     *
     * @param int $id
     */
    public function delete_flavor(int $id): void {
        global $DB;
        $sql = 'DELETE FROM {tiny_elements_comp_flavor} cf
                WHERE flavorname IN (
                    SELECT name FROM {tiny_elements_flavor}
                    WHERE id = ?
                )';
        $DB->execute($sql, [$id]);
        $DB->delete_records('tiny_elements_flavor', ['id' => $id]);
    }

    /**
     * Delete a variant.
     *
     * @param int $id
     * @return void
     */
    public function delete_variant(int $id): void {
        global $DB;
        $sql = 'DELETE FROM {tiny_elements_comp_variant} cv
                WHERE variant IN (
                    SELECT name FROM {tiny_elements_variant}
                    WHERE id = ?
                )';
        $DB->execute($sql, [$id]);
        $DB->delete_records('tiny_elements_variant', ['id' => $id]);
    }

    /**
     * Delete a component.
     *
     * @param int $id
     * @return void
     */
    public function delete_component(int $id): void {
        global $DB;
        $sql = 'DELETE FROM {tiny_elements_comp_flavor} cf
                WHERE componentname IN (
                    SELECT name FROM {tiny_elements_component}
                    WHERE id = ?
                )';
        $DB->execute($sql, [$id]);
        $sql = 'DELETE FROM {tiny_elements_comp_variant} WHERE component = ?';
        $DB->execute($sql, [$id]);
        $DB->delete_records('tiny_elements_component', ['id' => $id]);
    }
}
