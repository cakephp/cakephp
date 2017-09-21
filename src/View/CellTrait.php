<?php
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\View;

use Cake\Core\App;
use Cake\Utility\Inflector;
use Cake\View\Exception\MissingCellException;

/**
 * Provides cell() method for usage in Controller and View classes.
 */
trait CellTrait
{

    /**
     * Renders the given cell.
     *
     * Example:
     *
     * ```
     * // Taxonomy\View\Cell\TagCloudCell::smallList()
     * $cell = $this->cell('Taxonomy.TagCloud::smallList', ['limit' => 10]);
     *
     * // App\View\Cell\TagCloudCell::smallList()
     * $cell = $this->cell('TagCloud::smallList', ['limit' => 10]);
     * ```
     *
     * The `display` action will be used by default when no action is provided:
     *
     * ```
     * // Taxonomy\View\Cell\TagCloudCell::display()
     * $cell = $this->cell('Taxonomy.TagCloud');
     * ```
     *
     * Cells are not rendered until they are echoed.
     *
     * @param string $cell You must indicate cell name, and optionally a cell action. e.g.: `TagCloud::smallList`
     * will invoke `View\Cell\TagCloudCell::smallList()`, `display` action will be invoked by default when none is provided.
     * @param array $data Additional arguments for cell method. e.g.:
     *    `cell('TagCloud::smallList', ['a1' => 'v1', 'a2' => 'v2'])` maps to `View\Cell\TagCloud::smallList(v1, v2)`
     * @param array $options Options for Cell's constructor
     * @return \Cake\View\Cell The cell instance
     * @throws \Cake\View\Exception\MissingCellException If Cell class was not found.
     * @throws \BadMethodCallException If Cell class does not specified cell action.
     */
    protected function cell($cell, array $data = [], array $options = [])
    {
        $parts = explode('::', $cell);

        if (count($parts) === 2) {
            list($pluginAndCell, $action) = [$parts[0], $parts[1]];
        } else {
            list($pluginAndCell, $action) = [$parts[0], 'display'];
        }

        list($plugin) = pluginSplit($pluginAndCell);
        $className = App::className($pluginAndCell, 'View/Cell', 'Cell');

        if (!$className) {
            throw new MissingCellException(['className' => $pluginAndCell . 'Cell']);
        }

        if (!empty($data)) {
            $data = array_values($data);
        }
        $options = ['action' => $action, 'args' => $data] + $options;
        $cell = $this->_createCell($className, $action, $plugin, $options);

        return $cell;
    }

    /**
     * Create and configure the cell instance.
     *
     * @param string $className The cell classname.
     * @param string $action The action name.
     * @param string $plugin The plugin name.
     * @param array $options The constructor options for the cell.
     * @return \Cake\View\Cell
     */
    protected function _createCell($className, $action, $plugin, $options)
    {
        /* @var \Cake\View\Cell $instance */
        $instance = new $className($this->request, $this->response, $this->getEventManager(), $options);
        $instance->template = Inflector::underscore($action);

        $builder = $instance->viewBuilder();
        if (!empty($plugin)) {
            $builder->setPlugin($plugin);
        }
        if (!empty($this->helpers)) {
            $builder->setHelpers($this->helpers);
            $instance->helpers = $this->helpers;
        }

        if ($this instanceof View) {
            if (!empty($this->theme)) {
                $builder->setTheme($this->theme);
            }

            $class = get_class($this);
            $builder->setClassName($class);
            $instance->viewClass = $class;

            return $instance;
        }

        if (method_exists($this, 'viewBuilder')) {
            $builder->setTheme($this->viewBuilder()->getTheme());
        }

        if (isset($this->viewClass)) {
            $builder->setClassName($this->viewClass);
            $instance->viewClass = $this->viewClass;
        }

        return $instance;
    }
}
