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
 * TODO describe file printurls
 *
 * @package    tiny_elements
 * @copyright  2024 ISB Bayern
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use function DI\get;

require('../../../../../config.php');

require_login();

$url = new moodle_url('/lib/editor/tiny/plugins/elements/printurls.php', []);
$PAGE->set_url($url);
$PAGE->set_context(context_system::instance());

require_capability('tiny/elements:manage', context_system::instance());

$PAGE->set_heading($SITE->fullname);
echo $OUTPUT->header();

$fs = get_file_storage();
$files = $fs->get_area_files(context_system::instance()->id, 'tiny_elements', 'images');
$processedfiles = [];
foreach ($files as $file) {
    if ($file->is_directory()) {
        continue;
    }
    $processedfiles[] = [
        'id' => $file->get_id(),
        'name' => $file->get_filename(),
        'url' => moodle_url::make_pluginfile_url(
            $file->get_contextid(),
            $file->get_component(),
            $file->get_filearea(),
            $file->get_itemid(),
            $file->get_filepath(),
            $file->get_filename()
        )->out(),
    ];
}

echo($OUTPUT->render_from_template('tiny_elements/imageurls', ['imageurls' => $processedfiles]));

echo $OUTPUT->footer();
