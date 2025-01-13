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

use memory_xml_output;
use moodle_exception;
use stored_file;
use xml_writer;
use tiny_elements\local\utils;
use tiny_elements\local\constants;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/backup/util/xml/xml_writer.class.php');
require_once($CFG->dirroot . '/backup/util/xml/output/xml_output.class.php');
require_once($CFG->dirroot . '/backup/util/xml/output/memory_xml_output.class.php');

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
     * Export.
     *
     * @param int $compcatid
     * @return stored_file
     * @throws moodle_exception
     */
    public function export($compcatid = 0): stored_file {
        global $DB;
        $fs = get_file_storage();
        $fp = get_file_packer('application/zip');
        $compcats = $DB->get_records('tiny_elements_compcat');
        // It is necessary to get the files for each compcat separately to avoid mixing up files from
        // different categories.
        foreach ($compcats as $compcat) {
            $files = $fs->get_area_files($this->contextid, 'tiny_elements', 'images', $compcat->id);
            foreach ($files as $file) {
                $exportfiles[$compcat->name . '/' . $file->get_filepath() . $file->get_filename()] = $file;
            }
        }
        $filerecord = [
            'contextid' => $this->contextid,
            'component' => 'tiny_elements',
            'filearea' => 'export',
            'itemid' => time(),
            'filepath' => '/',
            'filename' => 'tiny_elements_export.xml',
        ];
        $exportxmlfile = $fs->create_file_from_string($filerecord, $this->exportxml());
        $exportfiles['tiny_elements_export.xml'] = $exportxmlfile;
        $filename = 'tiny_elements_export_' . time() . '.zip';
        $exportfile = $fp->archive_to_storage($exportfiles, $this->contextid, 'tiny_elements', 'export', 0, '/', $filename);
        if (!$exportfile) {
            throw new moodle_exception(get_string('error_export', 'tiny_elements'));
        }
        return $exportfile;
    }

    /**
     * Export XML.
     *
     * @return string
     */
    public function exportxml(): string {
        global $DB;

        // Start.
        $xmloutput = new memory_xml_output();
        $xmlwriter = new xml_writer($xmloutput);
        $xmlwriter->start();
        $xmlwriter->begin_tag('elements');

        // Tiny_elements_compcat.
        foreach (constants::TABLES as $table) {
            // Get columns.
            $columns = $DB->get_columns($table);

            // Get data.
            $data = $DB->get_records($table);

            $xmlwriter->begin_tag($table);
            foreach ($data as $value) {
                $xmlwriter->begin_tag(constants::ITEMNAME);
                foreach ($columns as $column) {
                    $name = $column->name;
                    $xmlwriter->full_tag($name, $value->$name ?? '');
                }
                $xmlwriter->end_tag(constants::ITEMNAME);
            }
            $xmlwriter->end_tag($table);
        }

        // End.
        $xmlwriter->end_tag('elements');
        $xmlwriter->stop();
        $xmlstr = $xmloutput->get_allcontents();

        // This is just here for compatibility reasons.
        $xmlstr = utils::replace_pluginfile_urls($xmlstr);

        return $xmlstr;
    }

    /**
     * Import files.
     *
     * @param array $files
     * @param int $categoryid
     * @param string $categoryname
     * @throws moodle_exception
     */
    public function importfiles($files, $categoryid, $categoryname = '') {
        $fs = get_file_storage();
        foreach ($files as $file) {
            if ($file->is_directory()) {
                continue;
            }
            $newfilepath = ($categoryname ? str_replace('/' . $categoryname, '', $file->get_filepath()) : $file->get_filepath());
            if (
                $oldfile = $fs->get_file(
                    $this->contextid,
                    'tiny_elements',
                    'images',
                    $categoryid,
                    $newfilepath,
                    $file->get_filename()
                )
            ) {
                if ($oldfile->get_contenthash() != $file->get_contenthash()) {
                    $oldfile->replace_file_with($file);
                }
            } else {
                $newfile = $fs->create_file_from_storedfile([
                    'contextid' => $this->contextid,
                    'component' => 'tiny_elements',
                    'filearea' => 'images',
                    'itemid' => $categoryid,
                    'filepath' => $newfilepath,
                    'filename' => $file->get_filename(),
                ], $file);
                if (!$newfile) {
                    throw new moodle_exception(
                        get_string('error_fileimport', 'tiny_elements', $newfilepath . $file->get_filename())
                    );
                }
            }
        }
    }

    /**
     * Import xml
     *
     * @param string $xmlcontent
     * @return boolean
     */
    public function importxml(string $xmlcontent): bool {
        try {
            $xml = simplexml_load_string($xmlcontent);
        } catch (\Exception $exception) {
            $xml = false;
        }
        if (!$xml) {
            return false;
        }

        // Create mapping array for tiny_elements_compcat table.
        $categorymap = [];

        // Create mapping array for tiny_elements_component table.
        $componentmap = [];

        foreach (constants::TABLES as $table) {
            $aliasname = constants::TABLE_ALIASES[$table];
            if (!isset($xml->$table) && !isset($xml->$aliasname) && !in_array($table, constants::OPTIONAL_TABLES)) {
                throw new moodle_exception(get_string('error_import_missing_table', 'tiny_elements', $table));
            }
        }

        $data = [];

        $aliases = array_flip(constants::TABLE_ALIASES);

        // Make data usable for further processing.
        foreach ($xml as $table => $rows) {
            foreach ($rows as $row) {
                $obj = new \stdClass();
                foreach ($row as $column => $value) {
                    $obj->$column = (string) $value;
                }
                if (in_array($table, constants::TABLES)) {
                    $data[$table][] = $obj;
                } else {
                    $data[$aliases[$table]][] = $obj;
                }
            }
        }

        // First process all component categories. We need the category ids for the components.
        foreach ($data['tiny_elements_compcat'] as $compcat) {
            // Save new id for mapping.
            $categorymap[$compcat->id] = self::import_category($compcat);
        }

        foreach ($data['tiny_elements_component'] as $component) {
            $componentmap[$component->id] = self::import_component($component, $categorymap);
        }

        foreach ($data['tiny_elements_flavor'] as $flavor) {
            self::import_flavor($flavor, $categorymap);
        }

        foreach ($data['tiny_elements_variant'] as $variant) {
            self::import_variant($variant, $categorymap);
        }

        foreach ($data['tiny_elements_comp_flavor'] as $componentflavor) {
            self::import_component_flavor($componentflavor, $categorymap);
        }

        foreach ($data['tiny_elements_comp_variant'] as $componentvariant) {
            self::import_component_variant($componentvariant, $componentmap);
        }

        return true;
    }

    /**
     * Import a component category.
     *
     * @param array|object $record
     * @return int id of the imported category
     */
    public static function import_category(array|object $record): int {
        global $DB;
        $record = (array) $record;
        $oldid = $record['id'];
        $current = $DB->get_record('tiny_elements_compcat', ['name' => $record['name']]);
        if ($current) {
            $record['id'] = $current->id;
            $DB->update_record('tiny_elements_compcat', $record);
        } else {
            $record['id'] = $DB->insert_record('tiny_elements_compcat', $record);
        }
        // Update pluginfile tags in css if the id has changed.
        if ($oldid != $record['id']) {
            $record['css'] = self::update_pluginfile_tags($oldid, $record['id'], $record['css']);
            $DB->update_record('tiny_elements_compcat', $record);
        }
        return $record['id'];
    }

    /**
     * Import a component.
     *
     * @param array|object $record
     * @param array $categorymap
     * @return int id of the imported component
     */
    public static function import_component(array|object $record, array $categorymap): int {
        global $DB;
        $record = (array) $record;
        if (isset($categorymap[$record['compcat']])) {
            $record['compcat'] = $categorymap[$record['compcat']];
        }

        $record['css'] = self::update_pluginfile_tags_bulk($categorymap, $record['css'] ?? '');
        $record['code'] = self::update_pluginfile_tags_bulk($categorymap, $record['code'] ?? '');
        $record['js'] = self::update_pluginfile_tags_bulk($categorymap, $record['js'] ?? '');
        $record['iconurl'] = self::update_pluginfile_tags_bulk($categorymap, $record['iconurl'] ?? '');

        $current = $DB->get_record('tiny_elements_component', ['name' => $record['name']]);
        if ($current) {
            $record['id'] = $current->id;
            $DB->update_record('tiny_elements_component', $record);
        } else {
            try {
                $record['id'] = $DB->insert_record('tiny_elements_component', $record);
            } catch (\Exception $e) {
                throw new moodle_exception(get_string('error_import_component', 'tiny_elements', $record['name']));
            }
        }

        if (!empty($record['flavors'])) {
            foreach (explode(',', $record['flavors']) as $flavor) {
                if ($flavor == '') {
                    continue;
                }
                $flavorrecord = [
                    'componentname' => $record['name'],
                    'flavorname' => $flavor,
                ];
                $DB->insert_record('tiny_elements_comp_flavor', $flavorrecord);
            }
        }

        if (!empty($record['variants'])) {
            foreach (explode(',', $record['variants']) as $variant) {
                if ($variant == '') {
                    continue;
                }
                $variantrecord = [
                    'component' => $record['id'],
                    'variant' => $variant,
                ];
                $DB->insert_record('tiny_elements_comp_variant', $variantrecord);
            }
        }

        return $record['id'];
    }

    /**
     * Import a flavor.
     *
     * @param array|object $record
     * @param array $categorymap
     * @return int id of the imported flavor
     */
    public static function import_flavor(array|object $record, array $categorymap): int {
        global $DB;
        $record = (array) $record;
        $current = $DB->get_record('tiny_elements_flavor', ['name' => $record['name']]);

        $record['css'] = self::update_pluginfile_tags_bulk($categorymap, $record['css'], 'import');
        $record['content'] = self::update_pluginfile_tags_bulk($categorymap, $record['content'], 'import');

        if ($current) {
            $record['id'] = $current->id;
            $DB->update_record('tiny_elements_flavor', $record);
        } else {
            $record['id'] = $DB->insert_record('tiny_elements_flavor', $record);
        }
        return $record['id'];
    }

    /**
     * Import a variant.
     *
     * @param array|object $record
     * @param array $categorymap
     * @return int id of the imported variant
     */
    public static function import_variant(array|object $record, array $categorymap): int {
        global $DB;
        $record = (array) $record;
        $current = $DB->get_record('tiny_elements_variant', ['name' => $record['name']]);

        $record['css'] = self::update_pluginfile_tags_bulk($categorymap, $record['css'] ?? '');
        $record['content'] = self::update_pluginfile_tags_bulk($categorymap, $record['content'] ?? '');
        $record['iconurl'] = self::update_pluginfile_tags_bulk($categorymap, $record['iconurl'] ?? '');

        if ($current) {
            $record['id'] = $current->id;
            $DB->update_record('tiny_elements_variant', $record);
        } else {
            $record['id'] = $DB->insert_record('tiny_elements_variant', $record);
        }
        return $record['id'];
    }

    /**
     * Import a relation between component and flavor.
     *
     * @param array|object $record
     * @param array $categorymap
     * @return int id of the imported relation
     */
    public static function import_component_flavor(array|object $record, array $categorymap): int {
        global $DB;
        $record = (array) $record;
        $current = $DB->get_record(
            'tiny_elements_comp_flavor',
            ['componentname' => $record['componentname'], 'flavorname' => $record['flavorname']]
        );

        $record['iconurl'] = self::update_pluginfile_tags_bulk($categorymap, $record['iconurl'] ?? '');

        if ($current) {
            $record['id'] = $current->id;
            $DB->update_record('tiny_elements_comp_flavor', $record);
        } else {
            $record['id'] = $DB->insert_record('tiny_elements_comp_flavor', $record);
        }
        return $record['id'];
    }

    /**
     * Import a relation between component and variant.
     *
     * @param array|object $record
     * @param array $componentmap
     * @return int id of the imported relation
     */
    public static function import_component_variant(array|object $record, array $componentmap): int {
        global $DB;
        $record = (array) $record;
        if (isset($componentmap[$record['component']])) {
            $record['component'] = $componentmap[$record['component']];
        }
        $current = $DB->get_record(
            'tiny_elements_comp_variant',
            ['component' => $record['component'], 'variant' => $record['variant']]
        );
        if (!$current) {
            $record['id'] = $DB->insert_record('tiny_elements_comp_variant', $record);
            return $record['id'];
        }
        return $current->id;
    }

    /**
     * Update the pluginfile tags in the given subject.
     *
     * @param array $categorymap
     * @param string $subject
     * @return string
     */
    public static function update_pluginfile_tags_bulk(array $categorymap, string $subject): string {
        $subject = self::update_c4l_pluginfile_tags($subject);
        foreach ($categorymap as $oldid => $newid) {
            $subject = self::update_pluginfile_tags($oldid, $newid, $subject, 'bulk');
        }
        $subject = self::remove_mark($subject, 'bulk');
        return $subject;
    }

    /**
     * Rename the pluginfile tags from tiny_c4l to tiny_elements.
     *
     * @param string $subject
     * @return string
     */
    public static function update_c4l_pluginfile_tags(string $subject): string {
        $oldstring = '@@PLUGINFILE@@/1/tiny_c4l/';
        $newstring = '@@PLUGINFILE@@/1/tiny_elements/';
        return str_replace($oldstring, $newstring, $subject);
    }

    /**
     * Update the pluginfile tags in the given subject.
     *
     * @param int $oldid
     * @param int $newid
     * @param string $subject
     * @param string $mark (optional) A string to mark the path - to be removed later.
     * @return string
     */
    public static function update_pluginfile_tags(int $oldid, int $newid, string $subject, string $mark = ''): string {
        $oldstring = '@@PLUGINFILE@@/1/tiny_elements/images/' . $oldid . '/';
        $newstring = '@@PLUGINFILE@@/1/tiny_elements/' . $mark . 'images/' . $newid . '/';
        return str_replace($oldstring, $newstring, $subject);
    }

    /**
     * Remove the mark from the given subject.
     *
     * @param string $subject
     * @param string $mark
     * @return string
     */
    public static function remove_mark(string $subject, string $mark): string {
        $newstring = '@@PLUGINFILE@@/1/tiny_elements/images/';
        $oldstring = '@@PLUGINFILE@@/1/tiny_elements/' . $mark . 'images/';
        return str_replace($oldstring, $newstring, $subject);
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
        $DB->delete_records('tiny_elements_component', ['compcat' => $id]);
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
     * Import data from a zip file.
     *
     * This method processes the provided zip file, extracts its contents,
     * and imports the relevant XML and related category files. It also handles
     * the cleanup of temporary files and rebuilds the system caches (CSS and JS).
     *
     * @param stored_file|string $zip The zip file to import, either as a file object or path.
     * @param int $draftitemid The draft item ID associated with the import process (default is 0).
     * @return void
     */
    public function import(stored_file|string $zip, $draftitemid = 0): void {
        global $DB;

        if ($zip instanceof stored_file || file_exists($zip)) {
            $fs = get_file_storage();
            $fp = get_file_packer('application/zip');
            $fp->extract_to_storage($zip, $this->contextid, 'tiny_elements', 'import', $draftitemid, '/');
            $manager = new manager();
            $xmlfile = $fs->get_file($this->contextid, 'tiny_elements', 'import', $draftitemid, '/', 'tiny_elements_export.xml');
            if (!$xmlfile) {
                $xmlfile = $fs->get_file($this->contextid, 'tiny_elements', 'import', $draftitemid, '/', 'tiny_c4l_export.xml');
            }
            $xmlcontent = $xmlfile->get_content();
            $manager->importxml($xmlcontent);
            $categories = $DB->get_records('tiny_elements_compcat');
            foreach ($categories as $category) {
                $categoryfiles = $fs->get_directory_files(
                    $this->contextid,
                    'tiny_elements',
                    'import',
                    $draftitemid,
                    '/' . $category->name . '/',
                    true,
                    false
                );
                $manager->importfiles($categoryfiles, $category->id, $category->name);
            }
            $fs->delete_area_files($this->contextid, 'tiny_elements', 'import', $draftitemid);

            local\utils::purge_css_cache();
            local\utils::rebuild_css_cache();
            local\utils::purge_js_cache();
            local\utils::rebuild_js_cache();
        }
    }
}
