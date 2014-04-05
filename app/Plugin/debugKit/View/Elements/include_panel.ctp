<?php
/**
 * Included Files Element
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
 * @since         DebugKit 2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 **/
?>
<h2> <?php echo __d('debug_kit', 'Included Files'); ?></h2>

<h4>Include Paths</h4>
<?php
	foreach ($content['paths'] as $i => $path) {
		if (strstr($path, CAKE)) {
			$content['paths'][$i] = '-> ' . $path;
			break;
		}
	}
	echo $this->Toolbar->makeNeatArray(array_filter($content['paths']));
	unset($content['paths']);
?>

<h4>Included Files</h4>
<?php echo $this->Toolbar->makeNeatArray($content);