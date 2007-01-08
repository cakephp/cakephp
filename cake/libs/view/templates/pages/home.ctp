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
 * @subpackage		cake.cake.libs.view.templates.pages
 * @since			CakePHP v 0.10.0.1076
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/mit-license.php The MIT License
 */
?>
<p class="notice"><?php echo sprintf(__('Your database configuration file is %s.', true), file_exists(CONFIGS.'database.php') ? __('present', true) . $filePresent = ' ' : __('not present', true));?></p>
<?php if (!empty($filePresent)):?>
<?php uses('model' . DS . 'connection_manager'); $db = ConnectionManager::getInstance(); ?>
<?php $connected = $db->getDataSource('default'); ?>
<p class="notice"><?php echo sprintf(__('Cake %s connect to the database.', true), $connected->isConnected() ? __('is able to', true) : __('is not able to', true));?></p>
<br />
<?php endif; ?>
<h2><?php __('CakePHP release information is on CakeForge'); ?></h2>
<a href="https://trac.cakephp.org/wiki/notes/1.2.x.x"><?php __('Read the release notes and get the latest version'); ?> </a>
<h2><?php __('Editing this Page'); ?></h2>
<p>
<?php __('To change the content of this page, create: /app/views/pages/home.thtml.'); ?><br />
<?php __('To change its layout, create: /app/views/layouts/default.thtml.'); ?><br />
<a href="http://manual.cakephp.org/"><?php __('See the views section of the manual for more info.'); ?> </a><br />
<?php __('You can also add some CSS styles for your pages at: app/webroot/css/.'); ?>
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