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

/**
 * Install script for Components for Learning (Elements)
 *
 * Documentation: {@link https://moodledev.io/docs/guides/upgrade}
 *
 * @package    tiny_elements
 * @copyright  2024 ISB Bayern
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use tiny_elements\importer;

/**
 * Executed on installation of Components for Learning (Elements)
 *
 * @return bool
 */
function xmldb_tiny_elements_install() {
    try {
        $basezip = __DIR__ . '/base.zip';
        $importer = new importer();
        $importer->import($basezip);
    } catch (Exception $e) {
        debugging($e->getMessage(), DEBUG_NORMAL);
        return false;
    }
    return true;
}
