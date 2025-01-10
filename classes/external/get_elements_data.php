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
 * Web service to retrieve all elements data.
 *
 * @package    tiny_elements
 * @copyright  2024 ISB Bayern
 * @author     Stefan Hanauska
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_elements_data extends external_api {
    /**
     * Describes the parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'contextid' => new external_value(PARAM_INT, 'Context id', VALUE_REQUIRED),
            'isstudent' => new external_value(PARAM_BOOL, 'Only return components for students', VALUE_REQUIRED),
        ]);
    }

    /**
     * Retrieve the categories, components, flavors and variants.
     * @param int $contextid the context id (currently only system context is supported)
     * @param bool $isstudent only return components for students
     * @return array associative array containing the aggregated information for all elements data.
     */
    public static function execute(int $contextid, bool $isstudent): array {
        // We usually need to call validate_parameters, but we do not have any (yet).
        self::validate_parameters(self::execute_parameters(), [
            'contextid' => $contextid,
            'isstudent' => $isstudent,
        ]);
        $context = \core\context::instance_by_id($contextid);
        self::validate_context($context);

        require_capability('tiny/elements:viewplugin', $context);

        return \tiny_elements\local\utils::get_elements_data($isstudent);
    }

    /**
     * Describes the return structure of the service.
     *
     * @return external_multiple_structure the return structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'categories' => new external_multiple_structure(
                new external_single_structure([
                    'id' => new external_value(PARAM_INT, 'the id of the category'),
                    'name' => new external_value(PARAM_TEXT, 'the name of the category'),
                    'displayname' => new external_value(PARAM_TEXT, 'the display name of the category'),
                    'displayorder' => new external_value(PARAM_INT, 'the display order of the category'),
                ], 'a component category')
            ),
            'components' => new external_multiple_structure(
                new external_single_structure([
                    'id' => new external_value(PARAM_INT, 'the id of the component'),
                    'name' => new external_value(PARAM_TEXT, 'the name of the component'),
                    'displayname' => new external_value(PARAM_TEXT, 'the display name of the component'),
                    'compcat' => new external_value(PARAM_INT, 'the id of the component category'),
                    'code' => new external_value(PARAM_RAW, 'the code'),
                    'text' => new external_value(PARAM_TEXT, 'the text'),
                    'variants' => new external_multiple_structure(new external_value(PARAM_TEXT, 'the variants')),
                    'flavors' => new external_multiple_structure(new external_value(PARAM_TEXT, 'the flavors')),
                    'displayorder' => new external_value(PARAM_INT, 'the display order of the component'),
                ], 'a component')
            ),
            'flavors' => new external_multiple_structure(
                new external_single_structure([
                    'id' => new external_value(PARAM_INT, 'the id of the flavor'),
                    'name' => new external_value(PARAM_TEXT, 'the name of the flavor'),
                    'displayname' => new external_value(PARAM_TEXT, 'the display name of the flavor'),
                    'content' => new external_value(PARAM_RAW, 'the content'),
                    'categories' => new external_value(PARAM_TEXT, 'the categories'),
                ], 'a component flavor')
            ),
            'variants' => new external_multiple_structure(
                new external_single_structure([
                    'id' => new external_value(PARAM_INT, 'the id of the variant'),
                    'name' => new external_value(PARAM_TEXT, 'the name of the variant'),
                    'displayname' => new external_value(PARAM_TEXT, 'the display name of the variant'),
                    'content' => new external_value(PARAM_RAW, 'the content'),
                    'c4lcompatibility' => new external_value(PARAM_BOOL, 'c4l compatibility'),
                ], 'a component variant')
            ),
        ]);
    }
}
