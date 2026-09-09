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

use tiny_elements\local\constants;

/**
 * Class manager
 *
 * @package    tiny_elements
 * @copyright  2024 ISB Bayern
 * @author     Tobias Garske
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
     * Get the context id.
     *
     * @return int
     */
    public function get_contextid(): int {
        return $this->contextid;
    }

    /**
     * Delete a category.
     *
     * @param int $id
     */
    public function delete_compcat(int $id): void {
        global $DB;
        $fs = get_file_storage();
        $categoryname = $DB->get_field('tiny_elements_compcat', 'name', ['id' => $id]);
        $fs->delete_area_files($this->contextid, 'tiny_elements', 'images', $id);
        $DB->delete_records('tiny_elements_compcat', ['id' => $id]);

        foreach ($DB->get_records('tiny_elements_component', ['categoryname' => $categoryname]) as $component) {
            $this->delete_component($component->id);
        }

        foreach ($DB->get_records('tiny_elements_flavor', ['categoryname' => $categoryname]) as $flavor) {
            $this->delete_flavor($flavor->id);
        }

        foreach ($DB->get_records('tiny_elements_variant', ['categoryname' => $categoryname]) as $variant) {
            $this->delete_variant($variant->id);
        }

        // Purge CSS and JS cache.
        \tiny_elements\local\utils::purge_css_cache();
        \tiny_elements\local\utils::rebuild_css_cache();
        \tiny_elements\local\utils::purge_js_cache();
        \tiny_elements\local\utils::rebuild_js_cache();
    }

    /**
     * Delete a flavor.
     *
     * @param int $id
     */
    public function delete_flavor(int $id): void {
        global $DB;
        $sql = "DELETE FROM {tiny_elements_comp_flavor}
                WHERE flavorname IN (
                    SELECT name FROM {tiny_elements_flavor}
                    WHERE id = ?
                )";
        $DB->execute($sql, [$id]);
        $DB->delete_records('tiny_elements_flavor', ['id' => $id]);

        // Purge CSS cache.
        \tiny_elements\local\utils::purge_css_cache();
        \tiny_elements\local\utils::rebuild_css_cache();
    }

    /**
     * Delete a variant.
     *
     * @param int $id
     * @return void
     */
    public function delete_variant(int $id): void {
        global $DB;
        $sql = "DELETE FROM {tiny_elements_comp_variant}
                WHERE variant IN (
                    SELECT name FROM {tiny_elements_variant}
                    WHERE id = ?
                )";
        $DB->execute($sql, [$id]);
        $DB->delete_records('tiny_elements_variant', ['id' => $id]);

        // Purge CSS cache.
        \tiny_elements\local\utils::purge_css_cache();
        \tiny_elements\local\utils::rebuild_css_cache();
    }

    /**
     * Delete a component.
     *
     * @param int $id
     * @return void
     */
    public function delete_component(int $id): void {
        global $DB;
        $componentname = $DB->get_field('tiny_elements_component', 'name', ['id' => $id]);
        $DB->delete_records('tiny_elements_comp_flavor', ['componentname' => $componentname]);
        $DB->delete_records('tiny_elements_comp_variant', ['componentname' => $componentname]);
        $DB->delete_records('tiny_elements_component', ['id' => $id]);

        // Purge CSS and JS cache.
        \tiny_elements\local\utils::purge_css_cache();
        \tiny_elements\local\utils::rebuild_css_cache();
        \tiny_elements\local\utils::purge_js_cache();
        \tiny_elements\local\utils::rebuild_js_cache();
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

    /**
     * Add a category.
     *
     * @param object $data
     * @return int id of the new category
     */
    public function add_compcat(object $data) {
        global $DB;
        $data->timecreated = time();
        $data->timemodified = time();
        $recordid = $DB->insert_record(constants::TABLES['compcat'], $data);
        file_save_draft_area_files(
            $data->compcatfiles,
            $this->contextid,
            'tiny_elements',
            'images',
            $recordid,
            constants::FILE_OPTIONS
        );

        // Purge CSS cache if necessary.
        if (!empty($data->css)) {
            \tiny_elements\local\utils::purge_css_cache();
            \tiny_elements\local\utils::rebuild_css_cache();
        }

        return $recordid;
    }

    /**
     * Add a flavor.
     *
     * @param object $data
     * @return int id of the new flavor
     */
    public function add_flavor(object $data) {
        global $DB;
        $data->timecreated = time();
        $data->timemodified = time();

        $result = $DB->insert_record(constants::TABLES['flavor'], $data);

        // Purge CSS cache if necessary.
        if (!empty($data->css)) {
            \tiny_elements\local\utils::purge_css_cache();
            \tiny_elements\local\utils::rebuild_css_cache();
        }

        return $result;
    }

    /**
     * Add a variant.
     *
     * @param object $data
     * @return int id of the new variant
     */
    public function add_variant(object $data) {
        global $DB;
        $data->timecreated = time();
        $data->timemodified = time();

        $result = $DB->insert_record(constants::TABLES['variant'], $data);

        // Purge CSS cache if necessary.
        if (!empty($data->css) || !empty($data->iconurl)) {
            \tiny_elements\local\utils::purge_css_cache();
            \tiny_elements\local\utils::rebuild_css_cache();
        }

        return $result;
    }

    /**
     * Add a component.
     *
     * @param object $data
     * @return int id of the new component
     */
    public function add_component(object $data) {
        global $DB;
        $data->timecreated = time();
        $data->timemodified = time();

        $data->id = $DB->insert_record(constants::TABLES['component'], $data);

        if (count($data->flavors) > 0) {
            foreach ($data->flavors as $flavor) {
                $DB->insert_record('tiny_elements_comp_flavor', [
                    'componentname' => $data->name,
                    'flavorname' => $flavor,
                ]);
            }
        }

        if (count($data->variants) > 0) {
            foreach ($data->variants as $variant) {
                $DB->insert_record('tiny_elements_comp_variant', [
                    'componentname' => $data->name,
                    'variant' => $variant,
                ]);
            }
        }

        // Purge CSS cache if necessary.
        if (!empty($data->css) || !empty($data->iconurl)) {
            \tiny_elements\local\utils::purge_css_cache();
            \tiny_elements\local\utils::rebuild_css_cache();
        }

        // Purge JS cache if necessary.
        if (!empty($data->js)) {
            \tiny_elements\local\utils::purge_js_cache();
            \tiny_elements\local\utils::rebuild_js_cache();
        }

        return $data->id;
    }

    /**
     * Update a category.
     *
     * @param object $data
     * @return bool
     */
    public function update_compcat(object $data): bool {
        global $DB;
        $data->timemodified = time();
        $oldrecord = $DB->get_record(constants::TABLES['compcat'], ['id' => $data->id]);
        file_save_draft_area_files(
            $data->compcatfiles,
            $this->contextid,
            'tiny_elements',
            'images',
            $data->id,
            constants::FILE_OPTIONS
        );

        $result = $DB->update_record(constants::TABLES['compcat'], $data);

        $result &= $DB->execute(
            "UPDATE {tiny_elements_component}
             SET categoryname = ?
             WHERE categoryname = ?",
            [$data->name, $oldrecord->name]
        );

        $result &= $DB->execute(
            "UPDATE {tiny_elements_flavor}
             SET categoryname = ?
             WHERE categoryname = ?",
            [$data->name, $oldrecord->name]
        );

        $result &= $DB->execute(
            "UPDATE {tiny_elements_variant}
             SET categoryname = ?
             WHERE categoryname = ?",
            [$data->name, $oldrecord->name]
        );

        // Purge CSS cache if necessary.
        if ($data->css != $oldrecord->css || $data->name != $oldrecord->name) {
            \tiny_elements\local\utils::purge_css_cache();
            \tiny_elements\local\utils::rebuild_css_cache();
        }

        return $result;
    }

    /**
     * Update a flavor.
     *
     * @param object $data
     * @return bool
     */
    public function update_flavor(object $data): bool {
        global $DB;
        $data->timemodified = time();
        $data->hideforstudents = !empty($data->hideforstudents);

        $oldrecord = $DB->get_record(constants::TABLES['flavor'], ['id' => $data->id]);

        $result = $DB->update_record(constants::TABLES['flavor'], $data);

        if ($oldrecord->name != $data->name) {
            $result &= $DB->execute(
                "UPDATE {tiny_elements_comp_flavor}
                 SET flavorname = ?
                 WHERE flavorname = ?",
                [$data->name, $oldrecord->name]
            );
        }

        // Purge CSS cache if necessary.
        if ($data->css != $oldrecord->css || $data->name != $oldrecord->name) {
            \tiny_elements\local\utils::purge_css_cache();
            \tiny_elements\local\utils::rebuild_css_cache();
        }

        return $result;
    }

    /**
     * Update a variant.
     *
     * @param object $data
     * @return bool
     */
    public function update_variant(object $data): bool {
        global $DB;
        $data->timemodified = time();

        $oldrecord = $DB->get_record(constants::TABLES['variant'], ['id' => $data->id]);

        $result = $DB->update_record(constants::TABLES['variant'], $data);

        if ($oldrecord->name != $data->name) {
            $result &= $DB->execute(
                "UPDATE {tiny_elements_comp_variant}
                 SET variant = ?
                 WHERE variant = ?",
                [$data->name, $oldrecord->name]
            );
        }

        // Purge CSS cache if necessary.
        if ($data->css != $oldrecord->css || $data->iconurl != $oldrecord->iconurl || $data->name != $oldrecord->name) {
            \tiny_elements\local\utils::purge_css_cache();
            \tiny_elements\local\utils::rebuild_css_cache();
        }

        return $result;
    }

    /**
     * Update a component.
     *
     * @param object $data
     * @return bool
     */
    public function update_component(object $data): bool {
        global $DB;
        $data->timemodified = time();
        $data->hideforstudents = !empty($data->hideforstudents);
        $oldrecord = $DB->get_record(constants::TABLES['component'], ['id' => $data->id]);
        $result = $DB->update_record(constants::TABLES['component'], $data);
        // Update component flavors, keep existing iconurls.
        if ($oldrecord) {
            $records = $DB->get_records(
                'tiny_elements_comp_flavor',
                ['componentname' => $oldrecord->name],
                '',
                'flavorname, iconurl'
            );
            $DB->delete_records('tiny_elements_comp_flavor', ['componentname' => $oldrecord->name]);
        }
        if (count($data->flavors) > 0) {
            foreach ($data->flavors as $flavor) {
                $DB->insert_record('tiny_elements_comp_flavor', [
                    'componentname' => $data->name,
                    'flavorname' => $flavor,
                    'iconurl' => $records[$flavor]->iconurl ?? '',
                ]);
            }
        }
        // Update component variants.
        if ($oldrecord) {
            $records = $DB->get_records('tiny_elements_comp_variant', ['componentname' => $oldrecord->name]);
            $DB->delete_records('tiny_elements_comp_variant', ['componentname' => $oldrecord->name]);
        }
        if (count($data->variants) > 0) {
            foreach ($data->variants as $variant) {
                $DB->insert_record('tiny_elements_comp_variant', [
                    'componentname' => $data->name,
                    'variant' => $variant,
                ]);
            }
        }

        // Purge CSS cache if necessary.
        if ($data->css != $oldrecord->css || $data->iconurl != $oldrecord->iconurl || $data->name != $oldrecord->name) {
            \tiny_elements\local\utils::purge_css_cache();
            \tiny_elements\local\utils::rebuild_css_cache();
        }

        // Purge JS cache if necessary.
        if (($oldrecord->js != $data->js)) {
            \tiny_elements\local\utils::purge_js_cache();
            \tiny_elements\local\utils::rebuild_js_cache();
        }

        return $result;
    }
}
