<?php
/**
 * Plural Forms Parser
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
 * @package       Cake.I18n
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */


/**
 * PluralForms handles the Plural-Forms header in PO/MO files.
 * This class includes the bare minimum to parse and execute plural formulas.
 *
 * @package       Cake.I18n
 */
class PluralForms {

/**
 * Operators used in formulas
 *
 * @var string
 */
	protected $_operators;

/**
 * Generates a C-like operator function with two operands.
 *
 * @param string $op A PHP operator.
 * @return string a function that applies its two parameters to $op
 *                and returns the result casted to int.
 */
	protected function _simpleOperator($op) {
		return create_function('$a, $b', "return (int)(\$a $op \$b);");
	}

/**
 * Initializes attributes.
 */
	public function __construct() {
		$this->_operators = array(
			'(' => array('prec' => 0, 'func' => null),
			')' => array('prec' => 0, 'func' => null),
			'%' => array(
				'prec' => 2,
				'nargs' => 2,
				'func' => function ($a, $b) {
					if ($b == 0) {
						throw new CakeException(__d('cake_dev', 'Division by zero in plural formula '.
						                                        'of the translation file header.'));
					} else {
						return $a % $b;
					}
				},
			),
			'<' => array('prec' => 5),
			'>' => array('prec' => 5),
			'<=' => array('prec' => 5),
			'>=' => array('prec' => 5),
			'==' => array('prec' => 6),
			'!=' => array('prec' => 6),
			'&&' => array('prec' => 10),
			'||' => array('prec' => 11),
			':' => array('prec' => 12, 'func' => null),
			'?' => array(
				'prec' => 12,
				'nargs' => 3,
				'func' => function($a, $b, $c) { return $a ? $b : $c; },
			),
	        );
		/* Complete operators definitions with default values */
		foreach ($this->_operators as $op => &$definition) {
			if (!array_key_exists('func', $definition)) {
				$definition['func'] = $this->_simpleOperator($op);
				$definition['nargs'] = 2;
			}
		}

		/* Make the array regex-friendly. We want to have '<=' before '<'
		 * and the like for the regex in _tokenize() */
		uksort($this->_operators, function($a, $b) { return strlen($b) - strlen($a); });
	}

/**
 * Tokenizes a plural formula.
 *
 * @param string $expr A plural formula.
 * @return array the plural formula tokenized.
 */
	protected function _tokenize($expr) {
		$ops = implode('|', array_map('preg_quote', array_keys($this->_operators)));
		preg_match_all("/ *($ops|n|[0-9]+|[^ ]+) */", $expr, $matches);
		return $matches[1];
	}

/**
 * Extracts the plural formula from Plural-Forms header.
 *
 * @param string $header A Plural-Forms header, without the "Plural-Forms:" prefix.
 * @return string the plural formula.
 */
	protected function _extractPluralExpr($header) {
		$parts = explode(';', $header);
		if (!isset($parts[1])) {
			throw new CakeException(__d('cake_dev', 'Syntax error in the Plural-Forms '.
			                                        'header of the translation file.'));
		}
		$pluralFormula = $parts[1];

		$parts = explode('=', $pluralFormula, 2);
		if (!isset($parts[1])) {
			throw new CakeException(__d('cake_dev', 'Syntax error in the Plural-Forms '.
			                                        'header of the translation file.'));
		}
		return $parts[1];
	}

/**
 * Converts a tokenized expression from infix to postfix form,
 * using the shunting-yard algorithm.
 *
 * @param array $tokens Tokenized expression.
 * @return array postfixed expression.
 */
	protected function _infixToPostfix($tokens) {
		$stack = array();
		$postfix = array();
		foreach ($tokens as $token) {
			if ($token == ')') {
				while ($stack && end($stack) != '(') {
					array_push($postfix, array_pop($stack));
				}
				array_pop($stack);
			} elseif (isset($this->_operators[$token])) {
				while ($stack && end($stack) != '(' && $token != '('
				       && $this->_operators[end($stack)]['prec'] < $this->_operators[$token]['prec']) {
					array_push($postfix, array_pop($stack));
				}
				array_push($stack, $token);
			} else {
				array_push($postfix, $token);
			}
		}
		while ($stack) {
			array_push($postfix, array_pop($stack));
		}
		return $postfix;
	}

/**
 * Parses a PO/MO file Plural-Forms header.
 * Returns a parsed formula that can be given to PluralForms::getPlural().
 *
 * @param string $header The header contents, without the "Plural-Forms:" prefix.
 * @return string a parsed plural formula.
 * @throws CakeException if $header is malformed.
 */
	public function parsePluralForms($header) {
		$expr = $this->_extractPluralExpr($header);
		$tokens = $this->_tokenize($expr);
		return $this->_infixToPostfix($tokens);
	}

/**
 * Executes an operator function. The function takes its parameters on
 * the stack and returns the result on the stack.
 *
 * @param string $op The operator.
 * @param array $stack The context stack.
 */
	protected function _callOperator($op, &$stack) {
		$nargs = $this->_operators[$op]['nargs'];
		if (count($stack) < $nargs) {
			throw new CakeException(__d('cake_dev', 'Syntax error in plural formula '.
			                                        'of the translation file header.'));
		}
		$args = array();
		for ($i = 1; $i <= $nargs; $i++) {
			$args[] = array_pop($stack);
		}
		$result = call_user_func_array(
			$this->_operators[$op]['func'],
			array_reverse($args)
		);
		array_push($stack, $result);
	}

/**
 * Executes a parsed plural formula with the given value for n.
 * Returns the plural index returned by the plural formula.
 *
 * @param string $postfix A plural formula as returned by PluralForms::parsePluralForms().
 * @param int $n The value for n in the plural formula.
 * @return string the result of the formula
 * @throws CakeException if $postfix has a syntax error.
 */
	public function getPlural($postfix, $n) {
		$stack = array();
		foreach ($postfix as $token) {
			if (isset($this->_operators[$token])) {
				if (is_callable($this->_operators[$token]['func'])) {
					$this->_callOperator($token, $stack);
				}
			} else {
				if ($token == 'n') {
					$result = $n;
				} else {
					$result = $token;
				}
				array_push($stack, $result);
			}
		}
		if (count($stack) != 1) {
			throw new CakeException(__d('cake_dev', 'Syntax error in plural formula '.
			                                        'of the translation file header.'));
		}
		return $stack[0];
	}
}
