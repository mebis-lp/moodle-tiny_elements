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
 * Helper for autocomplete form element to select only variants that belong to the
 * selected category.
 *
 * @module     tiny_elements/category_form_helper
 * @copyright  2025 ISB Bayern
 * @authors    Stefan Hanauska <stefan.hanauska@csg-in.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/ajax', 'core/notification'], function($, Ajax, Notification) {

    return {
        /**
         * Process the results for auto complete elements.
         *
         * @param {String} selector The selector of the auto complete element.
         * @param {Array} results An array or results.
         * @return {Array} New array of results.
         */
        processResults: function(selector, results) {
            var options = [];
            results.forEach((data) => {
                options.push({
                    value: data.name,
                    label: data.displayname
                });
            });
            return options;
        },

        /**
         * Source of data for Ajax element.
         *
         * @param {String} selector The selector of the auto complete element.
         * @param {String} query The query string.
         * @param {Function} callback A callback function receiving an array of results.
         */
        /* eslint-disable promise/no-callback-in-promise */
        transport: function(selector, query, callback) {
            var el = document.querySelector(selector);
            if (!el) {
                return;
            }
            const contextid = el.dataset.contextid ?? 1;
            const categoryname = el.closest('form').querySelector('select[name="categoryname"]').value;

            let methodname = 'tiny_elements_get_variants';
            if (el.name == 'flavors[]') {
                methodname = 'tiny_elements_get_flavors';
            }

            Ajax.call([{
                methodname: methodname,
                args: {
                    contextid: contextid,
                    categoryname: categoryname,
                    query: query,
                }
            }])[0].then(callback).catch(Notification.exception);
        }
    };

});
