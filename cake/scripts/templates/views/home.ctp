<?php
$output =
"<p>
	<span class=\"notice\">
		Your database configuration file is
		<?php
			\$filePresent = null;
			if(file_exists(CONFIGS.'database.php')):
				echo 'present';
				\$filePresent = true;
			else:
				echo 'NOT present';
			endif;
		?>
	</span>
</p>
<?php if (!empty(\$filePresent)):
 	uses('model' . DS . 'connection_manager');
	\$db = &ConnectionManager::getInstance();
 	\$connected = \$db->getDataSource('default');
?>
<p>
	<span class=\"notice\">
		Cake
		<?php if(\$connected->isConnected()):
		 		echo ' is able to';
			else:
				echo ' is not able to';
			endif;
		?>
		connect to the database.
	</span>
</p>\n";
$output .= "<?php endif;?>";
$output .= "<h2>Sweet, \"".Inflector::humanize($app)."\" got Baked by CakePHP!</h2>\n";
$output .= "<h3>Editing this Page</h3>\n";
$output .= "<p>\n";
$output .= "To change the content of this page, edit: ".$dir.DS."views".DS."pages".DS."home.ctp.<br />\n";
$output .= "To change its layout, edit: ".$dir.DS."views".DS."layouts".DS."default.ctp.<br />\n";
$output .= "You can also add some CSS styles for your pages at: ".$dir.DS."webroot/css/.\n";
$output .= "</p>\n";
?>