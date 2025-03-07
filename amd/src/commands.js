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
 * Tiny Elements commands.
 *
 * @module      tiny_elements/commands
 * @copyright   2022 Marc Català <reskit@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {getButtonImage} from 'editor_tiny/utils';
import {get_string as getString} from 'core/str';
import {handleAction} from './ui';
import {
    component,
    elementsButtonName,
    elementsMenuItemName,
    icon,
} from './common';
import {isElementsVisible} from './options';

export const getSetup = async() => {
    const [
        elementsButtonNameTitle,
        elementsMenuItemNameTitle,
        buttonImage,
    ] = await Promise.all([
        getString('button_elements', component),
        getString('menuitem_elements', component),
        getButtonImage('icon', component),
    ]);

    return (editor) => {
        if (isElementsVisible(editor)) {
            // Register the Elements Icon.
            editor.ui.registry.addIcon(icon, buttonImage.html);

            // Register the Elements Toolbar Button.
            editor.ui.registry.addButton(elementsButtonName, {
                icon,
                tooltip: elementsButtonNameTitle,
                onAction: () => handleAction(editor),
            });

            // Add the Elements Menu Item.
            // This allows it to be added to a standard menu, or a context menu.
            editor.ui.registry.addMenuItem(elementsMenuItemName, {
                icon,
                text: elementsMenuItemNameTitle,
                onAction: () => handleAction(editor),
            });
        }
    };
};
