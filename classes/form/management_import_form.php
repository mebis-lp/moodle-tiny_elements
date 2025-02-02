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

namespace tiny_elements\form;

/**
 * Class management_import_form
 *
 * @package    tiny_elements
 * @copyright  2024 YOUR NAME <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class management_import_form extends base_form {
    /**
     * Form definition.
     */
    public function definition() {
        $mform =& $this->_form;

        $mform->addElement(
            'filepicker',
            'backupfile',
            get_string('file'),
            null,
            ['accepted_types' => 'xml,zip']
        );

        $mform->addElement('advcheckbox', 'whatif', get_string('whatif', 'tiny_elements'));
        $mform->addHelpButton('whatif', 'whatif', 'tiny_elements');
    }

    /**
     * Form validation.
     *
     * @param array $data array of ("fieldname"=>value) of submitted data
     * @param array $files array of uploaded files "element_name"=>tmp_file_path
     * @return array of "element_name"=>"error_description" if there are errors,
     *         or an empty array if everything is OK (true allowed for backwards compatibility too).
     */
    public function validation($data, $files) {
        $errors = [];
        if (empty($data['backupfile'])) {
            $errors['backupfile'] = get_string('errorbackupfile', 'tiny_elements');
        }
        return $errors;
    }

    /**
     * Process the form submission, used if form was submitted via AJAX
     *
     * @return array Returns whether a new source was created.
     */
    public function process_dynamic_submission(): array {
        global $OUTPUT;
        $fs = get_file_storage();
        $data = $this->get_data();
        $draftitemid = $data->backupfile;
        file_save_draft_area_files($draftitemid, SYSCONTEXTID, 'tiny_elements', 'import', $draftitemid);
        $files = $fs->get_directory_files(SYSCONTEXTID, 'tiny_elements', 'import', $draftitemid, '/', false, false);
        do {
            $file = array_pop($files);
        } while ($file !== null && $file->is_directory());
        if ($file === null) {
            throw new \moodle_exception('errorbackupfile', 'tiny_elements');
        }
        $whatif = !empty($data->whatif);

        $importer = new \tiny_elements\importer(SYSCONTEXTID, $whatif);

        if ($file->get_mimetype() == 'application/zip') {
            $importer->import($file);
        } else {
            $xmlcontent = $file->get_content();
            $importer->importxml($xmlcontent);
        }

        $return = ['update' => !$whatif];

        if (!$whatif) {
            \tiny_elements\local\utils::purge_css_cache();
        } else {
            $results = $importer->get_importresults();
            $return['results'] = $results;
        }

        $fs->delete_area_files(SYSCONTEXTID, 'tiny_elements', 'import', $draftitemid);

        return $return;
    }

    /**
     * Load in existing data as form defaults
     */
    public function set_data_for_dynamic_submission(): void {
    }
}
