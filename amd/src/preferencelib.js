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
 * Helper for handling user preferences.
 *
 * @module     tiny_elements/preferencelib
 * @copyright  2024 ISB Bayern
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';
import Notification from 'core/notification';

export const Preferences = {
    category: 'elements_category',
    // eslint-disable-next-line camelcase
    category_flavors: 'elements_category_flavors',
    // eslint-disable-next-line camelcase
    component_variants: 'elements_component_variants'
};

/**
 * Load user preferences.
 * @param {string} name
 * @returns {Promise}
 */
export const loadPreferences = async(name) => {
    const request = {
        methodname: 'core_user_get_user_preferences',
        args: {
            name: name
        }
    };

    return Ajax.call([request])[0]
        .then(result => {
            try {
                let preferences = JSON.parse(result.preferences[0].value);
                return preferences;
            } catch (e) {
                Notification.exception(e);
                return {};
            }
    }).catch(Notification.exception);
};

/**
 * Save user preferences.
 * @param {object} rawPreferences
 * @returns {Promise}
 */
export const savePreferences = (rawPreferences) => {
    const request = {
        methodname: 'core_user_update_user_preferences',
        args: {
            preferences: rawPreferences
        }
    };

    return Ajax.call([request])[0].catch(Notification.exception);
};
