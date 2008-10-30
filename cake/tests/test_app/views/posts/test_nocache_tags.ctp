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
 * @subpackage    cake.cake.libs.view.templates.pages
 * @since         CakePHP(tm) v 0.10.0.1076
 * @version       $Revision$
 * @modifiedby    $LastChangedBy$
 * @lastmodified  $Date$
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
 */
?>
<p>
	<cake:nocache>
	<span class="notice">
		<?php
			__('Your tmp directory is ');
			if (is_writable(TMP)):
				__('writable.');
			else:
				__('NOT writable.');
			endif;
		?>
	</span>
	</cake:nocache>
</p>
<p>
	<span class="notice">
		<?php
			__('Your cache is ');
			if (Cache::isInitialized()):
				__('set up and initialized properly.');
				$settings = Cache::settings();
				echo '<p>' . $settings['engine'];
				__(' is being used to cache, to change this edit config/core.php ');
				echo '</p>';

				echo 'Settings: <ul>';
				foreach ($settings as $name => $value):
					echo '<li>' . $name . ': ' . $value . '</li>';
				endforeach;
				echo '</ul>';

			else:
				__('NOT working.');
				echo '<br />';
				if (is_writable(TMP)):
					__('Edit: config/core.php to insure you have the newset version of this file and the variable $cakeCache set properly');
				endif;
			endif;
		?>
	</span>
</p>
<p>
	<span class="notice">
		<?php
			__('Your database configuration file is ');
			$filePresent = null;
			if (file_exists(CONFIGS.'database.php')):
				__('present.');
				$filePresent = true;
			else:
				__('NOT present.');
				echo '<br/>';
				__('Rename config/database.php.default to config/database.php');
			endif;
		?>
	</span>
</p>
<?php
if (!empty($filePresent)):
 	uses('model' . DS . 'connection_manager');
	$db = ConnectionManager::getInstance();
 	$connected = $db->getDataSource('default');
?>
<p>
	<span class="notice">
		<?php
			__('Cake');
			if ($connected->isConnected()):
		 		__(' is able to ');
			else:
				__(' is NOT able to ');
			endif;
			__('connect to the database.');
		?>
	</span>
</p>
<?php endif; ?>
<h2><?php echo sprintf(__('Release Notes for CakePHP %s.', true), Configure::version()); ?></h2>
<a href="https://trac.cakephp.org/wiki/notes/1.2.x.x"><?php __('Read the release notes and get the latest version'); ?> </a>
<h2><?php __('Editing this Page'); ?></h2>
<p>
<?php __('To change the content of this page, create: /app/views/pages/home.ctp.'); ?><br />
<?php __('To change its layout, create: /app/views/layouts/default.ctp.'); ?><br />
<a href="http://manual.cakephp.org/"><?php __('See the views section of the manual for more info.'); ?> </a><br />
<?php __('You can also add some CSS styles for your pages at: app/webroot/css/.'); ?>
</p>
<h2><?php __('Getting Started'); ?></h2>
<p>
<a href="http://manual.cakephp.org/appendix/blog_tutorial"><?php __('The 15 min Blog Tutorial'); ?></a><br />
<a href="http://www-128.ibm.com/developerworks/edu/os-dw-os-php-cake1.html"><?php __('Cook up Web sites fast with CakePHP'); ?></a><br />
<a href="http://www-128.ibm.com/developerworks/edu/os-dw-os-php-wiki1.html"><?php __('Create an interactive production wiki using PHP'); ?></a>
</p>
<h2><?php __('More about Cake'); ?></h2>
<p>
<?php __('CakePHP is a rapid development framework for PHP which uses commonly known design patterns like Active Record, Association Data Mapping, Front Controller and MVC.'); ?>
</p>
<p>
<?php __('Our primary goal is to provide a structured framework that enables PHP users at all levels to rapidly develop robust web applications, without any loss to flexibility.'); ?>
</p>
<ul>
	<li><a href="http://www.cakefoundation.org/"><?php __('Cake Software Foundation'); ?> </a>
	<ul><li><?php __('Promoting development related to CakePHP'); ?></li></ul></li>
	<li><a href="http://bakery.cakephp.org"><?php __('The Bakery'); ?> </a>
	<ul><li><?php __('Everything CakePHP'); ?></li></ul></li>
	<li><a href="http://astore.amazon.com/cakesoftwaref-20/"><?php __('Book Store'); ?> </a>
	<ul><li><?php __('Recommended Software Books'); ?></li></ul></li>
	<li><a href="http://www.cafepress.com/cakefoundation"><?php __('CakeSchwag'); ?> </a>
	<ul><li><?php __('Get your own CakePHP gear - Doughnate to Cake'); ?></li></ul></li>
	<li><a href="http://www.cakephp.org"><?php __('CakePHP'); ?> </a>
	<ul><li><?php __('The Rapid Development Framework'); ?></li></ul></li>
	<li><a href="http://manual.cakephp.org"><?php __('CakePHP Manual'); ?> </a>
	<ul><li><?php __('Your Rapid Development Cookbook'); ?></li></ul></li>
	<li><a href="http://api.cakephp.org"><?php __('CakePHP API'); ?> </a>
	<ul><li><?php __('Docblock Your Best Friend'); ?></li></ul></li>
	<li><a href="http://www.cakeforge.org"><?php __('CakeForge'); ?> </a>
	<ul><li><?php __('Open Development for CakePHP'); ?></li></ul></li>
	<li><a href="https://trac.cakephp.org/"><?php __('CakePHP Trac'); ?> </a>
	<ul><li><?php __('For the Development of CakePHP (Tickets, SVN browser, Roadmap, Changelogs)'); ?></li></ul></li>
	<li><a href="http://groups-beta.google.com/group/cake-php"><?php __('CakePHP Google Group'); ?> </a>
	<ul><li><?php __('Community mailing list'); ?></li></ul></li>
	<li><a href="irc://irc.freenode.net/cakephp">irc.freenode.net #cakephp</a>
	<ul><li><?php __('Live chat about CakePHP'); ?></li></ul></li>
</ul>