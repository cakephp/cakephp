<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>CakePHP: the PHP Rapid Development Framework: <?php echo $title_for_layout;?></title>
<?php echo $html->charset('UTF-8');?>
<link rel="icon" href="<?php echo $this->webroot;?>favicon.ico" type="image/x-icon" />
<link rel="shortcut icon" href="<?php echo $this->webroot;?>favicon.ico" type="image/x-icon" />
<?php echo $html->css('cake.generic');?>
</head>
<body>
	<div id="container">
		<div id="header">
			<h1>CakePHP: the PHP Rapid Development Framework</h1>
		</div>
		<div id="content">
			<?php 
				if($session->check('Message.flash')):
						$session->flash();
				endif;
			?>
			
			<?php echo $content_for_layout;?>
			
		</div>
		<div id="footer">
			<?php echo $html->link(
							$html->image('cake.power.png', array('alt'=>"CakePHP: the PHP Rapid Development Framework", 'border'=>"0")),
							'http://www.cakephp.org/',
							array('target'=>'_new'),
							null,
							false
						);
			?>
		</div>
	</div>
</body>
</html>