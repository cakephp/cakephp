<?php
/**
 * MergeVar Plugin Controller
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
 * @package       Cake.Controller
 * @since         CakePHP(tm) v 3.0.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace MergeVar\Controller;

/**
 * MergeVarPlugin App Controller
 *
 */
class Controller extends \Cake\Controller\Controller {

/**
 * components
 *
 * @var array
 */
	public $components = array('Auth' => array('setting' => 'val', 'otherVal'));

/**
 * helpers
 *
 * @var array
 */
	public $helpers = array('Javascript');

/**
 * parent for mergeVars
 *
 * @var string
 */
	protected $_mergeParent = 'TestApp\Controller\MergeVarsAppController';
}
