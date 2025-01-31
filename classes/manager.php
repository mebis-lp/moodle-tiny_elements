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
        $sql = 'DELETE FROM {tiny_elements_comp_flavor} 
                WHERE componentname IN (
                    SELECT name FROM {tiny_elements_component}
                    WHERE id = ?
                )';
        $DB->execute($sql, [$id]);
        $sql = 'DELETE FROM {tiny_elements_comp_variant} WHERE component = ?';
        $DB->execute($sql, [$id]);
        $DB->delete_records('tiny_elements_component', ['id' => $id]);
    }

    /**
     * Duplicate a category.
     *
     * @param int $id
     */
    public function duplicate_compcat(int $id): void {
        global $DB;
        $compcat = $DB->get_record('tiny_elements_compcat', ['id' => $id]);
        if ($compcat) {
            $newcompcat = clone $compcat;
            unset($newcompcat->id);
            $newcompcat->displayname = get_string('copyof', 'tiny_elements', $newcompcat->displayname);
            $newcompcat->name .= time();
            $newcompcat->id = $DB->insert_record('tiny_elements_compcat', $newcompcat);
            $fs = get_file_storage();
            $files = $fs->get_area_files($this->contextid, 'tiny_elements', 'images', $id);
            foreach ($files as $file) {
                $fs->create_file_from_storedfile([
                    'contextid' => $this->contextid,
                    'component' => 'tiny_elements',
                    'filearea' => 'images',
                    'itemid' => $newcompcat->id,
                    'filepath' => $file->get_filepath(),
                    'filename' => $file->get_filename(),
                ], $file);
            }
        }
    }

    /**
     * Duplicate a flavor.
     *
     * @param int $id
     */
    public function duplicate_flavor(int $id): void {
        global $DB;
        $flavor = $DB->get_record('tiny_elements_flavor', ['id' => $id]);
        if ($flavor) {
            $newflavor = clone $flavor;
            unset($newflavor->id);
            $newflavor->displayname = get_string('copyof', 'tiny_elements', $newflavor->displayname);
            $newflavor->name .= time();
            $newflavor->id = $DB->insert_record('tiny_elements_flavor', $newflavor);
        }
    }

    /**
     * Duplicate a variant.
     *
     * @param int $id
     */
    public function duplicate_variant(int $id): void {
        global $DB;
        $variant = $DB->get_record('tiny_elements_variant', ['id' => $id]);
        if ($variant) {
            $newvariant = clone $variant;
            unset($newvariant->id);
            $newvariant->displayname = get_string('copyof', 'tiny_elements', $newvariant->displayname);
            $newvariant->name .= time();
            $newvariant->id = $DB->insert_record('tiny_elements_variant', $newvariant);
        }
    }

    /**
     * Duplicate a component.
     *
     * @param int $id
     */
    public function duplicate_component(int $id): void {
        global $DB;
        $component = $DB->get_record('tiny_elements_component', ['id' => $id]);
        if ($component) {
            $newcomponent = clone $component;
            unset($newcomponent->id);
            $newcomponent->displayname = get_string('copyof', 'tiny_elements', $newcomponent->displayname);
            $newcomponent->name .= time();
            $newcomponent->id = $DB->insert_record('tiny_elements_component', $newcomponent);
        }
    }
}
