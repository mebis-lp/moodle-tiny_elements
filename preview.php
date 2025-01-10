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
 * Creates a preview for elements components
 *
 * @package    tiny_elements
 * @copyright  2024 Tobias Garske, ISB Bayern
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../../../../config.php');

require_login();

$url = new moodle_url('/lib/editor/tiny/plugins/elements/preview.php', []);
$PAGE->set_url($url);
$PAGE->set_context(context_system::instance());
$PAGE->set_heading($SITE->fullname);
$PAGE->set_pagelayout('popup');

echo $OUTPUT->header();

$component  = required_param('component', PARAM_ALPHANUMEXT);
$flavor  = required_param('flavor', PARAM_ALPHANUMEXT);

$componentdata = $DB->get_record('tiny_elements_component', ['name' => $component]);
$categorydata = $DB->get_record('tiny_elements_compcat', ['id' => $componentdata->compcat]);
$flavordata = $DB->get_record('tiny_elements_flavor', ['name' => $flavor]);

$variant = '';
$varianthtml = '';
$componentdata->code = str_replace('{{CATEGORY}}', $categorydata->name, $componentdata->code);
$componentdata->code = str_replace('{{COMPONENT}}', $component, $componentdata->code);
$componentdata->code = str_replace('{{VARIANTS}}', $variant, $componentdata->code);
$componentdata->code = str_replace('{{VARIANTSHTML}}', $varianthtml, $componentdata->code);
$componentdata->code = str_replace('{{PLACEHOLDER}}', $componentdata->text ?? 'Lorem ipsum', $componentdata->code);

if (empty($flavordata)) {
    echo str_replace('{{FLAVOR}}', '', $componentdata->code);
} else {
    echo str_replace('{{FLAVOR}}', $flavordata->name, $componentdata->code);
}

echo $OUTPUT->footer();
