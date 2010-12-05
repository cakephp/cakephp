<h2>Sweet, "Test App" got Baked by CakePHP!</h2>
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
	$settings = array();
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
	if (file_exists(CONFIGS . 'database.php')):
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
if (!empty($filePresent)):
	if (!class_exists('ConnectionManager')) {
		require LIBS . 'model' . DS . 'connection_manager.php';
	}
?>
<p>
<?php
	if (true):
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
<h3><?php echo __('Editing this Page') ?></h3>
<p>
<?php
	echo __('To change the content of this page, edit: %s
		To change its layout, edit: %s
		You can also add some CSS styles for your pages at: %s',
		APP . 'views' . DS . 'pages' . DS . 'home.ctp.<br />',  APP . 'views' . DS . 'layouts' . DS . 'default.ctp.<br />', APP . 'webroot' . DS . 'css');
?>
</p>