<?php
/**
 * Test App Comment Model
 *
 *
 *
 * PHP 5
 *
 * CakePHP : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2012, Cake Software Foundation, Inc.
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc.
 * @link          http://cakephp.org CakePHP Project
 * @package       Cake.Test.TestApp.Model
 * @since         CakePHP v 1.2.0.7726
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace TestApp\Model;

class Post extends AppModel {

	public $useTable = 'posts';

	public $name = 'Post';

/**
 * find method
 *
 * @param string $type
 * @param array $options
 * @return void
 */
	public function find($type = 'first', $options = array()) {
		if ($type == 'popular') {
			$conditions = array($this->name . '.' . $this->primaryKey . ' > ' => '1');
			$options = Hash::merge($options, compact('conditions'));
			return parent::find('all', $options);
		}
		return parent::find($type, $options);
	}
}
