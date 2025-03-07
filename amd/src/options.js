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
 * Options helper for Elements plugin.
 *
 * @module      tiny_elements/options
 * @copyright   2022 Marc Català <reskit@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {getPluginOptionName} from 'editor_tiny/options';
import {pluginName} from './common';

const isstudentName = getPluginOptionName(pluginName, 'isstudent');
const showpreviewName = getPluginOptionName(pluginName, 'showpreview');
const viewElementsName = getPluginOptionName(pluginName, 'viewelements');
const cssUrlName = getPluginOptionName(pluginName, 'cssurl');
const canManageName = getPluginOptionName(pluginName, 'canmanage');
const markComponentsName = getPluginOptionName(pluginName, 'markcomponents');

export const register = (editor) => {
    const registerOption = editor.options.register;

    registerOption(isstudentName, {
        processor: 'boolean',
        "default":  false,
    });

    registerOption(showpreviewName, {
        processor: 'boolean',
        "default":  true,
    });

    registerOption(viewElementsName, {
        processor: 'boolean',
        "default":  true,
    });

    registerOption(cssUrlName, {
        processor: 'string',
        "default":  '',
    });

    registerOption(canManageName, {
        processor: 'boolean',
        "default":  false,
    });

    registerOption(markComponentsName, {
        processor: 'boolean',
        "default":  false,
    });
};

/**
 * Get the permissions configuration for the Tiny Elements plugin.
 *
 * @param {TinyMCE} editor
 * @returns {object}
 */
export const isElementsVisible = (editor) => editor.options.get(viewElementsName);

/**
 * Get whether user is a student configuration for the Tiny Elements plugin.
 *
 * @param {TinyMCE} editor
 * @returns {object}
 */
export const isStudent = (editor) => editor.options.get(isstudentName);

/**
 * Get the preview visibility configuration for the Tiny Elements plugin.
 *
 * @param {TinyMCE} editor
 * @returns {object}
 */
export const showPreview = (editor) => editor.options.get(showpreviewName);

/**
 * Get the css url for the Tiny Elements plugin (to be used in the editor).
 * @param {TinyMCE} editor
 * @returns {string}
 */
export const getCssUrl = (editor) => editor.options.get(cssUrlName);

/**
 * Whether the use hat tiny_elements/manage capability.
 * @param {TinyMCE} editor
 * @returns boolean
 */
export const canManage = (editor) => editor.options.get(canManageName);

/**
 * Whether to inject HTML comments to mark components.
 * @param {TinyMCE} editor
 * @returns boolean
 */
export const markComponents = (editor) => editor.options.get(markComponentsName);
