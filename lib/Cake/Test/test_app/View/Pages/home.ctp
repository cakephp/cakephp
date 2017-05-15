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
				echo __d('cake_dev', 'The %s is being used for caching. To change the config edit %s/core.php ', '<em>'. $settings['engine'] . 'Engine</em>', CONFIG);
			echo '</span>';
		else:
			echo '<span class="notice">';
				echo __d('cake_dev', 'Your cache is NOT working. Please check the settings in %s/core.php', CONFIG);
			echo '</span>';
		endif;
	?>
</p>
<p>
	<?php
		$filePresent = null;
		if (file_exists(CONFIG . 'database.php')):
			echo '<span class="notice success">';
				echo __d('cake_dev', 'Your database configuration file is present.');
				$filePresent = true;
			echo '</span>';
		else:
			echo '<span class="notice">';
				echo __d('cake_dev', 'Your database configuration file is NOT present.');
				echo '<br/>';
				echo __d('cake_dev', 'Rename config/database.php.default to config/database.php');
			echo '</span>';
		endif;
	?>
</p>
<?php
if (isset($filePresent)):
	App::uses('ConnectionManager', 'Model');
	try {
		$connected = ConnectionManager::getDataSource('default');
	} catch (Exception $e) {
		$connected = false;
	}
?>
<p>
	<?php
		if ($connected && $connected->isConnected()):
			echo '<span class="notice success">';
				echo __d('cake_dev', 'Cake is able to connect to the database.');
			echo '</span>';
		else:
			echo '<span class="notice">';
				echo __d('cake_dev', 'Cake is NOT able to connect to the database.');
			echo '</span>';
		endif;
	?>
</p>
<?php endif; ?>
<h3><?php echo __d('cake_dev', 'Editing this Page'); ?></h3>
<?php
echo __d('cake_dev', 'To change the content of this page, create: APP/views/pages/home.ctp.<br />
To change its layout, create: APP/views/layouts/default.ctp.<br />
You can also add some CSS styles for your pages at: APP/webroot/css.');
