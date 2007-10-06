<?php
$output = "<h2>Sweet, \"".Inflector::humanize($app)."\" got Baked by CakePHP!</h2>\n";
$output .="
<?php
if(Configure::read() > 0):
	Debugger::checkSessionKey();
endif;
?>
<p>
	<span class=\"notice\">
		<?php
			__('Your tmp directory is ');
			if (is_writable(TMP)):
				__('writable.');
			else:
				__('NOT writable.');
			endif;
		?>
	</span>
</p>
<p>
	<span class=\"notice\">
		<?php
			__('Your cache is ');
			if (Cache::isInitialized()):
				__('set up and initialized properly.');
				\$settings = Cache::settings();
				echo '<p>' . \$settings['class'];
				__(' is being used to cache, to change this edit config'.DS.'core.php ');
				echo '</p>';

				echo 'Settings: <ul>';
				foreach (\$settings as \$name => \$value):
					echo '<li>' . \$name . ': ' . \$value . '</li>';
				endforeach;
				echo '</ul>';

			else:
				__('NOT working.');
				echo '<br />';
				if (is_writable(TMP . 'cache')):
					__('Edit: config'.DS.'core.php to insure you have the newset version of this file and the variable \$cakeCache set properly');
				else:
					__('Your cache directory is not writable');
				endif;
			endif;
		?>
	</span>
</p>
<p>
	<span class=\"notice\">
		<?php
			__('Your database configuration file is ');
			\$filePresent = null;
			if (file_exists(CONFIGS.'database.php')):
				__('present.');
				\$filePresent = true;
			else:
				__('NOT present.');
				echo '<br/>';
				__('Rename config'.DS.'database.php.default to config'.DS.'database.php');
			endif;
		?>
	</span>
</p>
<?php
if (!empty(\$filePresent)):
 	uses('model' . DS . 'connection_manager');
	\$db = ConnectionManager::getInstance();
 	\$connected = \$db->getDataSource('default');
?>
<p>
	<span class=\"notice\">
		<?php
			__('Cake');
			if (\$connected->isConnected()):
		 		__(' is able to ');
			else:
				__(' is NOT able to ');
			endif;
			__('connect to the database.');
		?>
	</span>
</p>\n";
$output .= "<?php endif;?>\n";
$output .= "<h3>Editing this Page</h3>\n";
$output .= "<p>\n";
$output .= "To change the content of this page, edit: ".$dir."pages".DS."home.ctp.<br />\n";
$output .= "To change its layout, edit: ".$dir."layouts".DS."default.ctp.<br />\n";
$output .= "You can also add some CSS styles for your pages at: ".$dir."webroot".DS."css.\n";
$output .= "</p>\n";
?>