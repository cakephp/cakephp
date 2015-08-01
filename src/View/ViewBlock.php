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
 * @since         2.1.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\View;

use Cake\Core\Exception\Exception;

/**
 * ViewBlock implements the concept of Blocks or Slots in the View layer.
 * Slots or blocks are combined with extending views and layouts to afford slots
 * of content that are present in a layout or parent view, but are defined by the child
 * view or elements used in the view.
 *
 */
class ViewBlock
{

    /**
     * Override content
     *
     * @var string
     */
    const OVERRIDE = 'override';

    /**
     * Append content
     *
     * @var string
     */
    const APPEND = 'append';

    /**
     * Prepend content
     *
     * @var string
     */
    const PREPEND = 'prepend';

    /**
     * Block content. An array of blocks indexed by name.
     *
     * @var array
     */
    protected $_blocks = [];

    /**
     * The active blocks being captured.
     *
     * @var array
     */
    protected $_active = [];

    /**
     * Should the currently captured content be discarded on ViewBlock::end()
     *
     * @see ViewBlock::end()
     * @var bool
     */
    protected $_discardActiveBufferOnEnd = false;

    /**
     * Start capturing output for a 'block'
     *
     * Blocks allow you to create slots or blocks of dynamic content in the layout.
     * view files can implement some or all of a layout's slots.
     *
     * You can end capturing blocks using View::end(). Blocks can be output
     * using View::get();
     *
     * @param string $name The name of the block to capture for.
     * @param string $mode If ViewBlock::OVERRIDE existing content will be overridden by new content.
     *   If ViewBlock::APPEND content will be appended to existing content.
     *   If ViewBlock::PREPEND it will be prepended.
     * @throws \Cake\Core\Exception\Exception When starting a block twice
     * @return void
     */
    public function start($name, $mode = ViewBlock::OVERRIDE)
    {
        if (in_array($name, array_keys($this->_active))) {
            throw new Exception(sprintf("A view block with the name '%s' is already/still open.", $name));
        }
        $this->_active[$name] = $mode;
        ob_start();
    }

    /**
     * End a capturing block. The compliment to ViewBlock::start()
     *
     * @return void
     * @see ViewBlock::start()
     */
    public function end()
    {
        if ($this->_discardActiveBufferOnEnd) {
            $this->_discardActiveBufferOnEnd = false;
            ob_end_clean();
            return;
        }
        if (!empty($this->_active)) {
            $mode = end($this->_active);
            $active = key($this->_active);
            $content = ob_get_clean();
            if ($mode === ViewBlock::OVERRIDE) {
                $this->_blocks[$active] = $content;
            } else {
                $this->concat($active, $content, $mode);
            }
            array_pop($this->_active);
        }
    }

    /**
     * Concat content to an existing or new block.
     * Concating to a new block will create the block.
     *
     * Calling concat() without a value will create a new capturing
     * block that needs to be finished with View::end(). The content
     * of the new capturing context will be added to the existing block context.
     *
     * @param string $name Name of the block
     * @param mixed $value The content for the block
     * @param string $mode If ViewBlock::APPEND content will be appended to existing content.
     *   If ViewBlock::PREPEND it will be prepended.
     * @return void
     */
    public function concat($name, $value = null, $mode = ViewBlock::APPEND)
    {
        if ($value === null) {
            $this->start($name, $mode);
            return;
        }

        if (!isset($this->_blocks[$name])) {
            $this->_blocks[$name] = '';
        }
        if ($mode === ViewBlock::PREPEND) {
            $this->_blocks[$name] = $value . $this->_blocks[$name];
        } else {
            $this->_blocks[$name] .= $value;
        }
    }

    /**
     * Set the content for a block. This will overwrite any
     * existing content.
     *
     * @param string $name Name of the block
     * @param mixed $value The content for the block.
     * @return void
     */
    public function set($name, $value)
    {
        $this->_blocks[$name] = (string)$value;
    }

    /**
     * Get the content for a block.
     *
     * @param string $name Name of the block
     * @param string $default Default string
     * @return string The block content or $default if the block does not exist.
     */
    public function get($name, $default = '')
    {
        if (!isset($this->_blocks[$name])) {
            return $default;
        }
        return $this->_blocks[$name];
    }

    /**
     * Check if a block exists
     *
     * @param string $name Name of the block
     * @return bool
     */
    public function exists($name)
    {
        return isset($this->_blocks[$name]);
    }

    /**
     * Get the names of all the existing blocks.
     *
     * @return array An array containing the blocks.
     */
    public function keys()
    {
        return array_keys($this->_blocks);
    }

    /**
     * Get the name of the currently open block.
     *
     * @return mixed Either null or the name of the last open block.
     */
    public function active()
    {
        end($this->_active);
        return key($this->_active);
    }

    /**
     * Get the names of the unclosed/active blocks.
     *
     * @return array An array of unclosed blocks.
     */
    public function unclosed()
    {
        return $this->_active;
    }
}
