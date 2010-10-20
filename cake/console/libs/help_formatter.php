<?php
/**
 * A class to format help for console shells.  Can format to either
 * text or XML formats
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       cake
 * @subpackage    cake.cake.console
 * @since         CakePHP(tm) v 2.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class HelpFormatter {
/**
 * Build the help formatter for a an OptionParser
 *
 * @return void
 */
	public function __construct(ConsoleOptionParser $parser) {
		$this->_parser = $parser;
	}

/**
 * Get the help as formatted text suitable for output on the command line.
 *
 * @param integer $width The width of the help output.
 * @return string
 */
	public function text($width = 72) {
		$parser = $this->_parser;
		$out = array();
		$description = $parser->description();
		if (!empty($description)) {
			$out[] = String::wrap($description, $width);
			$out[] = '';
		}
		$out[] = '<info>Usage:</info>';
		$out[] = $this->_generateUsage();
		$out[] = '';
		$subcommands = $parser->subcommands();
		if (!empty($subcommands)) {
			$out[] = '<info>Subcommands:</info>';
			$out[] = '';
			$max = $this->_getMaxLength($subcommands) + 2;
			foreach ($subcommands as $command) {
				$out[] = String::wrap($command->help($max), array(
					'width' => $width,
					'indent' => str_repeat(' ', $max),
					'indentAt' => 1
				));
			}
			$out[] = '';
			$out[] = sprintf(
				__('To see help on a subcommand use <info>`cake %s [subcommand] --help`</info>'),
				$parser->command()
			);
			$out[] = '';
		}

		$options = $parser->options();
		if (!empty($options)) {
			$max = $this->_getMaxLength($options) + 8;
			$out[] = '<info>Options:</info>';
			$out[] = '';
			foreach ($options as $option) {
				$out[] = String::wrap($option->help($max), array(
					'width' => $width,
					'indent' => str_repeat(' ', $max),
					'indentAt' => 1
				));
			}
			$out[] = '';
		}

		$arguments = $parser->arguments();
		if (!empty($arguments)) {
			$max = $this->_getMaxLength($arguments) + 2;
			$out[] = '<info>Arguments:</info>';
			$out[] = '';
			foreach ($arguments as $argument) {
				$out[] = String::wrap($argument->help($max), array(
					'width' => $width,
					'indent' => str_repeat(' ', $max),
					'indentAt' => 1
				));
			}
			$out[] = '';
		}
		$epilog = $parser->epilog();
		if (!empty($epilog)) {
			$out[] = String::wrap($epilog, $width);
			$out[] = '';
		}
		return implode("\n", $out);
	}

/**
 * Generate the usage for a shell based on its arguments and options.
 * Usage strings favour short options over the long ones. and optional args will
 * be indicated with []
 *
 * @return string
 */
	protected function _generateUsage() {
		$usage = array('cake ' . $this->_parser->command());
		$subcommands = $this->_parser->subcommands();
		if (!empty($subcommands)) {
			$usage[] = '[subcommand]';
		}
		foreach ($this->_parser->options() as $option) {
			$usage[] = $option->usage();
		}
		foreach ($this->_parser->arguments() as $argument) {
			$usage[] = $argument->usage();
		}
		return implode(' ', $usage);
	}

/**
 * Iterate over a collection and find the longest named thing.
 *
 * @return integer
 */
	protected function _getMaxLength($collection) {
		$max = 0;
		foreach ($collection as $item) {
			$max = (strlen($item->name()) > $max) ? strlen($item->name()) : $max;
		}
		return $max;
	}

/**
 * Get the help as an xml string.
 *
 * @return string
 */
	public function xml() {
		
	}
}