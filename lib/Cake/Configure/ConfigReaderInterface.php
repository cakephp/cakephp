<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       Cake.Core
 * @since         CakePHP(tm) v 1.0.0.2363
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

/**
 * An interface for creating objects compatible with Configure::load()
 *
 * @package       Cake.Core
 */
interface ConfigReaderInterface {

/**
 * Read method is used for reading configuration information from sources.
 * These sources can either be static resources like files, or dynamic ones like
 * a database, or other datasource.
 *
 * @param string $key
 * @return array An array of data to merge into the runtime configuration
 */
	public function read($key);

}
