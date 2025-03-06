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
 * Do bulk updates on elements.
 *
 * @package    tiny_elements
 * @copyright  2025 ISB Bayern
 * @author     Stefan Hanauska <stefan.hanauska@csg-in.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use tiny_elements\local\utils;

require('../../../../../config.php');

require_login();

$url = new moodle_url('/lib/editor/tiny/plugins/elements/bulkupdate.php', []);
$PAGE->set_url($url);
$PAGE->set_context(context_system::instance());
$PAGE->set_heading(get_string('menuitem_elements', 'tiny_elements') . ' ' . get_string('management', 'tiny_elements'));

require_capability('tiny/elements:manage', context_system::instance());

echo $OUTPUT->header();

$flavornames = $DB->get_fieldset('tiny_elements_flavor', 'name');
$dbcompcats = $DB->get_records('tiny_elements_compcat');
$dbflavor = $DB->get_records('tiny_elements_flavor');
$dbcomponent = $DB->get_records('tiny_elements_component');
$dbvariant = $DB->get_records('tiny_elements_variant');

foreach ($dbcompcat as $compcat) {
    foreach ($dbflavor as $flavor) {
        $compcat->css = str_replace('.' . $flavor->name, '.elements-' . $flavor->name . '-flavor', $compcat->css);
    }
    $DB->update_record('tiny_elements_compcat', $compcat);
}

foreach ($dbcomponent as $component) {
    foreach ($dbflavor as $flavor) {
        $component->css = str_replace('.' . $flavor->name, '.elements-' . $flavor->name . '-flavor', $component->css);
    }
    $DB->update_record('tiny_elements_component', $component);
}

foreach ($dbvariant as $variant) {
    foreach ($dbflavor as $flavor) {
        $variant->css = str_replace('.' . $flavor->name, '.elements-' . $flavor->name . '-flavor', $variant->css);
    }
    $DB->update_record('tiny_elements_variant', $variant);
}

foreach ($dbflavor as $flavor) {
    $flavor->css = str_replace('.' . $flavor->name, '.elements-' . $flavor->name . '-flavor', $flavor->css);
    $DB->update_record('tiny_elements_flavor', $flavor);
}

utils::purge_and_rebuild_caches();

echo $OUTPUT->footer();
