<?php
/**
 * Overload abstraction interface.  Merges differences between PHP4 and 5.
 *
 * PHP versions 4 and 5
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
 * @subpackage    cake.cake.libs
 * @since         CakePHP(tm) v 1.2
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

/**
 * Overloadable class selector
 *
 * @package       cake
 * @subpackage    cake.cake.libs
 */

/**
 * Load the interface class based on the version of PHP.
 *
 */
if (!PHP5) {
	require(LIBS . 'overloadable_php4.php');
} else {
	require(LIBS . 'overloadable_php5.php');
}
?>