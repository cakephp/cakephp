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
 * @since         CakePHP(tm) v2.1
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

/**
 * ViewBlock implements the concept of Blocks or Slots in the View layer.
 * Slots or blocks are combined with extending views and layouts to afford slots
 * of content that are present in a layout or parent view, but are defined by the child
 * view or elements used in the view.
 *
 * @package Cake.View
 */
class ViewBlock {

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
	protected $_blocks = array();

/**
 * The active blocks being captured.
 *
 * @var array
 */
	protected $_active = array();

/**
 * Should the currently captured content be discarded on ViewBlock::end()
 *
 * @var bool
 * @see ViewBlock::end()
 * @see ViewBlock::startIfEmpty()
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
 * @throws CakeException When starting a block twice
 * @return void
 */
	public function start($name) {
		if (in_array($name, $this->_active)) {
			throw new CakeException(__("A view block with the name '%s' is already/still open.", $name));
		}
		$this->_active[] = $name;
		ob_start();
	}

/**
 * Start capturing output for a 'block' if it is empty
 *
 * Blocks allow you to create slots or blocks of dynamic content in the layout.
 * view files can implement some or all of a layout's slots.
 *
 * You can end capturing blocks using View::end(). Blocks can be output
 * using View::get();
 *
 * @param string $name The name of the block to capture for.
 * @return void
 */
	public function startIfEmpty($name) {
		if (empty($this->_blocks[$name])) {
			return $this->start($name);
		}
		$this->_discardActiveBufferOnEnd = true;
		ob_start();
	}

/**
 * End a capturing block. The compliment to ViewBlock::start()
 *
 * @return void
 * @see ViewBlock::start()
 */
	public function end() {
		if ($this->_discardActiveBufferOnEnd) {
			$this->_discardActiveBufferOnEnd = false;
			ob_end_clean();
			return;
		}
		if (!empty($this->_active)) {
			$active = end($this->_active);
			$content = ob_get_clean();
			if (!isset($this->_blocks[$active])) {
				$this->_blocks[$active] = '';
			}
			$this->_blocks[$active] .= $content;
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
	public function concat($name, $value = null, $mode = ViewBlock::APPEND) {
		if (isset($value)) {
			if (!isset($this->_blocks[$name])) {
				$this->_blocks[$name] = '';
			}
			if ($mode === ViewBlock::PREPEND) {
				$this->_blocks[$name] = $value . $this->_blocks[$name];
			} else {
				$this->_blocks[$name] .= $value;
			}
		} else {
			$this->start($name);
		}
	}

/**
 * Append to an existing or new block. Appending to a new
 * block will create the block.
 *
 * Calling append() without a value will create a new capturing
 * block that needs to be finished with View::end(). The content
 * of the new capturing context will be added to the existing block context.
 *
 * @param string $name Name of the block
 * @param string $value The content for the block.
 * @return void
 * @deprecated 3.0.0 As of 2.3 use ViewBlock::concat() instead.
 */
	public function append($name, $value = null) {
		$this->concat($name, $value);
	}

/**
 * Set the content for a block. This will overwrite any
 * existing content.
 *
 * @param string $name Name of the block
 * @param mixed $value The content for the block.
 * @return void
 */
	public function set($name, $value) {
		$this->_blocks[$name] = (string)$value;
	}

/**
 * Get the content for a block.
 *
 * @param string $name Name of the block
 * @param string $default Default string
 * @return string The block content or $default if the block does not exist.
 */
	public function get($name, $default = '') {
		if (!isset($this->_blocks[$name])) {
			return $default;
		}
		return $this->_blocks[$name];
	}

/**
 * Get the names of all the existing blocks.
 *
 * @return array An array containing the blocks.
 */
	public function keys() {
		return array_keys($this->_blocks);
	}

/**
 * Get the name of the currently open block.
 *
 * @return mixed Either null or the name of the last open block.
 */
	public function active() {
		return end($this->_active);
	}

/**
 * Get the names of the unclosed/active blocks.
 *
 * @return array An array of unclosed blocks.
 */
	public function unclosed() {
		return $this->_active;
	}

}
