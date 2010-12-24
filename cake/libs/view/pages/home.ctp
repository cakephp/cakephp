<?php
/**
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       cake.libs.view.templates.pages
 * @since         CakePHP(tm) v 0.10.0.1076
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
if (Configure::read() == 0):
	throw new NotFoundException();
endif;
?>
<h2><?php echo __('Release Notes for CakePHP %s.', Configure::version()); ?></h2>
<a href="http://cakephp.org/changelogs/1.3.6"><?php __('Read the changelog'); ?> </a>
<?php
if (Configure::read() > 0):
	Debugger::checkSecurityKeys();
endif;
?>
<p>
	<?php
		if (is_writable(TMP)):
			echo '<span class="notice success">';
				echo __('Your tmp directory is writable.');
			echo '</span>';
		else:
			echo '<span class="notice">';
				echo __('Your tmp directory is NOT writable.');
			echo '</span>';
		endif;
	?>
</p>
<p>
	<?php
		$settings = Cache::settings();
		if (!empty($settings)):
			echo '<span class="notice success">';
				echo __('The %s is being used for caching. To change the config edit APP/config/core.php ', '<em>'. $settings['engine'] . 'Engine</em>');
			echo '</span>';
		else:
			echo '<span class="notice">';
				echo __('Your cache is NOT working. Please check the settings in APP/config/core.php');
			echo '</span>';
		endif;
	?>
</p>
<p>
	<?php
		$filePresent = null;
		if (file_exists(CONFIGS.'database.php')):
			echo '<span class="notice success">';
				echo __('Your database configuration file is present.');
				$filePresent = true;
			echo '</span>';
		else:
			echo '<span class="notice">';
				echo __('Your database configuration file is NOT present.');
				echo '<br/>';
				echo __('Rename config/database.php.default to config/database.php');
			echo '</span>';
		endif;
	?>
</p>
<?php
	App::import('Core', 'Validation');
	if (!Validation::alphaNumeric('cakephp')) {
		echo '<p><span class="notice">';
		__('PCRE has not been compiled with Unicode support.');
		echo '<br/>';
		__('Recompile PCRE with Unicode support by adding <code>--enable-unicode-properties</code> when configuring');
		echo '</span></p>';
	}
?>
<?php
if (isset($filePresent)):
	if (!class_exists('ConnectionManager')) {
		require LIBS . 'model' . DS . 'connection_manager.php';
	}
	$db = ConnectionManager::getInstance();
	@$connected = $db->getDataSource('default');
?>
<p>
	<?php
		if ($connected->isConnected()):
			echo '<span class="notice success">';
	 			echo __('Cake is able to connect to the database.');
			echo '</span>';
		else:
			echo '<span class="notice">';
				echo __('Cake is NOT able to connect to the database.');
			echo '</span>';
		endif;
	?>
</p>
<?php endif;?>
<h3><?php echo __('Editing this Page'); ?></h3>
<p>
<?php
echo __('To change the content of this page, create: APP/views/pages/home.ctp.<br />
To change its layout, create: APP/views/layouts/default.ctp.<br />
You can also add some CSS styles for your pages at: APP/webroot/css.');
?>
</p>

<h3><?php echo __('Getting Started'); ?></h3>
<p>
	<?php
		echo $this->Html->link(
			sprintf('<strong>%s</strong> %s', __('New', true), __('CakePHP 1.3 Docs', true)),
			'http://book.cakephp.org/view/875/x1-3-Collection',
			array('target' => '_blank', 'escape' => false)
		);
	?>
</p>
<p>
	<?php
		echo $this->Html->link(
			__('The 15 min Blog Tutorial', true),
			'http://book.cakephp.org/view/1528/Blog',
			array('target' => '_blank', 'escape' => false)
		);
	?>
</p>

<h3><?php echo __('More about Cake'); ?></h3>
<p>
<?php echo __('CakePHP is a rapid development framework for PHP which uses commonly known design patterns like Active Record, Association Data Mapping, Front Controller and MVC.'); ?>
</p>
<p>
<?php echo __('Our primary goal is to provide a structured framework that enables PHP users at all levels to rapidly develop robust web applications, without any loss to flexibility.'); ?>
</p>

<ul>
	<li><a href="http://cakefoundation.org/"><?php echo __('Cake Software Foundation'); ?> </a>
	<ul><li><?php echo __('Promoting development related to CakePHP'); ?></li></ul></li>
	<li><a href="http://www.cakephp.org"><?php echo __('CakePHP'); ?> </a>
	<ul><li><?php echo __('The Rapid Development Framework'); ?></li></ul></li>
	<li><a href="http://book.cakephp.org"><?php echo __('CakePHP Documentation'); ?> </a>
	<ul><li><?php echo __('Your Rapid Development Cookbook'); ?></li></ul></li>
	<li><a href="http://api.cakephp.org"><?php echo __('CakePHP API'); ?> </a>
	<ul><li><?php echo __('Quick Reference'); ?></li></ul></li>
	<li><a href="http://bakery.cakephp.org"><?php echo __('The Bakery'); ?> </a>
	<ul><li><?php echo __('Everything CakePHP'); ?></li></ul></li>
	<li><a href="http://live.cakephp.org"><?php echo __('The Show'); ?> </a>
	<ul><li><?php echo __('The Show is a live and archived internet radio broadcast CakePHP-related topics and answer questions live via IRC, Skype, and telephone.'); ?></li></ul></li>
	<li><a href="http://groups.google.com/group/cake-php"><?php echo __('CakePHP Google Group'); ?> </a>
	<ul><li><?php echo __('Community mailing list'); ?></li></ul></li>
	<li><a href="irc://irc.freenode.net/cakephp">irc.freenode.net #cakephp</a>
	<ul><li><?php echo __('Live chat about CakePHP'); ?></li></ul></li>
	<li><a href="http://github.com/cakephp/"><?php echo __('CakePHP Code'); ?> </a>
	<ul><li><?php echo __('For the Development of CakePHP Git repository, Downloads'); ?></li></ul></li>
	<li><a href="http://cakephp.lighthouseapp.com/"><?php echo __('CakePHP Lighthouse'); ?> </a>
	<ul><li><?php echo __('CakePHP Tickets, Wiki pages, Roadmap'); ?></li></ul></li>
	<li><a href="http://www.cakeforge.org"><?php echo __('CakeForge'); ?> </a>
	<ul><li><?php echo __('Open Development for CakePHP'); ?></li></ul></li>
	<li><a href="http://astore.amazon.com/cakesoftwaref-20/"><?php echo __('Book Store'); ?> </a>
	<ul><li><?php echo __('Recommended Software Books'); ?></li></ul></li>
	<li><a href="http://www.cafepress.com/cakefoundation"><?php echo __('CakePHP gear'); ?> </a>
	<ul><li><?php echo __('Get your own CakePHP gear - Doughnate to Cake'); ?></li></ul></li>
</ul>
