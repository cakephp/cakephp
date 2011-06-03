<?php
/**
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       cake
 * @subpackage    cake.cake.libs.view.templates.errors
 * @since         CakePHP(tm) v 1.3
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
?>
<h2><?php __('Missing Behavior File'); ?></h2>
<p class="error">
	<strong><?php __('Error'); ?>: </strong>
	<?php printf(__('The Behavior file %s can not be found or does not exist.', true), APP_DIR . DS . 'models' . DS . 'behaviors' . DS . $file); ?>
</p>
<p  class="error">
	<strong><?php __('Error'); ?>: </strong>
	<?php printf(__('Create the class below in file: %s', true), APP_DIR . DS . 'models' . DS . 'behaviors' . DS . $file); ?>
</p>
<pre>
&lt;?php
class <?php echo $behaviorClass;?> extends ModelBehavior {

}
?&gt;
</pre>
<p class="notice">
	<strong><?php __('Notice'); ?>: </strong>
	<?php printf(__('If you want to customize this error message, create %s', true), APP_DIR . DS . 'views' . DS . 'errors' . DS . 'missing_behavior_file.ctp'); ?>
</p>
