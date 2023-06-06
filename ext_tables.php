<?php

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

if (! defined('TYPO3')) {
    die('Access denied.');
}


call_user_func(function () {
    ExtensionManagementUtility::allowTableOnStandardPages('tx_beacl_acl');
});
