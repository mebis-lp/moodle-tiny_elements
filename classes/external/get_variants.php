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
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;

/**
 * Web service to retrieve variants data.
 *
 * @package    tiny_elements
 * @copyright  2024 ISB Bayern
 * @author     Stefan Hanauska
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_variants extends external_api {
    /**
     * Describes the parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'contextid' => new external_value(PARAM_INT, 'Context id', VALUE_REQUIRED),
            'categoryname' => new external_value(PARAM_TEXT, 'Category name', VALUE_REQUIRED),
            'query' => new external_value(PARAM_TEXT, 'Query string', VALUE_REQUIRED),
        ]);
    }

    /**
     * Retrieve the variants.
     * @param int $contextid the context id (currently only system context is supported)
     * @param string $categoryname the category name
     * @param string $query the query string
     * @return array associative array containing the aggregated information for all elements data.
     */
    public static function execute(int $contextid, string $categoryname, string $query = ''): array {
        self::validate_parameters(self::execute_parameters(), [
            'contextid' => $contextid,
            'categoryname' => $categoryname,
            'query' => $query,
        ]);
        $context = \core\context::instance_by_id($contextid);
        self::validate_context($context);

        require_capability('tiny/elements:manage', $context);

        return \tiny_elements\local\utils::get_all_variants(false, $categoryname, $query);
    }

    /**
     * Describes the return structure of the service.
     *
     * @return external_multiple_structure the return structure
     */
    public static function execute_returns(): external_multiple_structure {
        return new external_multiple_structure(
            new external_single_structure([
                'id' => new external_value(PARAM_INT, 'the id of the variant'),
                'name' => new external_value(PARAM_TEXT, 'the name of the variant'),
                'displayname' => new external_value(PARAM_TEXT, 'the display name of the variant'),
                'categoryname' => new external_value(PARAM_TEXT, 'the category name of the variant'),
            ], 'a component variant')
        );
    }
}
