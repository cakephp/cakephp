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
namespace Cake\View;

use Cake\Core\App;
use Cake\Utility\Inflector;

/**
 * Provides cell() method for usage in Controller and View classes.
 *
 */
trait CellTrait {

/**
 * Renders the given cell.
 *
 * Example:
 *
 * {{{
 * // Taxonomy\View\Cell\TagCloudCell::smallList()
 * $cell = $this->cell('Taxonomy.TagCloud::smallList', ['limit' => 10]);
 *
 * // App\View\Cell\TagCloudCell::smallList()
 * $cell = $this->cell('TagCloud::smallList', ['limit' => 10]);
 * }}}
 *
 * The `display` action will be used by default when no action is provided:
 *
 * {{{
 * // Taxonomy\View\Cell\TagCloudCell::display()
 * $cell = $this->cell('Taxonomy.TagCloud');
 * }}}
 *
 * Cells are not rendered until they are echoed.
 *
 * @param string $cell You must indicate both cell name, and optionally a cell action. e.g.: `TagCloud::smallList`
 * will invoke `View\Cell\TagCloudCell::smallList()`, `display` action will be invoked by default when none is provided.
 * @param array $data Additional arguments for cell method. e.g.:
 *    `cell('TagCloud::smallList', ['a1' => 'v1', 'a2' => 'v2'])` maps to `View\Cell\TagCloud::smallList(v1, v2)`
 * @param array $options Options for Cell's constructor
 * @return \Cake\View\Cell The cell instance
 * @throws \Cake\View\Error\MissingCellException If Cell class was not found
 */
	public function cell($cell, $data = [], $options = []) {
		$parts = explode('::', $cell);

		if (count($parts) == 2) {
			list($pluginAndCell, $action) = [$parts[0], $parts[1]];
		} else {
			list($pluginAndCell, $action) = [$parts[0], 'display'];
		}

		list($plugin, $cellName) = pluginSplit($pluginAndCell);

		$className = App::classname($pluginAndCell, 'View/Cell', 'Cell');

		if (!$className) {
			throw new Error\MissingCellException(array('className' => $pluginAndCell . 'Cell'));
		}

		$cellInstance = new $className($this->request, $this->response, $this->getEventManager(), $options);
		$cellInstance->template = Inflector::underscore($action);
		$cellInstance->plugin = !empty($plugin) ? $plugin : null;
		$cellInstance->theme = !empty($this->theme) ? $this->theme : null;
		$length = count($data);

		if ($length) {
			$data = array_values($data);
		}

		call_user_func_array([$cellInstance, $action], $data);

		return $cellInstance;
	}

}
