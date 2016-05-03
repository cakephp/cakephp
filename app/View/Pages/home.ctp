<?php
/**
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       app.View.Pages
 * @since         CakePHP(tm) v 0.10.0.1076
 */

if (!Configure::read('debug')):
	throw new NotFoundException();
endif;

App::uses('Debugger', 'Utility');
?>
<h2><?php echo __d('cake_dev', 'Release Notes for CakePHP %s.', Configure::version()); ?></h2>
<p>
	<?php echo $this->Html->link(__d('cake_dev', 'Read the changelog'), 'http://cakephp.org/changelogs/' . Configure::version()); ?>
</p>
<?php
if (Configure::read('debug') > 0):
	Debugger::checkSecurityKeys();
endif;
?>
<?php if (file_exists(WWW_ROOT . 'css' . DS . 'cake.generic.css')): ?>
	<p id="url-rewriting-warning" style="background-color:#e32; color:#fff;">
		<?php echo __d('cake_dev', 'URL rewriting is not properly configured on your server.'); ?>
		1) <a target="_blank" href="http://book.cakephp.org/2.0/en/installation/url-rewriting.html" style="color:#fff;">Help me configure it</a>
		2) <a target="_blank" href="http://book.cakephp.org/2.0/en/development/configuration.html#cakephp-core-configuration" style="color:#fff;">I don't / can't use URL rewriting</a>
	</p>
<?php endif; ?>
<p>
<?php
if (version_compare(PHP_VERSION, '5.2.8', '>=')):
	echo '<span class="notice success">';
		echo __d('cake_dev', 'Your version of PHP is 5.2.8 or higher.');
	echo '</span>';
else:
	echo '<span class="notice">';
		echo __d('cake_dev', 'Your version of PHP is too low. You need PHP 5.2.8 or higher to use CakePHP.');
	echo '</span>';
endif;
?>
</p>
<p>
	<?php
	if (is_writable(TMP)):
		echo '<span class="notice success">';
			echo __d('cake_dev', 'Your tmp directory is writable.');
		echo '</span>';
	else:
		echo '<span class="notice">';
			echo __d('cake_dev', 'Your tmp directory is NOT writable.');
		echo '</span>';
	endif;
	?>
</p>
<p>
	<?php
	$settings = Cache::settings();
	if (!empty($settings)):
		echo '<span class="notice success">';
			echo __d('cake_dev', 'The %s is being used for core caching. To change the config edit %s', '<em>' . $settings['engine'] . 'Engine</em>', 'APP/Config/core.php');
		echo '</span>';
	else:
		echo '<span class="notice">';
			echo __d('cake_dev', 'Your cache is NOT working. Please check the settings in %s', 'APP/Config/core.php');
		echo '</span>';
	endif;
	?>
</p>
<p>
	<?php
	$filePresent = null;
	if (file_exists(APP . 'Config' . DS . 'database.php')):
		echo '<span class="notice success">';
			echo __d('cake_dev', 'Your database configuration file is present.');
			$filePresent = true;
		echo '</span>';
	else:
		echo '<span class="notice">';
			echo __d('cake_dev', 'Your database configuration file is NOT present.');
			echo '<br/>';
			echo __d('cake_dev', 'Rename %s to %s', 'APP/Config/database.php.default', 'APP/Config/database.php');
		echo '</span>';
	endif;
	?>
</p>
<?php
if (isset($filePresent)):
	App::uses('ConnectionManager', 'Model');
	try {
		$connected = ConnectionManager::getDataSource('default');
	} catch (Exception $connectionError) {
		$connected = false;
		$errorMsg = $connectionError->getMessage();
		if (method_exists($connectionError, 'getAttributes')):
			$attributes = $connectionError->getAttributes();
			if (isset($errorMsg['message'])):
				$errorMsg .= '<br />' . $attributes['message'];
			endif;
		endif;
	}
	?>
	<p>
		<?php
			if ($connected && $connected->isConnected()):
				echo '<span class="notice success">';
					echo __d('cake_dev', 'CakePHP is able to connect to the database.');
				echo '</span>';
			else:
				echo '<span class="notice">';
					echo __d('cake_dev', 'CakePHP is NOT able to connect to the database.');
					echo '<br /><br />';
					echo $errorMsg;
				echo '</span>';
			endif;
		?>
	</p>
<?php
endif;

App::uses('Validation', 'Utility');
if (!Validation::alphaNumeric('cakephp')):
	echo '<p><span class="notice">';
		echo __d('cake_dev', 'PCRE has not been compiled with Unicode support.');
		echo '<br/>';
		echo __d('cake_dev', 'Recompile PCRE with Unicode support by adding <code>--enable-unicode-properties</code> when configuring');
	echo '</span></p>';
endif;
?>

<p>
	<?php
	if (CakePlugin::loaded('DebugKit')):
		echo '<span class="notice success">';
			echo __d('cake_dev', 'DebugKit plugin is present');
		echo '</span>';
	else:
		echo '<span class="notice">';
			echo __d('cake_dev', 'DebugKit is not installed. It will help you inspect and debug different aspects of your application.');
			echo '<br/>';
			echo __d('cake_dev', 'You can install it from %s', $this->Html->link('GitHub', 'https://github.com/cakephp/debug_kit/tree/2.2'));
		echo '</span>';
	endif;
	?>
</p>

<h3><?php echo __d('cake_dev', 'Editing this Page'); ?></h3>
<p>
<?php
echo __d('cake_dev', 'To change the content of this page, edit: %s.<br />
To change its layout, edit: %s.<br />
You can also add some CSS styles for your pages at: %s.',
	'APP/View/Pages/home.ctp', 'APP/View/Layouts/default.ctp', 'APP/webroot/css');
?>
</p>

<h3><?php echo __d('cake_dev', 'Getting Started'); ?></h3>
<p>
	<?php
	echo $this->Html->link(
		sprintf('<strong>%s</strong> %s', __d('cake_dev', 'New'), __d('cake_dev', 'CakePHP 2.0 Docs')),
		'http://book.cakephp.org/2.0/en/',
		array('target' => '_blank', 'escape' => false)
	);
	?>
</p>
<p>
	<?php
	echo $this->Html->link(
		__d('cake_dev', 'The 15 min Blog Tutorial'),
		'http://book.cakephp.org/2.0/en/tutorials-and-examples/blog/blog.html',
		array('target' => '_blank', 'escape' => false)
	);
	?>
</p>

<h3><?php echo __d('cake_dev', 'Official Plugins'); ?></h3>
<p>
<ul>
	<li>
		<?php echo $this->Html->link('DebugKit', 'https://github.com/cakephp/debug_kit/tree/2.2') ?>:
		<?php echo __d('cake_dev', 'provides a debugging toolbar and enhanced debugging tools for CakePHP applications.'); ?>
	</li>
	<li>
		<?php echo $this->Html->link('Localized', 'https://github.com/cakephp/localized') ?>:
		<?php echo __d('cake_dev', 'contains various localized validation classes and translations for specific countries'); ?>
	</li>
</ul>
</p>

<h3><?php echo __d('cake_dev', 'More about CakePHP'); ?></h3>
<p>
<?php echo __d('cake_dev', 'CakePHP is a rapid development framework for PHP which uses commonly known design patterns like Active Record, Association Data Mapping, Front Controller and MVC.'); ?>
</p>
<p>
<?php echo __d('cake_dev', 'Our primary goal is to provide a structured framework that enables PHP users at all levels to rapidly develop robust web applications, without any loss to flexibility.'); ?>
</p>

<ul>
	<li><a href="http://cakephp.org">CakePHP</a>
	<ul><li><?php echo __d('cake_dev', 'The Rapid Development Framework'); ?></li></ul></li>
	<li><a href="http://book.cakephp.org"><?php echo __d('cake_dev', 'CakePHP Documentation'); ?> </a>
	<ul><li><?php echo __d('cake_dev', 'Your Rapid Development Cookbook'); ?></li></ul></li>
	<li><a href="http://api.cakephp.org"><?php echo __d('cake_dev', 'CakePHP API'); ?> </a>
	<ul><li><?php echo __d('cake_dev', 'Quick API Reference'); ?></li></ul></li>
	<li><a href="http://bakery.cakephp.org"><?php echo __d('cake_dev', 'The Bakery'); ?> </a>
	<ul><li><?php echo __d('cake_dev', 'Everything CakePHP'); ?></li></ul></li>
	<li><a href="http://plugins.cakephp.org"><?php echo __d('cake_dev', 'CakePHP Plugins'); ?> </a>
	<ul><li><?php echo __d('cake_dev', 'A comprehensive list of all CakePHP plugins created by the community'); ?></li></ul></li>
	<li><a href="http://community.cakephp.org"><?php echo __d('cake_dev', 'CakePHP Community Center'); ?> </a>
	<ul><li><?php echo __d('cake_dev', 'Everything related to the CakePHP community in one place'); ?></li></ul></li>
	<li><a href="https://groups.google.com/group/cake-php"><?php echo __d('cake_dev', 'CakePHP Google Group'); ?> </a>
	<ul><li><?php echo __d('cake_dev', 'Community mailing list'); ?></li></ul></li>
	<li><a href="http://discourse.cakephp.org/"><?php echo __d('cake_dev', 'CakePHP Official Forum'); ?> </a>
	<ul><li><?php echo __d('cake_dev', 'CakePHP discussion forum'); ?></li></ul></li>
	<li><a href="irc://irc.freenode.net/cakephp">irc.freenode.net #cakephp</a>
	<ul><li><?php echo __d('cake_dev', 'Live chat about CakePHP'); ?></li></ul></li>
	<li><a href="https://github.com/cakephp/"><?php echo __d('cake_dev', 'CakePHP Code'); ?> </a>
	<ul><li><?php echo __d('cake_dev', 'Find the CakePHP code on GitHub and contribute to the framework'); ?></li></ul></li>
	<li><a href="https://github.com/cakephp/cakephp/issues"><?php echo __d('cake_dev', 'CakePHP Issues'); ?> </a>
	<ul><li><?php echo __d('cake_dev', 'CakePHP Issues'); ?></li></ul></li>
	<li><a href="https://github.com/cakephp/cakephp/wiki#roadmaps"><?php echo __d('cake_dev', 'CakePHP Roadmaps'); ?> </a>
	<ul><li><?php echo __d('cake_dev', 'CakePHP Roadmaps'); ?></li></ul></li>
	<li><a href="http://training.cakephp.org"><?php echo __d('cake_dev', 'Training'); ?> </a>
	<ul><li><?php echo __d('cake_dev', 'Join a live session and get skilled with the framework'); ?></li></ul></li>
	<li><a href="http://cakefest.org"><?php echo __d('cake_dev', 'CakeFest'); ?> </a>
	<ul><li><?php echo __d('cake_dev', 'Don\'t miss our annual CakePHP conference'); ?></li></ul></li>
	<li><a href="http://cakefoundation.org"><?php echo __d('cake_dev', 'Cake Software Foundation'); ?> </a>
	<ul><li><?php echo __d('cake_dev', 'Promoting development related to CakePHP'); ?></li></ul></li>
</ul>
