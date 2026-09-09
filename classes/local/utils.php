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

use core\output\choicelist;
use core_filters\filter_manager;

/**
 * Utility class for tiny_elements.
 *
 * @package    tiny_elements
 * @copyright  2024 ISB Bayern
 * @author     Philipp Memmel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class utils {
    /**
     * Get all components.
     * @param bool $isstudent
     * @return array all components
     */
    public static function get_all_components(bool $isstudent = false): array {
        global $DB;
        $conditions = [];
        if ($isstudent) {
            $conditions['hideforstudents'] = 0;
        }
        $componentrecords = $DB->get_records('tiny_elements_component', $conditions, 'displayorder');
        $components = [];
        foreach ($componentrecords as $record) {
            $components[] = [
                    'id' => $record->id,
                    'name' => $record->name,
                    'displayname' => self::filter_string($record->displayname),
                    'categoryname' => $record->categoryname,
                    'code' => self::replace_pluginfile_urls($record->code, true),
                    'text' => $record->text,
                    'displayorder' => $record->displayorder,
                    'js' => self::replace_pluginfile_urls($record->js ?? '', true),
            ];
        }
        return $components;
    }

    /**
     * Get all variants.
     * @param string $categoryname
     * @param string $query
     * @return array all variants
     */
    public static function get_all_variants(string $categoryname = '', string $query = ''): array {
        global $DB;
        $where = '';
        $params = [];
        if (!empty($categoryname)) {
            if (!empty((int)$categoryname)) {
                $categoryname = $DB->get_field('tiny_elements_compcat', 'name', ['id' => $categoryname]);
            }
            $where .= 'categoryname = :categoryname';
            $params['categoryname'] = $categoryname;
        }
        if (!empty($query)) {
            if (!empty($where)) {
                $where .= ' AND ';
            }
            $sql = $DB->sql_like('name', ':query', false, false);
            $where .= '(' . $sql;
            $sql = $DB->sql_like('displayname', ':query2', false, false);
            $where .= ' OR ' . $sql . ')';
            $params['query'] = '%' . $DB->sql_like_escape($query) . '%';
            $params['query2'] = $params['query'];
        }
        $variants = $DB->get_records_sql(
            "SELECT * FROM {tiny_elements_variant}" . (!empty($where) ? " WHERE " . $where : ""),
            $params
        );
        foreach ($variants as $variant) {
            $variant->displayname = self::filter_string($variant->displayname);
            $variant->content = self::replace_pluginfile_urls($variant->content, true);
        }
        return $variants;
    }

    /**
     * Get all component variants.
     *
     * @return array all component variants
     */
    public static function get_all_comp_variants(): array {
        global $DB;
        $compvariants = $DB->get_records('tiny_elements_comp_variant', null, '');
        // Sort all variants to the component. key: component name, value: array of variant names.
        $components = [];
        foreach ($compvariants as $compvariant) {
            $components[$compvariant->componentname] = array_merge(
                [$compvariant->variant],
                $components[$compvariant->componentname] ?? []
            );
            $compvariant->displayname = self::filter_string($compvariant->displayname);
        }
        return $components;
    }

    /**
     * Get all component categories.
     * @return array all component categories
     */
    public static function get_all_compcats(): array {
        global $DB;
        $categories = $DB->get_records('tiny_elements_compcat', null, 'displayorder');
        foreach ($categories as $category) {
            $category->displayname = self::filter_string($category->displayname);
        }
        return array_values($categories);
    }

    /**
     * Get all component flavors.
     * @return array all component flavors
     */
    public static function get_all_comp_flavors(): array {
        global $DB;
        $compflavors = $DB->get_records('tiny_elements_comp_flavor', [], '');
        $components = [];
        foreach ($compflavors as $compflavor) {
            $components[$compflavor->componentname] = array_merge(
                [$compflavor->flavorname],
                $components[$compflavor->componentname] ?? []
            );
            $compflavor->displayname = self::filter_string($compflavor->displayname);
        }
        return $components;
    }

    /**
     * Get all flavors.
     * @param bool $isstudent
     * @param string $categoryname
     * @param string $query
     * @return array all flavors
     */
    public static function get_all_flavors(bool $isstudent = false, string $categoryname = '', string $query = ''): array {
        global $DB;
        $where = '';
        $params = [];
        if ($isstudent) {
            $where .= 'hideforstudents = 0';
        }
        if (!empty($categoryname)) {
            if (!empty((int)$categoryname)) {
                $categoryname = $DB->get_field('tiny_elements_compcat', 'name', ['id' => $categoryname]);
            }
            if (!empty($where)) {
                $where .= ' AND ';
            }
            $where .= 'categoryname = :categoryname';
            $params['categoryname'] = $categoryname;
        }
        if (!empty($query)) {
            if (!empty($where)) {
                $where .= ' AND ';
            }
            $sql = $DB->sql_like('name', ':query', false, false);
            $where .= '(' . $sql;
            $sql = $DB->sql_like('displayname', ':query2', false, false);
            $where .= ' OR ' . $sql . ')';

            $params['query'] = '%' . $DB->sql_like_escape($query) . '%';
            $params['query2'] = $params['query'];
        }

        $flavors = $DB->get_records_sql(
            "
            SELECT *
            FROM {tiny_elements_flavor}" .
            (!empty($where) ? " WHERE " . $where : "") .
            " ORDER BY displayorder",
            $params
        );

        $flavorsbyname = [];
        foreach ($flavors as $flavor) {
            $flavorsbyname[$flavor->name] = $flavor;
            $flavorsbyname[$flavor->name]->categories = [];
            $flavorsbyname[$flavor->name]->content = self::replace_pluginfile_urls($flavor->content ?? '', true);
            $flavor->displayname = self::filter_string($flavor->displayname);
        }
        return $flavorsbyname;
    }

    /**
     * Get all data for the elements editor.
     * @param bool $isstudent
     * @return array all data for the elements editor
     */
    public static function get_elements_data(bool $isstudent = false): array {
        $components = self::get_all_components($isstudent);
        $compcats = self::get_all_compcats();
        $flavors = self::get_all_flavors($isstudent);
        $variants = self::get_all_variants();
        $componentflavors = self::get_all_comp_flavors();
        $componentvariants = self::get_all_comp_variants();

        foreach ($components as $key => $component) {
            // Add flavors to components structure.
            $components[$key]['flavors'] = $componentflavors[$component['name']] ?? [];
            // Add categories to flavors.
            foreach ($components[$key]['flavors'] as $flavor) {
                if (!isset($flavors[$flavor])) {
                    continue;
                }
                $flavors[$flavor]->categories[] = $component['categoryname'];
            }

            // Add variants to components structure.
            $components[$key]['variants'] = $componentvariants[$component['name']] ?? [];
        }

        foreach ($flavors as $flavor) {
            $flavor->categories = join(',', array_unique($flavor->categories));
        }

        return [
                'components' => $components,
                'categories' => $compcats,
                'flavors' => $flavors,
                'variants' => $variants,
        ];
    }

    /**
     * Rebuild the css cache.
     *
     * @return int the new revision for the cache
     */
    public static function rebuild_css_cache(): int {
        global $DB;
        $cache = \cache::make('tiny_elements', constants::CACHE_AREA);
        $iconcssentries = [];
        $componentcssentries = [];
        $variantscssentries = [];
        $components = [];
        $variants = [];
        try {
            $components = $DB->get_records('tiny_elements_component', null, '', 'id, name, css, iconurl');
            $categorycssentries = $DB->get_fieldset('tiny_elements_compcat', 'css');
            $flavors = $DB->get_records('tiny_elements_flavor', null, 'id, name');
            $flavorcssentries = $DB->get_fieldset('tiny_elements_flavor', 'css');
            $variants = $DB->get_records('tiny_elements_variant', null, '', 'name, iconurl, css');
        } catch (\dml_exception $e) {
            // This is done to prevent the plugin from crashing the whole site if the database tables
            // are not yet installed for some reason.
            return 0;
        }
        foreach ($variants as $variant) {
            $variantscssentries[] = $variant->css;
            if (empty($variant->iconurl)) {
                continue;
            }
            $iconcssentries[] = self::variant_icon_css($variant->name, self::replace_pluginfile_urls($variant->iconurl, true));
        }
        $componentflavors = $DB->get_records('tiny_elements_comp_flavor');
        foreach ($componentflavors as $componentflavor) {
            if (empty($componentflavor->iconurl)) {
                continue;
            }
            $iconcssentries[] = self::button_icon_css(
                $componentflavor->componentname,
                self::replace_pluginfile_urls($componentflavor->iconurl, true),
                $componentflavor->flavorname
            );
        }
        foreach ($components as $component) {
            $componentcssentries[] = $component->css;
            if (empty($component->iconurl)) {
                continue;
            }
            $iconcssentries[] = self::button_icon_css($component->name, self::replace_pluginfile_urls($component->iconurl, true));
        }
        $cssentries = array_merge(
            $categorycssentries,
            $componentcssentries,
            $flavorcssentries,
            $variantscssentries,
            $iconcssentries,
        );
        $css = array_reduce(
            $cssentries,
            fn($current, $add) => $current . PHP_EOL . $add,
            '/* This file contains the stylesheet for the tiny_elements plugin.*/'
        );
        $css = self::replace_pluginfile_urls($css, true);
        $css = \core_minify::css($css);
        if (empty($css)) {
            $css = ' ';
        }
        $clock = \core\di::get(\core\clock::class);
        $rev = $clock->time();
        $cache->set(constants::CSS_CACHE_KEY, $css);
        $cache->set(constants::CSS_CACHE_REV, $rev);
        return $rev;
    }

    /**
     * Rebuild the js cache.
     * @return int the new revision for the cache
     */
    public static function rebuild_js_cache(): int {
        global $DB;
        $cache = \cache::make('tiny_elements', constants::CACHE_AREA);
        $jsentries = [];
        try {
            $jsentries = $DB->get_records_menu('tiny_elements_component', null, '', 'id, js');
        } catch (\dml_exception $e) {
            // This is done to prevent the plugin from crashing the whole site if the database tables
            // are not yet installed for some reason.
            return 0;
        }
        $js = array_reduce(
            $jsentries,
            fn($current, $add) => $current . PHP_EOL . $add,
            '/* This file contains the javascript for the tiny_elements plugin.*/'
        );
        $js = self::replace_pluginfile_urls($js, true);
        $js = \core_minify::js($js);
        if (empty($js)) {
            $js = ' ';
        }
        $cache->set(constants::JS_CACHE_KEY, $js);
        $clock = \core\di::get(\core\clock::class);
        $rev = $clock->time();
        $cache->set(constants::JS_CACHE_REV, $rev);
        return $rev;
    }

    /**
     * Purge the tiny_elements css cache.
     */
    public static function purge_css_cache(): void {
        $cache = \cache::make('tiny_elements', constants::CACHE_AREA);
        $cache->delete(constants::CSS_CACHE_KEY);
        $cache->delete(constants::CSS_CACHE_REV);
    }

    /**
     * Purge the tiny_elements js cache.
     */
    public static function purge_js_cache(): void {
        $cache = \cache::make('tiny_elements', constants::CACHE_AREA);
        $cache->delete(constants::JS_CACHE_KEY);
        $cache->delete(constants::JS_CACHE_REV);
    }

    /**
     * Purge and rebuild all caches.
     */
    public static function purge_and_rebuild_caches(): void {
        self::purge_css_cache();
        self::purge_js_cache();
        self::rebuild_css_cache();
        self::rebuild_js_cache();
    }

    /**
     * Helper function to retrieve the currently cached tiny_elements css.
     *
     * @return string|false the css code as string, false if no cache entry found
     */
    public static function get_css_from_cache(): string|false {
        $cache = \cache::make('tiny_elements', constants::CACHE_AREA);
        return $cache->get(constants::CSS_CACHE_KEY);
    }

    /**
     * Helper function to retrieve the currently cached tiny_elements js.
     *
     * @return string|false the js code as string, false if no cache entry found
     */
    public static function get_js_from_cache(): string|false {
        $cache = \cache::make('tiny_elements', constants::CACHE_AREA);
        return $cache->get(constants::JS_CACHE_KEY);
    }

    /**
     * Replace @@PLUGINFILE@@ with the correct URL and vice versa.
     *
     * @param string $content the content to replace the URL in
     * @param bool $realurl if true, get the real URL, otherwise replace it
     */
    public static function replace_pluginfile_urls(string $content, bool $realurl = false): string {
        global $CFG;
        if (!$realurl) {
            $content = str_replace($CFG->wwwroot . '/pluginfile.php', '@@PLUGINFILE@@', $content);
        } else {
            $content = str_replace('@@PLUGINFILE@@', $CFG->wwwroot . '/pluginfile.php', $content);
        }
        return $content;
    }

    /**
     * Get the css for a button with an icon.
     *
     * @param string $variant
     * @param string $iconurl
     * @return string
     */
    public static function variant_icon_css(string $variant, string $iconurl): string {
        return <<<CSS
        .elements-button-variant[data-variant="{$variant}"]::before {
            background-image: url('{$iconurl}');
        }
        CSS;
    }

    /**
     * Get the css for an icon.
     *
     * @param string $buttonclass
     * @param string $iconurl
     * @param string $flavor
     * @return string
     */
    public static function button_icon_css(string $buttonclass, string $iconurl, string $flavor = ''): string {
        $flavor = empty($flavor) ? '' : '[data-flavor="' . $flavor . '"]';
        return <<<CSS
        .elements-{$buttonclass}-icon{$flavor} .elements-button-text::before {
            background-image: url('{$iconurl}');
            background-size: contain;
            background-repeat: no-repeat;
            background-position: center;
            content: '';
        }
        CSS;
    }

    /**
     * Update the pluginfile tags in the given subject.
     *
     * @param array $categorymap
     * @param string $subject
     * @return string
     */
    public static function update_pluginfile_tags_bulk(array $categorymap, string $subject): string {
        foreach ($categorymap as $oldid => $newid) {
            $subject = self::update_pluginfile_tags($oldid, $newid, $subject, 'bulk');
        }
        $subject = self::remove_mark($subject, 'bulk');
        return $subject;
    }

    /**
     * Update the pluginfile tags in the given subject.
     *
     * @param int $oldid
     * @param int $newid
     * @param string $subject
     * @param string $mark (optional) A string to mark the path - to be removed later.
     * @return string
     */
    public static function update_pluginfile_tags(int $oldid, int $newid, string $subject, string $mark = ''): string {
        $oldstring = '@@PLUGINFILE@@/1/tiny_elements/images/' . $oldid . '/';
        $newstring = '@@PLUGINFILE@@/1/tiny_elements/' . $mark . 'images/' . $newid . '/';
        return str_replace($oldstring, $newstring, $subject);
    }

    /**
     * Remove the mark from the given subject.
     *
     * @param string $subject
     * @param string $mark
     * @return string
     */
    public static function remove_mark(string $subject, string $mark): string {
        $newstring = '@@PLUGINFILE@@/1/tiny_elements/images/';
        $oldstring = '@@PLUGINFILE@@/1/tiny_elements/' . $mark . 'images/';
        return str_replace($oldstring, $newstring, $subject);
    }

    /**
     * Return a list of all images in the given context.
     *
     * @param int $contextid
     * @param int $categoryid
     * @param string $categoryname
     * @return array
     */
    public static function get_all_images(int $contextid = SYSCONTEXTID, int $categoryid = 0, string $categoryname = ''): array {
        global $DB;
        if (empty($categoryid) && !empty($categoryname)) {
            $categoryid = $DB->get_field('tiny_elements_compcat', 'id', ['name' => $categoryname]);
        }
        $fs = get_file_storage();
        $files = $fs->get_area_files($contextid, 'tiny_elements', 'images', (empty($categoryid) ? false : $categoryid));
        $processedfiles = [];
        foreach ($files as $file) {
            if ($file->is_directory()) {
                continue;
            }

            $processedfiles[] = [
                'url' => \moodle_url::make_pluginfile_url(
                    $file->get_contextid(),
                    $file->get_component(),
                    $file->get_filearea(),
                    $file->get_itemid(),
                    $file->get_filepath(),
                    $file->get_filename()
                )->out(),
                'name' => $file->get_filepath() . $file->get_filename(),
            ];
        }
        return $processedfiles;
    }

    /**
     * Let a string pass through the filter(s).
     *
     * @param string|null $string
     * @return string the filtered string
     */
    public static function filter_string(?string $string = ''): string {
        if (empty($string)) {
            return '';
        }

        $allowedfilters = explode(',', str_replace(' ', '', get_config('tiny_elements', 'allowedfilters')));

        $filtermanager = filter_manager::instance();
        $skipfilters = array_diff(array_keys(filter_get_active_in_context(\context_system::instance())), $allowedfilters);

        return(
        $filtermanager->filter_text(
            $string,
            \context_system::instance(),
            ['escape' => false],
            $skipfilters
        )
        );
    }
}
