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
 * Elements Modal for Tiny.
 *
 * @module      tiny_elements/modal
 * @copyright   2022 Marc Català <reskit@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Modal from 'core/modal';
import ModalRegistry from 'core/modal_registry';

const ElementsModal = class extends Modal {
    static TYPE = 'tiny_elements/modal';
    static TEMPLATE = 'tiny_elements/modal';

    configure(modalConfig) {
        // Remove modal from DOM on close.
        modalConfig.removeOnClose = true;
        super.configure(modalConfig);
    }

    registerEventListeners() {
        // Call the parent registration.
        super.registerEventListeners();
    }
};

ModalRegistry.register(ElementsModal.TYPE, ElementsModal, ElementsModal.TEMPLATE);

export default ElementsModal;
