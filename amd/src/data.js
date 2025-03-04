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
 * Container for tiny_elements data (categories, components, flavors, variants).
 *
 * @module     tiny_elements/data
 * @copyright  2025 ISB Bayern
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {get_strings as getStrings} from 'core/str';
import {component as pluginname} from './common';
import {
    variantExists,
    setData as setVariantsData
} from './variantslib';
import {
    findById,
    findByName
} from './helper';
import {call as fetchMany} from 'core/ajax';

export default class Data {
    categories = [];
    components = [];
    flavors = [];
    variants = [];
    langStrings = {};
    userStudent = false;
    canManage = false;
    contextid = 1;

    constructor(contextid, userStudent, previewElements, canManage) {
        this.contextid = contextid;
        this.userStudent = userStudent;
        this.previewElements = previewElements;
        this.canManage = canManage;
        setVariantsData(this);
    }

    async loadData() {
        await this.loadElementsData();
        this.langStrings = await this.getAllStrings();
    }

    getComponents() {
        return this.components;
    }

    getFlavors() {
        return this.flavors;
    }

    getVariants() {
        return this.variants;
    }

    getComponentById(id) {
        return findById(this.components, id);
    }

    getCategoryFlavors(categoryname) {
        const categoryFlavors = [];
        this.flavors.forEach(flavor => {
            if (flavor.categoryname == categoryname) {
                categoryFlavors.push({
                    id: flavor.id,
                    name: flavor.name,
                    displayname: flavor.displayname,
                    displayorder: flavor.displayorder,
                });
            }
        });
        return categoryFlavors;
    }

    /**
     * Get the Elements categories for the dialogue.
     *
     * @returns {object} data
     */
    getCategories() {
        const cats = [];
        // Iterate over contexts.
        this.categories.forEach((category) => {
            let categoryFlavors = this.getCategoryFlavors(category.name);
            categoryFlavors.sort((a, b) => a.displayorder - b.displayorder);
            let hasFlavors = Array.isArray(categoryFlavors) && categoryFlavors.length;
            cats.push({
                categoryid: category.id,
                name: category.displayname,
                categoryname: category.name,
                type: category.id,
                displayorder: category.displayorder,
                flavors: categoryFlavors,
                hasFlavors: hasFlavors,
                active: '',
            });
        });
        // Sort by displayorder and set first to active.
        cats.sort((a, b) => a.displayorder - b.displayorder);
        if (cats.length > 0) {
            cats[0].active = 'active';
            if (cats[0].flavors.length > 0) {
                cats[0].flavors[0].factive = 'active';
            }
        }

        return cats;
    }

    getComponentVariants(component) {
        const componentVariants = [];
        component.variants.forEach(variant => {
            let variantitem = findByName(this.variants, variant);
            if (variantitem !== undefined) {
                let state = variantExists(component.name, variantitem.name) ? 'on' : 'off';
                componentVariants.push({
                    id: variantitem.id,
                    name: variantitem.name,
                    displayname: variantitem.displayname,
                    state: state,
                    imageClass: variantitem.name + '-variant-' + state,
                    variantclass: (variantitem.c4lcompatibility ? 'c4l' : 'elements') + '-' + variantitem.name + '-variant',
                    title: this.langStrings.get(variantitem.name),
                    content: variantitem.content,
                });
            }
        });
        return componentVariants;
    }

    getCategoryById(id) {
        return findById(this.categories, id);
    }

    getLangString(id) {
        return this.langStrings.get(id);
    }

    /**
     * Get the Elements buttons for the dialogue.
     *
     * @param {Editor} editor
     * @returns {object} buttons
     */
    getButtons(editor) {
        const buttons = [];
        // Not used at the moment.
        // eslint-disable-next-line no-unused-vars
        const sel = editor.selection.getContent();
        Object.values(this.components).forEach(component => {
            buttons.push({
                id: component.id,
                name: component.displayname,
                type: component.categoryname,
                imageClass: 'elements-' + component.name + '-icon',
                htmlcode: component.code,
                variants: this.getComponentVariants(component),
                flavorlist: component.flavors.join(','),
                category: component.categoryname,
                displayorder: component.displayorder,
            });
        });
        buttons.sort((a, b) => a.displayorder - b.displayorder);

        return buttons;
    }

    /**
     * Get the template context for the dialogue.
     *
     * @param {Editor} editor
     * @returns {object} data
     */
    getTemplateContext(editor) {
        return Object.assign({}, {
            elementid: editor.id,
            buttons: this.getButtons(editor),
            categories: this.getCategories(),
            preview: this.previewElements,
            canmanage: this.canManage,
        });
    }

    getPreviewElements() {
        return this.previewElements;
    }

    /**
     * Get language strings.
     *
     * @return {object} Language strings
     */
    async getAllStrings() {
        const keys = [];
        const compRegex = /{{#([^}]*)}}/g;

        this.components.forEach(element => {
            // Get lang strings from components.
            [...element.code.matchAll(compRegex)].forEach(strLang => {
                if (keys.indexOf(strLang[1]) === -1) {
                    keys.push(strLang[1]);
                }
            });

            // Get lang strings from text placeholders.
            [...element.text.matchAll(compRegex)].forEach(strLang => {
                if (keys.indexOf(strLang[1]) === -1) {
                    keys.push(strLang[1]);
                }
            });
        });

        const stringValues = await getStrings(keys.map((key) => ({key, pluginname})));
        return new Map(keys.map((key, index) => ([key, stringValues[index]])));
    }

    async loadElementsData() {
        const data = await fetchMany([{
            methodname: 'tiny_elements_get_elements_data',
            args: {
                isstudent: this.userStudent,
                contextid: this.contextid
            },
        }])[0];

        // TODO error handling.
        const indexedComponents = [];
        data.components.forEach(component => {
            indexedComponents[component.id] = component;
        });

        const indexedVariants = [];
        data.variants.forEach(variant => {
            indexedVariants[variant.id] = variant;
        });

        const indexedCategories = [];
        data.categories.forEach(category => {
            indexedCategories[category.id] = category;
        });

        this.components = indexedComponents;
        this.variants = indexedVariants;
        this.categories = indexedCategories;
        this.flavors = data.flavors;
    }
}
