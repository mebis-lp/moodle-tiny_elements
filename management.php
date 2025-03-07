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
 * Management site: create, import and edit components.
 *
 * @package    tiny_elements
 * @copyright  2024 Tobias Garske, ISB Bayern
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use tiny_elements\local\utils;

require('../../../../../config.php');

require_login();

$url = new moodle_url('/lib/editor/tiny/plugins/elements/management.php', []);
$PAGE->set_url($url);
$PAGE->set_context(context_system::instance());
$PAGE->set_heading(get_string('menuitem_elements', 'tiny_elements') . ' ' . get_string('management', 'tiny_elements'));
$compcatactive = optional_param('compcat', '', PARAM_ALPHANUMEXT);
$showbulkedit = optional_param('showbulkedit', false, PARAM_BOOL);

require_capability('tiny/elements:manage', context_system::instance());

echo $OUTPUT->header();

// Get all elements components.
$dbcompcats = $DB->get_records('tiny_elements_compcat', [], 'displayorder');
$dbflavor = $DB->get_records('tiny_elements_flavor', [], 'displayorder');
$dbcompflavor = $DB->get_records('tiny_elements_comp_flavor');
$dbcomponent = $DB->get_records('tiny_elements_component', [], 'displayorder');
$dbvariant = $DB->get_records('tiny_elements_variant');
$dbcompvariant = $DB->get_records('tiny_elements_comp_variant');

// Use array_values so mustache can parse it.
$compcats = array_values($dbcompcats);
$flavor = array_values($dbflavor);
$component = array_values($dbcomponent);
$variant = array_values($dbvariant);

// Build component preview images for management.
foreach ($component as $key => $value) {
    // Add corresponding flavors.
    $flavorsarr = [];
    $flavorexamplesarr = [];
    foreach ($dbcompflavor as $val) {
        if ($val->componentname == $value->name) {
            array_push($flavorsarr, $val->flavorname);
            if (!empty($val->iconurl)) {
                array_push($flavorexamplesarr, utils::replace_pluginfile_urls($val->iconurl, true));
            }
        }
    }
    $component[$key]->flavorsarr = $flavorsarr;
    if (empty($flavorexamplesarr)) {
        $component[$key]->flavorexamplesarr = [utils::replace_pluginfile_urls($value->iconurl, true)];
    } else {
        $component[$key]->flavorexamplesarr = $flavorexamplesarr;
    }
    // Keep only the first two entries.
    if (count($component[$key]->flavorexamplesarr) > 2) {
        $component[$key]->flavorexamplesarr = array_slice($component[$key]->flavorexamplesarr, 0, 2);
    }
}

// Add flavor previews.
foreach ($flavor as $key => $value) {
    // Look for an example in comp_flavor.
    foreach ($dbcompflavor as $val) {
        if ($val->flavorname == $value->name) {
            if (!empty($val->iconurl)) {
                $flavor[$key]->example = utils::replace_pluginfile_urls($val->iconurl, true);
                continue;
            }
        }
    }
}

// Use empty array to create an add item.
$addentry = [];
array_push($compcats, $addentry);
array_push($flavor, $addentry);
array_push($component, $addentry);
array_push($variant, $addentry);

// Add exportlink.
$exportlink = \moodle_url::make_pluginfile_url(
    SYSCONTEXTID,
    'tiny_elements',
    'export',
    null,
    '/',
    'tiny_elements_export.xml'
)->out();

$params = new \stdClass();
$params->compcatactive = $compcatactive;
$PAGE->requires->js_call_amd('tiny_elements/management', 'init', [$params]);
echo($OUTPUT->render_from_template('tiny_elements/management', [
    'compcats' => $compcats,
    'flavor' => $flavor,
    'component' => $component,
    'variant' => $variant,
    'exportlink' => $exportlink,
    'showbulkedit' => $showbulkedit,
]));
echo $OUTPUT->footer();
