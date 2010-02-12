<?php
/* SVN FILE: $Id$ */
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
 * @version       $Revision$
 * @modifiedby    $LastChangedBy$
 * @lastmodified  $Date$
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
 */
?>
<h2><?php echo sprintf(__('Missing Method in %s', true), $controller);?></h2>
<p class="error">
	<strong><?php __('Error') ?>: </strong>
	<?php echo sprintf(__('The action %1$s is not defined in controller %2$s', true), "<em>" . $action . "</em>", "<em>" . $controller . "</em>");?>
</p>
<p class="error">
	<strong><?php __('Error') ?>: </strong>
	<?php echo sprintf(__('Create %1$s%2$s in file: %3$s.', true), "<em>" . $controller . "::</em>", "<em>" . $action . "()</em>", APP_DIR . DS . "controllers" . DS . Inflector::underscore($controller) . ".php");?>
</p>
<pre>
&lt;?php
class <?php echo $controller;?> extends AppController {

	var $name = '<?php echo $controllerName;?>';

<strong>
	function <?php echo $action;?>() {

	}
</strong>
}
?&gt;
</pre>
<p class="notice">
	<strong><?php __('Notice') ?>: </strong>
	<?php echo sprintf(__('If you want to customize this error message, create %s.', true), APP_DIR . DS . "views" . DS . "errors" . DS . "missing_action.ctp");?>
</p>