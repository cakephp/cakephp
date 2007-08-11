<?php
/* SVN FILE: $Id$ */
/**
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) :  Rapid Development Framework <http://www.cakephp.org/>
 * Copyright 2005-2007, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright 2005-2007, Cake Software Foundation, Inc.
 * @link				http://www.cakefoundation.org/projects/info/cakephp CakePHP(tm) Project
 * @package			cake
 * @subpackage		cake.cake.libs.view.templates.errors
 * @since			CakePHP(tm) v 0.10.0.1076
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/mit-license.php The MIT License
 */
?>
<h1><?php __('Missing controller'); ?></h1>
<p class="error"><?php echo sprintf(__('You are seeing this error because controller <em>%s</em> could not be found.', true), $controller);?></p>

<p><span class="notice"><strong><?php __('Notice'); ?>: </strong>
<?php echo sprintf(__('If you want to customize this error message, create %s', true), APP_DIR.DS."views".DS."errors".DS."missing_controller.ctp");?></span></p>
<p><span class="notice"><strong><?php __('Fatal'); ?>: </strong>
<?php echo sprintf(__('Create the class below in file: %s', true), APP_DIR.DS."controllers".DS.Inflector::underscore($controller).".php");?></span></p>
<p>&lt;?php<br />
class <?php echo $controller;?> extends AppController {<br />
&nbsp;&nbsp;&nbsp;var $name = '<?php echo $controllerName;?>';<br />
}<br />
?&gt;<br />
</p>