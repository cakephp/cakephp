<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01//EN">

<html>
<head>
	<title><?php echo $title_for_layout; ?></title>
</head>

<body>
	<?php echo $this->fetch('content'); ?>

	<p>This email was sent using the <a href="http://cakephp.org">CakePHP Framework</a></p>
</body>
</html>