<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         DebugKit 0.1
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

App::uses('ToolbarHelper', 'DebugKit.View/Helper');
App::uses('Security', 'Utility');

/**
 * Html Toolbar Helper
 *
 * Injects the toolbar elements into HTML layouts.
 * Contains helper methods for
 *
 *
 * @since         DebugKit 0.1
 */
class HtmlToolbarHelper extends ToolbarHelper {

/**
 * helpers property
 *
 * @var array
 */
	public $helpers = array('Html', 'Form');

/**
 * settings property
 *
 * @var array
 */
	public $settings = array('format' => 'html', 'forceEnable' => false);

/**
 * Recursively goes through an array and makes neat HTML out of it.
 *
 * @param mixed $values Array to make pretty.
 * @param integer $openDepth Depth to add open class
 * @param integer $currentDepth current depth.
 * @param boolean $doubleEncode
 * @return string
 */
	public function makeNeatArray($values, $openDepth = 0, $currentDepth = 0, $doubleEncode = false) {
		static $printedObjects = null;
		if ($currentDepth === 0) {
			$printedObjects = new SplObjectStorage();
		}
		$className = "neat-array depth-$currentDepth";
		if ($openDepth > $currentDepth) {
			$className .= ' expanded';
		}
		$nextDepth = $currentDepth + 1;
		$out = "<ul class=\"$className\">";
		if (!is_array($values)) {
			if (is_bool($values)) {
				$values = array($values);
			}
			if ($values === null) {
				$values = array(null);
			}
		}
		if (empty($values)) {
			$values[] = '(empty)';
		}
		foreach ($values as $key => $value) {
			$out .= '<li><strong>' . $key . '</strong>';
			if (is_array($value) && count($value) > 0) {
				$out .= '(array)';
			} elseif (is_object($value)) {
				$out .= '(object)';
			}
			if ($value === null) {
				$value = '(null)';
			}
			if ($value === false) {
				$value = '(false)';
			}
			if ($value === true) {
				$value = '(true)';
			}
			if (empty($value) && $value != 0) {
				$value = '(empty)';
			}
			if ($value instanceof Closure) {
				$value = 'function';
			}

			$isObject = is_object($value);
			if ($isObject && $printedObjects->contains($value)) {
				$isObject = false;
				$value = ' - recursion';
			}

			if ($isObject) {
				$printedObjects->attach($value);
			}

			if (
				(
				$value instanceof ArrayAccess ||
				$value instanceof Iterator ||
				is_array($value) ||
				$isObject
				) && !empty($value)
			) {
				$out .= $this->makeNeatArray($value, $openDepth, $nextDepth, $doubleEncode);
			} else {
				$out .= h($value, $doubleEncode);
			}
			$out .= '</li>';
		}
		$out .= '</ul>';
		return $out;
	}

/**
 * Create an HTML message
 *
 * @param string $label label content
 * @param string $message message content
 * @return string
 */
	public function message($label, $message) {
		return sprintf('<p><strong>%s</strong> %s</p>', $label, $message);
	}

/**
 * Start a panel.
 * Make a link and anchor.
 *
 * @param $title
 * @param $anchor
 * @return string
 */
	public function panelStart($title, $anchor) {
		$link = $this->Html->link($title, '#' . $anchor);
		return $link;
	}

/**
 * Create a table.
 *
 * @param array $rows Rows to make.
 * @param array $headers Optional header row.
 * @return string
 */
	public function table($rows, $headers = array()) {
		$out = '<table class="debug-table">';
		if (!empty($headers)) {
			$out .= $this->Html->tableHeaders($headers);
		}
		$out .= $this->Html->tableCells($rows, array('class' => 'odd'), array('class' => 'even'), false, false);
		$out .= '</table>';
		return $out;
	}

/**
 * Send method
 *
 * @return void
 */
	public function send() {
		if (!$this->settings['forceEnable'] && Configure::read('debug') == 0) {
			return;
		}
		$view = $this->_View;
		$head = '';
		if (isset($view->viewVars['debugToolbarCss']) && !empty($view->viewVars['debugToolbarCss'])) {
			$head .= $this->Html->css($view->viewVars['debugToolbarCss']);
		}

		$js = sprintf('window.DEBUGKIT_JQUERY_URL = "%s";', $this->webroot('/debug_kit/js/jquery.js'));
		$head .= $this->Html->scriptBlock($js);

		if (isset($view->viewVars['debugToolbarJavascript'])) {
			foreach ($view->viewVars['debugToolbarJavascript'] as $script) {
				if ($script) {
					$head .= $this->Html->script($script);
				}
			}
		}
		if (preg_match('#</head>#', $view->output)) {
			$view->output = preg_replace('#</head>#', $head . "\n</head>", $view->output, 1);
		}
		$toolbar = $view->element('debug_toolbar', array('disableTimer' => true), array('plugin' => 'DebugKit'));
		if (preg_match('#</body>#', $view->output)) {
			$view->output = preg_replace('#</body>#', $toolbar . "\n</body>", $view->output, 1);
		}
	}

/**
 * Generates a SQL explain link for a given query
 *
 * @param string $sql SQL query string you want an explain link for.
 * @param $connection
 * @return string Rendered Html link or '' if the query is not a select/describe
 */
	public function explainLink($sql, $connection) {
		if (!preg_match('/^[\s()]*SELECT/i', $sql)) {
			return '';
		}
		$hash = Security::hash($sql . $connection, 'sha1', true);
		$url = array(
			'plugin' => 'debug_kit',
			'controller' => 'toolbar_access',
			'action' => 'sql_explain'
		);
		foreach (Router::prefixes() as $prefix) {
			$url[$prefix] = false;
		}
		$this->explainLinkUid = (isset($this->explainLinkUid) ? $this->explainLinkUid + 1 : 0);
		$uid = $this->explainLinkUid . '_' . rand(0, 10000);
		$form = $this->Form->create('log', array('url' => $url, 'id' => "logForm{$uid}"));
		$form .= $this->Form->hidden('log.ds', array('id' => "logDs{$uid}", 'value' => $connection));
		$form .= $this->Form->hidden('log.sql', array('id' => "logSql{$uid}", 'value' => $sql));
		$form .= $this->Form->hidden('log.hash', array('id' => "logHash{$uid}", 'value' => $hash));
		$form .= $this->Form->submit(__d('debug_kit', 'Explain'), array(
			'div' => false,
			'class' => 'sql-explain-link'
		));
		$form .= $this->Form->end();
		return $form;
	}

}
