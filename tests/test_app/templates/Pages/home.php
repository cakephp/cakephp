<?php
use Cake\Cache\Cache;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Datasource\ConnectionManager;
use Cake\Error;
use Cake\Error\Debugger;
use Cake\Validation\Validation;

if (!Configure::read('debug')):
    throw new Error\NotFoundException();
endif;
?>
<h2><?= sprintf('Release Notes for CakePHP %s.', Configure::version()); ?></h2>
<p>
    <a href="https://cakephp.org/changelogs/<?= Configure::version(); ?>">Read the changelog</a>
</p>

<?php if (file_exists(WWW_ROOT . 'css' . DS . 'cake.generic.css')): ?>
<p id="url-rewriting-warning" style="background-color:#e32; color:#fff;">
    URL rewriting is not properly configured on your server.
    1) <a target="_blank" href="https://book.cakephp.org/4/en/installation/url-rewriting.html" style="color:#fff;">Help me configure it</a>
    2) <a target="_blank" href="https://book.cakephp.org/4/en/development/configuration.html#core-configuration-baseurl" style="color:#fff;">I don't / can't use URL rewriting</a>
</p>
<?php endif; ?>

<p>
<?php if (version_compare(PHP_VERSION, '5.4.3', '>=')): ?>
    <span class="notice success">Your version of PHP is 5.4.3 or higher</span>
<?php else: ?>
    <span class="notice">Your version of PHP is too low. You need PHP 5.4.3 or higher to use CakePHP.</span>
<?php endif; ?>
</p>

<p>
<?php if (extension_loaded('mbstring')): ?>
    <span class="notice success">Your version of PHP has mbstring extension loaded.</span>
<?php else: ?>
    <span class="notice">Your version of PHP does NOT have the mbstring extension loaded.</span>
<?php endif; ?>
</p>

<p>
<?php if (is_writable(TMP)): ?>
    <span class="notice success">Your tmp directory is writable.</span>
<?php else: ?>
    <span class="notice">Your tmp directory is NOT writable.</span>
<?php endif; ?>
</p>

<p>
<?php
$engine = Cache::pool('_cake_model_');
$settings = $engine ? $engine->config() : false;
if (!empty($settings)): ?>
    <span class="notice success">The <em><?= $settings['engine'] ?>Engine</em> is being used for core caching. To change the config edit APP/Config/cache.php</span>
<?php else: ?>
    <span class="notice">Your cache is NOT working. Please check the settings in APP/Config/cache.php</span>
<?php endif; ?>
</p>

<p>
<?php
if (file_exists(APP . 'Config/datasources.php')): ?>
    <span class="notice success">Your datasources configuration file is present.</span>
<?php else: ?>
    <span class="notice">
    Your datasources configuration file is NOT present.
    <br/>
    Rename APP/Config/datasources.default.php to APP/Config/datasources.php
    </span>
<?php endif; ?>
</p>

<?php if (!Validation::alphaNumeric('cakephp')): ?>
    <p><span class="notice">'
        PCRE has not been compiled with Unicode support.';
        <br/>
        Recompile PCRE with Unicode support by adding <code>--enable-unicode-properties</code> when configuring
    </span></p>
<?php endif; ?>

<p>
<?php if (Plugin::loaded('DebugKit')): ?>
    <span class="notice success">DebugKit plugin is present</span>
<?php else: ?>
    <span class="notice">';
        DebugKit is not installed. It will help you inspect and debug different aspects of your application.
        <br/>
        You can install it from <?= $this->Html->link('GitHub', 'https://github.com/cakephp/debug_kit'); ?>
        </span>
<?php endif; ?>
</p>

<h3>Editing this Page</h3>
<p>
To change the content of this page, edit: APP/View/Pages/home.ctp.<br/>
To change its layout, edit: APP/View/Layout/default.ctp.<br/>
You can also add some CSS styles for your pages at: APP/webroot/css.;
</p>

<h3>Getting Started</h3>
<p>
    <?php
        echo $this->Html->link(
            '<strong>New</strong> CakePHP 3.0 Docs',
            'https://book.cakephp.org/4/en/',
            ['target' => '_blank', 'escape' => false]
        );
    ?>
</p>
<p>
    <?php
        echo $this->Html->link(
            'The 15 min Blog Tutorial',
            'https://book.cakephp.org/4/en/getting-started.html#blog-tutorial',
            ['target' => '_blank', 'escape' => false]
        );
    ?>
</p>

<h3>Official Plugins</h3>
<p>
<ul>
    <li>
        <?= $this->Html->link('DebugKit', 'https://github.com/cakephp/debug_kit') ?>:
        provides a debugging toolbar and enhanced debugging tools for CakePHP application.
    </li>
    <li>
        <?= $this->Html->link('Localized', 'https://github.com/cakephp/localized') ?>:
        contains various localized validation classes and translations for specific countries
    </li>
</ul>
</p>

<h3>More about CakePHP</h3>
<p>
CakePHP is a rapid development framework for PHP which uses commonly known design patterns like Active Record, Association Data Mapping, Front Controller and MVC.
</p>
<p>
Our primary goal is to provide a structured framework that enables PHP users at all levels to rapidly develop robust web applications, without any loss to flexibility.
</p>

<ul
    <li><a href="https://cakephp.org">CakePHP</a>
    <ul><li>The Rapid Development Framework</li></ul></li>
    <li><a href="https://book.cakephp.org">CakePHP Documentation </a>
    <ul><li>Your Rapid Development Cookbook</li></ul></li>
    <li><a href="https://api.cakephp.org">CakePHP API </a>
    <ul><li>Quick API Reference</li></ul></li>
    <li><a href="https://bakery.cakephp.org">The Bakery </a>
    <ul><li>Everything CakePHP</li></ul></li>
    <li><a href="https://plugins.cakephp.org">CakePHP Plugins </a>
    <ul><li>A comprehensive list of all CakePHP plugins created by the community</li></ul></li>
    <li><a href="https://community.cakephp.org">CakePHP Community Center </a>
    <ul><li>Everything related to the CakePHP community in one place</li></ul></li>
    <li><a href="https://groups.google.com/group/cake-php">CakePHP Google Group </a>
    <ul><li>Community mailing list</li></ul></li>
    <li><a href="irc://irc.freenode.net/cakephp">irc.freenode.net #cakephp</a>
    <ul><li>Live chat about CakePHP</li></ul></li>
    <li><a href="https://github.com/cakephp/">CakePHP Code </a>
    <ul><li>Find the CakePHP code on GitHub and contribute to the framework</li></ul></li>
    <li><a href="https://github.com/cakephp/cakephp/issues">CakePHP Issues </a>
    <ul><li>CakePHP Issues</li></ul></li>
    <li><a href="https://github.com/cakephp/cakephp/wiki#roadmaps">CakePHP Roadmaps </a>
    <ul><li>CakePHP Roadmaps</li></ul></li>
    <li><a href="https://training.cakephp.org">Training </a>
    <ul><li>Join a live session and get skilled with the framework</li></ul></li>
    <li><a href="https://cakefest.org">CakeFest </a>
    <ul><li>Don\'t miss our annual CakePHP conference</li></ul></li>
    <li><a href="https://cakefoundation.org">Cake Software Foundation </a>
    <ul><li>Promoting development related to CakePHP</li></ul></li>
</ul>
