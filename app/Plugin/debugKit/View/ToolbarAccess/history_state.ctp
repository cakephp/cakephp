<?php
/**
 * Toolbar history state view.
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
 * @since         DebugKit 1.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

$panels = array();
foreach ($toolbarState as $panelName => $panel) {
	if (!empty($panel) && !empty($panel['elementName'])) {
		$panels[$panelName] = $this->element($panel['elementName'], array(
			'content' => $panel['content']
		), array(
			'plugin' => Inflector::camelize($panel['plugin'])
		));
	}
}
echo json_encode($panels);
Configure::write('debug', 0);
