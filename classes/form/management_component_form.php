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

use tiny_elements\local\utils;

/**
 * Class management_component_form
 *
 * @package    tiny_elements
 * @copyright  2024 ISB Bayern
 * @author     Tobias Garske
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class management_component_form extends base_form {
    /**
     * Form definition.
     */
    public function definition() {
        global $DB;

        $compcats = $DB->get_records_menu('tiny_elements_compcat', null, 'displayname', 'name, displayname');
        $flavors = $DB->get_records_menu('tiny_elements_flavor', null, 'displayname', 'name, displayname');
        $variants = $DB->get_records_menu('tiny_elements_variant', null, 'displayname', 'name, displayname');

        $mform =& $this->_form;

        // Set this variable to access correct db table.
        $this->formtype = "component";

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('text', 'name', get_string('componentname', 'tiny_elements'), ['size' => '255']);
        $mform->setType('name', PARAM_TEXT);
        $mform->addHelpButton('name', 'componentname', 'tiny_elements');
        $mform->addRule('name', get_string('required'), 'required', null, 'client');
        $mform->addRule('name', get_string('validclassname', 'tiny_elements'), 'regex', '/^[_a-zA-Z][_a-zA-Z0-9-]*$/', 'client');

        $mform->addElement('text', 'displayname', get_string('displayname', 'tiny_elements'), ['size' => '1333']);
        $mform->setType('displayname', PARAM_TEXT);
        $mform->addHelpButton('displayname', 'displayname', 'tiny_elements');

        $mform->addElement('select', 'categoryname', get_string('category', 'tiny_elements'), $compcats);
        $mform->setType('categoryname', PARAM_TEXT);
        if (!empty($this->_ajaxformdata['categoryname'])) {
            $mform->setDefault('categoryname', $this->_ajaxformdata['categoryname']);
        }

        $mform->addElement($this->codemirror_present() ? 'editor' : 'textarea', 'code', get_string('code', 'tiny_elements'));
        $mform->setType('code', PARAM_RAW);
        $mform->addHelpButton('code', 'code', 'tiny_elements');

        $mform->addElement('textarea', 'text', get_string('text', 'tiny_elements'));
        $mform->setType('text', PARAM_TEXT);

        $mform->addElement(
            'autocomplete',
            'flavors',
            get_string('flavors', 'tiny_elements'),
            $flavors,
            ['multiple' => true, 'ajax' => 'tiny_elements/category_form_helper']
        );
        $mform->setType('flavors', PARAM_TEXT);

        $mform->addElement(
            'autocomplete',
            'variants',
            get_string('variants', 'tiny_elements'),
            $variants,
            ['multiple' => true, 'ajax' => 'tiny_elements/category_form_helper']
        );
        $mform->setType('variants', PARAM_TEXT);

        $mform->addElement('text', 'displayorder', get_string('displayorder', 'tiny_elements'));
        $mform->setType('displayorder', PARAM_INT);

        $mform->addElement($this->codemirror_present() ? 'editor' : 'textarea', 'css', get_string('css', 'tiny_elements'));
        $mform->setType('css', PARAM_RAW);

        $group = [];
        $group[] = $mform->createElement('text', 'iconurl', get_string('iconurl', 'tiny_elements'));
        $group[] = $mform->createElement(
            'button',
            'printurls',
            get_string('showprinturls', 'tiny_elements'),
            ['data-buttontype' => 'tiny_elements_printurls']
        );
        $mform->setType('iconurl', PARAM_URL);
        $mform->addElement('group', 'icongroup', get_string('iconurl', 'tiny_elements'), $group, false);

        $mform->addElement('checkbox', 'hideforstudents', get_string('hideforstudents', 'tiny_elements'));
        $mform->setType('hideforstudents', PARAM_INT);
    }

    /**
     * Form definition after data is loaded.
     */
    public function definition_after_data() {
        global $PAGE;
        parent::definition_after_data();
        $PAGE->requires->js_call_amd(
            'tiny_elements/imagepicker',
            'init',
            ['[data-buttontype="tiny_elements_printurls"]', '[name*="iconurl"]']
        );
    }

    /**
     * Process dynamic submission.
     *
     * @return array
     */
    public function process_dynamic_submission(): array {
        $context = $this->get_context_for_dynamic_submission();
        $formdata = $this->get_data();
        $this->postprocess_editors($formdata);

        $manager = new \tiny_elements\manager($context->id);

        if (empty($formdata->id)) {
            $result = $manager->add_component($formdata);
        } else {
            $result = $manager->update_component($formdata);
        }

        return [
            'update' => $result,
        ];
    }
}
