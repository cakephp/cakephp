<?php
/* SVN FILE: $Id$ */
/**
 *
 * PHP versions 4 and 5
 *
 * CakePHP :  Rapid Development Framework <http://www.cakephp.org/>
 * Copyright (c)	2006, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright (c) 2006, Cake Software Foundation, Inc.
 * @link				http://www.cakefoundation.org/projects/info/cakephp CakePHP Project
 * @package			cake
 * @subpackage		cake.cake.libs.view.templates.errors
 * @since			CakePHP v 0.10.0.1076
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/mit-license.php The MIT License
 */
?>
<h1><?php __('Missing Model'); ?></h1>
<p class="error"><?php echo sprintf(__("No class found for the <em>%s</em> model.", true), $model);?></p>
<p><span class="notice"><strong><?php __('Notice'); ?>: </strong>
<?php echo sprintf(__('If you want to customize this error message, create %s', true), APP_DIR.DS."views/errors/missing_model.thtml");?></span></p>
<p><span class="notice"><strong><?php __('Fatal'); ?>: </strong>
<?php echo sprintf(__('Create the class below in file: %s', true), APP_DIR.DS."models".DS.Inflector::underscore($model).".php");?></span></p>
<p>&lt;?php<br />
class <?php echo $model;?> extends AppModel {<br />
&nbsp;&nbsp;&nbsp;var $name = '<?php echo $model;?>';<br />
}<br />
?&gt;<br />
</p>