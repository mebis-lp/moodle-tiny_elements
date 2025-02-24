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
 * Class management_variant_form
 *
 * @package    tiny_elements
 * @copyright  2024 Tobias Garske, ISB Bayern
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class management_variant_form extends base_form {
    /**
     * Form definition.
     */
    public function definition() {
        global $DB;
        $mform =& $this->_form;

        // Set this variable to access correct db table.
        $this->formtype = "variant";

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('text', 'name', get_string('name', 'tiny_elements'), ['size' => '255']);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', get_string('required'), 'required', null, 'client');
        $mform->addRule('name', get_string('validclassname', 'tiny_elements'), 'regex', '/^[_a-zA-Z][_a-zA-Z0-9-]*$/', 'client');

        $mform->addElement('text', 'displayname', get_string('displayname', 'tiny_elements'), ['size' => '255']);
        $mform->setType('displayname', PARAM_TEXT);

        $compcats = $DB->get_records_menu('tiny_elements_compcat', null, 'displayname', 'name, displayname');
        $mform->addElement('select', 'categoryname', get_string('category', 'tiny_elements'), $compcats);
        $mform->setType('categoryname', PARAM_INT);
        if (!empty($this->_ajaxformdata['categoryname'])) {
            $mform->setDefault('categoryname', $this->_ajaxformdata['categoryname']);
        }

        $mform->addElement($this->codemirror_present() ? 'editor' : 'textarea', 'content', get_string('content', 'tiny_elements'));
        $mform->setType('content', PARAM_RAW);

        $mform->addElement($this->codemirror_present() ? 'editor' : 'textarea', 'css', get_string('css', 'tiny_elements'));
        $mform->setType('css', PARAM_RAW);

        $mform->addElement('url', 'iconurl', get_string('iconurl', 'tiny_elements'), ['size' => '255']);
        $mform->setType('iconurl', PARAM_URL);

        $mform->addElement(
            'static',
            'printurls',
            '',
            '<a href="#" onclick="window.open(\'printurls.php\', \'popup\', \'width=800,height=600\'); return false;">' .
            get_string('showprinturls', 'tiny_elements') . '</a>'
        );

        $mform->addElement('advcheckbox', 'c4lcompatibility', get_string('c4lcompatibility', 'tiny_elements'));
        $mform->setType('c4lcompatibility', PARAM_INT);
        $mform->setDefault('c4lcompatibility', 0);
        $mform->addHelpButton('c4lcompatibility', 'c4lcompatibility', 'tiny_elements');
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
            $result = $manager->add_variant($formdata);
        } else {
            $result = $manager->update_variant($formdata);
        }

        return [
            'update' => $result,
        ];
    }
}
