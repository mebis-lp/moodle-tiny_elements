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

namespace tiny_elements;

use context;
use editor_tiny\plugin;
use editor_tiny\plugin_with_buttons;
use editor_tiny\plugin_with_configuration;
use editor_tiny\plugin_with_menuitems;
use tiny_elements\local\utils;

/**
 * Tiny elements plugin for Moodle.
 *
 * @package    tiny_elements
 * @copyright  2022 Marc Català <reskit@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class plugininfo extends plugin implements plugin_with_buttons, plugin_with_configuration, plugin_with_menuitems {
    /**
     * Get the editor buttons for this plugins
     *
     * @return array
     */
    public static function get_available_buttons(): array {
        return [
            'tiny_elements/elements',
        ];
    }
    /**
     * Get the dropdown menu items for this plugin
     *
     * @return array
     */
    public static function get_available_menuitems(): array {
        return [
            'tiny_elements/elements',
        ];
    }

    /**
     * Get the configuration for the plugin, capabilities and
     * config (from settings.php)
     *
     * @param context $context
     * @param array $options
     * @param array $fpoptions
     * @param \editor_tiny\editor|null $editor
     *
     * @return array
     */
    public static function get_plugin_configuration_for_context(
        context $context,
        array $options,
        array $fpoptions,
        ?\editor_tiny\editor $editor = null
    ): array {

        $config = get_config('tiny_elements');
        $viewelements = has_capability('tiny/elements:viewplugin', $context);
        $showpreview = get_config('tiny_elements', 'enablepreview');
        $isstudent = !has_capability('gradereport/grader:view', $context);

        $cache = \cache::make('tiny_elements', utils::TINY_ELEMENTS_CACHE_AREA);
        $rev = $cache->get(utils::TINY_ELEMENTS_CSS_CACHE_REV);
        if (!$rev) {
            $rev = utils::rebuild_css_cache();
        }
        $cssurl = \moodle_url::make_pluginfile_url(
            SYSCONTEXTID,
            'tiny_elements',
            '',
            null,
            '',
            'tiny_elements_styles.css?rev=' . $rev
        )->out();

        return [
            'isstudent' => $isstudent,
            'showpreview' => ($showpreview == '1'),
            'viewelements' => $viewelements,
            'cssurl' => $cssurl,
        ];
    }

    /**
     * Check if the plugin is enabled for the context
     *
     * @param context $context
     * @param array $options
     * @param array $fpoptions
     * @param \editor_tiny\editor|null $editor
     *
     * @return bool
     */
    public static function is_enabled(
        context $context,
        array $options,
        array $fpoptions,
        ?\editor_tiny\editor $editor = null
    ): bool {
        return has_capability('tiny/elements:viewplugin', $context);
    }
}
