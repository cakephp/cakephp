<?php
$output = "
<iframe src=\"http://cakephp.org/bake-banner\" width=\"830\" height=\"160\" style=\"overflow:hidden; border:none;\">
	<p>For updates and important announcements, visit http://cakefest.org</p>
</iframe>\n";
$output .= "<h2>Sweet, \"" . Inflector::humanize($app) . "\" got Baked by CakePHP!</h2>\n";
$output .="
<?php
if (Configure::read() > 0):
	Debugger::checkSecurityKeys();
endif;
?>
<p>
<?php
	if (is_writable(TMP)):
		echo '<span class=\"notice success\">';
			__('Your tmp directory is writable.');
		echo '</span>';
	else:
		echo '<span class=\"notice\">';
			__('Your tmp directory is NOT writable.');
		echo '</span>';
	endif;
?>
</p>
<div id=\"url-rewriting-warning\" style=\"background-color:#e32; color:#fff; padding:3px; margin: 20px 0\">
	<?php __('URL rewriting is not properly configured on your server. '); ?>
	<ol style=\"padding-left:20px\">
		<li>
			<a target=\"_blank\" href=\"http://book.cakephp.org/view/917/Apache-and-mod_rewrite-and-htaccess\" style=\"color:#fff;\">
				<?php __('Help me configure it')?>
			</a>
		</li>
		<li>
			<a target=\"_blank\" href=\"http://book.cakephp.org/view/931/CakePHP-Core-Configuration-Variables\" style=\"color:#fff;\">
				<?php __('I don\'t / can\'t use URL rewriting')?>
			</a>
		</li>
	</ol>
</div>
<p>
<?php
	\$settings = Cache::settings();
	if (!empty(\$settings)):
		echo '<span class=\"notice success\">';
				printf(__('The %s is being used for caching. To change the config edit APP/config/core.php ', true), '<em>'. \$settings['engine'] . 'Engine</em>');
		echo '</span>';
	else:
		echo '<span class=\"notice\">';
				__('Your cache is NOT working. Please check the settings in APP/config/core.php');
		echo '</span>';
	endif;
?>
</p>
<p>
<?php
	\$filePresent = null;
	if (file_exists(CONFIGS . 'database.php')):
		echo '<span class=\"notice success\">';
			__('Your database configuration file is present.');
			\$filePresent = true;
		echo '</span>';
	else:
		echo '<span class=\"notice\">';
			__('Your database configuration file is NOT present.');
			echo '<br/>';
			__('Rename config/database.php.default to config/database.php');
		echo '</span>';
	endif;
?>
</p>
<?php
if (!empty(\$filePresent)):
	if (!class_exists('ConnectionManager')) {
		require LIBS . 'model' . DS . 'connection_manager.php';
	}
	\$db = ConnectionManager::getInstance();
 	\$connected = \$db->getDataSource('default');
?>
<p>
<?php
	if (\$connected->isConnected()):
		echo '<span class=\"notice success\">';
 			__('Cake is able to connect to the database.');
		echo '</span>';
	else:
		echo '<span class=\"notice\">';
			__('Cake is NOT able to connect to the database.');
		echo '</span>';
	endif;
?>
</p>\n";
$output .= "<?php endif;?>\n";
$output .= "<h3><?php __('Editing this Page') ?></h3>\n";
$output .= "<p>\n";
$output .= "<?php\n";
$output .= "\tprintf(__('To change the content of this page, edit: %s\n";
$output .= "\t\tTo change its layout, edit: %s\n";
$output .= "\t\tYou can also add some CSS styles for your pages at: %s', true),\n";
$output .= "\t\tAPP . 'views' . DS . 'pages' . DS . 'home.ctp.<br />',  APP . 'views' . DS . 'layouts' . DS . 'default.ctp.<br />', APP . 'webroot' . DS . 'css');\n";
$output .= "?>\n";
$output .= "</p>\n";
?>