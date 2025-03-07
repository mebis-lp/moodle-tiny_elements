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

namespace tiny_elements\admin;

/**
 * Class setting_customtext to show a link.
 *
 * @package    tiny_elements
 * @copyright  2024 Tobias Garske
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class setting_customtext extends \admin_setting {
    /**
     * Constructor.
     * @param string $name Unique setting name
     * @param string $visiblename Setting title
     * @param string $description Setting description
     * @param string $defaultsetting Default value
     */
    public function __construct($name, $visiblename, $description, $defaultsetting) {
        $this->nosave = true;
        parent::__construct($name, $visiblename, $description, $defaultsetting);
    }

    /**
     * Get the current setting.
     * @return string
     */
    public function get_setting() {
        // Nothing is saved.
        return '';
    }

    /**
     * Write the setting to the config.
     * @param string $data
     * @return bool
     */
    public function write_setting($data) {
        // Nothing is saved.
        return true;
    }

    /**
     * Return an HTML field with text only.
     * @param string $data
     * @param string $query
     * @return string
     */
    public function output_html($data, $query = '') {
        $html = '';
        return format_admin_setting($this, $this->visiblename, $html, $this->description, true, '', null, $query);
    }
}
