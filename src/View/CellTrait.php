<?php
declare(strict_types=1);

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
     * @param string $cell You must indicate cell name, and optionally a cell action. e.g.: `TagCloud::smallList` will
     *  invoke `View\Cell\TagCloudCell::smallList()`, `display` action will be invoked by default when none is provided.
     * @param array $data Additional arguments for cell method. e.g.:
     *    `cell('TagCloud::smallList', ['a1' => 'v1', 'a2' => 'v2'])` maps to `View\Cell\TagCloud::smallList(v1, v2)`
     * @param array $options Options for Cell's constructor
     * @return \Cake\View\Cell The cell instance
     * @throws \Cake\View\Exception\MissingCellException If Cell class was not found.
     * @throws \BadMethodCallException If Cell class does not specified cell action.
     */
    protected function cell(string $cell, array $data = [], array $options = []): Cell
    {
        $parts = explode('::', $cell);

        if (count($parts) === 2) {
            [$pluginAndCell, $action] = [$parts[0], $parts[1]];
        } else {
            [$pluginAndCell, $action] = [$parts[0], 'display'];
        }

        [$plugin] = pluginSplit($pluginAndCell);
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
     * @param string|null $plugin The plugin name.
     * @param array $options The constructor options for the cell.
     * @return \Cake\View\Cell
     */
    protected function _createCell(string $className, string $action, ?string $plugin, array $options): Cell
    {
        /** @var \Cake\View\Cell $instance */
        $instance = new $className($this->request, $this->response, $this->getEventManager(), $options);

        $builder = $instance->viewBuilder();
        $builder->setTemplate(Inflector::underscore($action));

        if (!empty($plugin)) {
            $builder->setPlugin($plugin);
        }
        if (!empty($this->helpers)) {
            $builder->setHelpers($this->helpers);
        }

        if ($this instanceof View) {
            if (!empty($this->theme)) {
                $builder->setTheme($this->theme);
            }

            $class = static::class;
            $builder->setClassName($class);
            $instance->viewBuilder()->setClassName($class);

            return $instance;
        }

        if (method_exists($this, 'viewBuilder')) {
            $builder->setTheme($this->viewBuilder()->getTheme());

            if ($this->viewBuilder()->getClassName() !== null) {
                $builder->setClassName($this->viewBuilder()->getClassName());
            }
        }

        return $instance;
    }
}
