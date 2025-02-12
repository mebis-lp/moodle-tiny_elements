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
 * Variants helper for Elements plugin.
 *
 * @module      tiny_elements/variantslib
 * @copyright   2023 Marc Català <reskit@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {
    findById,
    findByName
} from './helper';

let variantPreferences = {};
let DATA = {};

export const setData = (data) => {
    DATA = data;
};

export const getVariantPreferences = () => {
    return variantPreferences;
};

/**
 * Load user preferences.
 * @param {array} preferences
 * @returns {Promise}
 */
export const loadVariantPreferences = (preferences) => {
    if (preferences !== undefined && preferences !== null) {
        variantPreferences = preferences;
    } else {
        variantPreferences = {};
    }
};

/**
 * Get variant preferences for a single component-flavor combination.
 * @param {*} component
 * @param {*} flavor
 * @returns
 */
export const getVariantPreference = (component, flavor = '') => {
    let componentObj = findByName(DATA.getComponents(), component);
    let flavorObj = findByName(DATA.getFlavors(), flavor);

    if (componentObj === undefined) {
        return [];
    }

    if (flavor == '' && !variantPreferences[componentObj.id]) {
        return [];
    }

    if (flavor != '' && flavorObj === undefined) {
        return [];
    }

    if (flavor != '' && !variantPreferences[componentObj.id + '-' + flavorObj.id]) {
        return [];
    }

    if (flavor == '') {
        return variantPreferences[componentObj.id];
    } else {
        return variantPreferences[componentObj.id + '-' + flavorObj.id];
    }
};

/**
 * Returns whether a variant exists for a component.
 *
 * @param  {string} component Component name
 * @param  {string} variant   Variant name
 * @param {string} flavor Flavor name
 * @returns {bool}
 */
export const variantExists = (component, variant, flavor = '') => {
    let variantObj = findByName(DATA.getVariants(), variant);
    return getVariantPreference(component, flavor).indexOf(variantObj.id) !== -1;
};

/**
 * Returns each variant for a component as a CSS class.
 *
 * @param  {string} component Component name
 * @param {string} flavor Flavor name
 * @returns {Array}
 */
export const getVariantsClass = (component, flavor = '') => {
    let variants = [];
    getVariantPreference(component, flavor).forEach(variant => {
        let variantObj = findById(DATA.getVariants(), variant);
        if (variantObj !== undefined) {
            variants.push((variantObj.c4lcompatibility ? 'c4l' : 'elements') + '-' + variantObj.name + '-variant');
        }
    });

    return variants;
};

/**
 * Return all HTML variants for a component.
 *
 * @param  {string} component Component name
 * @returns {string}
 */
export const getVariantsHtml = (component) => {
    let variantsHtml = '';
    let variantObj = {};

    let componentObj = findByName(DATA.getComponents(), component);
    componentObj.variants.forEach(variant => {
        variantObj = findByName(DATA.getVariants(), variant);
        if (variantObj != undefined) {
            variantsHtml += variantObj.content;
        }
    });

    return variantsHtml;
};

/**
 * Return the HTML variant.
 *
 * @param  {string} variant Variant name
 * @returns {string}
 */
export const getVariantHtml = (variant) => {
    let variantHtml = [];
    let variantObj = {};

    variantObj = findByName(DATA.getVariants(), variant);
    if (variantObj != undefined) {
        variantHtml = variantObj.html;
    }
    return variantHtml;
};

/**
 * Add a variant to variantPreferences
 *
 * @param  {string} component Component name
 * @param  {string} variant   Variant name
 * @param {string} flavor Flavor name
 */
export const addVariant = (component, variant, flavor = '') => {
    let componentObj = findByName(DATA.getComponents(), component);
    let variantObj = findByName(DATA.getVariants(), variant);
    let flavorObj = findByName(DATA.getFlavors(), flavor);

    if (flavor == '') {
        if (!variantPreferences[componentObj.id]) {
            variantPreferences[componentObj.id] = [];
        }
        if (!variantExists(component, variant)) {
            variantPreferences[componentObj.id].push(variantObj.id);
        }
    } else {
        if (!variantPreferences[componentObj.id + '-' + flavorObj.id]) {
            variantPreferences[componentObj.id + '-' + flavorObj.id] = [];
        }
        if (!variantExists(component, variant, flavor)) {
            variantPreferences[componentObj.id + '-' + flavorObj.id].push(variantObj.id);
        }
    }
};

/**
 * Remove a variant from variantPreferences
 *
 * @param  {string} component Component name
 * @param  {string} variant   Variant name
 * @param {string} flavor Flavor name
 */
export const removeVariant = (component, variant, flavor = '') => {
    let componentObj = findByName(DATA.getComponents(), component);
    let variantObj = findByName(DATA.getVariants(), variant);
    let flavorObj = findByName(DATA.getFlavors(), flavor);

    if (flavor != '') {
        let index = variantPreferences[componentObj.id + '-' + flavorObj.id].indexOf(variantObj.id);
        if (index !== -1) {
            delete variantPreferences[componentObj.id + '-' + flavorObj.id][index];
        }
        if (variantPreferences[componentObj.id + '-' + flavorObj.id].length == 0) {
            delete variantPreferences[componentObj.id + '-' + flavorObj.id];
        }
    } else {
        let index = variantPreferences[componentObj.id].indexOf(variantObj.id);
        if (index !== -1) {
            delete variantPreferences[componentObj.id][index];
        }
        if (variantPreferences[componentObj.id].length == 0) {
            delete variantPreferences[componentObj.id];
        }
    }
};
