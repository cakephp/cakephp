<?php
/**
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       Cake.Model
 * @subpackage    cake.cake.libs.model
 * @since         CakePHP(tm) v 0.2.9
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

/**
 * Load Model and AppModel
 */
App::uses('AppModel', 'Model');

/**
 * Permissions linking AROs with ACOs
 *
 * @package       Cake.Model
 * @subpackage    cake.cake.libs.model
 */
class Permission extends AppModel {

/**
 * Model name
 *
 * @var string
 * @access public
 */
	public $name = 'Permission';

/**
 * Explicitly disable in-memory query caching
 *
 * @var boolean
 * @access public
 */
	public $cacheQueries = false;

/**
 * Override default table name
 *
 * @var string
 * @access public
 */
	public $useTable = 'aros_acos';

/**
 * Permissions link AROs with ACOs
 *
 * @var array
 * @access public
 */
	public $belongsTo = array('Aro', 'Aco');

/**
 * No behaviors for this model
 *
 * @var array
 * @access public
 */
	public $actsAs = null;

/**
 * Constructor, used to tell this model to use the
 * database configured for ACL
 */
	public function __construct() {
		$config = Configure::read('Acl.database');
		if (!empty($config)) {
			$this->useDbConfig = $config;
		}
		parent::__construct();
	}
}