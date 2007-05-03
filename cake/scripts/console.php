<?php
/* SVN FILE: $Id$ */
/**
 * Short description for file.
 *
 * Long description for file
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) :  Rapid Development Framework <http://www.cakephp.org/>
 * Copyright 2005-2007, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright 2005-2007, Cake Software Foundation, Inc.
 * @link				http://www.cakefoundation.org/projects/info/cakephp CakePHP(tm) Project
 * @package			cake
 * @subpackage		cake.cake.scripts
 * @since			CakePHP(tm) v 1.2.0.4604
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/mit-license.php The MIT License
 */
/**
 * @package		cake
 * @subpackage	cake.cake.scritps
 */
class ConsoleScipt extends CakeScript {
	var $ignoreList = array(T_WHITESPACE, T_OPEN_TAG, T_CLOSE_TAG);
	var $returnList = array(T_FOREACH, T_DO, T_WHILE, T_FOR, T_IF, T_RETURN,
									T_CLASS, T_FUNCTION, T_INTERFACE, T_PRINT, T_ECHO,
									T_COMMENT, T_UNSET, T_INCLUDE, T_REQUIRE, T_INCLUDE_ONCE,
									T_REQUIRE_ONCE,T_TRY);
	var $continueList = array(T_VARIABLE, T_STRING, T_NEW, T_EXTENDS, T_IMPLEMENTS,
									T_OBJECT_OPERATOR, T_DOUBLE_COLON, T_INSTANCEOF, T_CATCH, T_ELSE,
									T_AS, T_LNUMBER, T_DNUMBER, T_CONSTANT_ENCAPSED_STRING, T_ENCAPSED_AND_WHITESPACE,
									T_CHARACTER, T_ARRAY, T_DOUBLE_ARROW, T_CONST, T_PUBLIC,
									T_PROTECTED, T_PRIVATE, T_ABSTRACT, T_STATIC, T_VAR,
									T_INC, T_DEC, T_SL, T_SL_EQUAL, T_SR,
									T_SR_EQUAL, T_IS_EQUAL, T_IS_IDENTICAL, T_IS_GREATER_OR_EQUAL, T_IS_SMALLER_OR_EQUAL,
									T_BOOLEAN_OR, T_LOGICAL_OR, T_BOOLEAN_AND, T_LOGICAL_AND, T_LOGICAL_XOR,
									T_MINUS_EQUAL, T_PLUS_EQUAL, T_MUL_EQUAL, T_DIV_EQUAL, T_MOD_EQUAL,
									T_XOR_EQUAL, T_AND_EQUAL, T_OR_EQUAL, T_FUNC_C, T_CLASS_C,
									T_LINE, T_FILE);
	function main() {
		$models = @loadModels();
		foreach ($models as $model) {
			$class = Inflector::camelize(r('.php', '', $model));
			$models[$model] = $class;
			@${$class} =& new $class();
		}

		while (true) {
			$command = trim($this->in(''));

			switch($command) {
				case 'quit':
				case 'exit':
					return true;
				break;
				case 'models':
					$this->out('Model classes:');
					$this->out('--------------');

					foreach ($models as $model) {
						$this->out(" - {$model}");
					}
				break;
				default:
					$tokens = token_get_all($command);
					$semicolon = FALSE;
					$return = TRUE;
					$ignore = FALSE;
					$braces = array();
					$methods = array();
					$ws_t = array();
					$command = '';

					foreach ($tokens as $idx => $token) {
						// Parse the tokens
						if(is_array($token)) {
							if(in_array($token[0], $this->ignoreList)) {
								$ignore = TRUE;
							} elseif(in_array($token[0], $this->returnList)) {
								$return = FALSE;
							} elseif(in_array($token[0], $this->continueList)) {
								// everything is okay
							} else {
								$error = sprintf(">> Unknown tag: %d (%s): %s".PHP_EOL, $token[0], token_name($token[0]), $token[1]);
							}
							if($ignore == TRUE) {
								$command .= $token[1] . " ";
								$ws_t[] = array("token" => $token[0], "value" => $token[1]);
							}
						} else {
							$ws_t[] = array("token" => $token, "value" => '');
							$last_idx = count($ws_t) - 1;

							switch ($token) {
								case '(':
								break;
								case '{':
								break;
								case ')':
								break;
								case '}':
								break;
							}
						}
					}
			}
		}
	}
}
	function fatal_error_handler($buffer) {
		if(ereg("(error</b>:)(.+)(<br)", $buffer, $regs) ) {
			$err = preg_replace("/<.*?>/", "", $regs[2]);
			error_log($err);
			return "ERROR CAUGHT check log file";
		}
		return $buffer;
	}

	function handle_error ($errno, $errstr, $errfile, $errline) {
		error_log("$errstr in $errfile on line $errline");
		if($errno == FATAL || $errno == ERROR){
			ob_end_flush();
			echo "ERROR CAUGHT check log file";
			exit(0);
		}
	}
?>