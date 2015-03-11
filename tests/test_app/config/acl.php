<?php
/*
 * Test App PHP Based Acl Config File
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 *  Licensed under The MIT License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         0.10.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

// -------------------------------------
// Roles
// -------------------------------------
$config['roles'] = [
    'Role/admin' => null,
    'Role/data_acquirer' => null,
    'Role/accounting' => null,
    'Role/database_manager' => null,
    'Role/sales' => null,
    'Role/data_analyst' => 'Role/data_acquirer, Role/database_manager',
    'Role/reports' => 'Role/data_analyst',
    // allow inherited roles to be defined as an array or comma separated list
    'Role/manager' => [
        'Role/accounting',
        'Role/sales',
    ],
    'Role/accounting_manager' => 'Role/accounting',
    // managers
    'User/hardy' => 'Role/accounting_manager, Role/reports',
    'User/stan' => 'Role/manager',
    // accountants
    'User/peter' => 'Role/accounting',
    'User/jeff' => 'Role/accounting',
    // admins
    'User/jan' => 'Role/admin',
    // database
    'User/db_manager_1' => 'Role/database_manager',
    'User/db_manager_2' => 'Role/database_manager',
];

//-------------------------------------
// Rules
//-------------------------------------
$config['rules']['allow'] = [
    '/*' => 'Role/admin',
    '/controllers/*/manager_*' => 'Role/manager',
    '/controllers/reports/*' => 'Role/sales',
    '/controllers/invoices/*' => 'Role/accounting',
    '/controllers/invoices/edit' => 'User/db_manager_2',
    '/controllers/db/*' => 'Role/database_manager',
    '/controllers/*/(add|edit|publish)' => 'User/stan',
    '/controllers/users/dashboard' => 'Role/default',
    // test for case insensitivity
    'controllers/Forms/NEW' => 'Role/data_acquirer',
];
$config['rules']['deny'] = [
    // accountants and sales should not delete anything
    '/controllers/*/delete' => [
        'Role/sales',
        'Role/accounting'
    ],
    '/controllers/db/drop' => 'User/db_manager_2',
];
