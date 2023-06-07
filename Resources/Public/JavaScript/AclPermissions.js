import $ from 'jquery';
import Permissions from '@typo3/beuser/permissions.js';
import Notification from "@typo3/backend/notification.js"

const ajaxUrl = TYPO3.settings.ajaxUrls['user_access_permissions'];

const AclPermissions = {
    options: {
        containerSelector: '#PermissionControllerEdit'
    }
};

const newACLs = [];
const currentACLs = [];
let editAclRowTpl;

AclPermissions.getEditAclRowTpl = function () {
    if (!editAclRowTpl) {
        editAclRowTpl = $('#tx_beacl-edit-acl-row-template').html();
    }
    return editAclRowTpl;
};

/**
 * generates new hidden field
 *
 * @param name of field
 * @param value of field
 */
AclPermissions.createNewHiddenField = function (name, value) {
    const hiddenFields = document.getElementById('insertHiddenFields');
    const hiddenStore = document.createElement('input');
    hiddenStore.setAttribute('type', 'hidden');
    hiddenStore.setAttribute('value', value);
    hiddenStore.setAttribute('name', name);
    hiddenFields.appendChild(hiddenStore);
};

/**
 * create new ACL ID
 */
AclPermissions.getNewId = function () {
    return 'NEW' + Math.round(Math.random() * 10000000);
};

/**
 * add ACL
 */
AclPermissions.addACL = function () {
    const $container = $(AclPermissions.options.containerSelector);
    const pageID = $container.data('pageid');
    const ACLid = AclPermissions.getNewId();
    // save ACL ID in the new ACLs array
    newACLs.push(ACLid);
    // Create table row
    const tableRow = AclPermissions.getEditAclRowTpl().replace(/###uid###/g, ACLid);
    // append line to table
    $('#typo3-permissionMatrix tbody').append(tableRow);
};

AclPermissions.removeACL = function (id) {
    const $tableRow = $('#typo3-permissionMatrix tbody').find('tr[data-acluid="' + id + '"]');
    if ($tableRow.length) $tableRow.remove();
};

/**
 * Group-related: Set the new group by executing an ajax call
 *
 * @param {Object} $element
 */
AclPermissions.deleteACL = function ($element) {
    const $container = $(AclPermissions.options.containerSelector);
    const pageID = $container.data('pageid');
    const id = $element.data('acluid');

    // New ACL - simply remove ACL from table
    if (isNaN(id)) {
        AclPermissions.removeACL(id);
        return;
    }
    // Existing ACL - send delete request
    $.ajax({
        url: ajaxUrl,
        type: 'post',
        dataType: 'html',
        cache: false,
        data: {
            'action': 'delete_acl',
            'page': pageID,
            'acl': id
        }
    }).done(function (data) {
        // Remove from table
        AclPermissions.removeACL(id);
        // Show notification
        const title = data.title || 'Success';
        const msg = data.message || 'ACL deleted';
        Notification.success(title, msg, 5);
    }).fail(function (jqXHR, textStatus, error) {
        Notification.error(null, error);
    });
};

/**
 * update user and group information
 *
 * @param ACLid - ID of ACL
 * @param typeVal
 * @param objectId - Selected object id
 */
AclPermissions.updateUserGroup = function (ACLid, typeVal, objectId) {
    objectId = objectId || 0;
    const container = document.querySelector(AclPermissions.options.containerSelector);
    const pageID = container.dataset.pageid;
    const type = (typeVal === 1) ? 'group' : 'user';

    // Get child nodes of user/group selector
    const selector = document.querySelector(`select[name="data[pages][${pageID}][perms_${type}id]"]`);
    // Delete current object selector options
    const objSelector = document.querySelector(`select[name="data[tx_beacl_acl][${ACLid}][object_id]"]`);
    while (objSelector.firstChild) {
        objSelector.removeChild(objSelector.firstChild);
    }

    // Set new options on object selector
    let option, clonedOption;
    Array.from(selector.children).forEach((child) => {
        // Filter out values without IDs
        option = child;
        if (option.value > 0 && option.text !== '_cli_lowlevel') {
            clonedOption = option.cloneNode(true);
            clonedOption.removeAttribute('selected');
            objSelector.appendChild(clonedOption);
        }
    });
};


/**

 initializes events using deferred bound to document
 so AJAX reloads are no problem
 */
AclPermissions.initializeEvents = function () {
// Select user or group
    $(AclPermissions.options.containerSelector)
        .on('change', '.tx_beacl-edit-type-selector', function (evt) {
            evt.preventDefault();
            const $el = $(evt.target);
            AclPermissions.updateUserGroup($el.data('acluid'), $el.val(), 0);
        })
        .on('click', '.tx_beacl-addacl', function (evt) {
            evt.preventDefault();
            AclPermissions.addACL();
        })
        .on('click', '.tx_beacl-edit-delete', function (evt) {
            evt.preventDefault();
            AclPermissions.deleteACL($(this));
        })
        .find('.tx_beacl-edit-acl-row').each(function () {
        const acluid = $(this).data('acluid');
        let checkboxGroupCheckbox;
        if (acluid) {
            checkboxGroupCheckbox = $(this).find('[data-checkbox-group]');
            currentACLs.push(acluid);
        }
    });
};
AclPermissions.initializeEvents();

export default AclPermissions;
