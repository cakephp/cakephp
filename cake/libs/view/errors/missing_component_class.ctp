<?php
/* SVN FILE: $Id$ */
/**
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) :  Rapid Development Framework (http://www.cakephp.org)
 * Copyright 2005-2008, Cake Software Foundation, Inc. (http://www.cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright     Copyright 2005-2008, Cake Software Foundation, Inc. (http://www.cakefoundation.org)
 * @link          http://www.cakefoundation.org/projects/info/cakephp CakePHP(tm) Project
 * @package       cake
 * @subpackage    cake.cake.libs.view.templates.errors
 * @since         CakePHP(tm) v 0.10.0.1076
 * @version       $Revision$
 * @modifiedby    $LastChangedBy$
 * @lastmodified  $Date$
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
 */
?>
<h2><?php __('Missing Component Class'); ?></h2>
<p class="error">
	<strong><?php __('Error'); ?>: </strong>
	<?php echo sprintf(__('Component class %1$s in %2$s was not found.', true), "<em>" . $component . "Component</em>", "<em>" . $controller . "Controller</em>");?>
</p>
<p class="error">
	<strong><?php __('Error'); ?>: </strong>
	<?php echo sprintf(__('Create the class %s in file: %s', true), "<em>" . $component . "Component</em>", APP_DIR . DS . "controllers" . DS . "components" . DS . $file);?>
</p>
<pre>
&lt;?php
class <?php echo $component;?>Component extends Object {<br />

}
?&gt;
</pre>
<p class="notice">
	<strong><?php __('Notice'); ?>: </strong>
	<?php echo sprintf(__('If you want to customize this error message, create %s', true), APP_DIR . DS . "views" . DS . "errors" . DS . "missing_component_class.ctp");?>
</p>