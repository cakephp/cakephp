<?php
/**
 * Deals with Collections of objects.  Keeping registries of those objects,
 * loading and constructing new objects and triggering callbacks.
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       cake
 * @subpackage    cake.cake.libs.view
 * @since         CakePHP(tm) v 0.10.0.1076
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
abstract class ObjectCollection {
/**
 * Lists the currently-attached objects
 *
 * @var array
 * @access private
 */
	protected $_attached = array();

/**
 * Lists the currently-attached objects which are disabled
 *
 * @var array
 * @access private
 */
	protected $_disabled = array();

}