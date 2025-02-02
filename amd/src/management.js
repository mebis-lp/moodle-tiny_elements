import Modal from 'core/modal';
import ModalForm from 'core_form/modalform';
import Notification from 'core/notification';
import {get_string as getString} from 'core/str';
import {exception as displayException, deleteCancelPromise} from 'core/notification';
import {call as fetchMany} from 'core/ajax';
import {render as renderTemplate} from 'core/templates';
class PreviewModal extends Modal {
    static TYPE = "tiny_elements/management_preview";
    static TEMPLATE = "tiny_elements/management_preview";
    configure(modalConfig) {
        modalConfig.removeOnClose = true;
        modalConfig.large = true;
        super.configure(modalConfig);
    }
}

export const init = async(params) => {

    // Add listener to import xml files.
    let importxml = document.getElementById('elements_import');
    importxml.addEventListener('click', async(e) => {
        importModal(e);
    });

    // Add listener for adding a new item.
    let additem = document.getElementsByClassName('add');
    additem.forEach(element => {
        element.addEventListener('click', async(e) => {
            showModal(e, element.dataset.id, element.dataset.table);
        });
    });

    // Add listener to edit items.
    let edititems = document.getElementsByClassName('edit');
    edititems.forEach(element => {
        element.addEventListener('click', async(e) => {
            showModal(e, element.dataset.id, element.dataset.table);
        });
    });

    // Add listener to delete items.
    let deleteitems = document.getElementsByClassName('delete');
    deleteitems.forEach(element => {
        element.addEventListener('click', async(e) => {
            deleteModal(e, element.dataset.id, element.dataset.title, element.dataset.table);
        });
    });

    // Add listener to preview items.
    let previewitems = document.getElementsByClassName('preview');
    previewitems.forEach(element => {
        element.addEventListener('click', async(e) => {
            previewModal(e);
        });
    });

    // Add listener to select compcat to show corresponding items.
    let compcats = document.getElementsByClassName('compcat');
    compcats.forEach(element => {
        element.addEventListener('click', async(e) => {
            showItems(e, element.dataset.compcat);
        });
    });

    // Add listener to manage component flavor relation.
    let compflavor = document.getElementById('elements_compflavor_button');
    compflavor.addEventListener('click', async(e) => {
        compflavorModal(e);
    });

    // Add listener to duplicate items.
    let duplicateitems = document.getElementsByClassName('duplicate');
    duplicateitems.forEach(element => {
        element.addEventListener('click', async() => {
            duplicateItem(element.dataset.id, element.dataset.table);
            reload();
        });
    });

    // Add image and text to item setting click area.
    let enlargeItems = document.querySelectorAll(
        '.flavor .card-body > .clickingextended, .component .card-body > .clickingextended, .variant .card-body > .clickingextended'
    );
    enlargeItems.forEach(element => {
        element.addEventListener('click', async(e) => {
            let item = e.target.closest('.item');
            item.querySelector('a.edit').click();
        });
    });

    // After submitting a new item, reset active compcat.
    if (params.compcatactive) {
        let compcat = document.querySelector('.compcat[data-compcat="' + params.compcatactive + '"]');
        if (compcat) {
            showItems(false, params.compcatactive);
            compcat.classList.add('active');
        }
    }
};

/**
 * Show dynamic form to add/edit a source.
 * @param {*} e
 * @param {*} id
 * @param {*} table
 */
function showModal(e, id, table) {
    e.preventDefault();
    let title;
    if (id == 0) {
        title = getString('additem', 'tiny_elements');
    } else {
        title = getString('edititem', 'tiny_elements');
    }

    const modalForm = new ModalForm({
        // Set formclass, depending on component.
        formClass: "tiny_elements\\form\\management_" + table + "_form",
        args: {
            id: id,
        },
        modalConfig: {title: title},
    });
    // Conditional reload page after submit.
    modalForm.addEventListener(modalForm.events.FORM_SUBMITTED, () => reloadIfNew(modalForm.getFormNode()));

    modalForm.show();
}

/**
 * Show modal to preview css version.
 * @param {*} e
 */
async function previewModal(e) {
    e.preventDefault();
    let preview = e.target.closest(".preview");
    const modal = await PreviewModal.create({
        templateContext: {
            component: preview.dataset.component,
            flavors: preview.dataset.flavors.trim().split(" "),
            config: M.cfg,
        },
    });
    modal.show();
}

/**
 * Show dynamic form to import xml backups.
 * @param {*} e
 */
function importModal(e) {
    e.preventDefault();
    let title = getString('import', 'tiny_elements');

    const modalForm = new ModalForm({
        // Load import form.
        formClass: "tiny_elements\\form\\management_import_form",
        args: {},
        modalConfig: {title: title},
    });
    modalForm.addEventListener(modalForm.events.FORM_SUBMITTED, importModalSubmitted);

    modalForm.show();
}

/**
 * Process import form submit.
 * @param {*} e
 */
async function importModalSubmitted(e) {
    // Reload page after submit.
    if (e.detail.update) {
        location.reload();
    } else {
        e.stopPropagation();
        renderTemplate('tiny_elements/management_import_form_result', e.detail).then(async (html) => {
            await Notification.alert(
                getString('import_simulation', 'tiny_elements'),
                html,
                getString('close', 'tiny_elements')
            );
            return true;
        }).catch((error) => {
            displayException(error);
        });
    }
}

/**
 * Load modal to edit icon urls.
 * @param {*} e
 */
function compflavorModal(e) {
    e.preventDefault();
    let title = getString('manage', 'tiny_elements');

    const modalForm = new ModalForm({
        // Load import form.
        formClass: "tiny_elements\\form\\management_comp_flavor_form",
        args: {},
        modalConfig: {title: title},
    });

    modalForm.show();
}

/**
 * Show dynamic form to delete a source.
 * @param {*} e
 * @param {*} id
 * @param {*} title
 * @param {*} table
 */
function deleteModal(e, id, title, table) {
    e.preventDefault();

    deleteCancelPromise(
        getString('delete', 'tiny_elements', title),
        getString('deletewarning', 'tiny_elements'),
    ).then(async() => {
        if (id !== 0) {
            try {
                const deleted = await deleteItem(id, table);
                if (deleted) {
                    const link = document.querySelector('[data-table="' + table + '"][data-id="' + id + '"]');
                    if (link) {
                        const card = link.closest(".item");
                        card.remove();
                    }
                }
            } catch (error) {
                displayException(error);
            }
        }
        return;
    }).catch(() => {
        return;
    });
}

/**
 * Delete elements items.
 * @param {*} id
 * @param {*} table
 * @returns {mixed}
 */
export const deleteItem = (
    id,
    table,
) => fetchMany(
    [{
        methodname: 'tiny_elements_delete_item',
        args: {
            id,
            table,
        }
    }])[0];

/**
 * Show items after clicking a compcat.
 * @param {*} e
 * @param {*} compcat
 */
function showItems(e, compcat) {
    // But first hide all items.
    let itemsHide = document.querySelectorAll('.flavor, .component, .variant');
    itemsHide.forEach(element => {
        element.classList.add('hidden');
    });

    // Show component and variants with compcat name and read the flavors.
    let itemsShow = document.getElementsByClassName(compcat);
    let usedFlavors = [];
    itemsShow.forEach(element => {
        element.classList.remove('hidden');
        // Get all flavors to show if on compcat element.
        if (typeof element.dataset.flavors !== 'undefined') {
            let flavors = element.dataset.flavors.split(' ');
            for (let value of flavors) {
                if (!usedFlavors.includes(value) && value.length != 0) {
                    usedFlavors.push(value);
                }
            }
        }
    });

    // Show the flavors.
    let flavorstring = usedFlavors.map(item => `.${item}`).join(', ');
    if (flavorstring.length) {
        let flavorsShow = document.querySelectorAll(flavorstring);
        flavorsShow.forEach(element => {
            element.classList.remove('hidden');
        });
    }

    // Show add buttons.
    let addsShow = document.getElementsByClassName('addcontainer');
    addsShow.forEach(element => {
        element.classList.remove('hidden');
    });

    // Unmark all and mark clicked compcat.
    if (e) {
        let items = document.getElementsByClassName('compcat');
        items.forEach(element => {
            element.classList.remove('active');
        });
        let item = e.target.closest('.compcat');
        item.classList.add('active');
    }

    // Special case, unassigned items, show all items without connection to compcat.
    if (compcat == 'found-items') {
        let found = document.querySelector('.compcat[data-compcat="found-items"]');
        if (found.dataset.loneflavors.length) {
            let flavorsShow = document.querySelectorAll(found.dataset.loneflavors);
            flavorsShow.forEach(element => {
                element.classList.remove('hidden');
            });
        }
        if (found.dataset.lonevariants.length) {
            let variantsShow = document.querySelectorAll(found.dataset.lonevariants);
            variantsShow.forEach(element => {
                element.classList.remove('hidden');
            });
        }
        if (found.dataset.lonecomponents.length) {
            let componentsShow = document.querySelectorAll(found.dataset.lonecomponents);
            componentsShow.forEach(element => {
                element.classList.remove('hidden');
            });
        }
    }
}

/**
 * Reload for new items.
 * @param {*} form
 */
function reloadIfNew(form) {
    // Newly created element without id?
    if (!form.elements.id.value) {
        reload();
    }
}

/**
 * Reload page with active compcat.
 */
function reload() {
    // Reload page with active compcat.
    const compcat = document.querySelector('.compcat.active');
    const currentUrl = new URL(window.location.href);
    currentUrl.searchParams.set('compcat', compcat.dataset.compcat);
    window.location.href = currentUrl.toString();
}

/**
 * Duplicate elements items.
 * @param {*} id
 * @param {*} table
 * @returns {mixed}
 */
export const duplicateItem = (
    id,
    table,
) => fetchMany(
    [{
        methodname: 'tiny_elements_duplicate_item',
        args: {
            id,
            table,
        }
    }])[0];

