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

namespace tiny_elements\local;

/**
 * Constants for tiny_elements
 *
 * @package    tiny_elements
 * @author     Stefan Hanauska <stefan.hanauska@csg-in.de>
 * @copyright  2025 ISB Bayern
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class constants {
    /** @var array All tables to export data from. */
    public const TABLES = [
        'compcat' => 'tiny_elements_compcat',
        'component' => 'tiny_elements_component',
        'compflavor' => 'tiny_elements_comp_flavor',
        'compvariant' => 'tiny_elements_comp_variant',
        'flavor' => 'tiny_elements_flavor',
        'variant' => 'tiny_elements_variant',
    ];
    /** @var array All tables that are optional. */
    public const OPTIONAL_TABLES = ['tiny_elements_comp_flavor', 'tiny_elements_comp_variant'];

    /** @var string Item. */
    public const ITEMNAME = 'row';

    /** @var string CACHE_AREA the cache area for the tiny_elements plugin */
    public const CACHE_AREA = 'tiny_elements_css';

    /** @var string JS_CACHE_KEY the cache key for the js code */
    public const JS_CACHE_KEY = 'tiny_elements_js';

    /** @var string CSS_CACHE_KEY the cache key for the css code */
    public const CSS_CACHE_KEY = 'tiny_elements_css';

    /** @var string CSS_CACHE_REV the cache key for the css revision */
    public const CSS_CACHE_REV = 'tiny_elements_cssrev';

    /** @var string JS_CACHE_REV the cache key for the js revision */
    public const JS_CACHE_REV = 'tiny_elements_jsrev';

    /** @var array FILE_OPTIONS the options for the filemanager */
    public const FILE_OPTIONS = ['subdirs' => 1, 'accepted_types' => ['web_image']];

    /** @var array IMPORT_FILE_OPTIONS the options for the filemanager */
    public const IMPORT_FILE_OPTIONS = ['subdirs' => 0, 'accepted_types' => 'xml,zip', 'maxfiles' => 1];

    /** @var string FILE_NAMES_EXPORT xml file_name for export*/
    public const FILE_NAME_EXPORT = 'tiny_elements_export.xml';

    /** @var string FILE_NAME_METADATA xml file_name for metadata*/
    public const FILE_NAME_METADATA = 'tiny_elements_filemetadata';
}
