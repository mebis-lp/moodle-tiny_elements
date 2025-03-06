<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Tiny Elements plugin settings.
 *
 * @package     tiny_elements
 * @copyright   2022 Marc Català <reskit@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$ADMIN->add('editortiny', new admin_category('tiny_elements', new lang_string('pluginname', 'tiny_elements')));

$settings = new admin_settingpage('tiny_elements_settings', new lang_string('pluginname', 'tiny_elements'));

if ($ADMIN->fulltree) {
    // Custom components settings.
    $settings->add(
        new admin_setting_heading('tiny_elements/generalsettings', new lang_string('generalsettings', 'tiny_elements'), '')
    );

    // Configure component preview.
    $name = get_string('enablepreview', 'tiny_elements');
    $desc = get_string('enablepreview_desc', 'tiny_elements');
    $default = 1;
    $setting = new admin_setting_configcheckbox('tiny_elements/enablepreview', $name, $desc, $default);
    $settings->add($setting);

    // Mark inserted components.
    $name = get_string('markinserted', 'tiny_elements');
    $desc = get_string('markinserted_desc', 'tiny_elements');
    $default = 1;
    $setting = new admin_setting_configcheckbox('tiny_elements/markinserted', $name, $desc, $default);
    $settings->add($setting);

    // Add text with link to management as setting.
    $settings->add(new \tiny_elements\admin\setting_customtext(
        'tiny_elements/management',
        get_string('linktomanagername', 'tiny_elements'),
        get_string(
            'linktomanagerdesc',
            'tiny_elements',
            (new moodle_url('/lib/editor/tiny/plugins/elements/management.php'))->out()
        ),
        ''
    ));
}
