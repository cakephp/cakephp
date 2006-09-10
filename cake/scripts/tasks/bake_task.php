<?php
/* SVN FILE: $Id$ */
/**
 * Base class for bake tasks.
 *
 * Long description for file
 *
 * PHP versions 4 and 5
 *
 * CakePHP :  Rapid Development Framework <http://www.cakephp.org/>
 * Copyright (c)	2005, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright (c) 2005, Cake Software Foundation, Inc.
 * @link				http://www.cakefoundation.org/projects/info/cakephp CakePHP Project
 * @package			cake
 * @subpackage		cake.cake.scripts.bake
 * @since			CakePHP v 1.2
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/mit-license.php The MIT License
 */
class BakeTask {

	/**
	 * Override this function in subclasses to implement the task logic.
	 * @param array $params The command line params (without script and task name).
	 */
	function execute($params) {
		// empty
	}

	/**
	 * Override this function in subclasses to provide a help message for your task.
	 */
	function help() {
		echo "There is no help available for the specified task.\n";
	}
}