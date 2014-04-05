<?php
/**
 * HelpFormatter
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

App::uses('String', 'Utility');

/**
 * HelpFormatter formats help for console shells. Can format to either
 * text or XML formats. Uses ConsoleOptionParser methods to generate help.
 *
 * Generally not directly used. Using $parser->help($command, 'xml'); is usually
 * how you would access help. Or via the `--help=xml` option on the command line.
 *
 * Xml output is useful for integration with other tools like IDE's or other build tools.
 *
 * @package       Cake.Console
 * @since  CakePHP(tm) v 2.0
 */
class HelpFormatter {

/**
 * The maximum number of arguments shown when generating usage.
 *
 * @var integer
 */
	protected $_maxArgs = 6;

/**
 * The maximum number of options shown when generating usage.
 *
 * @var integer
 */
	protected $_maxOptions = 6;

/**
 * Build the help formatter for a an OptionParser
 *
 * @param ConsoleOptionParser $parser The option parser help is being generated for.
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
		$out[] = __d('cake_console', '<info>Usage:</info>');
		$out[] = $this->_generateUsage();
		$out[] = '';
		$subcommands = $parser->subcommands();
		if (!empty($subcommands)) {
			$out[] = __d('cake_console', '<info>Subcommands:</info>');
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
			$out[] = __d('cake_console', 'To see help on a subcommand use <info>`cake %s [subcommand] --help`</info>', $parser->command());
			$out[] = '';
		}

		$options = $parser->options();
		if (!empty($options)) {
			$max = $this->_getMaxLength($options) + 8;
			$out[] = __d('cake_console', '<info>Options:</info>');
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
			$out[] = __d('cake_console', '<info>Arguments:</info>');
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
 * Usage strings favor short options over the long ones. and optional args will
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
		$options = array();
		foreach ($this->_parser->options() as $option) {
			$options[] = $option->usage();
		}
		if (count($options) > $this->_maxOptions) {
			$options = array('[options]');
		}
		$usage = array_merge($usage, $options);
		$args = array();
		foreach ($this->_parser->arguments() as $argument) {
			$args[] = $argument->usage();
		}
		if (count($args) > $this->_maxArgs) {
			$args = array('[arguments]');
		}
		$usage = array_merge($usage, $args);
		return implode(' ', $usage);
	}

/**
 * Iterate over a collection and find the longest named thing.
 *
 * @param array $collection
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
 * @param boolean $string Return the SimpleXml object or a string. Defaults to true.
 * @return string|SimpleXmlElement See $string
 */
	public function xml($string = true) {
		$parser = $this->_parser;
		$xml = new SimpleXmlElement('<shell></shell>');
		$xml->addChild('command', $parser->command());
		$xml->addChild('description', $parser->description());

		$xml->addChild('epilog', $parser->epilog());
		$subcommands = $xml->addChild('subcommands');
		foreach ($parser->subcommands() as $command) {
			$command->xml($subcommands);
		}
		$options = $xml->addChild('options');
		foreach ($parser->options() as $option) {
			$option->xml($options);
		}
		$arguments = $xml->addChild('arguments');
		foreach ($parser->arguments() as $argument) {
			$argument->xml($arguments);
		}
		return $string ? $xml->asXml() : $xml;
	}

}
