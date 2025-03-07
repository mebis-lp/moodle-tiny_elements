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

use core_form\dynamic_form;
use tiny_elements\local\utils;
use context;

/**
 * Form for bulk editing displaynames of variants
 *
 * @package    tiny_elements
 * @copyright  2025 ISB Bayern
 * @author     Stefan Hanauska <stefan.hanauska@csg-in.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class management_displaynames_variants_form extends dynamic_form {
    /**
     * Form definition
     */
    public function definition() {
        global $DB;
        $count = $DB->count_records('tiny_elements_variant');
        $mform =& $this->_form;

        $group = [];
        $group[] = $mform->createElement('hidden', 'id');
        $group[] = $mform->createElement('static', 'name', get_string('variant', 'tiny_elements'));
        $group[] = $mform->createElement('text', 'displayname', get_string('displayname', 'tiny_elements'));

        $options = [
            'id' => [
                'type' => PARAM_INT,
            ],
            'name' => [
                'type' => PARAM_TEXT,
            ],
            'displayname' => [
                'type' => PARAM_TEXT,
            ],
        ];

        $this->repeat_elements($group, $count, $options, 'itemcount', 'adddummy', 0);

        $mform->removeElement('adddummy');

        $mform->setAttributes(['data-formtype' => 'tiny_elements_displaynames']);
    }

    /**
     * Returns context where this form is used
     *
     * @return context
     */
    protected function get_context_for_dynamic_submission(): context {
        return \context_system::instance();
    }

    /**
     *
     * Checks if current user has sufficient permissions, otherwise throws exception
     */
    protected function check_access_for_dynamic_submission(): void {
        require_capability('tiny/elements:manage', $this->get_context_for_dynamic_submission());
    }

    /**
     * Form processing.
     *
     * @return array
     */
    public function process_dynamic_submission(): array {
        global $DB;

        $formdata = $this->get_data();

        $result = true;

        foreach ($formdata->id as $key => $id) {
            $record = new \stdClass();
            $record->id = $id;
            $record->displayname = $formdata->displayname[$key];
            $result &= $DB->update_record('tiny_elements_variant', $record);
        }

        return [
            'update' => $result,
        ];
    }

    /**
     * Load in existing data as form defaults
     */
    public function set_data_for_dynamic_submission(): void {
        global $DB;

        $variants = $DB->get_records('tiny_elements_variant');

        $data = [];
        foreach ($variants as $variant) {
            $data['id'][] = $variant->id;
            $data['name'][] = $variant->name;
            $data['displayname'][] = $variant->displayname;
        }

        $data['itemcount'] = count($variants);

        $this->set_data($data);
    }

    /**
     * Returns url to set in $PAGE->set_url() when form is being rendered or submitted via AJAX
     *
     * @return moodle_url
     */
    protected function get_page_url_for_dynamic_submission(): \moodle_url {
        return new \moodle_url('/lib/editor/tiny/plugins/elements/management.php');
    }
}
