<?php
/** 
 * Postfix Admin 
 * 
 * LICENSE 
 * This source file is subject to the GPL license that is bundled with  
 * this package in the file LICENSE.TXT. 
 * 
 * Further details on the project are available at : 
 *     http://www.postfixadmin.com or http://postfixadmin.sf.net 
 * 
 * @version $Id$ 
 * @license GNU GPL v2 or later. 
 * 
 * File: list-admin.php
 * Lists all administrators
 * Template File: list-admin.tpl
 *
 * Template Variables: -none-
 *
 * Form POST \ GET Variables: -none-
 */

require_once("common.php");

authentication_require_role('global-admin');

$admin_properties = list_admins();

$smarty->assign ('admin_properties', $admin_properties);
$smarty->assign ('smarty_template', 'adminlistadmin');
$smarty->display ('index.tpl');

/* vim: set expandtab softtabstop=4 tabstop=4 shiftwidth=4: */
?>
