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
use tiny_elements\local\utils;
use core\exception\moodle_exception;

/**
 * Class importer
 *
 * @package    tiny_elements
 * @author     Stefan Hanauska <stefan.hanauska@csg-in.de>
 * @copyright  2025 ISB Bayern
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class importer {
    /** @var int $contextid */
    protected int $contextid;

    /** @var bool $dryrun If set to true, importer does a dry run and doesn't change anything in the database.*/
    protected bool $dryrun = false;

    /** @var array $importresults */
    protected array $importresults = [];

    /**
     * Constructor.
     *
     * @param int $contextid
     * @param bool $dryrun If true, the import process is simulated without any changes (default is false).
     */
    public function __construct(int $contextid = SYSCONTEXTID, bool $dryrun = false) {
        $this->contextid = $contextid;
        $this->dryrun = $dryrun;
    }

    /**
     * Import data from a zip file.
     *
     * This method processes the provided zip file, extracts its contents,
     * and imports the relevant XML and related category files. It also handles
     * the cleanup of temporary files and rebuilds the system caches (CSS and JS).
     *
     * @param \stored_file|string $zip The zip file to import, either as a file object or path.
     * @param int $draftitemid The draft item ID associated with the import process (default is 0).

     * @return void
     */
    public function import(\stored_file|string $zip, int $draftitemid = 0): void {
        global $DB;

        if ($zip instanceof \stored_file || file_exists($zip)) {
            $fs = get_file_storage();
            $fp = get_file_packer('application/zip');
            $fp->extract_to_storage($zip, $this->contextid, 'tiny_elements', 'import', $draftitemid, '/');
            $xmlfile = $fs->get_file($this->contextid, 'tiny_elements', 'import', $draftitemid, '/', constants::FILE_NAME_EXPORT);
            if (!$xmlfile) {
                throw new moodle_exception(get_string('error_import_missing_xml', 'tiny_elements'));
            }
            if (!$xmlfile) {
                throw new moodle_exception(get_string('error_import_missing_xml', 'tiny_elements'));
            }
            $xmlcontent = $xmlfile->get_content();
            // Import data.
            $categorymap = $this->importxml($xmlcontent);
            // Import files.
            [$incategoryids, $inparams] = $DB->get_in_or_equal(array_values($categorymap), SQL_PARAMS_NAMED);
            $categories = $DB->get_records_select('tiny_elements_compcat', 'id ' . $incategoryids, $inparams, '', 'id, name');
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
                $metadata = $this->update_files_metadata($category->name, $draftitemid);
                $this->importfiles($categoryfiles, $category->id, $metadata, $category->name);
            }
            $fs->delete_area_files($this->contextid, 'tiny_elements', 'import', $draftitemid);
        }
    }

    /**
     * Import files.
     *
     * @param array $files Array of stored_files.
     * @param int $categoryid ID of the category.
     * @param array $metadata Array of filemetadata objects.
     * @param string $categoryname Name of the category.
     * @throws moodle_exception
     */
    public function importfiles(array $files, int $categoryid, array $metadata, string $categoryname = ''): void {
        $fs = get_file_storage();
        foreach ($files as $file) {
            if ($file->is_directory()) {
                continue;
            }
            if ($file->get_mimetype() == 'application/xml') {
                $filename = $file->get_filename();
                if ($filename == constants::FILE_NAME_EXPORT || str_starts_with($filename, constants::FILE_NAME_METADATA)) {
                    continue;
                }
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
                    if (!$this->dryrun) {
                        $oldfile->replace_file_with($file);
                    }
                    $this->importresults[] = get_string('replacefile', 'tiny_elements', $newfilepath . $file->get_filename());
                } else {
                    $this->importresults[] = get_string('unchangedfile', 'tiny_elements', $newfilepath . $file->get_filename());
                }
            } else {
                if (!$this->dryrun) {
                    $filemetadata = [];
                    if (count($metadata) !== 0) {
                        // Find metadata for this file.
                        foreach ($metadata[$categoryname] as $metafile) {
                            if ($metafile->filename == $file->get_filename()) {
                                $filemetadata['source'] = $metafile->source;
                                $filemetadata['author'] = $metafile->author;
                                $filemetadata['license'] = $metafile->license;
                                break;
                            }
                        }
                    }
                    // Create file.
                    $filechanges = [
                        'contextid' => $this->contextid,
                        'component' => 'tiny_elements',
                        'filearea' => 'images',
                        'itemid' => $categoryid,
                        'filepath' => $newfilepath,
                        'filename' => $file->get_filename(),
                    ];
                    $newfile = $fs->create_file_from_storedfile(array_merge($filechanges, $filemetadata), $file);
                    if (!$newfile) {
                        throw new moodle_exception(
                            get_string('error_fileimport', 'tiny_elements', $newfilepath . $file->get_filename())
                        );
                    }
                }
                $this->importresults[] = get_string('newfile', 'tiny_elements', $newfilepath . $file->get_filename());
            }
        }
    }

    /**
     * Load xml and import data.
     *
     * @param string $xmlcontent XML content to be imported.
     * @return array $categorymap ID mapping of categories.
     */
    public function importxml(string $xmlcontent): array {
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
            if (!isset($xml->$table) && !in_array($table, constants::OPTIONAL_TABLES)) {
                throw new moodle_exception(get_string('error_import_missing_table', 'tiny_elements', $table));
            }
        }

        $data = [];

        // Make data usable for further processing.
        foreach ($xml as $table => $rows) {
            foreach ($rows as $row) {
                $obj = new \stdClass();
                foreach ($row as $column => $value) {
                    $obj->$column = (string) $value;
                }
                if (in_array($table, constants::TABLES)) {
                    $data[$table][] = $obj;
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

        if (!$this->dryrun) {
            local\utils::purge_and_rebuild_caches();
        }

        return $categorymap;
    }

    /**
     * Import a component category.
     *
     * @param array|object $record

     * @return int id of the imported category
     */
    public function import_category(array|object $record): int {
        global $DB;
        $record = (array) $record;
        $oldid = $record['id'];
        $current = $DB->get_record('tiny_elements_compcat', ['name' => $record['name']]);
        if ($current) {
            $record['id'] = $current->id;
            if (!$this->dryrun) {
                $DB->update_record('tiny_elements_compcat', $record);
            }
            $this->importresults[] = get_string('replacecategory', 'tiny_elements', $record['name']);
        } else {
            if (!$this->dryrun) {
                $record['id'] = $DB->insert_record('tiny_elements_compcat', $record);
            } else {
                $record['id'] = rand(1, PHP_INT_MAX);
            }
            $this->importresults[] = get_string('newcategory', 'tiny_elements', $record['name']);
        }
        // Update pluginfile tags in css if the id has changed.
        if ($oldid != $record['id'] && !$this->dryrun) {
            $record['css'] = utils::update_pluginfile_tags($oldid, $record['id'], $record['css']);
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
    public function import_component(array|object $record, array $categorymap): int {
        global $DB;
        $record = (array) $record;
        if (array_key_exists('compcat', $record) && array_key_exists($record['compcat'], $categorymap)) {
            $record['compcat'] = $categorymap[$record['compcat']];
            $record['categoryname'] = $DB->get_field('tiny_elements_compcat', 'name', ['id' => $record['compcat']]);
        }

        $record['css'] = utils::update_pluginfile_tags_bulk($categorymap, $record['css'] ?? '');
        $record['code'] = utils::update_pluginfile_tags_bulk($categorymap, $record['code'] ?? '');
        $record['js'] = utils::update_pluginfile_tags_bulk($categorymap, $record['js'] ?? '');
        $record['iconurl'] = utils::update_pluginfile_tags_bulk($categorymap, $record['iconurl'] ?? '');

        $current = $DB->get_record('tiny_elements_component', ['name' => $record['name']]);
        if ($current) {
            $record['id'] = $current->id;
            if (!$this->dryrun) {
                $DB->update_record('tiny_elements_component', $record);
            }
            $this->importresults[] = get_string('replacecomponent', 'tiny_elements', $record['name']);
        } else {
            try {
                if (!$this->dryrun) {
                    $record['id'] = $DB->insert_record('tiny_elements_component', $record);
                } else {
                    $record['id'] = rand(1, PHP_INT_MAX);
                }
                $this->importresults[] = get_string('newcomponent', 'tiny_elements', $record['name']);
            } catch (\Exception $e) {
                throw new moodle_exception(get_string('error_import_component', 'tiny_elements', $record['name']));
            }
        }

        if (!$this->dryrun) {
            if (!empty($record['flavors'])) {
                foreach (explode(',', $record['flavors']) as $flavor) {
                    if ($flavor == '') {
                        continue;
                    }
                    $flavorrecord = [
                        'componentname' => $record['name'],
                        'flavorname' => $flavor,
                    ];
                    $existing = $DB->get_record('tiny_elements_comp_flavor', $flavorrecord);
                    if (!$existing) {
                        $DB->insert_record('tiny_elements_comp_flavor', $flavorrecord);
                    }
                }
            }

            if (!empty($record['variants'])) {
                foreach (explode(',', $record['variants']) as $variant) {
                    if ($variant == '') {
                        continue;
                    }
                    $variantrecord = [
                        'componentname' => $record['name'],
                        'variant' => $variant,
                    ];
                    $existing = $DB->get_record('tiny_elements_comp_variant', $variantrecord);
                    if (!$existing) {
                        $DB->insert_record('tiny_elements_comp_variant', $variantrecord);
                    }
                }
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
    public function import_flavor(array|object $record, array $categorymap): int {
        global $DB;
        $record = (array) $record;
        $current = $DB->get_record('tiny_elements_flavor', ['name' => $record['name']]);

        $record['css'] = utils::update_pluginfile_tags_bulk($categorymap, $record['css'], 'import');
        $record['content'] = utils::update_pluginfile_tags_bulk($categorymap, $record['content'], 'import');

        if ($current) {
            $record['id'] = $current->id;
            if (!$this->dryrun) {
                $DB->update_record('tiny_elements_flavor', $record);
            }
            $this->importresults[] = get_string('replaceflavor', 'tiny_elements', $record['name']);
        } else {
            if (!$this->dryrun) {
                $record['id'] = $DB->insert_record('tiny_elements_flavor', $record);
            } else {
                $record['id'] = rand(1, PHP_INT_MAX);
            }
            $this->importresults[] = get_string('newflavor', 'tiny_elements', $record['name']);
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
    public function import_variant(array|object $record, array $categorymap): int {
        global $DB;
        $record = (array) $record;
        $current = $DB->get_record('tiny_elements_variant', ['name' => $record['name']]);

        $record['css'] = utils::update_pluginfile_tags_bulk($categorymap, $record['css'] ?? '');
        $record['content'] = utils::update_pluginfile_tags_bulk($categorymap, $record['content'] ?? '');
        $record['iconurl'] = utils::update_pluginfile_tags_bulk($categorymap, $record['iconurl'] ?? '');

        if ($current) {
            $record['id'] = $current->id;
            if (!$this->dryrun) {
                $DB->update_record('tiny_elements_variant', $record);
            }
            $this->importresults[] = get_string('replacevariant', 'tiny_elements', $record['name']);
        } else {
            if (!$this->dryrun) {
                $record['id'] = $DB->insert_record('tiny_elements_variant', $record);
            } else {
                $record['id'] = rand(1, PHP_INT_MAX);
            }
            $this->importresults[] = get_string('newvariant', 'tiny_elements', $record['name']);
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
    public function import_component_flavor(array|object $record, array $categorymap): int {
        global $DB;
        $record = (array) $record;
        $current = $DB->get_record(
            'tiny_elements_comp_flavor',
            ['componentname' => $record['componentname'], 'flavorname' => $record['flavorname']]
        );

        $record['iconurl'] = utils::update_pluginfile_tags_bulk($categorymap, $record['iconurl'] ?? '');

        if ($current) {
            $record['id'] = $current->id;
            if (!$this->dryrun) {
                $DB->update_record('tiny_elements_comp_flavor', $record);
            }
            $this->importresults[] = get_string(
                'replacecompflavor',
                'tiny_elements',
                $record['componentname'] . ' - ' . $record['flavorname']
            );
        } else {
            if (!$this->dryrun) {
                $record['id'] = $DB->insert_record('tiny_elements_comp_flavor', $record);
            } else {
                $record['id'] = rand(1, PHP_INT_MAX);
            }
            $this->importresults[] = get_string(
                'newcompflavor',
                'tiny_elements',
                $record['componentname'] . ' - ' . $record['flavorname']
            );
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
    public function import_component_variant(array|object $record, array $componentmap): int {
        global $DB;
        $record = (array) $record;

        if (!array_key_exists('componentname', $record)) {
            // Do not import relations for components that are not part of the import.
            if (!isset($componentmap[$record['component']])) {
                return 0;
            }
            $record['component'] = $componentmap[$record['component']];
            $record['componentname'] = $DB->get_field(
                'tiny_elements_component',
                'name',
                ['id' => $record['component']]
            );
        }

        $current = $DB->get_record(
            'tiny_elements_comp_variant',
            ['componentname' => $record['componentname'], 'variant' => $record['variant']]
        );
        if (!$current) {
            if (!$this->dryrun) {
                $record['id'] = $DB->insert_record('tiny_elements_comp_variant', $record);
            } else {
                $record['id'] = rand(1, PHP_INT_MAX);
            }
            $this->importresults[] = get_string(
                'newcompvariant',
                'tiny_elements',
                $record['componentname'] . ' - ' . $record['variant']
            );
            return $record['id'];
        }
        $this->importresults[] = get_string(
            'replacecompvariant',
            'tiny_elements',
            $record['componentname'] . ' - ' . $record['variant']
        );
        return $current->id;
    }

    /**
     * Get import results.
     *
     * @return array
     */
    public function get_importresults(): array {
        return $this->importresults;
    }

    /**
     * Updates files metadata.
     *
     * @param string $categoryname The name of the category to update metadata for.
     * @param int $draftitemid The draft item ID associated with the import process (default is 0).
     * @return array Array of filemetadata objects.
     */
    public function update_files_metadata($categoryname, $draftitemid = 0): array {
        $fs = get_file_storage();
        $xmlfile = $fs->get_file(
            $this->contextid,
            'tiny_elements',
            'import',
            $draftitemid,
            '\/' . $categoryname . '\/',
            constants::FILE_NAME_METADATA . '_' . $categoryname . '.xml'
        );
        // Manage older exports without filemetadata.
        if (!$xmlfile) {
            return [];
        }

        // Get xml content.
        $xmlcontent = $xmlfile->get_content();
        try {
            $xml = simplexml_load_string($xmlcontent);
        } catch (\Exception $exception) {
            $xml = false;
        }
        if (!$xml) {
            return [];
        }

        $data = [];
        // Make data usable for further processing.
        foreach ($xml as $catname => $rows) {
            foreach ($rows as $row) {
                $filemetadataobj = new \stdClass();
                foreach ($row as $column => $value) {
                    if ($column == 'license') {
                        $filemetadataobj->license = (string) $value->shortname;
                    } else {
                        $filemetadataobj->$column = (string) $value;
                    }
                }
                $data[$catname][] = $filemetadataobj;
            }
        }
        return $data;
    }
}
