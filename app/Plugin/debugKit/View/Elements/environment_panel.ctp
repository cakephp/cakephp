<?php
/**
 * Environment Panel Element
 *
 * Shows information about the current app environment
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
?>
<h2><?php echo __d('debug_kit', 'App Constants'); ?></h2>
<?php
	if (!empty($content['app'])) {
		$cakeRows = array();
		foreach ($content['app'] as $key => $val) {
			$cakeRows[] = array(
				$key,
				$val
			);
		}
		$headers = array('Constant', 'Value');
		echo $this->Toolbar->table($cakeRows, $headers, array('title' => 'Application Environment Vars'));
	} else {
		echo "No application environment available.";
	} ?>

<h2><?php echo __d('debug_kit', 'CakePHP Constants'); ?></h2>
<?php
	if (!empty($content['cake'])) {
		$cakeRows = array();
		foreach ($content['cake'] as $key => $val) {
			$cakeRows[] = array(
				$key,
				$val
			);
		}
		$headers = array('Constant', 'Value');
		echo $this->Toolbar->table($cakeRows, $headers, array('title' => 'CakePHP Environment Vars'));
	} else {
		echo "CakePHP environment unavailable.";
	} ?>

<h2><?php echo __d('debug_kit', 'PHP Environment');?></h2>
<?php
	$headers = array('Environment Variable', 'Value');

	if (!empty($content['php'])) {
		$phpRows = array();
		foreach ($content['php'] as $key => $val) {
			$phpRows[] = array(
				Inflector::humanize(strtolower($key)),
				$val
			);
		}
		echo $this->Toolbar->table($phpRows, $headers, array('title' => 'CakePHP Environment Vars'));
	} else {
		echo "PHP environment unavailable.";
	}

	if (isset($content['hidef'])) {
		echo '<h2>' . __d('debug_kit', 'Hidef Environment') . '</h2>';
		if (!empty($content['hidef'])) {
			$cakeRows = array();
			foreach ($content['hidef'] as $key => $val) {
				$cakeRows[] = array(
					$key,
					$val
				);
			}
			$headers = array('Constant', 'Value');
			echo $this->Toolbar->table($cakeRows, $headers, array('title' => 'Hidef Environment Vars'));
		} else {
			echo "No Hidef environment available.";
		}
	}
