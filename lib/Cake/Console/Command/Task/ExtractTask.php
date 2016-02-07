<?php
/**
 * Language string extractor
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
 * @since         CakePHP(tm) v 1.2.0.5012
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

App::uses('AppShell', 'Console/Command');
App::uses('File', 'Utility');
App::uses('Folder', 'Utility');
App::uses('Hash', 'Utility');

/**
 * Language string extractor
 *
 * @package       Cake.Console.Command.Task
 */
class ExtractTask extends AppShell {

/**
 * Paths to use when looking for strings
 *
 * @var string
 */
	protected $_paths = array();

/**
 * Files from where to extract
 *
 * @var array
 */
	protected $_files = array();

/**
 * Merge all domain and category strings into the default.pot file
 *
 * @var bool
 */
	protected $_merge = false;

/**
 * Current file being processed
 *
 * @var string
 */
	protected $_file = null;

/**
 * Contains all content waiting to be write
 *
 * @var string
 */
	protected $_storage = array();

/**
 * Extracted tokens
 *
 * @var array
 */
	protected $_tokens = array();

/**
 * Extracted strings indexed by category, domain, msgid and context.
 *
 * @var array
 */
	protected $_translations = array();

/**
 * Destination path
 *
 * @var string
 */
	protected $_output = null;

/**
 * An array of directories to exclude.
 *
 * @var array
 */
	protected $_exclude = array();

/**
 * Holds whether this call should extract model validation messages
 *
 * @var bool
 */
	protected $_extractValidation = true;

/**
 * Holds the validation string domain to use for validation messages when extracting
 *
 * @var bool
 */
	protected $_validationDomain = 'default';

/**
 * Holds whether this call should extract the CakePHP Lib messages
 *
 * @var bool
 */
	protected $_extractCore = false;

/**
 * Method to interact with the User and get path selections.
 *
 * @return void
 */
	protected function _getPaths() {
		$defaultPath = APP;
		while (true) {
			$currentPaths = count($this->_paths) > 0 ? $this->_paths : array('None');
			$message = __d(
				'cake_console',
				"Current paths: %s\nWhat is the path you would like to extract?\n[Q]uit [D]one",
				implode(', ', $currentPaths)
			);
			$response = $this->in($message, null, $defaultPath);
			if (strtoupper($response) === 'Q') {
				$this->err(__d('cake_console', 'Extract Aborted'));
				return $this->_stop();
			} elseif (strtoupper($response) === 'D' && count($this->_paths)) {
				$this->out();
				return;
			} elseif (strtoupper($response) === 'D') {
				$this->err(__d('cake_console', '<warning>No directories selected.</warning> Please choose a directory.'));
			} elseif (is_dir($response)) {
				$this->_paths[] = $response;
				$defaultPath = 'D';
			} else {
				$this->err(__d('cake_console', 'The directory path you supplied was not found. Please try again.'));
			}
			$this->out();
		}
	}

/**
 * Execution method always used for tasks
 *
 * @return void
 */
	public function execute() {
		if (!empty($this->params['exclude'])) {
			$this->_exclude = explode(',', str_replace('/', DS, $this->params['exclude']));
		}
		if (isset($this->params['files']) && !is_array($this->params['files'])) {
			$this->_files = explode(',', $this->params['files']);
		}
		if (isset($this->params['paths'])) {
			$this->_paths = explode(',', $this->params['paths']);
		} elseif (isset($this->params['plugin'])) {
			$plugin = Inflector::camelize($this->params['plugin']);
			if (!CakePlugin::loaded($plugin)) {
				CakePlugin::load($plugin);
			}
			$this->_paths = array(CakePlugin::path($plugin));
			$this->params['plugin'] = $plugin;
		} else {
			$this->_getPaths();
		}

		if (isset($this->params['extract-core'])) {
			$this->_extractCore = !(strtolower($this->params['extract-core']) === 'no');
		} else {
			$response = $this->in(__d('cake_console', 'Would you like to extract the messages from the CakePHP core?'), array('y', 'n'), 'n');
			$this->_extractCore = strtolower($response) === 'y';
		}

		if (!empty($this->params['exclude-plugins']) && $this->_isExtractingApp()) {
			$this->_exclude = array_merge($this->_exclude, App::path('plugins'));
		}

		if (!empty($this->params['ignore-model-validation']) || (!$this->_isExtractingApp() && empty($plugin))) {
			$this->_extractValidation = false;
		}
		if (!empty($this->params['validation-domain'])) {
			$this->_validationDomain = $this->params['validation-domain'];
		}

		if ($this->_extractCore) {
			$this->_paths[] = CAKE;
			$this->_exclude = array_merge($this->_exclude, array(
				CAKE . 'Test',
				CAKE . 'Console' . DS . 'Templates'
			));
		}

		if (isset($this->params['output'])) {
			$this->_output = $this->params['output'];
		} elseif (isset($this->params['plugin'])) {
			$this->_output = $this->_paths[0] . DS . 'Locale';
		} else {
			$message = __d('cake_console', "What is the path you would like to output?\n[Q]uit", $this->_paths[0] . DS . 'Locale');
			while (true) {
				$response = $this->in($message, null, rtrim($this->_paths[0], DS) . DS . 'Locale');
				if (strtoupper($response) === 'Q') {
					$this->err(__d('cake_console', 'Extract Aborted'));
					return $this->_stop();
				} elseif ($this->_isPathUsable($response)) {
					$this->_output = $response . DS;
					break;
				} else {
					$this->err(__d('cake_console', 'The directory path you supplied was not found. Please try again.'));
				}
				$this->out();
			}
		}

		if (isset($this->params['merge'])) {
			$this->_merge = !(strtolower($this->params['merge']) === 'no');
		} else {
			$this->out();
			$response = $this->in(__d('cake_console', 'Would you like to merge all domain and category strings into the default.pot file?'), array('y', 'n'), 'n');
			$this->_merge = strtolower($response) === 'y';
		}

		if (empty($this->_files)) {
			$this->_searchFiles();
		}

		$this->_output = rtrim($this->_output, DS) . DS;
		if (!$this->_isPathUsable($this->_output)) {
			$this->err(__d('cake_console', 'The output directory %s was not found or writable.', $this->_output));
			return $this->_stop();
		}

		$this->_extract();
	}

/**
 * Add a translation to the internal translations property
 *
 * Takes care of duplicate translations
 *
 * @param string $category The category
 * @param string $domain The domain
 * @param string $msgid The message string
 * @param array $details The file and line references
 * @return void
 */
	protected function _addTranslation($category, $domain, $msgid, $details = array()) {
		$context = '';
		if (isset($details['msgctxt'])) {
			$context = $details['msgctxt'];
		}

		if (empty($this->_translations[$category][$domain][$msgid][$context])) {
			$this->_translations[$category][$domain][$msgid][$context] = array(
				'msgid_plural' => false,
			);
		}

		if (isset($details['msgid_plural'])) {
			$this->_translations[$category][$domain][$msgid][$context]['msgid_plural'] = $details['msgid_plural'];
		}
		if (isset($details['file'])) {
			$line = 0;
			if (isset($details['line'])) {
				$line = $details['line'];
			}
			$this->_translations[$category][$domain][$msgid][$context]['references'][$details['file']][] = $line;
		}
	}

/**
 * Extract text
 *
 * @return void
 */
	protected function _extract() {
		$this->out();
		$this->out();
		$this->out(__d('cake_console', 'Extracting...'));
		$this->hr();
		$this->out(__d('cake_console', 'Paths:'));
		foreach ($this->_paths as $path) {
			$this->out('   ' . $path);
		}
		$this->out(__d('cake_console', 'Output Directory: ') . $this->_output);
		$this->hr();
		$this->_extractTokens();
		$this->_extractValidationMessages();
		$this->_buildFiles();
		$this->_writeFiles();
		$this->_paths = $this->_files = $this->_storage = array();
		$this->_translations = $this->_tokens = array();
		$this->_extractValidation = true;
		$this->out();
		$this->out(__d('cake_console', 'Done.'));
	}

/**
 * Gets the option parser instance and configures it.
 *
 * @return ConsoleOptionParser
 */
	public function getOptionParser() {
		$parser = parent::getOptionParser();

		$parser->description(
			__d('cake_console', 'CakePHP Language String Extraction:')
		)->addOption('app', array(
			'help' => __d('cake_console', 'Directory where your application is located.')
		))->addOption('paths', array(
			'help' => __d('cake_console', 'Comma separated list of paths.')
		))->addOption('merge', array(
			'help' => __d('cake_console', 'Merge all domain and category strings into the default.po file.'),
			'choices' => array('yes', 'no')
		))->addOption('no-location', array(
			'boolean' => true,
			'default' => false,
			'help' => __d('cake_console', 'Do not write lines with locations'),
		))->addOption('output', array(
			'help' => __d('cake_console', 'Full path to output directory.')
		))->addOption('files', array(
			'help' => __d('cake_console', 'Comma separated list of files.')
		))->addOption('exclude-plugins', array(
			'boolean' => true,
			'default' => true,
			'help' => __d('cake_console', 'Ignores all files in plugins if this command is run inside from the same app directory.')
		))->addOption('plugin', array(
			'help' => __d('cake_console', 'Extracts tokens only from the plugin specified and puts the result in the plugin\'s Locale directory.')
		))->addOption('ignore-model-validation', array(
			'boolean' => true,
			'default' => false,
			'help' => __d('cake_console', 'Ignores validation messages in the $validate property.' .
				' If this flag is not set and the command is run from the same app directory,' .
				' all messages in model validation rules will be extracted as tokens.'
			)
		))->addOption('validation-domain', array(
			'help' => __d('cake_console', 'If set to a value, the localization domain to be used for model validation messages.')
		))->addOption('exclude', array(
			'help' => __d('cake_console', 'Comma separated list of directories to exclude.' .
				' Any path containing a path segment with the provided values will be skipped. E.g. test,vendors'
			)
		))->addOption('overwrite', array(
			'boolean' => true,
			'default' => false,
			'help' => __d('cake_console', 'Always overwrite existing .pot files.')
		))->addOption('extract-core', array(
			'help' => __d('cake_console', 'Extract messages from the CakePHP core libs.'),
			'choices' => array('yes', 'no')
		));

		return $parser;
	}

/**
 * Extract tokens out of all files to be processed
 *
 * @return void
 */
	protected function _extractTokens() {
		foreach ($this->_files as $file) {
			$this->_file = $file;
			$this->out(__d('cake_console', 'Processing %s...', $file), 1, Shell::VERBOSE);

			$code = file_get_contents($file);
			$allTokens = token_get_all($code);

			$this->_tokens = array();
			foreach ($allTokens as $token) {
				if (!is_array($token) || ($token[0] !== T_WHITESPACE && $token[0] !== T_INLINE_HTML)) {
					$this->_tokens[] = $token;
				}
			}
			unset($allTokens);
			$this->_parse('__', array('singular'));
			$this->_parse('__n', array('singular', 'plural'));
			$this->_parse('__d', array('domain', 'singular'));
			$this->_parse('__c', array('singular', 'category'));
			$this->_parse('__dc', array('domain', 'singular', 'category'));
			$this->_parse('__dn', array('domain', 'singular', 'plural'));
			$this->_parse('__dcn', array('domain', 'singular', 'plural', 'count', 'category'));

			$this->_parse('__x', array('context', 'singular'));
			$this->_parse('__xn', array('context', 'singular', 'plural'));
			$this->_parse('__dx', array('domain', 'context', 'singular'));
			$this->_parse('__dxc', array('domain', 'context', 'singular', 'category'));
			$this->_parse('__dxn', array('domain', 'context', 'singular', 'plural'));
			$this->_parse('__dxcn', array('domain', 'context', 'singular', 'plural', 'count', 'category'));
			$this->_parse('__xc', array('context', 'singular', 'category'));

		}
	}

/**
 * Parse tokens
 *
 * @param string $functionName Function name that indicates translatable string (e.g: '__')
 * @param array $map Array containing what variables it will find (e.g: category, domain, singular, plural)
 * @return void
 */
	protected function _parse($functionName, $map) {
		$count = 0;
		$categories = array('LC_ALL', 'LC_COLLATE', 'LC_CTYPE', 'LC_MONETARY', 'LC_NUMERIC', 'LC_TIME', 'LC_MESSAGES');
		$tokenCount = count($this->_tokens);

		while (($tokenCount - $count) > 1) {
			$countToken = $this->_tokens[$count];
			$firstParenthesis = $this->_tokens[$count + 1];
			if (!is_array($countToken)) {
				$count++;
				continue;
			}

			list($type, $string, $line) = $countToken;
			if (($type == T_STRING) && ($string === $functionName) && ($firstParenthesis === '(')) {
				$position = $count;
				$depth = 0;

				while (!$depth) {
					if ($this->_tokens[$position] === '(') {
						$depth++;
					} elseif ($this->_tokens[$position] === ')') {
						$depth--;
					}
					$position++;
				}

				$mapCount = count($map);
				$strings = $this->_getStrings($position, $mapCount);

				if ($mapCount === count($strings)) {
					extract(array_combine($map, $strings));
					$category = isset($category) ? $category : 6;
					$category = (int)$category;
					$categoryName = $categories[$category];

					$domain = isset($domain) ? $domain : 'default';
					$details = array(
						'file' => $this->_file,
						'line' => $line,
					);
					if (isset($plural)) {
						$details['msgid_plural'] = $plural;
					}
					if (isset($context)) {
						$details['msgctxt'] = $context;
					}
					// Skip LC_TIME files as we use a special file format for them.
					if ($categoryName !== 'LC_TIME') {
						$this->_addTranslation($categoryName, $domain, $singular, $details);
					}
				} elseif (!is_array($this->_tokens[$count - 1]) || $this->_tokens[$count - 1][0] != T_FUNCTION) {
					$this->_markerError($this->_file, $line, $functionName, $count);
				}
			}
			$count++;
		}
	}

/**
 * Looks for models in the application and extracts the validation messages
 * to be added to the translation map
 *
 * @return void
 */
	protected function _extractValidationMessages() {
		if (!$this->_extractValidation) {
			return;
		}

		$plugins = array(null);
		if (empty($this->params['exclude-plugins'])) {
			$plugins = array_merge($plugins, App::objects('plugin', null, false));
		}
		foreach ($plugins as $plugin) {
			$this->_extractPluginValidationMessages($plugin);
		}
	}

/**
 * Extract validation messages from application or plugin models
 *
 * @param string $plugin Plugin name or `null` to process application models
 * @return void
 */
	protected function _extractPluginValidationMessages($plugin = null) {
		App::uses('AppModel', 'Model');
		if (!empty($plugin)) {
			if (!CakePlugin::loaded($plugin)) {
				return;
			}
			App::uses($plugin . 'AppModel', $plugin . '.Model');
			$plugin = $plugin . '.';
		}
		$models = App::objects($plugin . 'Model', null, false);

		foreach ($models as $model) {
			App::uses($model, $plugin . 'Model');
			$reflection = new ReflectionClass($model);
			if (!$reflection->isSubClassOf('Model')) {
				continue;
			}
			$properties = $reflection->getDefaultProperties();
			$validate = $properties['validate'];
			if (empty($validate)) {
				continue;
			}

			$file = $reflection->getFileName();
			$domain = $this->_validationDomain;
			if (!empty($properties['validationDomain'])) {
				$domain = $properties['validationDomain'];
			}
			foreach ($validate as $field => $rules) {
				$this->_processValidationRules($field, $rules, $file, $domain);
			}
		}
	}

/**
 * Process a validation rule for a field and looks for a message to be added
 * to the translation map
 *
 * @param string $field the name of the field that is being processed
 * @param array $rules the set of validation rules for the field
 * @param string $file the file name where this validation rule was found
 * @param string $domain default domain to bind the validations to
 * @param string $category the translation category
 * @return void
 */
	protected function _processValidationRules($field, $rules, $file, $domain, $category = 'LC_MESSAGES') {
		if (!is_array($rules)) {
			return;
		}

		$dims = Hash::dimensions($rules);
		if ($dims === 1 || ($dims === 2 && isset($rules['message']))) {
			$rules = array($rules);
		}

		foreach ($rules as $rule => $validateProp) {
			$msgid = null;
			if (isset($validateProp['message'])) {
				if (is_array($validateProp['message'])) {
					$msgid = $validateProp['message'][0];
				} else {
					$msgid = $validateProp['message'];
				}
			} elseif (is_string($rule)) {
				$msgid = $rule;
			}
			if ($msgid) {
				$msgid = $this->_formatString(sprintf("'%s'", $msgid));
				$details = array(
					'file' => $file,
					'line' => 'validation for field ' . $field
				);
				$this->_addTranslation($category, $domain, $msgid, $details);
			}
		}
	}

/**
 * Build the translate template file contents out of obtained strings
 *
 * @return void
 */
	protected function _buildFiles() {
		$paths = $this->_paths;
		$paths[] = realpath(APP) . DS;

		usort($paths, function ($a, $b) {
			return strlen($b) - strlen($a);
		});

		foreach ($this->_translations as $category => $domains) {
			foreach ($domains as $domain => $translations) {
				foreach ($translations as $msgid => $contexts) {
					foreach ($contexts as $context => $details) {
						$plural = $details['msgid_plural'];
						$header = '';
						if (empty($this->params['no-location'])) {
							$files = $details['references'];
							$occurrences = array();
							foreach ($files as $file => $lines) {
								$lines = array_unique($lines);
								$occurrences[] = $file . ':' . implode(';', $lines);
							}
							$occurrences = implode("\n#: ", $occurrences);
							$header = '#: ' . str_replace(DS, '/', str_replace($paths, '', $occurrences)) . "\n";
						}

						$sentence = '';
						if ($context) {
							$sentence .= "msgctxt \"{$context}\"\n";
						}
						if ($plural === false) {
							$sentence .= "msgid \"{$msgid}\"\n";
							$sentence .= "msgstr \"\"\n\n";
						} else {
							$sentence .= "msgid \"{$msgid}\"\n";
							$sentence .= "msgid_plural \"{$plural}\"\n";
							$sentence .= "msgstr[0] \"\"\n";
							$sentence .= "msgstr[1] \"\"\n\n";
						}

						$this->_store($category, $domain, $header, $sentence);
						if (($category !== 'LC_MESSAGES' || $domain !== 'default') && $this->_merge) {
							$this->_store('LC_MESSAGES', 'default', $header, $sentence);
						}
					}
				}
			}
		}
	}

/**
 * Prepare a file to be stored
 *
 * @param string $category The category
 * @param string $domain The domain
 * @param string $header The header content.
 * @param string $sentence The sentence to store.
 * @return void
 */
	protected function _store($category, $domain, $header, $sentence) {
		if (!isset($this->_storage[$category])) {
			$this->_storage[$category] = array();
		}
		if (!isset($this->_storage[$category][$domain])) {
			$this->_storage[$category][$domain] = array();
		}
		if (!isset($this->_storage[$category][$domain][$sentence])) {
			$this->_storage[$category][$domain][$sentence] = $header;
		} else {
			$this->_storage[$category][$domain][$sentence] .= $header;
		}
	}

/**
 * Write the files that need to be stored
 *
 * @return void
 */
	protected function _writeFiles() {
		$overwriteAll = false;
		if (!empty($this->params['overwrite'])) {
			$overwriteAll = true;
		}
		foreach ($this->_storage as $category => $domains) {
			foreach ($domains as $domain => $sentences) {
				$output = $this->_writeHeader();
				foreach ($sentences as $sentence => $header) {
					$output .= $header . $sentence;
				}

				$filename = $domain . '.pot';
				if ($category === 'LC_MESSAGES') {
					$File = new File($this->_output . $filename);
				} else {
					new Folder($this->_output . $category, true);
					$File = new File($this->_output . $category . DS . $filename);
				}
				$response = '';
				while ($overwriteAll === false && $File->exists() && strtoupper($response) !== 'Y') {
					$this->out();
					$response = $this->in(
						__d('cake_console', 'Error: %s already exists in this location. Overwrite? [Y]es, [N]o, [A]ll', $filename),
						array('y', 'n', 'a'),
						'y'
					);
					if (strtoupper($response) === 'N') {
						$response = '';
						while (!$response) {
							$response = $this->in(__d('cake_console', "What would you like to name this file?"), null, 'new_' . $filename);
							$File = new File($this->_output . $response);
							$filename = $response;
						}
					} elseif (strtoupper($response) === 'A') {
						$overwriteAll = true;
					}
				}
				$File->write($output);
				$File->close();
			}
		}
	}

/**
 * Build the translation template header
 *
 * @return string Translation template header
 */
	protected function _writeHeader() {
		$output = "# LANGUAGE translation of CakePHP Application\n";
		$output .= "# Copyright YEAR NAME <EMAIL@ADDRESS>\n";
		$output .= "#\n";
		$output .= "#, fuzzy\n";
		$output .= "msgid \"\"\n";
		$output .= "msgstr \"\"\n";
		$output .= "\"Project-Id-Version: PROJECT VERSION\\n\"\n";
		$output .= "\"PO-Revision-Date: YYYY-mm-DD HH:MM+ZZZZ\\n\"\n";
		$output .= "\"Last-Translator: NAME <EMAIL@ADDRESS>\\n\"\n";
		$output .= "\"Language-Team: LANGUAGE <EMAIL@ADDRESS>\\n\"\n";
		$output .= "\"MIME-Version: 1.0\\n\"\n";
		$output .= "\"Content-Type: text/plain; charset=utf-8\\n\"\n";
		$output .= "\"Content-Transfer-Encoding: 8bit\\n\"\n";
		$output .= "\"Plural-Forms: nplurals=INTEGER; plural=EXPRESSION;\\n\"\n\n";
		return $output;
	}

/**
 * Get the strings from the position forward
 *
 * @param int &$position Actual position on tokens array
 * @param int $target Number of strings to extract
 * @return array Strings extracted
 */
	protected function _getStrings(&$position, $target) {
		$strings = array();
		$count = count($strings);
		while ($count < $target && ($this->_tokens[$position] === ',' || $this->_tokens[$position][0] == T_CONSTANT_ENCAPSED_STRING || $this->_tokens[$position][0] == T_LNUMBER)) {
			$count = count($strings);
			if ($this->_tokens[$position][0] == T_CONSTANT_ENCAPSED_STRING && $this->_tokens[$position + 1] === '.') {
				$string = '';
				while ($this->_tokens[$position][0] == T_CONSTANT_ENCAPSED_STRING || $this->_tokens[$position] === '.') {
					if ($this->_tokens[$position][0] == T_CONSTANT_ENCAPSED_STRING) {
						$string .= $this->_formatString($this->_tokens[$position][1]);
					}
					$position++;
				}
				$strings[] = $string;
			} elseif ($this->_tokens[$position][0] == T_CONSTANT_ENCAPSED_STRING) {
				$strings[] = $this->_formatString($this->_tokens[$position][1]);
			} elseif ($this->_tokens[$position][0] == T_LNUMBER) {
				$strings[] = $this->_tokens[$position][1];
			}
			$position++;
		}
		return $strings;
	}

/**
 * Format a string to be added as a translatable string
 *
 * @param string $string String to format
 * @return string Formatted string
 */
	protected function _formatString($string) {
		$quote = substr($string, 0, 1);
		$string = substr($string, 1, -1);
		if ($quote === '"') {
			$string = stripcslashes($string);
		} else {
			$string = strtr($string, array("\\'" => "'", "\\\\" => "\\"));
		}
		$string = str_replace("\r\n", "\n", $string);
		return addcslashes($string, "\0..\37\\\"");
	}

/**
 * Indicate an invalid marker on a processed file
 *
 * @param string $file File where invalid marker resides
 * @param int $line Line number
 * @param string $marker Marker found
 * @param int $count Count
 * @return void
 */
	protected function _markerError($file, $line, $marker, $count) {
		$this->err(__d('cake_console', "Invalid marker content in %s:%s\n* %s(", $file, $line, $marker));
		$count += 2;
		$tokenCount = count($this->_tokens);
		$parenthesis = 1;

		while ((($tokenCount - $count) > 0) && $parenthesis) {
			if (is_array($this->_tokens[$count])) {
				$this->err($this->_tokens[$count][1], false);
			} else {
				$this->err($this->_tokens[$count], false);
				if ($this->_tokens[$count] === '(') {
					$parenthesis++;
				}

				if ($this->_tokens[$count] === ')') {
					$parenthesis--;
				}
			}
			$count++;
		}
		$this->err("\n", true);
	}

/**
 * Search files that may contain translatable strings
 *
 * @return void
 */
	protected function _searchFiles() {
		$pattern = false;
		if (!empty($this->_exclude)) {
			$exclude = array();
			foreach ($this->_exclude as $e) {
				if (DS !== '\\' && $e[0] !== DS) {
					$e = DS . $e;
				}
				$exclude[] = preg_quote($e, '/');
			}
			$pattern = '/' . implode('|', $exclude) . '/';
		}
		foreach ($this->_paths as $i => $path) {
			$this->_paths[$i] = realpath($path) . DS;
			$Folder = new Folder($this->_paths[$i]);
			$files = $Folder->findRecursive('.*\.(php|ctp|thtml|inc|tpl)', true);
			if (!empty($pattern)) {
				$files = preg_grep($pattern, $files, PREG_GREP_INVERT);
				$files = array_values($files);
			}
			$this->_files = array_merge($this->_files, $files);
		}
		$this->_files = array_unique($this->_files);
	}

/**
 * Returns whether this execution is meant to extract string only from directories in folder represented by the
 * APP constant, i.e. this task is extracting strings from same application.
 *
 * @return bool
 */
	protected function _isExtractingApp() {
		return $this->_paths === array(APP);
	}

/**
 * Checks whether or not a given path is usable for writing.
 *
 * @param string $path Path to folder
 * @return bool true if it exists and is writable, false otherwise
 */
	protected function _isPathUsable($path) {
		return is_dir($path) && is_writable($path);
	}
}
