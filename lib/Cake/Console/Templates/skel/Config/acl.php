<?php
/**
 * This is the PHP base ACL configuration file.
 *
 * Use it to configure access control of your CakePHP application.
 *
 * PHP 5
 *
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       app.Config
 * @since         CakePHP(tm) v 2.1
 */

/**
 * Example
 * -------
 *
 * Assumptions:
 *
 * 1. In your application you created a User model with the following properties:
 *    username, group_id, password, email, firstname, lastname and so on.
 * 2. You configured AuthComponent to authorize actions via
 *    $this->Auth->authorize = array('Actions' => array('actionPath' => 'controllers/'),...)
 *
 * Now, when a user (i.e. jeff) authenticates successfully and requests a controller action (i.e. /invoices/delete)
 * that is not allowed by default (e.g. via $this->Auth->allow('edit') in the Invoices controller) then AuthComponent
 * will ask the configured ACL interface if access is granted. Under the assumptions 1. and 2. this will be
 * done via a call to Acl->check() with
 *
 *    array('User' => array('username' => 'jeff', 'group_id' => 4, ...))
 *
 * as ARO and
 *
 *    '/controllers/invoices/delete'
 *
 * as ACO.
 *
 * If the configured map looks like
 *
 *    $config['map'] = array(
 *       'User' => 'User/username',
 *       'Role' => 'User/group_id',
 *    );
 *
 * then PhpAcl will lookup if we defined a role like User/jeff. If that role is not found, PhpAcl will try to
 * find a definition for Role/4. If the definition isn't found then a default role (Role/default) will be used to
 * check rules for the given ACO. The search can be expanded by defining aliases in the alias configuration.
 * E.g. if you want to use a more readable name than Role/4 in your definitions you can define an alias like
 *
 *    $config['alias'] = array(
 *       'Role/4' => 'Role/editor',
 *    );
 *
 * In the roles configuration you can define roles on the lhs and inherited roles on the rhs:
 *
 *    $config['roles'] = array(
 *       'Role/admin' => null,
 *       'Role/accountant' => null,
 *       'Role/editor' => null,
 *       'Role/manager' => 'Role/editor, Role/accountant',
 *       'User/jeff' => 'Role/manager',
 *    );
 *
 * In this example manager inherits all rules from editor and accountant. Role/admin doesn't inherit from any role.
 * Lets define some rules:
 *
 *    $config['rules'] = array(
 *       'allow' => array(
 *       	'*' => 'Role/admin',
 *       	'controllers/users/(dashboard|profile)' => 'Role/default',
 *       	'controllers/invoices/*' => 'Role/accountant',
 *       	'controllers/articles/*' => 'Role/editor',
 *       	'controllers/users/*'  => 'Role/manager',
 *       	'controllers/invoices/delete'  => 'Role/manager',
 *       ),
 *       'deny' => array(
 *       	'controllers/invoices/delete' => 'Role/accountant, User/jeff',
 *       	'controllers/articles/(delete|publish)' => 'Role/editor',
 *       ),
 *    );
 *
 * Ok, so as jeff inherits from Role/manager he's matched every rule that references User/jeff, Role/manager,
 * Role/editor, Role/accountant and Role/default. However, for jeff, rules for User/jeff are more specific than
 * rules for Role/manager, rules for Role/manager are more specific than rules for Role/editor and so on.
 * This is important when allow and deny rules match for a role. E.g. Role/accountant is allowed
 * controllers/invoices/* but at the same time controllers/invoices/delete is denied. But there is a more
 * specific rule defined for Role/manager which is allowed controllers/invoices/delete. However, the most specific
 * rule denies access to the delete action explicitly for User/jeff, so he'll be denied access to the resource.
 *
 * If we would remove the role definition for User/jeff, then jeff would be granted access as he would be resolved
 * to Role/manager and Role/manager has an allow rule.
 */

/**
 * The role map defines how to resolve the user record from your application
 * to the roles you defined in the roles configuration.
 */
$config['map'] = array(
	'User' => 'User/username',
	'Role' => 'User/group_id',
);

/**
 * define aliases to map your model information to
 * the roles defined in your role configuration.
 */
$config['alias'] = array(
	'Role/4' => 'Role/editor',
);

/**
 * role configuration
 */
$config['roles'] = array(
	'Role/admin' => null,
);

/**
 * rule configuration
 */
$config['rules'] = array(
	'allow' => array(
		'*' => 'Role/admin',
	),
	'deny' => array(),
);
