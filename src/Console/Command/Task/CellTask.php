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
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Console\Command\Task;

use Cake\Console\Command\Task\SimpleBakeTask;

/**
 * Task for creating cells.
 */
class CellTask extends SimpleBakeTask {

/**
 * Task name used in path generation.
 *
 * @var string
 */
	public $pathFragment = 'View/Cell/';

/**
 * {@inheritDoc}
 */
	public function name() {
		return 'cell';
	}

/**
 * {@inheritDoc}
 */
	public function fileName($name) {
		return $name . 'Cell.php';
	}

/**
 * {@inheritDoc}
 */
	public function template() {
		return 'cell';
	}

/**
 * Bake the Cell class and template file.
 *
 * @param string $name The name of the cell to make.
 * @return void
 */
	public function bake($name) {
		$this->bakeTemplate($name);
		return parent::bake($name);
	}

/**
 * Bake an empty file for a cell.
 *
 * @param string $name The name of the cell a template is needed for.
 * @return void
 */
	public function bakeTemplate($name) {
		$templatePath = implode(DS, ['Template', 'Cell', $name, 'display.ctp']);
		$restore = $this->pathFragment;
		$this->pathFragment = $templatePath;

		$path = $this->getPath();
		$this->pathFragment = $restore;

		$this->createFile($path, '');
	}

}
