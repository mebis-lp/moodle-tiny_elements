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

namespace tiny_elements\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use tiny_elements\manager;

/**
 * Webservice to delete tiny component entries.
 *
 * @package    tiny_elements
 * @copyright  2024 ISB Bayern
 * @author     Tobias Garske
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class delete_item extends external_api {
    /**
     * Describes the parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'id' => new external_value(PARAM_INT, 'Item id.', VALUE_REQUIRED),
            'table' => new external_value(PARAM_TEXT, 'Tablename.', VALUE_REQUIRED),
        ]);
    }

    /**
     * Execute the service.
     *
     * @param int $id the id of the item to delete
     * @param string $table the table to delete from
     * @return array
     * @throws invalid_parameter_exception
     * @throws dml_exception
     */
    public static function execute(int $id, string $table): array {
        global $DB;
        self::validate_parameters(self::execute_parameters(), [
            'id' => $id,
            'table' => $table,
        ]);
        $systemcontext = \context_system::instance();
        self::validate_context($systemcontext);
        require_capability('tiny/elements:manage', $systemcontext);

        $manager = new manager();
        switch ($table) {
            case 'compcat':
                $manager->delete_compcat($id);
                break;
            case 'flavor':
                $manager->delete_flavor($id);
                break;
            default:
                $DB->delete_records_select('tiny_elements_' . $table, 'id = ?', [$id]);
                break;
        }

        return ['result' => true];
    }

    /**
     * Describes the return structure of the service..
     *
     * @return external_single_structure the return structure
     */
    public static function execute_returns() {
        return new external_single_structure([
            'result' => new external_value(PARAM_BOOL, 'Removed successfully.'),
        ]);
    }
}
