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
 * External service definitions for tiny_elements
 *
 * @package    tiny_elements
 * @copyright  ISB Bayern, 2024
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
        'tiny_elements_get_elements_data' => [
                'classname' => 'tiny_elements\external\get_elements_data',
                'description' => 'Retrieve all elements data',
                'type' => 'read',
                'ajax' => true,
                'capabilities' => 'tiny/elements:viewplugin',
        ],
        'tiny_elements_delete_item' => [
                'classname'     => 'tiny_elements\external\delete_item',
                'methodname'    => 'execute',
                'description'   => 'Delete item.',
                'type'          => 'write',
                'ajax'          => true,
                'capabilities'  => 'tiny/elements:manage',
        ],
        'tiny_elements_duplicate_item' => [
                'classname'     => 'tiny_elements\external\duplicate_item',
                'methodname'    => 'execute',
                'description'   => 'Duplicate item.',
                'type'          => 'write',
                'ajax'          => true,
                'capabilities'  => 'tiny/elements:manage',
        ],
        'tiny_elements_get_variants' => [
                'classname' => 'tiny_elements\external\get_variants',
                'description' => 'Retrieve variants data',
                'type' => 'read',
                'ajax' => true,
                'capabilities' => 'tiny/elements:manage',
        ],
        'tiny_elements_get_flavors' => [
                'classname' => 'tiny_elements\external\get_flavors',
                'description' => 'Retrieve flavors data',
                'type' => 'read',
                'ajax' => true,
                'capabilities' => 'tiny/elements:manage',
        ],
];
