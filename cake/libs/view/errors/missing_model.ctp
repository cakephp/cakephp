<?php
/**
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       cake
 * @subpackage    cake.cake.libs.view.templates.errors
 * @since         CakePHP(tm) v 0.10.0.1076
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
?>
<h2><?php __('Missing Model'); ?></h2>
<p class="error">
	<strong><?php __('Error'); ?>: </strong>
	<?php printf(__('<em>%s</em> could not be found.', true), $model); ?>
</p>
<p class="error">
	<strong><?php __('Error'); ?>: </strong>
	<?php printf(__('Create the class %s in file: %s', true), '<em>' . $model . '</em>', APP_DIR . DS . 'models' . DS . Inflector::underscore($model) . '.php'); ?>
</p>
<pre>
&lt;?php
class <?php echo $model;?> extends AppModel {

	var $name = '<?php echo $model;?>';

}
?&gt;
</pre>
<p class="notice">
	<strong><?php __('Notice'); ?>: </strong>
	<?php printf(__('If you want to customize this error message, create %s', true), APP_DIR . DS . 'views' . DS . 'errors' . DS . 'missing_model.ctp'); ?>
</p>