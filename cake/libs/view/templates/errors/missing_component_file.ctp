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
<h1>Missing Component File</h1>
<p class="error">You are seeing this error because the component file can't be found or doesn't exist.</p>
<p><span class="notice"><strong>Notice:</strong> If you want to customize this error message, create <?php echo APP_DIR.DS."views/errors/missing_component_file.thtml"; ?>.</span></p>
<p><span class="notice"><strong>Fatal</strong>: Create the class below in file : <?php echo APP_DIR.DS."controllers".DS."components".DS.$file; ?></p>
<p>&lt;?php<br />
class <?php echo $component;?>Component extends Object {<br />

}<br />
?&gt;<br /> </p>