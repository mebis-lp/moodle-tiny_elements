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
 * Class management_comp_flavor
 *
 * @package    tiny_elements
 * @copyright 2024 ISB Bayern
 * @author     Stefan Hanauska
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class management_comp_flavor_form extends dynamic_form {
    /**
     * Form definition
     */
    public function definition() {
        global $DB;

        $data = $this->_ajaxformdata;

        $conditions = [];

        if (!empty($data['component'])) {
            $conditions['componentname'] = $data['component'];
        }

        if (!empty($data['flavor'])) {
            $conditions['flavorname'] = $data['flavor'];
        }

        $count = $DB->count_records('tiny_elements_comp_flavor', $conditions);
        $mform =& $this->_form;

        $mform->addElement('hidden', 'component');
        $mform->setType('component', PARAM_TEXT);
        $mform->addElement('hidden', 'flavor');
        $mform->setType('flavor', PARAM_TEXT);

        $group = [];
        $group[] = $mform->createElement('hidden', 'id');
        $group[] = $mform->createElement('static', 'name', get_string('component_flavor', 'tiny_elements'));
        $group[] = $mform->createElement('text', 'iconurl', get_string('iconurl', 'tiny_elements'));

        $options = [
            'id' => [
                'type' => PARAM_INT,
            ],
            'name' => [
                'type' => PARAM_TEXT,
            ],
            'iconurl' => [
                'type' => PARAM_URL,
            ],
        ];

        $this->repeat_elements($group, $count, $options, 'itemcount', 'adddummy', 0);

        $mform->removeElement('adddummy');

        $mform->setAttributes(['data-formtype' => 'tiny_elements_comp_flavor']);
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
            $record->iconurl = utils::replace_pluginfile_urls($formdata->iconurl[$key] ?? '');
            $result &= $DB->update_record('tiny_elements_comp_flavor', $record);
        }

        // Purge CSS cache.
        \tiny_elements\local\utils::purge_css_cache();
        \tiny_elements\local\utils::rebuild_css_cache();

        return [
            'update' => $result,
        ];
    }

    /**
     * Load in existing data as form defaults
     */
    public function set_data_for_dynamic_submission(): void {
        global $DB;

        $data = $this->_ajaxformdata;

        $conditions = [];

        if (!empty($data['component'])) {
            $conditions['componentname'] = $data['component'];
        }

        if (!empty($data['flavor'])) {
            $conditions['flavorname'] = $data['flavor'];
        }

        $compflavor = $DB->get_records('tiny_elements_comp_flavor', $conditions);

        $data = [];
        foreach ($compflavor as $item) {
            $data['id'][] = $item->id;
            $data['name'][] = $item->componentname . '/' . $item->flavorname;
            $data['iconurl'][] = utils::replace_pluginfile_urls($item->iconurl ?? '', true);
        }

        $data['itemcount'] = count($compflavor);

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
