<?php
/**
 * This is the PHP base ACL configuration file.
 *
 *Utilisez le pour configurer le control d'accès de votre application cakePHP.
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Pour un copyright et un numeros de license s'il vous plait jetez unc oup d'oeil à LICENSE.txt
 *Les redistributions conserverons l'avis copyright ci dessus.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       app.Config
 * @since         CakePHP(tm) v 2.1
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

/**
 * Exemple
 * -------
 *
 * Supposotions:
 *
 * 1.Vous créez un model d'utilisateurs dans votre application avec les propriétés suivantes:
 *    nom d'utilisateur, group_id, mot de passe, email, Prenom, Nom et ainsi de suite.
 * 2. Vous configurez  AuthComponent afin d'authoriser les actions via *    $this->Auth->authorize = array('Actions' => array('actionPath' => 'controllers/'),...)
 *
 * Maintenant ,l'utilisateurs (i.e. jeff) s'authentifie avec succès  et demande un controleur d'action (i.e. /invoices/delete)
 * ceci n'est pas permis par defaut (e.g. via $this->Auth->allow('edit') in the Invoices controller) ensuite AuthComponent
 * demanderas a l'interface ACL configuré si l'accès est interdit . Dans l 1.Selon les hypothèses 1 et 2. ca sera
 * fait via a appel à  Acl->check() avec
 *
 * {{{
 * array('User' => array('username' => 'jeff', 'group_id' => 4, ...))
 * }}}
 *
 * as ARO and
 *
 * {{{
 * '/controllers/invoices/delete'
 * }}}
 *
 * as ACO.
 *
 *Si la carte configuré ressemble à
 *
 * {{{
 * $config['map'] = array(
 *    'User' => 'User/username',
 *    'Role' => 'User/group_id',
 * );
 * }}}
 *
 * Alors PhpAcl chercheras si on à defini User/jeff comme un rôle. Si ce rôle n'est pas trouvé , PhpAcl essaiera de
 * trouver une   definition pour le  Role/4. Si la definition n'est pas trouver alors le rôle par defaut sera utiliser pour(Role/default) 
 * verifier les règles pour le ACO donné. La recherche peut être étendu en definissant  des alias dans la configuration d'alias.
 * E.g. Si vous voulez utilisé un nom plus lisible que le Role/4 dans vos definitions vous pouvez definir un alias comme *
 * {{{
 * $config['alias'] = array(
 *    'Role/4' => 'Role/editor',
 * );
 * }}}
 *
 * Dans la configuration des rôles vous pouvez définir des rôles sur le lhs et hérité des rôles du rhs:
 *
 * {{{
 * $config['roles'] = array(
 *    'Role/admin' => null,
 *    'Role/accountant' => null,
 *    'Role/editor' => null,
 *    'Role/manager' => 'Role/editor, Role/accountant',
 *    'User/jeff' => 'Role/manager',
 * );
 * }}}
 *
 * Dans cette exemple le manager herite de toutes les règles de l'editeur et  du responsable . Rôle/admin n'herite d'aucun rôle.
 * D"finissons quelques règles :
 *
 * {{{
 * $config['rules'] = array(
 *    'allow' => array(
 *        '*' => 'Role/admin',
 *        'controllers/users/(dashboard|profile)' => 'Role/default',
 *        'controlleurs/commandes/*' => 'Rôle/comptable',
 *        'controlleurs/articles/*' => 'Rôle/editeur',
         'controlleurs/utilisateurs/*'  => 'Rôle/manager',
 *        'controlleurs/commandes/supprimer'  => 'Rôle/manager',
 *    ),
 *    'deny' => array(
 *        'controlleurs/commandes/supprimer' => 'Rôle/comptable, Utilisateur/jeff',
 *        'controlleurs/articles/(delete|publish)' => 'Role/editor',
 *    ),
 * );
 * }}}
 *
 * Ok, so as jeff inherits from Role/manager he's matched every rule that references User/jeff, Role/manager,
 * Role/editeur, and Rôle/comptable . Cependant, pour jeff, les règles pour Utilisateur/jeff sont plus specifique  que 
 * les règles pour Rôle/manager, les règles pour Rôle/manager sont plus specifique que les règles de terrain ainsi de suite .
 * Ceci est important pour authoriser et refuser les règles de correspondance pour un rôle. E.g. Rôle/comptanle est auhtoriser 
 * controlleurs/commandes/* mais en même temps controlleurs/commandes/supprimer est refuser.Mais il y a une règles plus *specifique definit pour rôle/manager qui est autorisé controlleurs/commandes/supprimer. Cependant la règle la plus specique refuse l'acces à la l'action de suppression explicite pour utilisateur/jeff , donc il sera interdit d'accès au ressource.
 *
 * Si on enleve la definition de rôle pour Utilisateur/jeff,Jeff sera, alors jeff  sera interdit granted access as he would be resolved
 * to Role/manager and Role/manager has an allow rule.
 */

/**
 * La carte de rôle definit comment resoudre l'enregistrement pour votre application
 * aux rôles que vous avez definis dans les rôles de configuration..
 */
$config['map'] = array(
	'User' => 'User/username',
	'Role' => 'User/group_id',
);

/**
 * definir des alias pour le model de rôle.information to
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
