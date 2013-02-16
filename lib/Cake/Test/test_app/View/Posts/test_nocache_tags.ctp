<?php
/**
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       cake.libs.view.templates.pages
 * @since         CakePHP(tm) v 0.10.0.1076
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
?>
<p>
	<!--nocache-->
	<span class="notice">
		<?php
			echo __d('cake', 'Your tmp directory is ');
			if (is_writable(TMP)):
				echo __d('cake', 'writable.');
			else:
				echo __d('cake', 'NOT writable.');
			endif;
		?>
	</span>
	<!--/nocache-->
</p>
<p>
	<span class="notice">
		<?php
			echo __d('cake', 'Your cache is ');
			if (Cache::isInitialized('default')):
				echo __d('cake', 'set up and initialized properly.');
				$settings = Cache::settings();
				echo '<p>' . $settings['engine'];
				echo __d('cake', ' is being used to cache, to change this edit config/core.php ');
				echo '</p>';

				echo 'Settings: <ul>';
				foreach ($settings as $name => $value):
					if (is_array($value)):
						$value = join(',', $value);
					endif;
					echo '<li>' . $name . ': ' . $value . '</li>';
				endforeach;
				echo '</ul>';

			else:
				echo __d('cake', 'NOT working.');
				echo '<br />';
				if (is_writable(TMP)):
					echo __d('cake', 'Edit: config/core.php to insure you have the newset version of this file and the variable $cakeCache set properly');
				endif;
			endif;
		?>
	</span>
</p>
<p>
	<span class="notice">
		<?php
			echo __d('cake', 'Your database configuration file is ');
			$filePresent = null;
			if (file_exists(APP . 'Config'.'database.php')):
				echo __d('cake', 'present.');
				$filePresent = true;
			else:
				echo __d('cake', 'NOT present.');
				echo '<br/>';
				echo __d('cake', 'Rename config/database.php.default to config/database.php');
			endif;
		?>
	</span>
</p>
<?php
if (!empty($filePresent)):
	App::uses('ConnectionManager', 'Model');
 	$connected = ConnectionManager::getDataSource('default');
?>
<p>
	<span class="notice">
		<?php echo __d('cake', 'Cake');
			if ($connected->isConnected()):
		 		__d('cake', ' is able to ');
			else:
				__d('cake', ' is NOT able to ');
			endif;
			__d('cake', 'connect to the database.');
		?>
	</span>
</p>
<?php endif; ?>
<h2><?php echo __d('cake', 'Release Notes for CakePHP %s.', Configure::version()); ?></h2>
<a href="https://trac.cakephp.org/wiki/notes/1.2.x.x"><?php echo __d('cake', 'Read the release notes and get the latest version'); ?> </a>
<h2><?php echo __d('cake', 'Editing this Page'); ?></h2>
<p>
<?php echo __d('cake', 'To change the content of this page, create: /app/View/Pages/home.ctp.'); ?><br />
<?php echo __d('cake', 'To change its layout, create: /app/View/Layouts/default.ctp.'); ?><br />
<a href="http://manual.cakephp.org/"><?php echo __d('cake', 'See the views section of the manual for more info.'); ?> </a><br />
<?php echo __d('cake', 'You can also add some CSS styles for your pages at: app/webroot/css/.'); ?>
</p>
<h2><?php echo __d('cake', 'Getting Started'); ?></h2>
<p>
<a href="http://manual.cakephp.org/appendix/blog_tutorial"><?php echo __d('cake', 'The 15 min Blog Tutorial'); ?></a><br />
<a href="http://www-128.ibm.com/developerworks/edu/os-dw-os-php-cake1.html"><?php echo __d('cake', 'Cook up Web sites fast with CakePHP'); ?></a><br />
<a href="http://www-128.ibm.com/developerworks/edu/os-dw-os-php-wiki1.html"><?php echo __d('cake', 'Create an interactive production wiki using PHP'); ?></a>
</p>
<h2><?php echo __d('cake', 'More about Cake'); ?></h2>
<p>
<?php echo __d('cake', 'CakePHP is a rapid development framework for PHP which uses commonly known design patterns like Active Record, Association Data Mapping, Front Controller and MVC.'); ?>
</p>
<p>
<?php echo __d('cake', 'Our primary goal is to provide a structured framework that enables PHP users at all levels to rapidly develop robust web applications, without any loss to flexibility.'); ?>
</p>
<ul>
	<li><a href="http://cakefoundation.org/"><?php echo __d('cake', 'Cake Software Foundation'); ?> </a>
	<ul><li><?php echo __d('cake', 'Promoting development related to CakePHP'); ?></li></ul></li>
	<li><a href="http://bakery.cakephp.org"><?php echo __d('cake', 'The Bakery'); ?> </a>
	<ul><li><?php echo __d('cake', 'Everything CakePHP'); ?></li></ul></li>
	<li><a href="http://astore.amazon.com/cakesoftwaref-20/"><?php echo __d('cake', 'Book Store'); ?> </a>
	<ul><li><?php echo __d('cake', 'Recommended Software Books'); ?></li></ul></li>
	<li><a href="http://www.cafepress.com/cakefoundation"><?php echo __d('cake', 'CakeSchwag'); ?> </a>
	<ul><li><?php echo __d('cake', 'Get your own CakePHP gear - Doughnate to Cake'); ?></li></ul></li>
	<li><a href="http://www.cakephp.org"><?php echo __d('cake', 'CakePHP'); ?> </a>
	<ul><li><?php echo __d('cake', 'The Rapid Development Framework'); ?></li></ul></li>
	<li><a href="http://manual.cakephp.org"><?php echo __d('cake', 'CakePHP Manual'); ?> </a>
	<ul><li><?php echo __d('cake', 'Your Rapid Development Cookbook'); ?></li></ul></li>
	<li><a href="http://api.cakephp.org"><?php echo __d('cake', 'CakePHP API'); ?> </a>
	<ul><li><?php echo __d('cake', 'Docblock Your Best Friend'); ?></li></ul></li>
	<li><a href="http://www.cakeforge.org"><?php echo __d('cake', 'CakeForge'); ?> </a>
	<ul><li><?php echo __d('cake', 'Open Development for CakePHP'); ?></li></ul></li>
	<li><a href="https://trac.cakephp.org/"><?php echo __d('cake', 'CakePHP Trac'); ?> </a>
	<ul><li><?php echo __d('cake', 'For the Development of CakePHP (Tickets, SVN browser, Roadmap, Changelogs)'); ?></li></ul></li>
	<li><a href="http://groups-beta.google.com/group/cake-php"><?php echo __d('cake', 'CakePHP Google Group'); ?> </a>
	<ul><li><?php echo __d('cake', 'Community mailing list'); ?></li></ul></li>
	<li><a href="irc://irc.freenode.net/cakephp">irc.freenode.net #cakephp</a>
	<ul><li><?php echo __d('cake', 'Live chat about CakePHP'); ?></li></ul></li>
</ul>
