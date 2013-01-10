<?php
/**
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       Cake.Test.test_app.View.Json
 * @since         CakePHP(tm) v 2.1.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

$paging = isset($this->Paginator->options['url']) ? $this->Paginator->options['url'] : null;

$formatted = array(
	'user' => $user['User']['username'],
	'list' => array(),
	'paging' => $paging,
);
foreach ($user['Item'] as $item) {
	$formatted['list'][] = $item['name'];
}

echo json_encode($formatted);
