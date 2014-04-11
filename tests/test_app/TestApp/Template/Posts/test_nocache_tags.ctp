<?php
use Cake\Datasource\ConnectionManager;
use Cake\Core\Configure;
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
			echo __d('cake', 'Your datasources configuration file is ');
			$filePresent = null;
			if (file_exists(APP . 'Config'.'datasources.php')):
				echo __d('cake', 'present.');
				$filePresent = true;
			else:
				echo __d('cake', 'NOT present.');
				echo '<br/>';
				echo __d('cake', 'Rename App/Config/datasources.default.php to App/Config/datasources.php');
			endif;
		?>
	</span>
</p>
<?php
if (!empty($filePresent)):
 	$connected = ConnectionManager::getDataSource('default');
?>
<p>
	<span class="notice">
		<?= __d('cake', 'Cake');
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
<h2><?= __d('cake', 'Release Notes for CakePHP %s.', Configure::version()); ?></h2>
<a href="https://trac.cakephp.org/wiki/notes/1.2.x.x"><?= __d('cake', 'Read the release notes and get the latest version'); ?> </a>
<h2><?= __d('cake', 'Editing this Page'); ?></h2>
<p>
<?= __d('cake', 'To change the content of this page, create: /app/View/Pages/home.ctp.'); ?><br />
<?= __d('cake', 'To change its layout, create: /app/View/Layout/default.ctp.'); ?><br />
<a href="http://manual.cakephp.org/"><?= __d('cake', 'See the views section of the manual for more info.'); ?> </a><br />
<?= __d('cake', 'You can also add some CSS styles for your pages at: app/webroot/css/.'); ?>
</p>
<h2><?= __d('cake', 'Getting Started'); ?></h2>
<p>
<a href="http://manual.cakephp.org/appendix/blog_tutorial"><?= __d('cake', 'The 15 min Blog Tutorial'); ?></a><br />
<a href="http://www-128.ibm.com/developerworks/edu/os-dw-os-php-cake1.html"><?= __d('cake', 'Cook up Web sites fast with CakePHP'); ?></a><br />
<a href="http://www-128.ibm.com/developerworks/edu/os-dw-os-php-wiki1.html"><?= __d('cake', 'Create an interactive production wiki using PHP'); ?></a>
</p>
<h2><?= __d('cake', 'More about CakePHP'); ?></h2>
<p>
<?= __d('cake', 'CakePHP is a rapid development framework for PHP which uses commonly known design patterns like Active Record, Association Data Mapping, Front Controller and MVC.'); ?>
</p>
<p>
<?= __d('cake', 'Our primary goal is to provide a structured framework that enables PHP users at all levels to rapidly develop robust web applications, without any loss to flexibility.'); ?>
</p>
<ul>
	<li><a href="http://cakefoundation.org/"><?= __d('cake', 'Cake Software Foundation'); ?> </a>
	<ul><li><?= __d('cake', 'Promoting development related to CakePHP'); ?></li></ul></li>
	<li><a href="http://bakery.cakephp.org"><?= __d('cake', 'The Bakery'); ?> </a>
	<ul><li><?= __d('cake', 'Everything CakePHP'); ?></li></ul></li>
	<li><a href="http://astore.amazon.com/cakesoftwaref-20/"><?= __d('cake', 'Book Store'); ?> </a>
	<ul><li><?= __d('cake', 'Recommended Software Books'); ?></li></ul></li>
	<li><a href="http://www.cafepress.com/cakefoundation"><?= __d('cake', 'CakeSchwag'); ?> </a>
	<ul><li><?= __d('cake', 'Get your own CakePHP gear - Doughnate to Cake'); ?></li></ul></li>
	<li><a href="http://www.cakephp.org"><?= __d('cake', 'CakePHP'); ?> </a>
	<ul><li><?= __d('cake', 'The Rapid Development Framework'); ?></li></ul></li>
	<li><a href="http://manual.cakephp.org"><?= __d('cake', 'CakePHP Manual'); ?> </a>
	<ul><li><?= __d('cake', 'Your Rapid Development Cookbook'); ?></li></ul></li>
	<li><a href="http://api.cakephp.org"><?= __d('cake', 'CakePHP API'); ?> </a>
	<ul><li><?= __d('cake', 'Docblock Your Best Friend'); ?></li></ul></li>
	<li><a href="http://www.cakeforge.org"><?= __d('cake', 'CakeForge'); ?> </a>
	<ul><li><?= __d('cake', 'Open Development for CakePHP'); ?></li></ul></li>
	<li><a href="https://trac.cakephp.org/"><?= __d('cake', 'CakePHP Trac'); ?> </a>
	<ul><li><?= __d('cake', 'For the Development of CakePHP (Tickets, SVN browser, Roadmap, Changelogs)'); ?></li></ul></li>
	<li><a href="http://groups-beta.google.com/group/cake-php"><?= __d('cake', 'CakePHP Google Group'); ?> </a>
	<ul><li><?= __d('cake', 'Community mailing list'); ?></li></ul></li>
	<li><a href="irc://irc.freenode.net/cakephp">irc.freenode.net #cakephp</a>
	<ul><li><?= __d('cake', 'Live chat about CakePHP'); ?></li></ul></li>
</ul>
