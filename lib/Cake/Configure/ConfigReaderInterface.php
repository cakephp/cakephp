<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       Cake.Core
 * @since         CakePHP(tm) v 1.0.0.2363
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
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
 * @param string $key Key to read.
 * @return array An array of data to merge into the runtime configuration
 */
	public function read($key);

/**
 * Dumps the configure data into source.
 *
 * @param string $key The identifier to write to.
 * @param array $data The data to dump.
 * @return bool True on success or false on failure.
 */
	public function dump($key, $data);

}
