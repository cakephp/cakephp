<?php
/**
 * Request Panel Element
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
 * @since         DebugKit 0.1
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
?>
<h2> <?php echo __d('debug_kit', 'Request'); ?></h2>

<h4>Cake Params</h4>
<?php echo $this->Toolbar->makeNeatArray($content['params']); ?>

<h4>Post data</h4>
<?php
if (empty($content['data'])):
	echo '<p class="info">' . __d('debug_kit', 'No post data.') . '</p>';
else:
	echo $this->Toolbar->makeNeatArray($content['data']);
endif;
?>

<h4>Query string</h4>
<?php
if (empty($content['query'])):
	echo '<p class="info">' . __d('debug_kit', 'No querystring data.') . '</p>';
else:
	echo $this->Toolbar->makeNeatArray($content['query']);
endif;
?>

<h4>Cookie</h4>
<?php if (isset($content['cookie'])): ?>
	<?php echo $this->Toolbar->makeNeatArray($content['cookie']); ?>
<?php else: ?>
	<p class="info">To view Cookies, add CookieComponent to Controller</p>
<?php endif; ?>

<h4><?php echo __d('debug_kit', 'Current Route') ?></h4>
<?php echo $this->Toolbar->makeNeatArray($content['currentRoute']);
